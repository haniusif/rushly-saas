<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $table = 'integration_settings';

    protected $fillable = [
        'platform',
        'is_enabled',
        'app_url',
        'writeback_token',
        'api_base',
        'default_city_id',
        'default_category_id',
        'default_delivery_type_id',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'meta'       => 'array',
    ];

    public static function forPlatform(string $platform): self
    {
        return static::firstOrCreate(['platform' => $platform]);
    }

    public function logoUrl(): string
    {
        // Logos live with the rest of the partner artwork under
        // public/images/partners/. Fall back through extensions so a webp,
        // png, or jpg works without touching this method.
        foreach (['png', 'webp', 'svg', 'jpg'] as $ext) {
            if (file_exists(public_path("images/partners/{$this->platform}.{$ext}"))) {
                return asset("images/partners/{$this->platform}.{$ext}");
            }
        }
        return '';
    }

    public function displayName(): string
    {
        return match ($this->platform) {
            'salla'       => 'Salla',
            'zid'         => 'Zid',
            'shopify'     => 'Shopify',
            'woocommerce' => 'WooCommerce',
            default       => ucfirst($this->platform),
        };
    }

    public function bridgeReady(): bool
    {
        return $this->is_enabled
            && filled($this->app_url)
            && filled($this->writeback_token);
    }
}
