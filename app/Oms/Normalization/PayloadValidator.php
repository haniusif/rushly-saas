<?php

namespace App\Oms\Normalization;

use App\Oms\Exceptions\NormalizationException;
use Illuminate\Support\Facades\Validator;

/**
 * Thin wrapper around Laravel's Validator that throws a
 * NormalizationException (with field-level errors) instead of a
 * ValidationException. Two reasons for the wrapper:
 *
 *   1. NormalizationException is non-retryable by convention; the
 *      generic ValidationException is HTTP-framing baggage that doesn't
 *      belong in a background job.
 *   2. Field errors get flattened into a `flat` array of "<field>: <msg>"
 *      strings — easier to render in the webhook_events viewer without
 *      needing a nested structure.
 */
class PayloadValidator
{
    /**
     * @throws NormalizationException
     */
    public function validate(array $payload, array $rules, string $providerCode): void
    {
        $v = Validator::make($payload, $rules);
        if ($v->fails()) {
            $errors = $v->errors()->toArray();
            $flat   = [];
            foreach ($errors as $field => $messages) {
                foreach ((array) $messages as $m) {
                    $flat[] = "{$field}: {$m}";
                }
            }
            throw new NormalizationException(
                "Payload validation failed for provider '{$providerCode}': " . implode('; ', $flat),
                ['errors' => $errors, 'flat' => $flat, 'provider' => $providerCode],
            );
        }
    }
}
