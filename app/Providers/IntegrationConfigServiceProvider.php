<?php

namespace App\Providers;

use App\Models\Backend\IntegrationSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Pulls integration_settings rows from the DB at boot and overlays them onto
 * config('services.<platform>.*'). This means existing code that reads
 * config('services.zid.writeback_token') keeps working untouched after an
 * admin edits the value through the Integrations page.
 *
 * The DB row wins over .env on a per-key basis: if app_url is set in the DB,
 * we use it; otherwise we leave whatever .env produced.
 */
class IntegrationConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Never crash boot if the table does not exist yet (fresh clone before
        // migrations have run, or any environment that lacks the table).
        try {
            if (! Schema::hasTable('integration_settings')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        foreach (IntegrationSetting::all() as $setting) {
            $prefix = "services.{$setting->platform}";

            $this->mergeKey("{$prefix}.is_enabled", $setting->is_enabled);
            $this->mergeKey("{$prefix}.app_url", $setting->app_url);
            $this->mergeKey("{$prefix}.writeback_token", $setting->writeback_token);
            $this->mergeKey("{$prefix}.api_base", $setting->api_base);
            $this->mergeKey("{$prefix}.default_city_id", $setting->default_city_id);
            $this->mergeKey("{$prefix}.default_category_id", $setting->default_category_id);
            $this->mergeKey("{$prefix}.default_delivery_type_id", $setting->default_delivery_type_id);
        }
    }

    private function mergeKey(string $key, mixed $value): void
    {
        // Empty string from a nullable form field should not blow away a
        // working .env value, so only override when the DB has a real value.
        if ($value === null || $value === '') {
            return;
        }
        config([$key => $value]);
    }
}
