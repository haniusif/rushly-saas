<?php

namespace App\Models\Backend\Wms\Concerns;

/**
 * Shared tenant-scoping for every WMS model. Mirrors the existing
 * `scopeCompanywise()` pattern on Parcel/Merchant/etc. so calls like
 * `WmsProduct::companywise()->where(...)` work identically.
 */
trait Companywise
{
    public function scopeCompanywise($query)
    {
        return $query->where($this->getTable() . '.company_id', settings()->id);
    }
}
