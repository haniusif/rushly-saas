<?php
namespace App\Repositories\SocialLoginSettings;

use App\Enums\Status;
use App\Models\Backend\Setting;
use App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface;


class SocialLoginSettingsRepository implements SocialLoginSettingsInterface
{
    public function update($request, $social)
    {
        try {
            if ($social === 'google') {
                $onlyInput = ['google_client_id', 'google_client_secret', 'google_status'];
                $statusKey = 'google_status';
            } elseif ($social === 'facebook') {
                $onlyInput = ['facebook_client_id', 'facebook_client_secret', 'facebook_status'];
                $statusKey = 'facebook_status';
            } else {
                return false;
            }

            // Normalize the status to the Status enum. The legacy Bootstrap
            // Blade sent unchecked HTML checkboxes as the literal string 'on'
            // when checked; the new Inertia form sends 1/0 as ints. Accept
            // any truthy value (1, '1', 'on', true) so we don't silently
            // drop the toggle depending on which client is submitting.
            $rawStatus = $request->input($statusKey);
            $isActive = $rawStatus === 'on'
                || $rawStatus === true
                || $rawStatus === 1
                || $rawStatus === '1';
            $request[$statusKey] = $isActive ? Status::ACTIVE : Status::INACTIVE;

            // Setting has $fillable = ['key','value'] so company_id can't be
            // mass-assigned; use firstOrNew + direct property assignment so
            // fresh rows get scoped to this tenant. Old ->first()->save()
            // path silently no-op'd when the row didn't exist.
            $companyId = settings()->id;
            foreach ($request->only($onlyInput) as $key => $value) {
                $setting = Setting::companywise()->where('key', $key)->first()
                    ?? new Setting(['key' => $key]);
                $setting->company_id = $companyId;
                $setting->key        = $key;
                $setting->value      = (string) ($value ?? '');
                $setting->save();
            }
            return true;
        } catch (\Throwable $th) {
            \Log::error('SocialLoginSettingsRepository::update failed', [
                'social' => $social,
                'error'  => $th->getMessage(),
            ]);
            return false;
        }
    }
}
