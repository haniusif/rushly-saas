<?php

namespace App\Salla\Services;

use App\Salla\Models\Merchant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApiClient
{
    public function __construct(private Merchant $merchant)
    {
        if (! $merchant->access_token) {
            throw new RuntimeException("Salla merchant {$merchant->id} has no access token");
        }
    }

    public function updateShipmentStatus(string $sallaShipmentId, string $status, ?string $note = null): array
    {
        $response = $this->request('post', "/shipments/{$sallaShipmentId}/status", [
            'status' => $status,
            'note'   => $note,
        ]);

        $this->ensureSuccess($response, 'shipment.status');
        return $response->json();
    }

    public function returnWaybill(string $sallaShipmentId, string $awbNumber, string $labelUrl): array
    {
        $response = $this->request('post', "/shipments/{$sallaShipmentId}/awb", [
            'awb_number' => $awbNumber,
            'label_url'  => $labelUrl,
        ]);

        $this->ensureSuccess($response, 'shipment.awb');
        return $response->json();
    }

    public function getOrder(int $orderId): array
    {
        $response = $this->request('get', "/orders/{$orderId}");
        $this->ensureSuccess($response, 'order.fetch');
        return $response->json();
    }

    private function request(string $method, string $path, array $payload = []): Response
    {
        $base = rtrim(config('salla.api_base'), '/');
        return Http::withToken($this->merchant->access_token)
            ->acceptJson()
            ->{$method}($base.$path, $payload);
    }

    private function ensureSuccess(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        Log::error("salla.api.{$context} failed", [
            'merchant' => $this->merchant->salla_merchant_id,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        throw new RuntimeException("Salla API {$context} failed: ".$response->status());
    }
}
