<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use Illuminate\Support\Facades\Http;
use App\Models\Backend\ParcelStatusMapping;

class ParcelController extends Controller
{
    public function details(Parcel $parcel)
    {
        $parcel->load([
            'lastParcel3pl','parcels_3pl','parcelEvent',
            'merchant.user','shop','city','area',
        ]);

        $pandaTracking = null;
        $pandaMappings = []; // [awb => ['current' => [...], 'activity' => [index => [...]]]]

        $last = $parcel->lastParcel3pl;
        
        $awb_pdf =  $last->awb_pdf;
        
        
        $companyName = $last->company_name ?? $last->parcel_3pl_name ?? null;

        if ($companyName && preg_match('/panda/i', $companyName)) {
            $awb = $last->awb_number ?? $parcel->tracking_id;

            if ($awb) {
                try {
                    $baseUrl = config('services.deliverypanda.base_url');
                    $apiKey  = config('services.deliverypanda.key');
                    $timeout = (int) config('services.deliverypanda.timeout', 30);
                    $url     = rtrim($baseUrl, '/') . '/GetTracking';

                    $payload = ['AwbNumber' => [$awb]];

                    $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'API-KEY'      => $apiKey,
                        ])
                        ->timeout($timeout)
                        ->post($url, $payload);
                        
                      

                    if ($response->successful()) {
                        $json = $response->json();
                        
 
                        if (($json['success'] ?? 0) == 1 && !empty($json['TrackResponse'])) {
                            $pandaTracking = $json['TrackResponse'];

                            foreach ($pandaTracking as $tIdx => $item) {
                                $shipment = $item['Shipment'] ?? [];
                                $awbNum   = $shipment['awb_number'] ?? null;

                                // ---- mapping للـ current_status
                                $extCurrentText = $shipment['current_status'] ?? null; // مثال: "Delivered"
                                $mapCurrent = ParcelStatusMapping::mapExternalToInternal(
                                    'DeliveryPanda', // اسم الشركة كما هو في جدولك
                                    null,            // لا يوجد كود صريح في رد Panda هنا
                                    $extCurrentText,
                                    null
                                );

                                $pandaMappings[$awbNum]['current'] = [
                                    'external_label'   => $extCurrentText,
                                    'parcel_status_id' => $mapCurrent->parcel_status_id ?? null,
                                    'internal_label'   => isset($mapCurrent)
                                        ? trans('parcelStatus.'.$mapCurrent->parcel_status_id)
                                        : null,
                                    'notes'            => $mapCurrent->notes_en ?? $mapCurrent->notes_ar ?? null,
                                ];

                                // ---- mapping لكل عنصر Activity.status
                                $pandaMappings[$awbNum]['activity'] = [];
                                $activityList = $shipment['Activity'] ?? [];
                                foreach ($activityList as $aIdx => $act) {
                                    $extActivityText = $act['status'] ?? null; // مثال: "Submitted", "Assigned", ...
                                    $mapAct = ParcelStatusMapping::mapExternalToInternal(
                                        'DeliveryPanda',
                                        null,
                                        $extActivityText,
                                        null
                                    );

                                    $pandaMappings[$awbNum]['activity'][$aIdx] = [
                                        'external_label'   => $extActivityText,
                                        'parcel_status_id' => $mapAct->parcel_status_id ?? null,
                                        'internal_label'   => isset($mapAct)
                                            ? trans('parcelStatus.'.$mapAct->parcel_status_id)
                                            : null,
                                        'notes'            => $mapAct->notes_en ?? $mapAct->notes_ar ?? null,
                                    ];
                                }
                            }
                        } else {
                            $pandaTracking = ['error' => $json['message'] ?? 'Unknown Panda response'];
                        }
                    } else {
                        $pandaTracking = ['error' => 'Panda API request failed: HTTP '.$response->status()];
                    }
                } catch (\Throwable $e) {
                    $pandaTracking = ['error' => 'Panda API exception: '.$e->getMessage()];
                }
            }
        }
        
 
        return view('admin.parcels.partials.details', [
            'awb_pdf'         => $awb_pdf,
            'parcel'         => $parcel,
            'pandaTracking'  => $pandaTracking,
            'pandaMappings'  => $pandaMappings, // ⬅️ جديد
        ]);
    }
}
