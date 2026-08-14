<?php

namespace App\Oms\Models;

use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\WebhookEvent;
use App\Models\Backend\Merchant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canonical OMS order. Materialised by OrderService::receiveNormalized()
 * from an OrderDTO produced by the per-provider mapper.
 *
 * Distinct from `Parcel` — Parcels are courier-side shipments (1..N
 * per Order). Phase 6 FulfillmentRouter is what decides how an Order
 * becomes 1..N Parcels.
 */
class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'company_id',
        'connection_id',
        'source_provider_code',
        'merchant_id',
        'webhook_event_id',
        'remote_order_id',
        'remote_order_number',
        'provider_status',
        'status',
        'payment_status',
        'fulfillment_status',
        'payment_method',
        'customer_remote_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_name',
        'shipping_phone',
        'shipping_line1',
        'shipping_line2',
        'shipping_city_name',
        'shipping_area_name',
        'shipping_region',
        'shipping_country',
        'shipping_postcode',
        'shipping_city_id',
        'shipping_area_id',
        'subtotal',
        'tax',
        'shipping_fee',
        'discount',
        'total',
        'cod_amount',
        'currency',
        'note',
        'normalized_snapshot',
        'extra',
        'occurred_at',
        'received_at',
    ];

    protected $casts = [
        'subtotal'            => 'decimal:2',
        'tax'                 => 'decimal:2',
        'shipping_fee'        => 'decimal:2',
        'discount'            => 'decimal:2',
        'total'               => 'decimal:2',
        'cod_amount'          => 'decimal:2',
        'normalized_snapshot' => 'array',
        'extra'               => 'array',
        'occurred_at'         => 'datetime',
        'received_at'         => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderByDesc('occurred_at');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CommerceConnection::class, 'connection_id');
    }

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }
}
