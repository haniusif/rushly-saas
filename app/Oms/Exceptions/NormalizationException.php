<?php

namespace App\Oms\Exceptions;

use Exception;

/**
 * Thrown by the normalization pipeline when a provider payload fails
 * validation or can't be mapped to a canonical OrderDTO. Carries the
 * per-field errors (same shape Laravel's Validator produces) so the
 * webhook_events viewer can render them clearly.
 *
 * Non-retryable by convention — a bad payload will still be bad on retry.
 * Callers (SallaWebhookHandler) should catch this specifically and stamp
 * `normalization_error` on the row rather than throwing (which would
 * trigger Laravel's queue retry pointlessly).
 */
class NormalizationException extends Exception
{
    public function __construct(
        string $message,
        public readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
