<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tour\StoreRequest;
use App\Http\Requests\Tour\UpdateRequest;
use App\Models\Backend\Tour;
use App\Models\Backend\TourStep;
use App\Models\Backend\TourEvent;
use App\Models\Backend\UserTourProgress;
use App\Repositories\Tour\TourRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Admin CRUD + analytics for onboarding tours. Mounted under
 * /admin/tours in the tenant middleware group; gated by tour_manage.
 */
class TourManagerController extends Controller
{
    public function __construct(protected TourRepositoryInterface $repo) {}

    public function index()
    {
        $companyId = settings()->id;
        $tours = Tour::withCount('steps')
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByRaw('company_id IS NULL')
            ->orderBy('module')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/Tours/Index', [
            'tours' => $tours->map(fn ($t) => [
                'id'            => $t->id,
                'company_id'    => $t->company_id,
                'is_system'     => $t->company_id === null,
                'key'           => $t->key,
                'module'        => $t->module,
                'title'         => $t->title,
                'description'   => $t->description,
                'version'       => (int) $t->version,
                'is_active'     => (bool) $t->is_active,
                'auto_start'    => (bool) $t->auto_start,
                'role_scope'    => $t->role_scope ?? [],
                'trigger_route' => $t->trigger_route,
                'step_count'    => (int) $t->steps_count,
                'updated_at'    => optional($t->updated_at)->format('Y-m-d H:i'),
            ])->values(),
            'urls' => [
                'create'    => route('admin.tours.create'),
                'analytics' => route('admin.tours.analytics'),
            ],
            't' => $this->labels(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tours/Create', [
            'urls' => [
                'store'  => route('admin.tours.store'),
                'cancel' => route('admin.tours.index'),
            ],
            'lookups' => $this->lookups(),
            't'       => $this->labels(),
        ]);
    }

    public function store(StoreRequest $request)
    {
        $tour = DB::transaction(function () use ($request) {
            $tour = Tour::create([
                'company_id'    => settings()->id,
                'key'           => $request->input('key'),
                'module'        => $request->input('module'),
                'title'         => $request->input('title'),
                'description'   => $request->input('description'),
                'role_scope'    => $request->input('role_scope'),
                'meta'          => [],
                'version'       => (int) $request->input('version', 1),
                'is_active'     => (bool) $request->input('is_active', true),
                'auto_start'    => (bool) $request->input('auto_start', false),
                'trigger_route' => $request->input('trigger_route'),
            ]);
            $this->syncSteps($tour, (array) $request->input('steps', []));
            return $tour;
        });
        Toastr::success('Tour created', 'Success');
        return redirect()->route('admin.tours.edit', $tour->id);
    }

    public function edit(int $id)
    {
        $tour = Tour::with('steps')->findOrFail($id);
        return Inertia::render('Admin/Tours/Edit', [
            'tour'    => $this->serializeTour($tour),
            'lookups' => $this->lookups(),
            'urls'    => [
                'update' => route('admin.tours.update', $tour->id),
                'delete' => route('admin.tours.delete', $tour->id),
                'toggle' => route('admin.tours.toggle', $tour->id),
                'preview'=> route('admin.tours.preview', $tour->id),
                'cancel' => route('admin.tours.index'),
            ],
            't' => $this->labels(),
        ]);
    }

    public function update(UpdateRequest $request, int $id)
    {
        $tour = Tour::findOrFail($id);
        DB::transaction(function () use ($tour, $request) {
            $tour->update([
                'key'           => $request->input('key'),
                'module'        => $request->input('module'),
                'title'         => $request->input('title'),
                'description'   => $request->input('description'),
                'role_scope'    => $request->input('role_scope'),
                'version'       => (int) $request->input('version', $tour->version),
                'is_active'     => (bool) $request->input('is_active', true),
                'auto_start'    => (bool) $request->input('auto_start', false),
                'trigger_route' => $request->input('trigger_route'),
            ]);
            $this->syncSteps($tour, (array) $request->input('steps', []));
        });
        Toastr::success('Tour updated', 'Success');
        return redirect()->route('admin.tours.edit', $tour->id);
    }

    public function destroy(int $id)
    {
        $tour = Tour::findOrFail($id);
        $tour->steps()->delete();
        $tour->delete();
        Toastr::success('Tour deleted', 'Success');
        return redirect()->route('admin.tours.index');
    }

    public function toggle(int $id)
    {
        $tour = Tour::findOrFail($id);
        $tour->is_active = ! $tour->is_active;
        $tour->save();
        return response()->json(['ok' => true, 'is_active' => $tour->is_active]);
    }

    public function preview(int $id)
    {
        $tour = Tour::with('steps')->findOrFail($id);
        return Inertia::render('Admin/Tours/Preview', [
            'tour' => $this->serializeTour($tour),
            'urls' => [
                'back' => route('admin.tours.edit', $tour->id),
            ],
            't' => $this->labels(),
        ]);
    }

    /**
     * Aggregate analytics per tour: starts, completions, skips, drop-off,
     * avg time-per-step. Cheap because tour_events is well-indexed.
     */
    public function analytics()
    {
        $companyId = settings()->id;

        // Per-tour funnel counts
        $funnel = TourEvent::selectRaw('tour_key, event, COUNT(*) as n')
            ->where('company_id', $companyId)
            ->whereIn('event', ['started', 'completed', 'skipped', 'dismissed'])
            ->groupBy('tour_key', 'event')
            ->get()
            ->groupBy('tour_key');

        // Drop-off: most common step_index right before a skip/dismiss
        $dropoff = TourEvent::selectRaw('tour_key, step_index, COUNT(*) as n')
            ->where('company_id', $companyId)
            ->whereIn('event', ['skipped', 'dismissed'])
            ->groupBy('tour_key', 'step_index')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->groupBy('tour_key');

        // Average time per step (from step_forward duration_ms)
        $avgTime = TourEvent::selectRaw('tour_key, AVG(duration_ms) as avg_ms')
            ->where('company_id', $companyId)
            ->where('event', 'step_forward')
            ->whereNotNull('duration_ms')
            ->groupBy('tour_key')
            ->get()
            ->keyBy('tour_key');

        $tours = Tour::where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('title')
            ->get(['id', 'key', 'title', 'module', 'is_active']);

        $rows = $tours->map(function ($tour) use ($funnel, $dropoff, $avgTime) {
            $f = $funnel->get($tour->key, collect());
            $starts     = (int) ($f->firstWhere('event', 'started')->n ?? 0);
            $completes  = (int) ($f->firstWhere('event', 'completed')->n ?? 0);
            $skips      = (int) ($f->firstWhere('event', 'skipped')->n ?? 0);
            $dismisses  = (int) ($f->firstWhere('event', 'dismissed')->n ?? 0);
            $drop       = $dropoff->get($tour->key, collect())->first();
            return [
                'tour_key'        => $tour->key,
                'title'           => $tour->title,
                'module'          => $tour->module,
                'is_active'       => (bool) $tour->is_active,
                'starts'          => $starts,
                'completes'       => $completes,
                'skips'           => $skips,
                'dismisses'       => $dismisses,
                'completion_rate' => $starts > 0 ? round(($completes / $starts) * 100, 1) : 0.0,
                'dropoff_step'    => $drop ? (int) $drop->step_index : null,
                'avg_step_ms'     => optional($avgTime->get($tour->key))->avg_ms
                    ? (int) $avgTime->get($tour->key)->avg_ms
                    : null,
            ];
        })->values();

        return Inertia::render('Admin/Tours/Analytics', [
            'rows' => $rows,
            'urls' => [
                'index' => route('admin.tours.index'),
            ],
            't' => $this->labels(),
        ]);
    }

    protected function syncSteps(Tour $tour, array $steps): void
    {
        $tour->steps()->delete();
        foreach (array_values($steps) as $i => $s) {
            TourStep::create([
                'tour_id'           => $tour->id,
                'sort_order'        => $i,
                'target'            => (array) ($s['target'] ?? []),
                'placement'         => $s['placement'] ?? 'auto',
                'spotlight_padding' => (int) ($s['spotlight_padding'] ?? 8),
                'translations'      => (array) ($s['translations'] ?? []),
                'action'            => $s['action'] ?? null,
            ]);
        }
    }

    protected function serializeTour(Tour $tour): array
    {
        return [
            'id'            => $tour->id,
            'company_id'    => $tour->company_id,
            'is_system'     => $tour->company_id === null,
            'key'           => $tour->key,
            'module'        => $tour->module,
            'title'         => $tour->title,
            'description'   => $tour->description,
            'role_scope'    => $tour->role_scope ?? [],
            'version'       => (int) $tour->version,
            'is_active'     => (bool) $tour->is_active,
            'auto_start'    => (bool) $tour->auto_start,
            'trigger_route' => $tour->trigger_route,
            'steps'         => $tour->steps->map(fn ($s) => [
                'id'                => $s->id,
                'sort_order'        => (int) $s->sort_order,
                'target'            => $s->target,
                'placement'         => $s->placement,
                'spotlight_padding' => (int) $s->spotlight_padding,
                'translations'      => $s->translations,
                'action'            => $s->action,
            ])->values()->all(),
        ];
    }

    protected function lookups(): array
    {
        return [
            'roles' => [
                ['value' => 1, 'label' => 'Admin'],
                ['value' => 2, 'label' => 'Merchant'],
                ['value' => 3, 'label' => 'Delivery man'],
                ['value' => 4, 'label' => 'Incharge'],
                ['value' => 5, 'label' => 'Hub'],
                ['value' => 6, 'label' => 'Super admin'],
            ],
            'placements' => [
                ['value' => 'auto',   'label' => 'Auto'],
                ['value' => 'top',    'label' => 'Top'],
                ['value' => 'bottom', 'label' => 'Bottom'],
                ['value' => 'start',  'label' => 'Start'],
                ['value' => 'end',    'label' => 'End'],
            ],
            'target_types' => [
                ['value' => 'data-tour',  'label' => 'data-tour attribute'],
                ['value' => 'selector',   'label' => 'CSS selector'],
                ['value' => 'route-name', 'label' => 'Route name (navigate)'],
            ],
        ];
    }

    protected function labels(): array
    {
        return [
            'title'           => __('menus.tours') ?: 'Onboarding tours',
            'title_index'     => __('menus.tours') ?: 'Onboarding tours',
            'add'             => __('levels.add') ?: 'Add tour',
            'edit'            => __('levels.edit') ?: 'Edit',
            'delete'          => __('levels.delete') ?: 'Delete',
            'save'            => __('levels.save') ?: 'Save',
            'cancel'          => __('levels.cancel') ?: 'Cancel',
            'active'          => __('levels.active') ?: 'Active',
            'inactive'        => __('levels.inactive') ?: 'Inactive',
            'no_data'         => __('levels.no_data_found') ?: 'No tours yet.',
            'preview'         => __('tours.preview') ?: 'Preview',
            'analytics'       => __('tours.analytics') ?: 'Analytics',
            'system'          => __('tours.system') ?: 'System',
            'tenant'          => __('tours.tenant') ?: 'Tenant',
            'key'             => __('tours.key') ?: 'Key',
            'module'          => __('tours.module') ?: 'Module',
            'version'         => __('tours.version') ?: 'Version',
            'role_scope'      => __('tours.role_scope') ?: 'Roles',
            'auto_start'      => __('tours.auto_start') ?: 'Auto-start on first login',
            'trigger_route'   => __('tours.trigger_route') ?: 'Trigger on route (optional)',
            'description'     => __('levels.description') ?: 'Description',
            'steps'           => __('tours.steps') ?: 'Steps',
            'add_step'        => __('tours.add_step') ?: 'Add step',
            'remove_step'     => __('tours.remove_step') ?: 'Remove',
            'reorder_hint'    => __('tours.reorder_hint') ?: 'Drag to reorder',
            'target'          => __('tours.target') ?: 'Target',
            'target_type'     => __('tours.target_type') ?: 'Type',
            'target_value'    => __('tours.target_value') ?: 'Value',
            'placement'       => __('tours.placement') ?: 'Placement',
            'padding'         => __('tours.padding') ?: 'Spotlight padding',
            'lang_en'         => __('tours.lang_en') ?: 'English',
            'lang_ar'         => __('tours.lang_ar') ?: 'Arabic',
            'step_title'      => __('tours.step_title') ?: 'Title',
            'step_body'       => __('tours.step_body') ?: 'Body',
            'action'          => __('tours.action') ?: 'Action (JSON, optional)',
            'starts'          => __('tours.starts') ?: 'Starts',
            'completes'       => __('tours.completes') ?: 'Completes',
            'skips'           => __('tours.skips') ?: 'Skips',
            'completion_rate' => __('tours.completion_rate') ?: 'Completion %',
            'dropoff'         => __('tours.dropoff') ?: 'Drop-off step',
            'avg_step'        => __('tours.avg_step') ?: 'Avg time/step',
            'back'            => __('levels.back') ?: 'Back',
        ];
    }
}
