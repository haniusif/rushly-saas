<?php

namespace App\Odoo\Observers;

use App\Models\Backend\CourierStatement;
use App\Odoo\Jobs\PushCourierBillJob;
use App\Odoo\Models\Settings;

class CourierStatementObserver
{
    public function created(CourierStatement $row): void
    {
        if (! $this->ready($row->company_id)) return;
        if ((int) $row->type !== 2) return;
        PushCourierBillJob::dispatch($row->id);
    }

    private function ready(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '' && $s->host_url;
    }
}
