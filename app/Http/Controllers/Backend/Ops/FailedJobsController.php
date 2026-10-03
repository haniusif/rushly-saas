<?php

namespace App\Http\Controllers\Backend\Ops;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * /admin/ops/failed-jobs — thin read/retry/delete UI over Laravel's
 * built-in `failed_jobs` table.
 *
 * Filters to the queues the Commerce / Shipping / Fulfillment modules
 * dispatch to — the goal is ops visibility into integration failures,
 * not a general-purpose failed_jobs browser (which would risk
 * exposing sensitive job payloads from unrelated modules).
 *
 * Retry + forget are Laravel's own artisan commands invoked
 * programmatically — they know how to serialise / dispatch the job
 * back, so we don't touch the payload ourselves.
 */
class FailedJobsController extends Controller
{
    /** Queues this admin surfaces — everything else is filtered out. */
    private const RELEVANT_QUEUES = ['commerce', 'shipping', 'fulfillment', 'default'];

    public function __construct()
    {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index(Request $request)
    {
        $q = DB::table('failed_jobs')
            ->whereIn('queue', self::RELEVANT_QUEUES)
            ->orderByDesc('failed_at')
            ->limit(200);

        if ($queue = $request->query('queue')) {
            $q->where('queue', $queue);
        }
        if ($cls = $request->query('class_like')) {
            // Match on decoded job class name (stored inside `payload` JSON)
            $q->where('payload', 'like', '%' . $cls . '%');
        }

        $rows = $q->get()->map(function ($r) {
            $payload = json_decode($r->payload, true) ?: [];
            $jobClass = $payload['displayName'] ?? ($payload['data']['commandName'] ?? '—');
            return [
                'id'        => $r->id,
                'uuid'      => $r->uuid,
                'queue'     => $r->queue,
                'job_class' => $jobClass,
                'connection'=> $r->connection,
                'exception' => mb_substr((string) $r->exception, 0, 400),
                'failed_at' => $r->failed_at,
            ];
        })->values();

        return Inertia::render('Admin/Ops/FailedJobs/Index', [
            'rows'    => $rows,
            'filters' => [
                'queue'      => $request->query('queue', ''),
                'class_like' => $request->query('class_like', ''),
            ],
            'queues'  => self::RELEVANT_QUEUES,
            'urls'    => [
                'index' => route('ops.failed-jobs.index'),
            ],
            't' => [
                'page_title' => 'Failed jobs',
                'subtitle'   => 'Failed background jobs from the Commerce / Shipping / Fulfillment modules. Retry re-queues the same payload; delete drops the row without re-attempting.',
                'no_rows'    => 'No failed jobs — nice.',
            ],
        ]);
    }

    public function retry(int $id)
    {
        $row = DB::table('failed_jobs')->where('id', $id)->first();
        abort_if(! $row, 404);
        Artisan::call('queue:retry', ['id' => [$row->uuid]]);
        Toastr::success('Job re-queued.', 'Success');
        return back();
    }

    public function forget(int $id)
    {
        $row = DB::table('failed_jobs')->where('id', $id)->first();
        abort_if(! $row, 404);
        Artisan::call('queue:forget', ['id' => $row->uuid]);
        Toastr::success('Failed job removed.', 'Success');
        return back();
    }
}
