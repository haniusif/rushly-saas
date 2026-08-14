<?php

namespace App\Repositories\Zatca;

use App\Models\Backend\Zatca\ZatcaSetting;

interface ZatcaSettingRepositoryInterface
{
    public function forCurrentCompany(): ZatcaSetting;

    public function update(int $companyId, array $data): ZatcaSetting;
}
