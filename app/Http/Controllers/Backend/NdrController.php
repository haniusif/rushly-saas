<?php

namespace App\Http\Controllers\Backend;

use App\Enums\NdrAction;
use App\Enums\NdrFailureReason;
use App\Enums\NdrStatus;
use App\Exports\NdrExport;
use App\Http\Controllers\Controller;
use App\Models\Backend\Ndr;
use App\Models\Backend\Parcel;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Hub\HubInterface;
use App\Repositories\NdrRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class NdrController extends Controller
{
    protected NdrRepositoryInterface $repo;
    protected DeliveryManInterface $deliveryman;
    protected HubInterface $hub;

    public function __construct(
        NdrRepositoryInterface $repo,
        DeliveryManInterface $deliveryman,
        HubInterface $hub
    ) {
        $this->repo        = $repo;
        $this->deliveryman = $deliveryman;
        $this->hub         = $hub;
    }

    public function index(Request $request)
    {
        $paginator      = $this->repo->all($request);
        $stats          = $this->repo->stats();
        $failureReasons = $this->failureReasonOptions();
        $deliverymans   = $this->deliveryman->selectable();

        $rows = collect($paginator->items())->map(fn ($n) => [
            'id'              => $n->id,
            'tracking_id'     => optional($n->parcel)->tracking_id ?? ('#' . $n->parcel_id),
            'attempt_number' => (int) $n->attempt_number,
            'failure_reason'  => $n->failure_reason,
            'failure_label'   => ucwords(str_replace('_', ' ', $n->failure_reason)),
            'deliveryman'     => optional($n->deliveryman)->name,
            'status'          => $n->status,
            'status_label'    => ucwords(str_replace('_', ' ', $n->status)),
            'created_at'      => optional($n->created_at)->diffForHumans(),
            'url'             => route('ndr.show', $n->id),
        ])->values();

        $deliverymanRows = collect($deliverymans instanceof \Illuminate\Pagination\AbstractPaginator
            ? $deliverymans->items() : $deliverymans)->map(fn ($d) => [
                'id'   => $d->user_id ?? $d->id,
                'name' => optional($d->user)->name ?? $d->name ?? ('#' . $d->id),
            ])->values();

        $filters = $request->only(['status', 'failure_reason', 'deliveryman_id', 'date_from', 'date_to']);

        return Inertia::render('Admin/Ndr/Index', [
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
            'filters' => array_merge([
                'status' => '', 'failure_reason' => '', 'deliveryman_id' => '',
                'date_from' => '', 'date_to' => '',
            ], $filters),
            'stats' => [
                'today'       => (int) ($stats['today'] ?? 0),
                'open'        => (int) ($stats['open'] ?? 0),
                'in_progress' => (int) ($stats['in_progress'] ?? 0),
                'resolved'    => (int) ($stats['resolved'] ?? 0),
                'return_rate' => (float) ($stats['return_rate'] ?? 0),
            ],
            'lookups' => [
                'statuses'    => ['open', 'in_progress', 'resolved', 'returned'],
                'reasons'     => collect($failureReasons)->map(fn ($label, $key) => [
                    'value' => $key, 'label' => $label,
                ])->values(),
                'deliverymen' => $deliverymanRows,
            ],
            'urls' => [
                'index'  => route('ndr.index'),
                'export' => route('ndr.export', $filters),
            ],
            't' => [
                'title'          => __('ndr.title') ?: 'NDR',
                'list'           => __('levels.list') ?: 'List',
                'today_ndrs'     => __('ndr.today_ndrs') ?: 'Today',
                'pending'        => __('ndr.pending') ?: 'Pending',
                'resolved'       => __('ndr.resolved') ?: 'Resolved',
                'return_rate'    => __('ndr.return_rate') ?: 'Return rate',
                'all_status'     => __('ndr.all_status') ?: 'All status',
                'all_reasons'    => __('ndr.all_reasons') ?: 'All reasons',
                'any_deliveryman'=> __('ndr.any_deliveryman') ?: 'Any courier',
                'attempt'        => __('ndr.attempt') ?: 'Attempt',
                'failure_reason' => __('ndr.failure_reason') ?: 'Failure reason',
                'tracking'       => __('levels.tracking') ?: 'Tracking',
                'deliveryman'    => __('levels.deliveryman') ?: 'Courier',
                'status'         => __('levels.status') ?: 'Status',
                'created'        => __('levels.created') ?: 'Created',
                'actions'        => __('levels.actions') ?: 'Actions',
                'view'           => __('levels.view') ?: 'View',
                'filter'         => __('levels.filter') ?: 'Filter',
                'clear'          => __('levels.clear') ?: 'Clear',
                'export'         => __('levels.export_to_excel') ?: 'Export',
                'no_rows'        => __('ndr.no_ndrs_found') ?: 'No NDRs found',
                'showing_results'=> 'Showing :from – :to of :total',
                'from'           => 'From',
                'to'             => 'To',
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'ndr-'.now()->format('Ymd-His').'.xlsx';
        return Excel::download(new NdrExport($request), $filename);
    }

    public function show(int $id)
    {
        $ndr = $this->repo->find($id);
        if (!$ndr) {
            Toastr::error(__('NDR not found.'));
            return redirect()->route('ndr.index');
        }

        $hubs = $this->hub->all();
        return view('backend.ndr.show', compact('ndr', 'hubs'));
    }

    public function create(Parcel $parcel)
    {
        // Business rule: max 3 attempts AND only one open NDR per parcel per day.
        $todayOpen = $this->repo->todayOpenForParcel($parcel->id);
        if ($todayOpen) {
            Toastr::warning(__('An open NDR already exists for this parcel today.'));
            return redirect()->route('ndr.show', $todayOpen->id);
        }

        $attemptNumber  = Ndr::companywise()->where('parcel_id', $parcel->id)->count() + 1;
        if ($attemptNumber > 3) {
            Toastr::error(__('Maximum 3 NDR attempts already recorded for this parcel.'));
            return redirect()->route('ndr.index');
        }

        $failureReasons = $this->failureReasonOptions();
        return view('backend.ndr.create', compact('parcel', 'attemptNumber', 'failureReasons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parcel_id'         => ['required', 'integer', 'exists:parcels,id'],
            'failure_reason'    => ['required', 'string'],
            'driver_notes'      => ['nullable', 'string'],
            'driver_photo'      => ['nullable', 'image', 'max:5120'],
            'next_attempt_date' => ['nullable', 'date'],
            'deliveryman_id'    => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // One-per-day rule (validation layer).
        if ($this->repo->todayOpenForParcel((int) $data['parcel_id'])) {
            Toastr::warning(__('An open NDR already exists for this parcel today.'));
            return redirect()->route('ndr.index');
        }

        // Attempt number = existing NDRs for this parcel + 1.
        $attemptNumber = Ndr::companywise()->where('parcel_id', $data['parcel_id'])->count() + 1;

        if ($request->hasFile('driver_photo')) {
            $file = $request->file('driver_photo');
            $name = date('YmdHis') . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/ndr/'), $name);
            $data['driver_photo'] = 'uploads/ndr/' . $name;
        }

        $data['attempt_number'] = $attemptNumber;
        $data['created_by']     = Auth::id();
        $data['deliveryman_id'] = $data['deliveryman_id'] ?? Auth::id();

        $ndr = $this->repo->create($data);

        Toastr::success(__('NDR recorded (attempt :n).', ['n' => $ndr->attempt_number]));
        return redirect()->route('ndr.show', $ndr->id);
    }

    public function updateAction(Request $request, int $id)
    {
        $request->validate([
            'action_taken'      => ['required', 'string', 'in:reschedule,return_to_merchant,transfer_hub,escalate'],
            'next_attempt_date' => ['nullable', 'date'],
            'hub_id'            => ['nullable', 'integer', 'exists:hubs,id'],
            'delivery_man_id'   => ['nullable', 'integer'],
            'date'              => ['nullable', 'date'],
        ]);

        $ndr = $this->repo->find($id);
        if (!$ndr) {
            Toastr::error(__('NDR not found.'));
            return redirect()->route('ndr.index');
        }

        if ($this->repo->applyAction($ndr, $request->input('action_taken'), $request)) {
            Toastr::success(__('Action recorded.'));
        } else {
            Toastr::error(__('Could not apply action.'));
        }

        return redirect()->route('ndr.show', $ndr->id);
    }

    public function resolve(Request $request, int $id)
    {
        $ndr = $this->repo->find($id);
        if (!$ndr) {
            Toastr::error(__('NDR not found.'));
            return redirect()->route('ndr.index');
        }

        if ($this->repo->resolve($ndr, Auth::id())) {
            Toastr::success(__('NDR marked as resolved.'));
        } else {
            Toastr::error(__('Could not resolve NDR.'));
        }

        return redirect()->route('ndr.show', $ndr->id);
    }

    /** Reflect on the NdrFailureReason interface so the view is data-driven. */
    protected function failureReasonOptions(): array
    {
        $rc = new \ReflectionClass(NdrFailureReason::class);
        $out = [];
        foreach ($rc->getConstants() as $name => $value) {
            $out[$value] = ucwords(str_replace('_', ' ', strtolower($name)));
        }
        return $out;
    }
}
