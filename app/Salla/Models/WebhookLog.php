<?php

namespace App\Salla\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'salla_webhook_logs';

    public const UPDATED_AT = null;

    public const STATUS_RECEIVED  = 'received';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_HANDLED   = 'handled';
    public const STATUS_UNHANDLED = 'unhandled';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'event',
        'strategy',
        'salla_merchant_id',
        'status',
        'signature_valid',
        'rejection_reason',
        'payload',
        'headers',
        'error',
        'duration_ms',
        'ip',
    ];

    protected $casts = [
        'payload'         => 'array',
        'headers'         => 'array',
        'signature_valid' => 'boolean',
        'created_at'      => 'datetime',
    ];

    private const SENSITIVE_BODY_KEYS = [
        'access_token',
        'refresh_token',
        'rushly_merchant_token',
        'authorization',
    ];

    private const SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'x-salla-signature',
        'x-csrf-token',
    ];

    public static function sanitisePayload($payload)
    {
        if (! is_array($payload)) {
            return $payload;
        }
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_BODY_KEYS, true)) {
                $payload[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $payload[$key] = self::sanitisePayload($value);
            }
        }
        return $payload;
    }

    public static function sanitiseHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            $clean = is_array($value) && count($value) === 1 ? $value[0] : $value;
            $out[$name] = in_array(strtolower((string) $name), self::SENSITIVE_HEADERS, true)
                ? '[REDACTED]'
                : $clean;
        }
        return $out;
    }
}
