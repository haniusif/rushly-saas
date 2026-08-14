<?php

namespace App\Qoyod\Observers;

use App\Models\Backend\CourierStatement;
use App\Qoyod\Jobs\PushCourierBillJob;
use App\Qoyod\Models\Settings;

class CourierStatementObserver
{
    public function created(CourierStatement $row): void
    {
        if (! $this->enabledForCompany($row->company_id)) return;

        // Only EXPENSE statements (type = 2) become AP bills in Qoyod.
        if ((int) $row->type !== 2) return;

        PushCourierBillJob::dispatch($row->id);
    }

    private function enabledForCompany(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '';
    }
}
