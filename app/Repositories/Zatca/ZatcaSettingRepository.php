<?php

namespace App\Repositories\Zatca;

use App\Models\Backend\Zatca\ZatcaSetting;

class ZatcaSettingRepository implements ZatcaSettingRepositoryInterface
{
    public function forCurrentCompany(): ZatcaSetting
    {
        // settings() returns the tenant's GeneralSettings model — it
        // doesn't accept a key argument. The old `settings('company_id')`
        // call was returning the whole object, then (int)$object silently
        // produced a warning + fell through to 0, which then
        // created a shared 0-scoped ZatcaSetting row for every tenant.
        $companyId = (int) settings()->id;
        return ZatcaSetting::query()->firstOrCreate(
            ['company_id' => $companyId],
            [
                'seller_name_en' => '',
                'seller_name_ar' => '',
                'vat_number'     => '',
                'enabled'        => false,
            ],
        );
    }

    public function update(int $companyId, array $data): ZatcaSetting
    {
        $setting = ZatcaSetting::query()->firstOrCreate(['company_id' => $companyId]);
        $setting->fill($data)->save();
        return $setting->refresh();
    }
}
