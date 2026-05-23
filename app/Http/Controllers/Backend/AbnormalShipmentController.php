<?php

namespace App\Http\Controllers\Backend;

use App\Enums\AbnormalSeverity;
use App\Http\Controllers\Controller;
use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Config;
use App\Repositories\AbnormalShipmentRepositoryInterface;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Hub\HubInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbnormalShipmentController extends Controller
{
    protected AbnormalShipmentRepositoryInterface $repo;
    protected DeliveryManInterface $deliveryman;
    protected HubInterface $hub;

    public function __construct(
        AbnormalShipmentRepositoryInterface $repo,
        DeliveryManInterface $deliveryman,
        HubInterface $hub
    ) {
        $this->repo        = $repo;
        $this->deliveryman = $deliveryman;
        $this->hub         = $hub;
    }

    public function index(Request $request)
    {
        $abnormals = $this->repo->all($request);

        $base    = AbnormalShipment::companywise();
        $summary = [
            'stalled_3'  => (clone $base)->where('stale_days', '>=', 3)->whereIn('status', ['open', 'investigating'])->count(),
            'stalled_5'  => (clone $base)->where('stale_days', '>=', 5)->whereIn('status', ['open', 'investigating'])->count(),
            'stalled_7'  => (clone $base)->where('stale_days', '>=', 7)->whereIn('status', ['open', 'investigating'])->count(),
            'closed_lost'=> (clone $base)->where('status', 'closed_lost')->count(),
        ];

        $deliverymans = $this->deliveryman->all();
        $threshold    = $this->repo->getThresholdDays();

        return view('backend.abnormal.index', compact('abnormals', 'summary', 'deliverymans', 'threshold'));
    }

    public function show(int $id)
    {
        $abnormal = $this->repo->find($id);
        if (!$abnormal) {
            Toastr::error(__('Abnormal record not found.'));
            return redirect()->route('abnormal.index');
        }

        $deliverymans = $this->deliveryman->all();
        $hubs         = $this->hub->all();
        $events       = ParcelEvent::where('parcel_id', $abnormal->parcel_id)
                            ->orderByDesc('id')->limit(15)->get();
        $autoEscalate = max(1, (int) $this->getConfig('abnormal_auto_escalation_days', 7));

        return view('backend.abnormal.show', compact('abnormal', 'deliverymans', 'hubs', 'events', 'autoEscalate'));
    }

    public function assign(Request $request, int $id)
    {
        $request->validate(['assigned_to' => ['required', 'integer', 'exists:users,id']]);
        $a = $this->repo->find($id);
        if (!$a) { return redirect()->route('abnormal.index'); }

        $this->repo->assign($a, (int) $request->input('assigned_to'));
        Toastr::success(__('Assigned to investigator.'));
        return redirect()->route('abnormal.show', $a->id);
    }

    public function takeAction(Request $request, int $id)
    {
        $request->validate([
            'action' => ['required', 'string', 'in:reassign_deliveryman,create_ndr,log_contact,escalate,close_lost'],
        ]);

        $a = $this->repo->find($id);
        if (!$a) { return redirect()->route('abnormal.index'); }

        switch ($request->input('action')) {
            case 'reassign_deliveryman':
                // Caller picks a driver; we delegate via the bulk-action endpoint contract.
                Toastr::info(__('Use the Parcel page to reassign — link below.'));
                return redirect()->route('abnormal.show', $a->id);

            case 'create_ndr':
                return redirect()->route('ndr.create', $a->parcel_id);

            case 'log_contact':
                $a->resolution_note = trim(($a->resolution_note ? $a->resolution_note . "\n" : '')
                    . '[' . now()->toDateTimeString() . '] '
                    . optional(Auth::user())->name . ' — '
                    . ($request->input('note') ?? 'Customer contact logged.'));
                $a->save();
                Toastr::success(__('Contact attempt logged.'));
                break;

            case 'escalate':
                $a->escalated_at = now();
                if ($a->status === 'open') $a->status = 'investigating';
                $a->save();
                Toastr::success(__('Escalated to management.'));
                break;

            case 'close_lost':
                // Dual-approval gate. First click flags pending; second click by a DIFFERENT user finalises.
                $note = (string) ($a->resolution_note ?? '');
                if (!str_contains($note, '[pending-close-lost-by:')) {
                    $a->resolution_note = trim($note . "\n[pending-close-lost-by:" . Auth::id() . " at " . now()->toDateTimeString() . "]");
                    $a->save();
                    Toastr::warning(__('Close-as-Lost requested. A second supervisor must confirm.'));
                } else {
                    if (preg_match('/\[pending-close-lost-by:(\d+) at/', $note, $m) && (int)$m[1] === (int)Auth::id()) {
                        Toastr::error(__('A different supervisor must confirm the close-as-lost request.'));
                        return redirect()->route('abnormal.show', $a->id);
                    }
                    $a->status       = 'closed_lost';
                    $a->resolved_by  = Auth::id();
                    $a->resolved_at  = now();
                    $a->resolution_note = trim($note . "\n[confirmed-close-lost-by:" . Auth::id() . " at " . now()->toDateTimeString() . "]");
                    $a->save();
                    Toastr::success(__('Closed as Lost.'));
                }
                break;
        }

        return redirect()->route('abnormal.show', $a->id);
    }

    public function resolve(Request $request, int $id)
    {
        $a = $this->repo->find($id);
        if (!$a) { return redirect()->route('abnormal.index'); }

        $this->repo->resolve($a, Auth::id(), $request->input('note'));
        Toastr::success(__('Marked as resolved.'));
        return redirect()->route('abnormal.show', $a->id);
    }

    public function settings()
    {
        $config = [
            'threshold_days'        => (int) $this->getConfig('abnormal_threshold_days', 3),
            'auto_escalation_days'  => (int) $this->getConfig('abnormal_auto_escalation_days', 7),
            'exclude_holidays'      => (bool) $this->getConfig('abnormal_exclude_holidays', true),
            'exclude_customs'       => (bool) $this->getConfig('abnormal_exclude_customs', true),
            'exclude_on_hold'       => (bool) $this->getConfig('abnormal_exclude_on_hold', true),
            'daily_digest_enabled'  => (bool) $this->getConfig('abnormal_daily_digest_enabled', true),
        ];
        return view('backend.abnormal.settings', compact('config'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'threshold_days'       => ['required', 'integer', 'min:1', 'max:60'],
            'auto_escalation_days' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $this->setConfig('abnormal_threshold_days',       (int) $request->threshold_days);
        $this->setConfig('abnormal_auto_escalation_days', (int) $request->auto_escalation_days);
        $this->setConfig('abnormal_exclude_holidays',     $request->boolean('exclude_holidays') ? 1 : 0);
        $this->setConfig('abnormal_exclude_customs',      $request->boolean('exclude_customs') ? 1 : 0);
        $this->setConfig('abnormal_exclude_on_hold',      $request->boolean('exclude_on_hold') ? 1 : 0);
        $this->setConfig('abnormal_daily_digest_enabled', $request->boolean('daily_digest_enabled') ? 1 : 0);

        Toastr::success(__('Settings saved.'));
        return redirect()->route('abnormal.settings');
    }

    // ===== Helpers =====

    protected function getConfig(string $key, $default = null)
    {
        $row = Config::where('company_id', settings()->id)->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    protected function setConfig(string $key, $value): void
    {
        Config::updateOrCreate(
            ['company_id' => settings()->id, 'key' => $key],
            ['value' => (string) $value]
        );
    }
}
