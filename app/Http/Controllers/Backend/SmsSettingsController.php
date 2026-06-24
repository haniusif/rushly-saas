<?php

namespace App\Http\Controllers\Backend;

use App\Enums\SmsSetup;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsSetting\StoreRequest;
use App\Repositories\SmsSetting\SmsSettingInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class SmsSettingsController extends Controller
{
    protected $repo;

    public function __construct(SmsSettingInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return Inertia::render('Admin/SmsSettings/Index', [
            'providers' => [
                'reve' => [
                    'method' => SmsSetup::REVE,
                    'name'   => 'REVE SMS',
                    'fields' => [
                        'reve_api_key'       => smsSettings('reve_api_key'),
                        'reve_secret_key'    => smsSettings('reve_secret_key'),
                        'reve_api_url'       => smsSettings('reve_api_url'),
                        'reve_username'      => smsSettings('reve_username'),
                        'reve_user_password' => smsSettings('reve_user_password'),
                    ],
                    'active' => smsSettings('reve_status') == Status::ACTIVE,
                ],
                'twilio' => [
                    'method' => SmsSetup::TWILIO,
                    'name'   => 'TWILIO SMS',
                    'fields' => [
                        'twilio_sid'   => smsSettings('twilio_sid'),
                        'twilio_token' => smsSettings('twilio_token'),
                        'twilio_from'  => smsSettings('twilio_from'),
                    ],
                    'active' => smsSettings('twilio_status') == Status::ACTIVE,
                ],
                'nexmo' => [
                    'method' => SmsSetup::NEXMO,
                    'name'   => 'NEXMO SMS',
                    'fields' => [
                        'nexmo_key'        => smsSettings('nexmo_key'),
                        'nexmo_secret_key' => smsSettings('nexmo_secret_key'),
                    ],
                    'active' => smsSettings('nexmo_status') == Status::ACTIVE,
                ],
                'msegat' => [
                    'method' => SmsSetup::MSEGAT,
                    'name'   => 'MSEGAT SMS',
                    'fields' => [
                        'msegat_user_name' => smsSettings('msegat_user_name'),
                        'msegat_api_key'   => smsSettings('msegat_api_key'),
                        'msegat_sender'    => smsSettings('msegat_sender'),
                    ],
                    'active' => smsSettings('msegat_status') == Status::ACTIVE,
                ],
                'taqnyat' => [
                    'method' => SmsSetup::TAQNYAT,
                    'name'   => 'Taqnyat SMS',
                    'fields' => [
                        'taqnyat_token'  => smsSettings('taqnyat_token'),
                        'taqnyat_sender' => smsSettings('taqnyat_sender'),
                    ],
                    'active' => smsSettings('taqnyat_status') == Status::ACTIVE,
                ],
            ],
            'permissions' => [
                'update' => hasPermission('sms_settings_update') || hasPermission('sms_settings_create'),
            ],
            'urls' => [
                'submit_reve'   => route('sms-settings.update', SmsSetup::REVE),
                'submit_twilio' => route('sms-settings.update', SmsSetup::TWILIO),
                'submit_nexmo'  => route('sms-settings.update', SmsSetup::NEXMO),
                'submit_msegat' => route('sms-settings.update', SmsSetup::MSEGAT),
                'submit_taqnyat' => route('sms-settings.update', SmsSetup::TAQNYAT),
            ],
            't' => [
                'title'        => __('smsSettings.title') ?: 'SMS settings',
                'list'         => __('levels.list') ?: 'List',
                'status'       => __('levels.status') ?: 'Status',
                'save'         => __('levels.save_change') ?: 'Save changes',
                'api_key'      => __('smsSettings.api_key') ?: 'API key',
                'secret_key'   => __('smsSettings.secret_key') ?: 'Secret key',
                'api_url'      => __('smsSettings.api_url') ?: 'API URL',
                'username'     => __('smsSettings.username') ?: 'Username',
                'user_password'=> __('smsSettings.user_password') ?: 'User password',
                'twilio_sid'   => __('levels.twilio_sid') ?: 'Twilio SID',
                'twilio_token' => __('levels.twilio_token') ?: 'Twilio token',
                'twilio_from'  => __('levels.twilio_from') ?: 'Twilio from',
                'nexmo_key'    => __('levels.nexmo_key') ?: 'Nexmo key',
                'nexmo_secret_key' => __('levels.nexmo_secret_key') ?: 'Nexmo secret key',
                'msegat_user_name' => 'Username',
                'msegat_api_key'   => 'API key',
                'msegat_sender'    => 'Sender ID',
                'msegat_help'      => 'Saudi Arabia SMS gateway (msegat.com). Sender ID must be pre-approved on your MSEGAT account.',
                'ph_msegat_user_name' => 'Your MSEGAT username',
                'ph_msegat_api_key'   => 'Your MSEGAT API key',
                'ph_msegat_sender'    => 'e.g. RUSHLY',
                'taqnyat_token'    => 'Bearer token',
                'taqnyat_sender'   => 'Sender ID',
                'taqnyat_help'     => 'Saudi SMS gateway (taqnyat.sa). Sender ID is case-sensitive and must be pre-approved on your Taqnyat account.',
                'ph_taqnyat_token' => 'Your Taqnyat API bearer token',
                'ph_taqnyat_sender'=> 'e.g. RUSHLY',
                'ph_api_key'       => __('placeholder.Enter_api_key') ?: 'Enter API key',
                'ph_secret_key'    => __('placeholder.Enter_secret_key') ?: 'Enter secret key',
                'ph_api_url'       => __('placeholder.Enter_api_url') ?: 'Enter API URL',
                'ph_username'      => __('placeholder.Enter_username') ?: 'Enter username',
                'ph_user_password' => __('placeholder.Enter_user_password') ?: 'Enter user password',
            ],
        ]);
    }

    public function update(StoreRequest $request, $smsMethod)
    {
        if ($this->repo->update($smsMethod, $request)) {
            Toastr::success(__('smsSettings.update_msg'), __('message.success'));
            return redirect()->route('sms-settings.index');
        }
        Toastr::error(__('smsSettings.error_msg'), __('message.error'));
        return redirect()->back();
    }
}
