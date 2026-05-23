<?php

namespace App\Repositories;

use App\Models\Backend\Ndr;
use Illuminate\Http\Request;

interface NdrRepositoryInterface
{
    /** List NDRs for the current tenant, optionally filtered. */
    public function all(Request $request = null);

    /** Fetch a single NDR by ID (companywise-scoped). */
    public function find(int $id): ?Ndr;

    /** Create a new NDR row + side effects (event log, notifications hook). */
    public function create(array $data): Ndr;

    /** Apply an action (reschedule / return / transfer_hub / escalate). */
    public function applyAction(Ndr $ndr, string $action, Request $request): bool;

    /** Mark an NDR as resolved by a user. */
    public function resolve(Ndr $ndr, int $resolvedByUserId): bool;

    /** Latest open NDR for a given parcel (1-per-day rule). */
    public function todayOpenForParcel(int $parcelId): ?Ndr;

    /** Stats for dashboard cards (today / pending / resolved / return_rate). */
    public function stats(): array;
}
