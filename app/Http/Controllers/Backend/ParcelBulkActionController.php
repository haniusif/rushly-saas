<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Backend\Parcel;
use App\Models\Backend\Parcels_3pl;
use Illuminate\Support\Facades\Http;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Hub\HubInterface;
use App\Enums\ParcelStatus;
use App\Support\ParcelStatusHelper;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;

use App\Services\DeliveryPandaService;
use App\Services\ZajelService;
use App\Services\AramexService;
use App\Services\JetService;
use App\Services\LogestechsService;

class ParcelBulkActionController extends Controller
{
    
   
   
       protected $merchant;
    protected $repo;
    protected $shop;
    protected $deliveryPanda;
    protected ZajelService $zajel;
    protected AramexService $aramex;
    protected JetService $jet;
    protected LogestechsService $logestechs;
    public function __construct(
        ParcelInterface $repo,
        MerchantInterface $merchant,
        ShopsInterface $shop,
        DeliveryManInterface $deliveryman,
        HubInterface $hub,
        DeliveryPandaService $deliveryPanda,
        ZajelService $zajel,
        AramexService $aramex,
        JetService $jet,
        LogestechsService $logestechs
        )
    {
        $this->merchant     = $merchant;
        $this->repo         = $repo;
        $this->shop         = $shop;
        $this->deliveryman  = $deliveryman;
        $this->hub          = $hub;
        $this->deliveryPanda = $deliveryPanda;
        $this->zajel         = $zajel;
        $this->aramex        = $aramex;
        $this->jet           = $jet;
        $this->logestechs    = $logestechs;
    }
    
     
   
 
 
public function check(Request $request)
{
    // ---- Parse incoming IDs (robust) ----
    $raw = (string) $request->input('ids', '');
    $raw = urldecode($raw);
    $raw = str_replace(["\xC2\xA0"], ' ', $raw); // NBSP to space
    $raw = trim($raw);

    // default: hide zero-count statuses (override by ?hide_zero=0)
    $hideZero = $request->boolean('hide_zero', true);

    if ($raw === '') {
        return response()->json([
            'data'   => [],
            'counts' => [
                'total'              => 0,
                'by_status'          => [],
                'by_status_full'     => [],
                'by_status_non_zero' => [],
                'derived'            => ['canceled' => 0, 'return_flow' => 0],
            ],
        ]);
    }

    // Remove prefixes like "Tracking ID(s):"
    $clean = preg_replace('/\bTracking\s*ID(?:s)?\b\s*:?\s*/iu', '', $raw);

    // Extract RLxxxxxx and numeric IDs
    preg_match_all('/^RL\d{6,}$/im', $clean, $m1);
    $trackingIds = $m1[0] ?? [];

    preg_match_all('/(?<![A-Z])\b\d+\b(?![A-Z])/i', $clean, $m2);
    $numericIds = $m2[0] ?? [];

    // Also split tokens by newline/commas/spaces and re-collect
    $tokens = preg_split('/[\r\n,;|\s]+/u', $clean);
    foreach ($tokens as $t) {
        $t = trim($t);
        if ($t === '') continue;
        if (preg_match('/^RL\d{6,}$/i', $t)) {
            $trackingIds[] = $t;
        } elseif (preg_match('/^\d+$/', $t)) {
            $numericIds[] = $t;
        }
    }

    $trackingIds = array_values(array_unique($trackingIds));
    $numericIds  = array_values(array_unique($numericIds));

    if (empty($trackingIds) && empty($numericIds)) {
        return response()->json([
            'data'   => [],
            'counts' => [
                'total'              => 0,
                'by_status'          => [],
                'by_status_full'     => [],
                'by_status_non_zero' => [],
                'derived'            => ['canceled' => 0, 'return_flow' => 0],
            ],
        ]);
    }

    // ---- Fetch parcels ----
    $rows = Parcel::with(['city','area','merchant'])
        ->where(function ($q) use ($trackingIds, $numericIds) {
            if (!empty($trackingIds)) {
                $q->whereIn('tracking_id', $trackingIds);
            }
            if (!empty($numericIds)) {
                $q->orWhereIn('id', $numericIds);
            }
        })
        ->limit(500)
        ->get();

    // ---- Transform for frontend ----
    $parcels = $rows->map(function ($p) {
        $s = (int) $p->status;
        return [
            'id'           => $p->id,
            'tracking_id'  => $p->tracking_id ?? ('#' . $p->id),
            'merchant'     => optional($p->merchant)->business_name ?? '',
            'city'         => optional($p->city)->en_name ?? '',
            'area'         => optional($p->area)->en_name ?? '',
            'status'       => $s,
            'status_label' => ParcelStatusHelper::label($s),
            'status_class' => ParcelStatusHelper::badgeClass($s),
        ];
    })->values();

    // ---- Build dynamic counts from all known statuses ----
    $allStatuses = ParcelStatusHelper::getStatusList(); // [id, name, label, class]
    $countsIndex = [];
    foreach ($allStatuses as $st) {
        $countsIndex[(int)$st['id']] = [
            'id'    => (int)$st['id'],
            'label' => $st['label'],
            'class' => $st['class'],
            'count' => 0,
        ];
    }

    foreach ($parcels as $row) {
        $sid = (int) $row['status'];
        if (isset($countsIndex[$sid])) {
            $countsIndex[$sid]['count']++;
        }
    }

    $byStatus = array_values($countsIndex);
    usort($byStatus, static fn ($a, $b) => $a['id'] <=> $b['id']);

    // Derived summaries
    $total      = $parcels->count();
    $canceled   = 0;
    $returnFlow = 0;
    foreach ($parcels as $row) {
        $sid = (int) $row['status'];
        if (ParcelStatusHelper::isCanceled($sid))   $canceled++;
        if (ParcelStatusHelper::isReturnFlow($sid)) $returnFlow++;
    }

    // Non-zero view
    $byStatusNonZero = array_values(array_filter($byStatus, static fn ($st) => (int)$st['count'] > 0));

    return response()->json([
        'data'   => $parcels,
        'counts' => [
            'total'              => $total,
            'by_status'          => $hideZero ? $byStatusNonZero : $byStatus,
            'by_status_full'     => $byStatus,          // always included for flexibility
            'by_status_non_zero' => $byStatusNonZero,   // always included for convenience
            'derived'            => [
                'canceled'    => $canceled,
                'return_flow' => $returnFlow,
            ],
        ],
    ]);
}
 


    /**
     * Apply shipment actions: change status / assign deliveryman / assign 3PL.
     */
public function apply(Request $request)
{
    $request->validate([
        'shipment_ids'   => ['required','string'],
        'action_type'    => ['nullable','in:change_status,assign_deliveryman,assign_3pl,cancel'],
        'status'         => ['nullable','integer'],
        'deliveryman_id' => ['nullable','integer','exists:delivery_man,id'],
        'company'        => ['nullable','string','in:panda,zajel,aramex,jet,logestechs'],
        'logestechs_company_id' => ['nullable','string','max:64','required_if:company,logestechs'],
        'logestechs_email'      => ['nullable','string','max:120','required_if:company,logestechs'],
        'logestechs_password'   => ['nullable','string','max:120','required_if:company,logestechs'],
    ]);
    
    // dd($request->all());
    
    
    $action = (string) $request->input('action_type', '');
    $company = (string) $request->input('company', '');
    $status = (int) $request->input('status', '');

    [$trackingIds, $numericIds] = $this->splitIds($request->string('checked_ids'));

    if (empty($trackingIds) && empty($numericIds)) {
        return back()->with('error', __('No matching shipments found.'));
    }

    $parcels = Parcel::query()
        ->where(function ($q) use ($trackingIds, $numericIds) {
            if (!empty($trackingIds)) $q->whereIn('tracking_id', $trackingIds);
            if (!empty($numericIds))  $q->orWhereIn('id', $numericIds);
        })
        ->with(['merchant','city','area'])
        ->get();
        
        // return count($parcels);

    if ($parcels->isEmpty()) {
        return back()->with('error', __('No matching shipments found.'));
    }
    
 

     
    if($action == 'change_status'){
        
     
    return  $this->change_status($parcels , $request);
     
       
    }elseif($action == 'assign_3pl'){
        
        
           $rwh_parcels = Parcel::query()
        ->where(function ($q) use ($trackingIds, $numericIds) {
            if (!empty($trackingIds)) $q->whereIn('tracking_id', $trackingIds);
            if (!empty($numericIds))  $q->orWhereIn('id', $numericIds);
        })
         
        ->where('status' , ParcelStatus::RECEIVED_WAREHOUSE)
        ->count();
           
           
       if ($company === 'zajel') {
            return $this->assignZajelBulk($parcels, $rwh_parcels, $request);
        }

        if ($company === 'aramex') {
            return $this->assignAramexBulk($parcels, $rwh_parcels, $request);
        }

        if ($company === 'jet') {
            return $this->assignJetBulk($parcels, $rwh_parcels, $request);
        }

        if ($company === 'logestechs') {
            return $this->assignLogestechsBulk($parcels, $rwh_parcels, $request);
        }

        if ($company !== 'panda') {
            return back()->with('error', __('3PL company not supported or not selected.'));
        }

        $baseUrl = rtrim(config('services.deliverypanda.base_url', env('PANDA_BASE_URL', 'https://app.deliverypanda.me')), '/');
        $apiKey  = (string) config('services.deliverypanda.key', env('PANDA_API_KEY', ''));

        if (empty($apiKey)) {
            return back()->with('error', __('Missing Panda API key.'));
        }

        $success = 0;
        $fail    = 0;
        $errors  = [];
        
        
              if(count($parcels) != $rwh_parcels) {
               return back()->with('error', __('All selected shipment must be RECEIVED_WAREHOUSE'));   
              }
              
          
          
        

        foreach ($parcels as $p) {
      
            try {
                $resp = $this->PandaThirdParty($p->id);
               
            
                   if(isset($resp['success']) && $resp['success'] == 1){
                    $success++;
                    
                    $AwbNumber = $resp['AwbNumber'] ?? "-";
                    $AwbPdf = $resp['AwbPdf'] ?? "-";
                
                   Parcels_3pl::create([
                        'parcel_id'      => $p->id,
                        'parcel_3pl_name' => 'panda',
                        'awb_number'      => $AwbNumber,
                        'awb_pdf'         => $AwbPdf,
                        'response'        => $resp,
                    ]);
                    
                }elseif(isset($resp['success']) && $resp['success'] == 0){
                     $fail++;
                         $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => $resp['message'] ?? "",
                    ]);
                    
                    
                     Parcels_3pl::create([
                    'parcel_id'      => $p->id,
                    'parcel_3pl_name' => 'panda',
                    'awb_number'      => null,
                    'awb_pdf'         => null,
                    'response'        => $resp,
                ]);
                    
                    
                    
                }else{
                  $fail++;
                       $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => $resp['message'] ?? "",
                    ]);
                    
                }
                
              $request->merge(['delivery_man_id' => 12]);
              $ok = $this->repo->deliverymanAssign($p->id, $request);  
                
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = __('Parcel :id failed: :msg', [
                    'id'  => $p->id,
                    'msg' => $e->getMessage(),
                ]);

                // Log exception case
                Parcels_3pl::create([
                    'parcel_id'      => $p->id,
                    'parcel_3pl_name' => 'panda',
                    'awb_number'      => null,
                    'awb_pdf'         => null,
                    'response'        => ['exception' => $e->getMessage()],
                ]);
            }
        }

        $summary = __('3PL assignment finished. Success: :s, Fail: :f', [
            's' => $success, 'f' => $fail
        ]);

        if ($fail > 0) {
            return back()->with('warning', $summary)->with('errors_list', $errors);
        }

        return back()->with('success', $summary);
    

    }elseif($action == 'cancel'){

        // Only "Created" (PENDING) shipments may be cancelled — same rule as the
        // single-parcel cancel on /admin/parcel/details. Anything past pickup must
        // go through the return flow instead, so it is skipped here.
        $reason = trim((string) $request->input('note', '')) ?: null;

        $cancelled = 0;
        $skipped   = [];
        $failed    = [];

        foreach ($parcels as $p) {
            if (! $p->isCancellable()) {
                $skipped[] = $p->tracking_id ?? ('#' . $p->id);
                continue;
            }
            try {
                if ($this->repo->cancelShipment($p->id, $reason)) {
                    $cancelled++;
                } else {
                    $failed[] = $p->tracking_id ?? ('#' . $p->id);
                }
            } catch (\Throwable $e) {
                Log::warning('bulk cancel failed', ['parcel_id' => $p->id, 'error' => $e->getMessage()]);
                $failed[] = $p->tracking_id ?? ('#' . $p->id);
            }
        }

        $msg = __('Cancelled :n shipment(s).', ['n' => $cancelled]);
        if (! empty($skipped)) {
            $msg .= ' ' . __('Skipped :n — only newly created shipments can be cancelled: :ids', [
                'n'   => count($skipped),
                'ids' => implode(', ', array_slice($skipped, 0, 20)),
            ]);
        }
        if (! empty($failed)) {
            return back()->with('warning', $msg)->with('errors_list', $failed);
        }
        return back()->with('success', $msg);

    }else{

          return back()->with('error', __('Select action type'));

    }

    


 
   

 

    return back()->with('success', __('Shipment actions applied successfully on :count shipments.', [
        'count' => $parcels->count()
    ]));
}





/**
 * Map of every status that can be bulk-applied → which repo method runs
 * and which extra request fields it needs.
 *
 * Skipped intentionally:
 *   10 DELIVER, 12 ASSIGN_MERCHANT, 13 RETURNED_MERCHANT, 24 RETURN_TO_COURIER
 *   → no clean repo handler
 *   32 PARTIAL_DELIVERED
 *   → needs unique cash_collection per parcel; doesn't fit bulk
 *   34 ASSIGN_TO_3PL
 *   → handled by the separate `assign_3pl` action_type
 */
public function statusMap(): array
{
    return [
        ParcelStatus::PENDING                            => ['method' => null,                                         'requires' => []],
        ParcelStatus::PICKUP_ASSIGN                      => ['method' => 'pickupdatemanAssigned',                      'requires' => ['delivery_man_id']],
        ParcelStatus::PICKUP_RE_SCHEDULE                 => ['method' => 'PickupReSchedule',                           'requires' => ['delivery_man_id','date']],
        ParcelStatus::RECEIVED_BY_PICKUP_MAN             => ['method' => 'receivedBypickupman',                        'requires' => []],
        ParcelStatus::RECEIVED_WAREHOUSE                 => ['method' => 'receivedWarehouse',                          'requires' => ['hub_id']],
        ParcelStatus::TRANSFER_TO_HUB                    => ['method' => 'transfertohub',                              'requires' => ['hub_id']],
        ParcelStatus::DELIVERY_MAN_ASSIGN                => ['method' => 'deliverymanAssign',                          'requires' => ['delivery_man_id']],
        ParcelStatus::DELIVERY_RE_SCHEDULE               => ['method' => 'deliveryReschedule',                         'requires' => ['delivery_man_id','date']],
        ParcelStatus::DELIVERED                          => ['method' => 'parcelDelivered',                            'requires' => []],
        ParcelStatus::RETURN_WAREHOUSE                   => ['method' => 'returntoQourier',                            'requires' => []],
        ParcelStatus::PICKUP_ASSIGN_CANCEL               => ['method' => 'pickupdatemanAssignedCancel',                'requires' => []],
        ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL      => ['method' => 'receivedBypickupmanCancel',                  'requires' => []],
        ParcelStatus::RECEIVED_WAREHOUSE_CANCEL          => ['method' => 'receivedWarehouseCancel',                    'requires' => []],
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL         => ['method' => 'deliverymanAssignCancel',                    'requires' => []],
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL        => ['method' => 'deliveryReScheduleCancel',                   'requires' => []],
        ParcelStatus::RECEIVED_BY_HUB                    => ['method' => 'receivedByHub',                              'requires' => ['hub_id']],
        ParcelStatus::TRANSFER_TO_HUB_CANCEL             => ['method' => 'transfertoHubCancel',                        'requires' => []],
        ParcelStatus::RECEIVED_BY_HUB_CANCEL             => ['method' => 'receivedByHubCancel',                        'requires' => []],
        ParcelStatus::DELIVERED_CANCEL                   => ['method' => 'parcelDeliveredCancel',                      'requires' => []],
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL          => ['method' => 'PickupReScheduleCancel',                     'requires' => []],
        ParcelStatus::RETURN_TO_COURIER_CANCEL           => ['method' => 'returntoQourierCancel',                      'requires' => []],
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT          => ['method' => 'returnAssignToMerchant',                     'requires' => ['delivery_man_id','date']],
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE        => ['method' => 'returnAssignToMerchantReschedule',           'requires' => ['delivery_man_id','date']],
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL => ['method' => 'returnAssignToMerchantRescheduleCancel',     'requires' => []],
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL   => ['method' => 'returnAssignToMerchantCancel',               'requires' => []],
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT        => ['method' => 'returnReceivedByMerchant',                   'requires' => []],
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL => ['method' => 'returnReceivedByMerchantCancel',             'requires' => []],
        ParcelStatus::PARTIAL_DELIVERED_CANCEL           => ['method' => 'parcelPartialDeliveredCancel',               'requires' => []],
    ];
}

public function change_status($parcels, Request $request)
{
    $status = (int) $request->input('status', 0);

    // Normalize incoming form-name → repo-expected name (no destructive merging)
    if ($request->filled('driver_id') && !$request->filled('delivery_man_id')) {
        $request->merge(['delivery_man_id' => $request->input('driver_id')]);
    }
    if ($request->filled('schedule_at') && !$request->filled('date')) {
        $request->merge(['date' => $request->input('schedule_at')]);
    }

    // Decode JSON-string contract preserved from legacy callers
    if (is_string($parcels)) {
        $parcels = json_decode($parcels);
    }
    if (!is_iterable($parcels)) {
        Toastr::error(__('Invalid parcels format'));
        return back();
    }

    $map = $this->statusMap();
    if (!isset($map[$status])) {
        Toastr::error(__('Unsupported status for bulk action'));
        return back();
    }

    $spec     = $map[$status];
    $method   = $spec['method'];
    $requires = $spec['requires'];

    // Pre-flight: required fields must be set ONCE, not per parcel
    foreach ($requires as $field) {
        if (!$request->filled($field)) {
            Toastr::error(__(':field is required for this status', ['field' => $field]));
            return back();
        }
    }

    $success = [];
    $failed  = [];

    // NOTE: tables in this DB are MyISAM (non-transactional). With
    // enforce_gtid_consistency=ON, wrapping repo calls in DB::transaction()
    // triggers MySQL error 1785 and silently rolls back. Per-parcel try/catch
    // is the right scope here — each statement auto-commits in MyISAM.
    foreach ($parcels as $parcel) {
        $parcel_id = is_object($parcel) ? ($parcel->id ?? null) : ($parcel['id'] ?? null);
        if (!$parcel_id) { continue; }

        try {
            if ($method === null) {
                // PENDING (or any future no-op-method status) → generic update
                $ok = (bool) $this->repo->statusUpdate($parcel_id, $status);
            } else {
                $ok = (bool) $this->repo->{$method}($parcel_id, $request);
            }
            $ok ? ($success[] = $parcel_id) : ($failed[] = $parcel_id);
        } catch (\Throwable $e) {
            Log::warning('bulk change_status failed', [
                'parcel_id' => $parcel_id,
                'status'    => $status,
                'method'    => $method,
                'error'     => $e->getMessage(),
            ]);
            $failed[] = $parcel_id;
        }
    }

    $okCount   = count($success);
    $failCount = count($failed);

    if ($failCount === 0) {
        Toastr::success(__('Status updated for :n parcel(s).', ['n' => $okCount]));
    } elseif ($okCount === 0) {
        Toastr::error(__('All :n parcel(s) failed.', ['n' => $failCount]));
    } else {
        Toastr::warning(__(':ok ok / :fail failed. Failed IDs: :ids', [
            'ok' => $okCount, 'fail' => $failCount, 'ids' => implode(', ', $failed),
        ]));
    }

    return back();
}







    /**
     * Split raw input into tracking_ids and numeric ids.
     */
    private function splitIds(string $raw): array
    {
        $raw = urldecode($raw);
        $raw = str_replace(["\xC2\xA0"], ' ', $raw);
        $raw = trim($raw);

        $clean = preg_replace('/\bTracking\s*ID(?:s)?\b\s*:?\s*/iu', '', $raw);

        $tokens = preg_split('/[\r\n,;|\s]+/u', $clean);
        $tokens = array_values(array_filter(array_map('trim', $tokens)));

        $tracking = [];
        $numeric  = [];

        preg_match_all('/RL\d{6,}/i', $clean, $m1);
        if (!empty($m1[0])) $tracking = array_merge($tracking, $m1[0]);

        foreach ($tokens as $t) {
            if ($t === '') continue;
            if (preg_match('/^RL\d{6,}$/i', $t)) {
                $tracking[] = $t;
            } elseif (preg_match('/^\d+$/', $t)) {
                $numeric[] = (int)$t;
            }
        }

        return [array_values(array_unique($tracking)), array_values(array_unique($numeric))];
    }

 

    
    /**
     * Bulk-assign the given parcels to Zajel. Mirrors the Panda flow but
     * uses the cleaner ZajelService (no transport bugs, no hardcoded driver).
     */
    protected function assignZajelBulk($parcels, int $rwhCount, Request $request)
    {
        if (! $this->zajel->isConfigured()) {
            return back()->with('error', __('Zajel is not configured (missing ZAJEL_API_KEY / ZAJEL_CUSTOMER_CODE).'));
        }

        if (count($parcels) !== $rwhCount) {
            return back()->with('error', __('All selected shipment must be RECEIVED_WAREHOUSE'));
        }

        $success = 0;
        $fail    = 0;
        $errors  = [];

        foreach ($parcels as $p) {
            try {
                $payload  = $this->zajel->buildShipmentPayload($p);
                $response = $this->zajel->createShipment($payload);

                if (! empty($response['_error']) || empty($response['success'])) {
                    $fail++;
                    $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => $response['message'] ?? ($response['title'] ?? 'unknown error'),
                    ]);
                    Parcels_3pl::create([
                        'parcel_id'       => $p->id,
                        'parcel_3pl_name' => 'zajel',
                        'awb_number'      => null,
                        'awb_pdf'         => null,
                        'response'        => $response,
                    ]);
                    continue;
                }

                $awb       = $response['referenceNumber'] ?? null;
                $labelInfo = $awb ? $this->zajel->getShipmentLabel((string) $awb) : null;
                $awbPdf    = is_array($labelInfo) && empty($labelInfo['_error'])
                    ? ($labelInfo['url'] ?? $labelInfo['label_url'] ?? null)
                    : null;

                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'zajel',
                    'awb_number'      => $awb,
                    'awb_pdf'         => $awbPdf,
                    'response'        => $response,
                ]);
                $success++;
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = __('Parcel :id failed: :msg', [
                    'id'  => $p->id,
                    'msg' => $e->getMessage(),
                ]);
                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'zajel',
                    'awb_number'      => null,
                    'awb_pdf'         => null,
                    'response'        => ['exception' => $e->getMessage()],
                ]);
            }
        }

        $summary = __('3PL assignment finished. Success: :s, Fail: :f', ['s' => $success, 'f' => $fail]);
        if ($fail > 0) {
            return back()->with('warning', $summary)->with('errors_list', $errors);
        }
        return back()->with('success', $summary);
    }


    /**
     * Bulk-assign the given parcels to Aramex. Uses SOAP CreateShipments and
     * persists one Parcels_3pl row per parcel (success or failure).
     */
    protected function assignAramexBulk($parcels, int $rwhCount, Request $request)
    {
        if (! $this->aramex->isConfigured()) {
            return back()->with('error', __('Aramex is not configured (missing ARAMEX_USERNAME / ARAMEX_ACCOUNT_NUMBER).'));
        }

        if (count($parcels) !== $rwhCount) {
            return back()->with('error', __('All selected shipment must be RECEIVED_WAREHOUSE'));
        }

        $success = 0;
        $fail    = 0;
        $errors  = [];

        foreach ($parcels as $p) {
            try {
                $shipment = $this->aramex->buildShipmentPayload($p);
                $response = $this->aramex->createShipments([$shipment]);

                $hasErr   = ! empty($response['_error']) || ! empty($response['HasErrors']);
                $awb      = null;
                $labelUrl = null;
                $errMsg   = $response['message'] ?? null;

                if (! $hasErr) {
                    $processed = $response['Shipments']['ProcessedShipment'] ?? null;
                    if ($processed && isset($processed[0])) {
                        $processed = $processed[0];
                    }
                    if ($processed) {
                        $hasErr   = ! empty($processed['HasErrors']);
                        $awb      = $processed['ID'] ?? null;
                        $labelUrl = $processed['ShipmentLabel']['LabelURL'] ?? null;
                        if ($hasErr) {
                            $notifs = $processed['Notifications']['Notification'] ?? [];
                            if (isset($notifs[0])) {
                                $errMsg = $notifs[0]['Message'] ?? null;
                            } else {
                                $errMsg = $notifs['Message'] ?? null;
                            }
                        }
                    } else {
                        $hasErr = true;
                    }
                }

                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'aramex',
                    'awb_number'      => $hasErr ? null : $awb,
                    'awb_pdf'         => $hasErr ? null : $labelUrl,
                    'response'        => $response,
                ]);

                if ($hasErr) {
                    $fail++;
                    $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => $errMsg ?: 'unknown error',
                    ]);
                } else {
                    $success++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = __('Parcel :id failed: :msg', [
                    'id'  => $p->id,
                    'msg' => $e->getMessage(),
                ]);
                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'aramex',
                    'awb_number'      => null,
                    'awb_pdf'         => null,
                    'response'        => ['exception' => $e->getMessage()],
                ]);
            }
        }

        $summary = __('3PL assignment finished. Success: :s, Fail: :f', ['s' => $success, 'f' => $fail]);
        if ($fail > 0) {
            return back()->with('warning', $summary)->with('errors_list', $errors);
        }
        return back()->with('success', $summary);
    }


    /**
     * Bulk-assign the given parcels to J&T (jet.co.id). Mirrors the Zajel/Aramex
     * shape: per-parcel call, Parcels_3pl row on success or failure, summary banner.
     */
    protected function assignJetBulk($parcels, int $rwhCount, Request $request)
    {
        if (! $this->jet->isConfigured()) {
            return back()->with('error', __('Jet is not configured (missing JET_USERNAME / JET_API_KEY / JET_SECRET_KEY / JET_ORDER_URL).'));
        }

        if (count($parcels) !== $rwhCount) {
            return back()->with('error', __('All selected shipment must be RECEIVED_WAREHOUSE'));
        }

        $success = 0;
        $fail    = 0;
        $errors  = [];

        foreach ($parcels as $p) {
            try {
                $orderPayload = $this->jet->buildOrderPayload($p);
                $response     = $this->jet->createOrder($orderPayload);

                $detail = $response['detail'] ?? null;
                if ($detail && isset($detail[0])) $detail = $detail[0];

                $statusOk = ! empty($response['success']) && is_array($detail)
                            && (($detail['status'] ?? '') === 'Sukses')
                            && ! empty($detail['awb_no']);

                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'jet',
                    'awb_number'      => $statusOk ? ($detail['awb_no'] ?? null) : null,
                    'awb_pdf'         => null,
                    'response'        => $response,
                ]);

                if ($statusOk) {
                    $success++;
                } else {
                    $fail++;
                    $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => (is_array($detail) ? ($detail['reason'] ?? '') : '')
                                 ?: ($response['desc'] ?? $response['message'] ?? 'unknown error'),
                    ]);
                }
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = __('Parcel :id failed: :msg', [
                    'id'  => $p->id,
                    'msg' => $e->getMessage(),
                ]);
                Parcels_3pl::create([
                    'parcel_id'       => $p->id,
                    'parcel_3pl_name' => 'jet',
                    'awb_number'      => null,
                    'awb_pdf'         => null,
                    'response'        => ['exception' => $e->getMessage()],
                ]);
            }
        }

        $summary = __('3PL assignment finished. Success: :s, Fail: :f', ['s' => $success, 'f' => $fail]);
        if ($fail > 0) {
            return back()->with('warning', $summary)->with('errors_list', $errors);
        }
        return back()->with('success', $summary);
    }


    /**
     * Bulk-assign the given parcels to Logestechs against a single target
     * company_id (picked at submit time). Mirrors Zajel/Aramex/Jet shape;
     * note the service is currently a STUB until Postman docs land.
     */
    protected function assignLogestechsBulk($parcels, int $rwhCount, Request $request)
    {
        if (! $this->logestechs->isConfigured()) {
            return back()->with('error', __('Logestechs is not configured (missing LOGESTECHS_BASE_URL).'));
        }
        $targetCompanyId = trim((string) $request->input('logestechs_company_id'));
        $lEmail          = trim((string) $request->input('logestechs_email'));
        $lPassword       = (string) $request->input('logestechs_password');
        if ($targetCompanyId === '' || $lEmail === '' || $lPassword === '') {
            return back()->with('error', __('logestechs_company_id, logestechs_email, and logestechs_password are required.'));
        }
        if (count($parcels) !== $rwhCount) {
            return back()->with('error', __('All selected shipment must be RECEIVED_WAREHOUSE'));
        }

        $success = 0;
        $fail    = 0;
        $errors  = [];

        foreach ($parcels as $p) {
            try {
                // Resolve per-parcel destination village (cached by Logestechs response).
                $villageQuery = (string) (optional($p->area)->en_name ?: optional($p->city)->en_name ?: '');
                $village      = $villageQuery !== '' ? $this->logestechs->resolveVillage($targetCompanyId, $villageQuery) : null;

                $payload  = $this->logestechs->buildCreatePayload($p, $lEmail, $lPassword, null, $village);
                $response = $this->logestechs->createShipment($payload, $targetCompanyId);

                $hasErr = ! empty($response['_error']);
                $awb    = $response['barcode']      ?? null;
                $label  = $response['barcodeImage'] ?? null;

                Parcels_3pl::create([
                    'parcel_id'         => $p->id,
                    'parcel_3pl_name'   => 'logestechs',
                    'target_company_id' => $targetCompanyId,
                    'awb_number'        => $hasErr ? null : $awb,
                    'awb_pdf'           => $hasErr ? null : $label,
                    'response'          => $response,
                ]);

                if ($hasErr) {
                    $fail++;
                    $errors[] = __('Parcel :id failed: :msg', [
                        'id'  => $p->id,
                        'msg' => $response['body']['error'] ?? $response['message'] ?? 'unknown error',
                    ]);
                } else {
                    $success++;
                }
            } catch (\Throwable $e) {
                $fail++;
                $errors[] = __('Parcel :id failed: :msg', [
                    'id'  => $p->id,
                    'msg' => $e->getMessage(),
                ]);
                Parcels_3pl::create([
                    'parcel_id'         => $p->id,
                    'parcel_3pl_name'   => 'logestechs',
                    'target_company_id' => $targetCompanyId,
                    'awb_number'        => null,
                    'awb_pdf'           => null,
                    'response'          => ['exception' => $e->getMessage()],
                ]);
            }
        }

        $summary = __('3PL assignment finished. Success: :s, Fail: :f', ['s' => $success, 'f' => $fail]);
        if ($fail > 0) {
            return back()->with('warning', $summary)->with('errors_list', $errors);
        }
        return back()->with('success', $summary);
    }


        public function PandaThirdParty($id)
{
   
        $parcel = $this->repo->details($id);
    //     $data = [
    //         "AwbNumber" => $parcel->tracking_id  ?? $parcel->id,
          
            
    //         "ToCompany" => "Rushly",  // $parcel->merchant->business_name ?? 'Unknown',
    //         "ToAddress" => $parcel->customer_address ?? 'Unknown',
    //         "ToCity" => strtoupper($parcel->city->en_name ?? 'Dubai'),
    //         "ToLocation" => strtoupper($parcel->area->en_name ?? 'Dubai'),
    //         "ToCountry" => "UAE",
    //         "ToCperson" => $parcel->customer_name ?? 'Rushly',
    //         "ToContactno" => $parcel->customer_phone ?? '',
    //         "ToMobileno" => $parcel->customer_phone ?? '',
    //         "ReferenceNumber" => $parcel->reference_number ?? 'REF-' . $parcel->id,
    //         "Weight" => number_format((float) $parcel->weight, 2, '.', ''),  
    //         "Pieces" => $parcel->number_of_boxes ?? 1,
    //         "PackageType" => "Domestic Parcel",
    //         "CurrencyCode" => "AED",
    //         "NcndAmount" => $parcel->cash_collection ?? 0,
    //         "ItemDescription" => $parcel->package_description ?? 'General goods',
    //         "SpecialInstruction" => $parcel->note ?? '',
    //         "BranchName" => "Dubai"
    //     ];
        
 
    //   $response = $this->deliveryPanda->createAgentBooking($data);
      
    //   return $response;
      
      
       $data = [
            "AwbNumber" => $parcel->tracking_id ?? $parcel->id,
            
            "FromCompany" => "Rushly",
            "FromAddress" => "Dubai",
            "FromCity" => "Dubai",
            "FromLocation" => "Dubai",
            "FromCountry" => "UAE",
            "FromCperson" => "Rushly",
            "FromContactno" => "-",
            "FromMobileno" => "-",

    
            "ToCompany" => $parcel->customer_name ?? '-',
            "ToAddress" => $parcel->customer_address ?? 'Unknown',
            "ToCity" => strtoupper($parcel->city->en_name ?? 'Dubai'),
            "ToLocation" => strtoupper($parcel->area->en_name ?? 'Dubai'),
            "ToCountry" => "UAE",
            "ToCperson" => $parcel->customer_name ?? '-',
            "ToContactno" => $parcel->customer_phone ?? '',
            "ToMobileno" => $parcel->customer_phone ?? '',
            
            "ReferenceNumber" => $parcel->reference_number ?? 'REF-' . $parcel->id,
            "Weight" => number_format((float) $parcel->weight, 2, '.', ''),  
            "Pieces" => $parcel->number_of_boxes ?? 1,
            "PackageType" => "Domestic Parcel",
            "CurrencyCode" => "AED",
            "NcndAmount" => $parcel->cash_collection ?? 0,
            "ItemDescription" => $parcel->package_description ?? 'General goods',
            "SpecialInstruction" => $parcel->note ?? '',
            "BranchName" => "Dubai"
        ];

        $response = $this->deliveryPanda->createCustomerToCustomer($data);
        
 
       return $response;

    
   

   
}


 
public function parcel_bulk_action(Request $request)
{
    $merchants    = $this->merchant->all();
    $deliverymans = $this->deliveryman->all();
    $hubs         = $this->hub->all();

    // Single source of truth: every status the controller can apply,
    // in workflow order (pickup → warehouse → delivery → return → cancels).
    $orderedIds = [
        // Pickup & Inbound
        ParcelStatus::PENDING,
        ParcelStatus::PICKUP_ASSIGN,
        ParcelStatus::PICKUP_RE_SCHEDULE,
        ParcelStatus::RECEIVED_BY_PICKUP_MAN,
        ParcelStatus::RECEIVED_WAREHOUSE,
        ParcelStatus::TRANSFER_TO_HUB,
        ParcelStatus::RECEIVED_BY_HUB,

        // Last-mile
        ParcelStatus::DELIVERY_MAN_ASSIGN,
        ParcelStatus::DELIVERY_RE_SCHEDULE,
        ParcelStatus::DELIVERED,

        // Return flow
        ParcelStatus::RETURN_WAREHOUSE,
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,

        // Cancels (grouped at the end so they don't crowd the common picks)
        ParcelStatus::PICKUP_ASSIGN_CANCEL,
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL,
        ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL,
        ParcelStatus::RECEIVED_WAREHOUSE_CANCEL,
        ParcelStatus::TRANSFER_TO_HUB_CANCEL,
        ParcelStatus::RECEIVED_BY_HUB_CANCEL,
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,
        ParcelStatus::DELIVERED_CANCEL,
        ParcelStatus::PARTIAL_DELIVERED_CANCEL,
        ParcelStatus::RETURN_TO_COURIER_CANCEL,
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL,
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL,
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT_CANCEL,
    ];

    $map = $this->statusMap();

    // Build the array the blade + JS expect.
    // Only emit statuses that the controller actually knows how to handle.
    $statuses = [];
    foreach ($orderedIds as $id) {
        if (!isset($map[$id])) { continue; }
        $statuses[] = [
            'id'       => $id,
            'label'    => ParcelStatusHelper::label($id),
            'class'    => ParcelStatusHelper::badgeClass($id),
            'requires' => $map[$id]['requires'],
        ];
    }

    return view('backend.parcel.parcel_bulk_action', compact(
        'merchants', 'request', 'deliverymans', 'statuses', 'hubs'
    ));
}



    
}
