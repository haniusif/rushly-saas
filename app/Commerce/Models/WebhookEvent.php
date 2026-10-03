<?php

namespace App\Commerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per inbound webhook delivery. The persistence layer in front of
 * the WebhookIngestService → IngestWebhookJob → SallaWebhookHandler chain.
 *
 * Payload is JSON-cast on read. The raw column stores the body verbatim so
 * the same event can be re-processed after normalization changes without
 * needing the storefront to redeliver — `replay` on the admin viewer just
 * dispatches a new IngestWebhookJob against the same row.
 */
class WebhookEvent extends Model
{
    protected $table = 'webhook_events';

    protected $fillable = [
        'company_id',
        'connection_id',
        'provider_code',
        'event_type',
        'idempotency_key',
        'signature',
        'payload',
        'normalized_payload',
        'normalization_error',
        'received_at',
        'processed_at',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload'            => 'array',
        'normalized_payload' => 'array',
        'received_at'        => 'datetime',
        'processed_at'       => 'datetime',
        'attempts'           => 'integer',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CommerceConnection::class, 'connection_id');
    }

    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }

    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereNull('processed_at')->where('attempts', '>', 0);
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
