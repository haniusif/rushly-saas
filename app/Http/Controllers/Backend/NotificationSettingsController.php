<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSetting\StoreRequest;
use Illuminate\Http\Request;
use App\Repositories\NotificationSettings\NotificationSettingsInterface;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    protected $repo;
    public function __construct(NotificationSettingsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $settings = $this->repo->all();

        return Inertia::render('Admin/NotificationSettings/Index', [
            'settings' => [
                // Only the two fields the form actually consumes — no secrets
                // leak into the Inertia payload beyond what the operator
                // already needs to see to edit.
                'fcm_secret_key' => (string) ($settings->fcm_secret_key ?? ''),
                'fcm_topic'      => (string) ($settings->fcm_topic ?? ''),
            ],
            'permissions' => [
                'update' => hasPermission('notification_settings_update'),
            ],
            'urls' => [
                'submit' => route('notification-settings.update'),
            ],
            't' => [
                'title'           => __('menus.notification_settings') ?: 'Notification settings',
                'fcm_secret_key'  => __('levels.fcm_secret_key') ?: 'FCM secret key',
                'fcm_topic'       => __('levels.fcm_topic') ?: 'FCM topic',
                'fcm_secret_hint' => 'Server key from Firebase → Project settings → Cloud Messaging.',
                'fcm_topic_hint'  => 'Topic name each subscribed device listens on.',
                'save'            => __('levels.save_change') ?: 'Save changes',
            ],
        ]);
    }

    public function update(StoreRequest $request)
    {
        // Original impl returned view() from a PUT which broke the
        // post/redirect/get pattern (double-submit on refresh, no session
        // flash carry-over). Redirect back so the Inertia flash banner
        // owns the success message.
        $this->repo->update($request);
        return redirect()->route('notification-settings.index')
            ->with('success', __('settings.save_change'));
    }
}
