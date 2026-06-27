<?php

namespace App\Services\Performance;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Value object for Performance Dashboard query filters.
 *
 * Resolves date range, driver, hub, merchant from a Request (or array)
 * with safe defaults (last 30 days, all entities). The range is inclusive
 * of both endpoints — services use `whereBetween([$from->startOfDay(), $to->endOfDay()])`.
 */
class PerformanceFilters
{
    public Carbon $from;
    public Carbon $to;
    public ?int $driverId;
    public ?int $hubId;
    public ?int $merchantId;
    public ?int $supplierCompanyId;
    public ?int $deliveryTypeId;

    public function __construct(
        Carbon $from,
        Carbon $to,
        ?int $driverId = null,
        ?int $hubId = null,
        ?int $merchantId = null,
        ?int $supplierCompanyId = null,
        ?int $deliveryTypeId = null
    ) {
        $this->from              = $from->copy()->startOfDay();
        $this->to                = $to->copy()->endOfDay();
        $this->driverId          = $driverId;
        $this->hubId             = $hubId;
        $this->merchantId        = $merchantId;
        $this->supplierCompanyId = $supplierCompanyId;
        $this->deliveryTypeId    = $deliveryTypeId;
    }

    public static function fromRequest(Request $request): self
    {
        $today = Carbon::today();
        $from  = $request->filled('from') ? Carbon::parse($request->input('from')) : $today->copy()->subDays(29);
        $to    = $request->filled('to')   ? Carbon::parse($request->input('to'))   : $today;

        // Sanity: swap if reversed, clamp range to <= 366 days.
        if ($from->gt($to)) [$from, $to] = [$to, $from];
        if ($from->diffInDays($to) > 366) $from = $to->copy()->subDays(366);

        return new self(
            $from,
            $to,
            self::int($request, 'driver_id'),
            self::int($request, 'hub_id'),
            self::int($request, 'merchant_id'),
            self::int($request, 'supplier_company_id'),
            self::int($request, 'delivery_type_id'),
        );
    }

    private static function int(Request $r, string $key): ?int
    {
        $v = $r->input($key);
        return is_numeric($v) ? (int) $v : null;
    }

    /** Length of the selected range in days (inclusive). */
    public function days(): int
    {
        return (int) $this->from->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay()) + 1;
    }

    /** Same-length range immediately before $from..$to — used for growth/MoM. */
    public function previousPeriod(): self
    {
        $days = $this->days();
        $prevTo   = $this->from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return new self($prevFrom, $prevTo, $this->driverId, $this->hubId, $this->merchantId, $this->supplierCompanyId, $this->deliveryTypeId);
    }

    public function toArray(): array
    {
        return [
            'from'                => $this->from->toDateString(),
            'to'                  => $this->to->toDateString(),
            'driver_id'           => $this->driverId,
            'hub_id'              => $this->hubId,
            'merchant_id'         => $this->merchantId,
            'supplier_company_id' => $this->supplierCompanyId,
            'delivery_type_id'    => $this->deliveryTypeId,
        ];
    }
}
