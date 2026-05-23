<?php

namespace App\Repositories;

use App\Models\Backend\AbnormalShipment;
use Illuminate\Http\Request;

interface AbnormalShipmentRepositoryInterface
{
    /** List abnormal shipments (companywise), optionally filtered. */
    public function all(Request $request = null);

    /** Single abnormal record by ID, scoped to tenant. */
    public function find(int $id): ?AbnormalShipment;

    /** Detect + upsert. Runs from the hourly console command. */
    public function detect(int $thresholdDays = 3): array;

    /** Assign a user to investigate. */
    public function assign(AbnormalShipment $a, int $userId): bool;

    /** Resolve with note + resolver user id. */
    public function resolve(AbnormalShipment $a, int $userId, ?string $note = null): bool;

    /** Auto-resolve an open record when a new parcel event arrives. */
    public function autoResolveByParcel(int $parcelId): int;

    /** Read the per-tenant detection threshold from Config (defaults to 3). */
    public function getThresholdDays(?int $companyId = null): int;
}
