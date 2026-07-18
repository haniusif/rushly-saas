<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\SmsSendSetting;
use App\Repositories\SmsSendSetting\SmsSendSettingInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsSendSettingsController extends Controller
{
    protected $repo;

    public function __construct(SmsSendSettingInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(function ($s) {
            $eventKey = 'SmsSendStatus.' . $s->sms_send_status;
            $label    = trans($eventKey);
            return [
                'id'          => $s->id,
                'event_key'   => (int) $s->sms_send_status,
                'event_label' => $label === $eventKey ? $this->fallbackEventLabel((int) $s->sms_send_status) : $label,
                'status'      => (int) $s->status,
                'is_active'   => (int) $s->status === Status::ACTIVE,
            ];
        })->values();

        return Inertia::render('Admin/SmsSendSettings/Index', [
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
            'permissions' => [
                'toggle' => hasPermission('sms_send_settings_status_change'),
            ],
            'urls' => [
                'index'  => route('sms-send-settings.index'),
                'toggle' => route('sms-send-settings.status'),
            ],
            't' => [
                'title'   => __('smsSendSettings.title') ?: 'SMS send settings',
                'list'    => __('levels.list') ?: 'List',
                'id'      => __('levels.id') ?: 'ID',
                'event'   => __('levels.name') ?: 'Event',
                'status'  => __('levels.status') ?: 'Status',
                'active'  => 'Enabled',
                'inactive'=> 'Disabled',
                'no_rows' => 'No SMS events configured.',
                'hint'    => 'Toggle each event to enable or disable the outbound SMS.',
                'prev'    => 'Prev',
                'next'    => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function status(Request $request)
    {
        $row = SmsSendSetting::companywise()->where('id', $request->id)->first();
        if (!$row) {
            return back()->with('error', 'Row not found.');
        }

        $row->status = (int) $row->status === Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;
        $row->save();

        return back()->with('success', 'Status updated.');
    }

    private function fallbackEventLabel(int $code): string
    {
        return match ($code) {
            1 => 'Parcel created',
            2 => 'Parcel delivered / cancelled — customer',
            3 => 'Parcel delivered / cancelled — merchant',
            default => 'Event #' . $code,
        };
    }
}
