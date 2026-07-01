<?php

namespace App\Repositories\Tour;

use App\Models\Backend\Tour;
use App\Models\User;

interface TourRepositoryInterface
{
    /**
     * Tours applicable to the given user in the current tenant context.
     * Applies role_scope + module + active filters, and dedupes company
     * override rows over system defaults sharing the same key.
     */
    public function forUser(User $user, ?string $locale = null): array;

    /**
     * Return one tour by key (company-scoped, falling back to system).
     */
    public function findByKey(string $key): ?Tour;

    /**
     * Record or update a user's progress on a tour.
     */
    public function saveProgress(User $user, string $key, int $version, string $status, int $currentStep): void;

    /**
     * Log an analytics event.
     */
    public function logEvent(User $user, string $key, string $event, ?int $stepIndex = null, ?int $durationMs = null, array $meta = []): void;
}
