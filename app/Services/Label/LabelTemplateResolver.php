<?php

namespace App\Services\Label;

use App\Enums\LabelTemplate;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use Illuminate\Support\Facades\Cache;

/**
 * Picks the active label template for a given parcel using:
 *   1) merchant-level override (`merchants.label_template`)
 *   2) tenant default (`general_settings.default_label_template`)
 *   3) hard-coded fallback (Generic)
 */
class LabelTemplateResolver
{
    public function forParcel(Parcel $parcel): LabelTemplate
    {
        $override = optional($parcel->merchant)->label_template;
        if ($override && $tpl = LabelTemplate::tryFrom((string) $override)) {
            return $tpl;
        }
        return $this->tenantDefault();
    }

    public function forMerchant(?Merchant $merchant): LabelTemplate
    {
        if ($merchant && $merchant->label_template) {
            if ($tpl = LabelTemplate::tryFrom((string) $merchant->label_template)) {
                return $tpl;
            }
        }
        return $this->tenantDefault();
    }

    public function tenantDefault(): LabelTemplate
    {
        // Cache::store() bypasses stancl/tenancy's CacheManager tag-wrapping, which
        // fails on the file driver. Tenant isolation is preserved by company_id in the key.
        $key = (string) Cache::store()->remember(
            'label_template.default.' . (settings('company_id') ?? 0),
            300,
            fn () => optional(settings())->default_label_template ?: LabelTemplate::default()->value,
        );

        return LabelTemplate::tryFrom($key) ?? LabelTemplate::default();
    }

    public function setTenantDefault(LabelTemplate $tpl): void
    {
        $setting = settings();
        if ($setting) {
            $setting->forceFill(['default_label_template' => $tpl->value])->save();
        }
        Cache::store()->forget('label_template.default.' . (settings('company_id') ?? 0));
    }
}
