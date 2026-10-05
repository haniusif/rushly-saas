<?php

namespace App\Services\InternalApi;

use App\Enums\ParcelStatus;
use App\Models\Backend\City;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Support\ParcelStatusHelper;
use Illuminate\Support\Str;

/**
 * Business logic for the internal Merchant shipment API consumed by rushly-api.
 *
 * rushly-saas is the SSOT: this reuses the domain models and the real
 * Parcel cancellation guard. Because the internal API has no authenticated
 * user (so the ScopedToCompany global scope + settings() would fail open to
 * company 1), EVERY query is explicitly constrained to the validated
 * company_id + merchant_id, and the tenant global scope is removed so the
 * explicit constraints are authoritative.
 *
 * Methods return ['status' => int, 'body' => array]; the controller serialises.
 */
class MerchantShipmentService
{
    public function create(array $p): array
    {
        $companyId  = (int) $p['company_id'];
        $merchantId = (int) $p['merchant_id'];

        $merchant = Merchant::withoutGlobalScope('tenant')->find($merchantId);
        if (! $merchant) {
            return $this->err(422, 'MERCHANT_NOT_FOUND', 'Unknown merchant.');
        }
        if ((int) $merchant->company_id !== $companyId) {
            return $this->err(422, 'MERCHANT_MISMATCH', 'Merchant does not belong to the given company.');
        }

        // shop_id — the merchant's default ("active") shop.
        $shop = $merchant->activeShop;
        if (! $shop) {
            return $this->err(422, 'NO_DEFAULT_SHOP', 'Merchant has no default shop configured.');
        }

        // city_id — resolved by name (matches the bulk-import lookup); never invented.
        $cityName = (string) ($p['address']['city'] ?? '');
        $cityId = City::where('is_active', 1)
            ->where(fn ($q) => $q->where('name', $cityName)->orWhere('en_name', $cityName))
            ->value('id');
        if (! $cityId) {
            return $this->err(422, 'CITY_NOT_FOUND', 'Destination city could not be matched.', ['city' => $cityName]);
        }

        $method = ($p['payment']['method'] ?? 'prepaid') === 'cod' ? 'cod' : 'prepaid';
        $cod    = $method === 'cod' ? (float) ($p['payment']['cod_amount'] ?? 0) : 0.0;

        $addr = $p['address'];
        $customerAddress = Str::limit(trim(implode(', ', array_filter([
            $addr['address_line'] ?? null,
            $addr['district'] ?? null,
            $cityName,
        ]))), 188, '');

        $parcel = new Parcel();
        $parcel->company_id       = $companyId;
        $parcel->merchant_id      = $merchantId;
        $parcel->merchant_shop_id = $shop->id;
        $parcel->city_id          = $cityId;
        $parcel->category_id      = (int) config('internal_api.defaults.category_id');
        $parcel->delivery_type_id = (int) config('internal_api.defaults.delivery_type_id');
        $parcel->customer_name    = (string) $p['recipient']['name'];
        $parcel->customer_phone   = (string) $p['recipient']['phone'];
        $parcel->customer_address = $customerAddress;
        $parcel->cash_collection  = $cod;
        $parcel->reference_number = $p['reference'] ?? null;
        $parcel->weight           = isset($p['shipment']['weight']) ? (float) $p['shipment']['weight'] : null;
        $parcel->number_of_boxes  = (int) ($p['shipment']['pieces'] ?? 1);
        $parcel->note             = $this->composeNote($p);
        $parcel->status           = ParcelStatus::PENDING;

        if (isset($addr['latitude']))  { $parcel->customer_lat  = (string) $addr['latitude']; }
        if (isset($addr['longitude'])) { $parcel->customer_long = (string) $addr['longitude']; }

        $parcel->save();

        // Tracking id is derived from the parcel id, with the OWNING company's
        // prefix (not settings(), which would fail open to company 1).
        $prefix = optional(GeneralSettings::find($companyId))->par_track_prefix ?: 'RL-';
        $parcel->tracking_id = Str::upper($prefix).(10000000000 + (int) $parcel->id);
        $parcel->save();

        return ['status' => 201, 'body' => ['shipment' => $this->present($parcel->fresh())]];
    }

    public function list(array $q): array
    {
        $query = $this->scopedParcels((int) $q['company_id'], (int) $q['merchant_id']);

        if (! empty($q['reference'])) {
            $query->where('reference_number', $q['reference']);
        }
        if (! empty($q['tracking_number'])) {
            $query->where('tracking_id', $q['tracking_number']);
        }
        if (! empty($q['status_in'])) {
            $query->whereIn('status', array_map('intval', (array) $q['status_in']));
        }
        if (! empty($q['created_from'])) { $query->whereDate('created_at', '>=', $q['created_from']); }
        if (! empty($q['created_to']))   { $query->whereDate('created_at', '<=', $q['created_to']); }
        if (! empty($q['updated_from'])) { $query->whereDate('updated_at', '>=', $q['updated_from']); }
        if (! empty($q['updated_to']))   { $query->whereDate('updated_at', '<=', $q['updated_to']); }

        $perPage = min(100, max(1, (int) ($q['per_page'] ?? 20)));
        $page    = max(1, (int) ($q['page'] ?? 1));

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return ['status' => 200, 'body' => [
            'data'       => array_map(fn ($p) => $this->present($p), $paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]];
    }

    public function get(int $companyId, int $merchantId, string $tracking): array
    {
        $parcel = $this->findScoped($companyId, $merchantId, $tracking);
        if (! $parcel) {
            return $this->err(404, 'NOT_FOUND', 'Shipment not found.');
        }

        return ['status' => 200, 'body' => ['shipment' => $this->present($parcel)]];
    }

    public function cancel(int $companyId, int $merchantId, string $tracking, ?string $reason, ?string $note): array
    {
        $parcel = $this->findScoped($companyId, $merchantId, $tracking);
        if (! $parcel) {
            return $this->err(404, 'NOT_FOUND', 'Shipment not found.');
        }

        // Already cancelled — idempotent success.
        if ((int) $parcel->status === ParcelStatus::CANCELLED) {
            return ['status' => 200, 'body' => ['shipment' => $this->present($parcel)]];
        }

        // Reuse the real domain guard: only PENDING is cancellable.
        if (! $parcel->isCancellable()) {
            return $this->err(409, 'NOT_CANCELLABLE', 'Shipment cannot be cancelled in its current status.', [
                'current_status' => (int) $parcel->status,
            ]);
        }

        $parcel->cancelShipment($reason ?: $note);

        return ['status' => 200, 'body' => ['shipment' => $this->present($parcel->fresh())]];
    }

    public function tracking(int $companyId, int $merchantId, string $tracking): array
    {
        $parcel = $this->findScoped($companyId, $merchantId, $tracking);
        if (! $parcel) {
            return $this->err(404, 'NOT_FOUND', 'Shipment not found.');
        }

        $last = $parcel->lastParcelEvent;
        $lastStatus = $last ? (int) $last->parcel_status : (int) $parcel->status;
        $lastTime   = $last ? optional($last->created_at) : optional($parcel->created_at);

        return ['status' => 200, 'body' => ['tracking' => [
            'tracking_number' => $parcel->tracking_id,
            'status'          => (int) $parcel->status,
            'last_update'     => [
                'status'      => $lastStatus,
                'description' => ParcelStatusHelper::label($lastStatus),
                'city'        => optional($parcel->city)->name,
                'timestamp'   => $lastTime?->toIso8601String(),
            ],
            // No reliable ETA source today — never fabricated.
            'estimated_delivery' => null,
        ]]];
    }

    public function history(int $companyId, int $merchantId, string $tracking): array
    {
        $parcel = $this->findScoped($companyId, $merchantId, $tracking);
        if (! $parcel) {
            return $this->err(404, 'NOT_FOUND', 'Shipment not found.');
        }

        $events = ParcelEvent::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('parcel_id', $parcel->id)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $history = [];

        // Ensure the timeline starts at creation even if no explicit event row.
        $hasCreated = $events->contains(fn ($e) => (int) $e->parcel_status === ParcelStatus::PENDING);
        if (! $hasCreated) {
            $history[] = [
                'status'      => ParcelStatus::PENDING,
                'description' => ParcelStatusHelper::label(ParcelStatus::PENDING),
                'timestamp'   => optional($parcel->created_at)->toIso8601String(),
            ];
        }

        foreach ($events as $e) {
            $status = (int) $e->parcel_status;
            $history[] = [
                'status'      => $status,
                'description' => ParcelStatusHelper::label($status),
                'timestamp'   => optional($e->created_at)->toIso8601String(),
            ];
        }

        return ['status' => 200, 'body' => ['tracking_number' => $parcel->tracking_id, 'history' => $history]];
    }

    // ---- helpers ------------------------------------------------------------

    private function scopedParcels(int $companyId, int $merchantId)
    {
        return Parcel::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('merchant_id', $merchantId);
    }

    private function findScoped(int $companyId, int $merchantId, string $tracking): ?Parcel
    {
        return $this->scopedParcels($companyId, $merchantId)
            ->where('tracking_id', $tracking)
            ->first();
    }

    private function present(Parcel $p): array
    {
        $cod = (float) $p->cash_collection;

        return [
            'id'              => (int) $p->id,
            'tracking_number' => $p->tracking_id,
            'reference'       => $p->reference_number,
            'status'          => (int) $p->status,
            'recipient'       => [
                'name'  => $p->customer_name,
                'phone' => $p->customer_phone,
            ],
            'payment'         => [
                'method'     => $cod > 0 ? 'cod' : 'prepaid',
                'cod_amount' => $cod,
            ],
            'created_at'      => optional($p->created_at)->toIso8601String(),
            'updated_at'      => optional($p->updated_at)->toIso8601String(),
        ];
    }

    private function composeNote(array $p): ?string
    {
        $parts = array_filter([
            $p['notes'] ?? null,
            isset($p['shipment']['description']) ? 'Items: '.$p['shipment']['description'] : null,
        ]);

        return $parts ? Str::limit(implode(' | ', $parts), 1000, '') : null;
    }

    private function err(int $status, string $code, string $message, array $details = []): array
    {
        return ['status' => $status, 'body' => ['error' => array_filter([
            'code'    => $code,
            'message' => $message,
            'details' => $details ?: null,
        ], fn ($v) => $v !== null)]];
    }
}
