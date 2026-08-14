<?php

namespace App\Oms\Services;

use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\WebhookEvent;
use App\Oms\DTOs\OrderDTO;
use App\Oms\DTOs\OrderItemDTO;
use App\Oms\Enums\FulfillmentStatus;
use App\Oms\Enums\OrderStatus;
use App\Oms\Enums\PaymentStatus;
use App\Oms\Events\OrderReceived;
use App\Oms\Events\OrderUpdated;
use App\Oms\Models\Order;
use App\Oms\Models\OrderEvent;
use App\Oms\Models\OrderItem;
use App\Oms\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * OMS entry point. Materialises a canonical OrderDTO into an `orders`
 * row + `order_items` rows + at least one `order_events` audit row.
 *
 * Idempotent: (connection_id, remote_order_id) is the UNIQUE key. A
 * webhook replay produces:
 *   - identical DTO → no-op (returns existing Order, no event fired)
 *   - changed DTO   → update + OrderUpdated event with diff
 *
 * Does NOT create Parcels — that decision belongs to Phase 6
 * FulfillmentRouter, dispatched from the OrderReceived listener.
 */
class OrderService
{
    public function __construct(
        private readonly OrderRepository $repo,
    ) {}

    public function receiveNormalized(
        OrderDTO $dto,
        CommerceConnection $connection,
        ?WebhookEvent $sourceEvent = null,
    ): Order {
        return DB::transaction(function () use ($dto, $connection, $sourceEvent) {
            $existing = $this->repo->findByConnectionAndRemote($connection->id, $dto->remoteOrderId);

            if ($existing) {
                return $this->applyUpdate($existing, $dto, $connection, $sourceEvent);
            }

            return $this->createNew($dto, $connection, $sourceEvent);
        });
    }

    // ---------------------------------------------------------------

    private function createNew(OrderDTO $dto, CommerceConnection $connection, ?WebhookEvent $sourceEvent): Order
    {
        $order = new Order();
        $this->fillCommonFields($order, $dto, $connection, $sourceEvent);
        $order->status             = OrderStatus::PENDING;
        $order->payment_status     = $this->mapPaymentStatus($dto->financialStatus);
        $order->fulfillment_status = FulfillmentStatus::UNFULFILLED;
        $order->received_at        = now();
        $order->save();

        $this->replaceItems($order, $dto->items);

        OrderEvent::create([
            'order_id'    => $order->id,
            'company_id'  => $order->company_id,
            'event_type'  => OrderEvent::TYPE_CREATED,
            'payload'     => [
                'source_provider_code' => $dto->providerCode,
                'remote_order_id'      => $dto->remoteOrderId,
                'provider_status'      => $dto->providerStatus,
                'total'                => $dto->total,
                'currency'             => $dto->currency,
                'items_count'          => count($dto->items),
            ],
            'occurred_at' => now(),
        ]);

        Event::dispatch(new OrderReceived($order));
        return $order->fresh(['items']);
    }

    private function applyUpdate(Order $order, OrderDTO $dto, CommerceConnection $connection, ?WebhookEvent $sourceEvent): Order
    {
        // Capture original values for the diff. Compare against columns
        // the mapper is authoritative for (not internal ones like
        // status/fulfillment_status which the OMS itself owns).
        $original = $order->only([
            'provider_status', 'payment_method',
            'customer_name', 'customer_phone', 'customer_email',
            'shipping_line1', 'shipping_city_name', 'shipping_country',
            'subtotal', 'tax', 'shipping_fee', 'discount', 'total', 'cod_amount',
            'currency', 'note',
        ]);

        $this->fillCommonFields($order, $dto, $connection, $sourceEvent);

        // Auto-canonicalise payment_status from the mapper's financial_status
        // if the mapper produced one; the OMS's own status columns aren't
        // touched here (Phase 6 flows drive those).
        if ($dto->financialStatus) {
            $order->payment_status = $this->mapPaymentStatus($dto->financialStatus);
        }

        $order->save();

        $this->replaceItems($order, $dto->items);

        $changes = $this->diff($original, $order);
        if ($changes) {
            OrderEvent::create([
                'order_id'    => $order->id,
                'company_id'  => $order->company_id,
                'event_type'  => OrderEvent::TYPE_UPDATED,
                'payload'     => ['changes' => $changes],
                'occurred_at' => now(),
            ]);
            Event::dispatch(new OrderUpdated($order, $changes));
        }

        return $order->fresh(['items']);
    }

    private function fillCommonFields(Order $order, OrderDTO $dto, CommerceConnection $connection, ?WebhookEvent $sourceEvent): void
    {
        $order->company_id            = (int) $connection->company_id;
        $order->connection_id         = $connection->id;
        $order->source_provider_code  = $dto->providerCode;
        $order->merchant_id           = $connection->merchant_id ?: null;
        $order->webhook_event_id      = $sourceEvent?->id;

        $order->remote_order_id       = $dto->remoteOrderId;
        $order->remote_order_number   = $dto->remoteOrderNumber;
        $order->provider_status       = $dto->providerStatus;
        $order->payment_method        = $dto->paymentMethod;

        $order->customer_remote_id    = $dto->customer->remoteId;
        $order->customer_name         = $dto->customer->name;
        $order->customer_email        = $dto->customer->email;
        $order->customer_phone        = $dto->customer->phone;

        $s = $dto->shippingAddress;
        $order->shipping_name         = $s->name;
        $order->shipping_phone        = $s->phone;
        $order->shipping_line1        = $s->line1;
        $order->shipping_line2        = $s->line2;
        $order->shipping_city_name    = $s->cityName;
        $order->shipping_area_name    = $s->areaName;
        $order->shipping_region       = $s->region;
        $order->shipping_country      = $s->country;
        $order->shipping_postcode     = $s->postcode;
        $order->shipping_city_id      = $s->resolvedCityId;
        $order->shipping_area_id      = $s->resolvedAreaId;

        $order->subtotal              = $dto->subtotal;
        $order->tax                   = $dto->tax;
        $order->shipping_fee          = $dto->shippingFee;
        $order->discount              = $dto->discount;
        $order->total                 = $dto->total;
        $order->cod_amount            = $dto->codAmount;
        $order->currency              = $dto->currency;
        $order->note                  = $dto->note;

        $order->extra                 = $dto->extra ?: null;
        $order->normalized_snapshot   = $dto->toArray();

        $order->occurred_at           = $dto->occurredAt ? $this->parseIso($dto->occurredAt) : null;
    }

    /** @param OrderItemDTO[] $items */
    private function replaceItems(Order $order, array $items): void
    {
        // Full replace strategy — cheaper than diffing a small list of
        // items, and correct behavior when the storefront edits/removes
        // a line. cascadeOnDelete on the FK handles the removal.
        $order->items()->delete();

        foreach ($items as $i => $item) {
            OrderItem::create([
                'order_id'          => $order->id,
                'sort_order'        => $i,
                'sku'               => $item->sku,
                'name'              => $item->name,
                'quantity'          => $item->quantity,
                'unit_price'        => $item->unitPrice,
                'total_price'       => $item->totalPrice,
                'currency'          => $item->currency,
                'remote_product_id' => $item->remoteProductId,
                'remote_variant_id' => $item->remoteVariantId,
                'extra'             => $item->extra ?: null,
            ]);
        }
    }

    private function mapPaymentStatus(?string $canonicalFinancialStatus): string
    {
        // Mapper output → OMS PaymentStatus. 1:1 for now.
        return match ($canonicalFinancialStatus) {
            'paid'      => PaymentStatus::PAID,
            'pending'   => PaymentStatus::PENDING,
            'refunded'  => PaymentStatus::REFUNDED,
            'voided'    => PaymentStatus::VOIDED,
            default     => PaymentStatus::UNKNOWN,
        };
    }

    /** @return array<string, array{from: mixed, to: mixed}> */
    private function diff(array $original, Order $order): array
    {
        $out = [];
        foreach ($original as $key => $wasValue) {
            $isValue = $order->{$key};
            // Cast decimals to string comparison to avoid float vs decimal noise
            if (is_numeric($wasValue) && is_numeric($isValue)) {
                if ((string) $wasValue !== (string) $isValue) {
                    $out[$key] = ['from' => $wasValue, 'to' => $isValue];
                }
            } elseif ($wasValue !== $isValue) {
                $out[$key] = ['from' => $wasValue, 'to' => $isValue];
            }
        }
        return $out;
    }

    private function parseIso(string $s): ?\DateTimeInterface
    {
        try {
            return new \DateTimeImmutable($s);
        } catch (\Throwable) {
            return null;
        }
    }
}
