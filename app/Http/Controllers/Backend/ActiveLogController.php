<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActiveLogController extends Controller
{
    public function index()
    {
        $paginator = Activity::with('causer')
            ->whereHas('causer', function ($query) {
                $query->where('company_id', settings()->id);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        $rows = collect($paginator->items())->map(fn ($log) => [
            'id'           => $log->id,
            'log_name'     => (string) $log->log_name,
            'event'        => (string) $log->event,
            'event_label'  => trans('levels.' . $log->event) ?: $log->event,
            'subject_type' => class_basename((string) $log->subject_type),
            'description'  => trans('levels.' . $log->description) ?: $log->description,
            'causer'       => optional($log->causer)->name,
            'created_at'   => optional($log->created_at)->format('Y-m-d H:i'),
            'urls'         => [
                'view' => route('log-activity-view', $log->id),
            ],
        ])->values();

        return Inertia::render('Admin/Log/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            't' => [
                'title'        => __('menus.logs') ?: 'Logs',
                'list'         => __('levels.list') ?: 'List',
                'log_name'     => __('logs.log_name') ?: 'Log',
                'event'        => __('logs.event') ?: 'Event',
                'subject_type' => __('logs.subject_type') ?: 'Subject',
                'description'  => __('logs.description') ?: 'Description',
                'causer'       => __('logs.user') ?: 'User',
                'when'         => __('levels.created_at') ?: 'When',
                'no_rows'      => 'No activity yet.',
                'prev'         => 'Prev',
                'next'         => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function view($id)
    {
        $logDetails = Activity::find($id);
        return view('backend.log.view', compact('logDetails'));
    }
}
