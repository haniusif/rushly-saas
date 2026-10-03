<?php

namespace App\Commerce\Logging;

use App\Commerce\Models\CommerceApiLog;
use Illuminate\Support\Facades\Log;

/**
 * Writes one row to commerce_api_logs per outbound HTTP call from a
 * CommerceProvider. Used by AbstractCommerceProvider so every provider
 * gets logging for free.
 *
 * Failure to write a log row must never throw — diagnostic infrastructure
 * shouldn't take down the actual integration. Mirrors the Shipping
 * module's ApiLogger shape so dashboards / prune jobs can be uniform.
 */
class ApiLogger
{
    public function record(array $entry): void
    {
        if (! (bool) config('commerce.logging.enabled', true)) {
            return;
        }

        try {
            CommerceApiLog::create([
                'company_id'      => $entry['company_id']      ?? null,
                'connection_id'   => $entry['connection_id']   ?? null,
                'provider_code'   => $entry['provider_code']   ?? '',
                'endpoint'        => mb_substr((string) ($entry['endpoint'] ?? ''), 0, 255),
                'method'          => mb_strtoupper((string) ($entry['method'] ?? 'GET')),
                'request_headers' => $this->maskHeaders($entry['request_headers'] ?? []),
                'request_body'    => $this->stringify($entry['request_body'] ?? null),
                'response_status' => $entry['response_status'] ?? null,
                'response_body'   => $this->stringify($entry['response_body'] ?? null),
                'duration_ms'     => $entry['duration_ms']     ?? null,
                'error'           => isset($entry['error']) ? (string) $entry['error'] : null,
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('commerce.api_log_write_failed', [
                'error'    => $e->getMessage(),
                'endpoint' => $entry['endpoint'] ?? null,
            ]);
        }
    }

    private function maskHeaders(array $headers): array
    {
        $sensitive = array_map('strtolower', (array) config('commerce.logging.sensitive_headers', []));
        foreach ($headers as $k => $v) {
            if (in_array(strtolower((string) $k), $sensitive, true)) {
                $headers[$k] = '***masked***';
            }
        }
        return $headers;
    }

    private function stringify($value): ?string
    {
        if ($value === null) return null;
        if (is_string($value)) return mb_substr($value, 0, 65_000);
        return mb_substr(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 0, 65_000);
    }
}
