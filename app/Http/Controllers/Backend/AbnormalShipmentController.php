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
use Inertia\Inertia;

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
        $paginator = $this->repo->all($request);

        $base    = AbnormalShipment::companywise();
        $summary = [
            'stalled_3'  => (clone $base)->where('stale_days', '>=', 3)->whereIn('status', ['open', 'investigating'])->count(),
            'stalled_5'  => (clone $base)->where('stale_days', '>=', 5)->whereIn('status', ['open', 'investigating'])->count(),
            'stalled_7'  => (clone $base)->where('stale_days', '>=', 7)->whereIn('status', ['open', 'investigating'])->count(),
            'closed_lost'=> (clone $base)->where('status', 'closed_lost')->count(),
        ];

        $deliverymans = $this->deliveryman->selectable();
        $threshold    = $this->repo->getThresholdDays();

        $rows = collect($paginator->items())->map(fn ($a) => [
            'id'            => $a->id,
            'tracking_id'   => optional($a->parcel)->tracking_id ?? ('#' . $a->parcel_id),
            'customer_name' => optional($a->parcel)->customer_name,
            'last_event'    => optional($a->last_event_at)->diffForHumans(),
            'stale_days'    => (int) $a->stale_days,
            'severity'      => $a->severity,
            'status'        => $a->status,
            'status_label'  => ucwords(str_replace('_', ' ', $a->status)),
            'url'           => route('abnormal.show', $a->id),
        ])->values();

        $deliverymanRows = collect($deliverymans instanceof \Illuminate\Pagination\AbstractPaginator
            ? $deliverymans->items() : $deliverymans)->map(fn ($d) => [
                'id'   => $d->user_id ?? $d->id,
                'name' => optional($d->user)->name ?? $d->name ?? ('#' . $d->id),
            ])->values();

        $filters = $request->only(['min_days', 'severity', 'status', 'assigned_to']);

        return Inertia::render('Admin/Abnormal/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters'   => array_merge([
                'min_days' => '', 'severity' => '', 'status' => '', 'assigned_to' => '',
            ], $filters),
            'summary'   => array_map('intval', $summary),
            'threshold' => (int) $threshold,
            'lookups'   => [
                'min_days'    => ['3', '5', '7'],
                'severities'  => ['warning', 'danger', 'critical'],
                'statuses'    => ['open', 'investigating', 'resolved', 'closed_lost'],
                'deliverymen' => $deliverymanRows,
            ],
            'urls' => [
                'index'    => route('abnormal.index'),
                'settings' => route('abnormal.settings'),
            ],
            't' => [
                'title'             => __('abnormal.title') ?: 'Abnormal shipments',
                'settings'          => __('settings.title') ?: 'Settings',
                'stalled_3_days'    => __('abnormal.stalled_3_days') ?: 'Stalled 3+ days',
                'stalled_5_days'    => __('abnormal.stalled_5_days') ?: 'Stalled 5+ days',
                'stalled_7_days'    => __('abnormal.stalled_7_days_critical') ?: 'Critical 7+ days',
                'closed_as_lost'    => __('abnormal.closed_as_lost') ?: 'Closed as lost',
                'duration'          => __('abnormal.duration') ?: 'Duration',
                'any_severity'      => __('abnormal.any_severity') ?: 'Any severity',
                'all_status'        => __('ndr.all_status') ?: 'All status',
                'any_investigator'  => __('abnormal.any_investigator') ?: 'Any investigator',
                'detection_threshold'=> __('abnormal.detection_threshold') ?: 'Detection threshold',
                'days'              => __('abnormal.days') ?: 'days',
                'all'               => __('levels.all') ?: 'All',
                'tracking'          => __('levels.tracking') ?: 'Tracking',
                'customer'          => __('levels.customer') ?: 'Customer',
                'last_event'        => __('abnormal.last_event') ?: 'Last event',
                'stale_days'        => __('abnormal.stale_days') ?: 'Stale days',
                'severity'          => __('abnormal.severity') ?: 'Severity',
                'status'            => __('levels.status') ?: 'Status',
                'actions'           => __('levels.actions') ?: 'Actions',
                'open'              => __('levels.open') ?: 'Open',
                'filter'            => __('levels.filter') ?: 'Filter',
                'clear'             => __('levels.clear') ?: 'Clear',
                'no_rows'           => __('abnormal.no_abnormal') ?: 'No abnormal shipments',
                'showing_results'   => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function show(int $id)
    {
        $a = $this->repo->find($id);
        if (!$a) {
            Toastr::error(__('Abnormal record not found.'));
            return redirect()->route('abnormal.index');
        }
        $a->loadMissing(['parcel.merchant', 'assignedTo']);

        $deliverymans = $this->deliveryman->selectable();
        $events       = ParcelEvent::where('parcel_id', $a->parcel_id)
                            ->orderByDesc('id')->limit(15)->get();
        $autoEscalate = max(1, (int) $this->getConfig('abnormal_auto_escalation_days', 7));
        $stale        = (int) $a->stale_days;

        $deliverymanRows = collect($deliverymans instanceof \Illuminate\Pagination\AbstractPaginator
            ? $deliverymans->items() : $deliverymans)->map(fn ($d) => [
                'id'   => $d->user_id ?? $d->id,
                'name' => optional($d->user)->name ?? $d->name ?? ('#' . $d->id),
            ])->values();

        return \Inertia\Inertia::render('Admin/Abnormal/View', [
            'abnormal' => [
                'id'             => $a->id,
                'severity'       => $a->severity,
                'status'         => $a->status,
                'status_label'   => ucwords(str_replace('_', ' ', $a->status)),
                'stale_days'     => $stale,
                'auto_escalate'  => $autoEscalate,
                'stale_pct'      => min(100, (int) round(($stale / max(1, $autoEscalate)) * 100)),
                'detected_at'    => optional($a->detected_at)->diffForHumans(),
                'last_event_at'  => optional($a->last_event_at)->toDateTimeString(),
                'last_event_rel' => optional($a->last_event_at)->diffForHumans(),
                'escalated_at'   => optional($a->escalated_at)->toDateTimeString(),
                'resolution_note'=> $a->resolution_note,
                'assigned_to'    => $a->assigned_to,
                'assigned_name'  => optional($a->assignedTo)->name,
                'parcel' => [
                    'id'             => $a->parcel_id,
                    'tracking_id'    => optional($a->parcel)->tracking_id,
                    'customer_name'  => optional($a->parcel)->customer_name,
                    'customer_phone' => optional($a->parcel)->customer_phone,
                    'merchant'       => optional(optional($a->parcel)->merchant)->business_name,
                ],
            ],
            'events' => $events->map(fn ($e) => [
                'id'         => $e->id,
                'status'     => (int) $e->parcel_status,
                'label'      => \App\Support\ParcelStatusHelper::label((int) $e->parcel_status),
                'color'      => \App\Support\ParcelStatusHelper::color((int) $e->parcel_status),
                'hub_id'     => $e->hub_id,
                'created_at' => optional($e->created_at)->toDateTimeString(),
            ])->values(),
            'lookups' => [
                'deliverymen' => $deliverymanRows,
            ],
            'permissions' => [
                'manage' => hasPermission('abnormal_manage'),
            ],
            'urls' => [
                'index'         => route('abnormal.index'),
                'parcel_view'   => route('parcel.details', $a->parcel_id),
                'assign'        => route('abnormal.assign',  $a->id),
                'action'        => route('abnormal.action',  $a->id),
                'resolve'       => route('abnormal.resolve', $a->id),
                'create_ndr'    => route('ndr.create',       $a->parcel_id),
                'settings'      => route('abnormal.settings'),
            ],
            't' => [
                'title'              => 'Abnormal shipment',
                'title_index'        => __('abnormal.title') ?: 'Abnormal shipments',
                'back'               => 'Back to list',
                'customer'           => __('levels.customer') ?: 'Customer',
                'phone'              => __('levels.phone') ?: 'Phone',
                'merchant'           => __('levels.merchant') ?: 'Merchant',
                'detected'           => __('abnormal.detected') ?: 'Detected',
                'last_event'         => __('abnormal.last_event') ?: 'Last event',
                'assigned_to'        => __('abnormal.assigned_to') ?: 'Assigned to',
                'nobody_yet'         => __('abnormal.nobody_yet') ?: 'Nobody yet',
                'stale_progress'     => __('abnormal.stale_progress') ?: 'Stale progress',
                'days_of'            => 'of',
                'days'               => 'days (auto-escalation threshold)',
                'event_timeline'     => __('abnormal.event_timeline') ?: 'Event timeline',
                'no_events'          => __('abnormal.no_events') ?: 'No events.',
                'investigation'      => __('abnormal.investigation') ?: 'Investigation',
                'assign_investigator'=> __('abnormal.assign_to_investigator') ?: 'Assign to investigator',
                'assign'             => __('abnormal.assign') ?: 'Assign',
                'take_action'        => __('abnormal.take_action') ?: 'Take action',
                'create_ndr'         => __('abnormal.create_ndr') ?: 'Create NDR',
                'log_contact'        => __('abnormal.log_customer_contact') ?: 'Log customer contact',
                'escalate'           => 'Escalate',
                'close_lost'         => 'Close as lost',
                'close_lost_warn'    => 'Close-as-Lost needs a second supervisor to confirm.',
                'resolve'            => 'Resolve',
                'resolution_note'    => 'Resolution note',
                'resolution_placeholder' => __('abnormal.resolution_note_placeholder') ?: 'How was this resolved?',
                'severity'           => __('abnormal.severity') ?: 'Severity',
                'status'             => __('levels.status') ?: 'Status',
                'view_parcel'        => 'Open parcel',
            ],
        ]);
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
