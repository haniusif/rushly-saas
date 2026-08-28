<?php

namespace App\Shipping\Providers\EcoExpress;

use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;
use App\Shipping\DTOs\TestResultDTO;
use App\Shipping\DTOs\TrackingDTO;
use App\Shipping\Exceptions\ProviderRejectedShipmentException;
use App\Shipping\Exceptions\ProviderUnavailableException;
use App\Shipping\Providers\AbstractProvider;
use App\Shipping\Providers\EcoExpress\Mappers\ShipmentRequestMapper;
use App\Shipping\Providers\EcoExpress\Mappers\StatusMapper;
use Illuminate\Support\Facades\Cache;

/**
 * EcoExpress (Eco Freight / Focalsoft) — UAE courier.
 *
 * Verified against the sandbox at staging1.focalsoft.ae:8443 rather than taken
 * from the published Apiary spec, which disagrees with the deployed service in
 * several places. Where they differ, the deployed behaviour wins and the
 * discrepancy is noted at the call site.
 *
 * AUTH — OAuth2 client credentials. The spec advertises JSON; the service
 * returns 401 for a JSON body and only accepts form encoding. Tokens last
 * ~30 minutes (expires_in 1799), so they are cached per connection and reused
 * rather than minted per request.
 *
 * NOT SUPPORTED — EcoExpress publishes no cancellation and no AWB/label
 * endpoint. cancelShipment() and printAwb() therefore throw
 * ProviderUnavailableException with a reason, which is the contract's
 * documented way to express a missing capability. Both are worth revisiting if
 * EcoExpress adds the endpoints.
 */
class EcoExpressProvider extends AbstractProvider implements ShippingProviderInterface
{
    public function code(): string
    {
        return 'ecoexpress';
    }

    /** EcoExpress has no domain-based company lookup. */
    public function resolveCompanyByDomain(string $domain): ?string
    {
        return null;
    }

    // ------------------------------------------------------------------
    // Auth
    // ------------------------------------------------------------------

    /**
     * Mint or reuse a bearer token.
     *
     * Cached at 80% of the advertised lifetime so a token can never expire
     * mid-flight on a slow call.
     *
     * The key hashes the client id AND the secret. Hashing only the id looks
     * sufficient but is not: rotating a secret — or typing the wrong one —
     * leaves the key unchanged, so the old token keeps being served and the
     * connection reports healthy on credentials that no longer work. Caught by
     * a test that expected a bad secret to fail and got ok=true.
     */
    private function token(ConnectionDTO $c): string
    {
        $clientId     = (string) $c->setting('client_id', '');
        $clientSecret = (string) $c->setting('client_secret', '');

        if ($clientId === '' || $clientSecret === '') {
            throw new ProviderUnavailableException(
                'EcoExpress connection is missing client_id / client_secret.'
            );
        }

        $cacheKey = 'shipping:ecoexpress:token:' . ($c->id ?? 'adhoc')
            . ':' . sha1($clientId . ':' . $clientSecret);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // Form-encoded, NOT JSON. The published spec shows a JSON body; the
        // deployed service answers 401 to it.
        $resp = $this->http('POST', '/connect/client/v2/authentication', $c, fn ($r) => $r
            ->asForm()
            ->withBody(http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]), 'application/x-www-form-urlencoded'),
            ['grant_type' => 'client_credentials', 'client_id' => $clientId, 'client_secret' => '***']);

        $body = $resp->json();
        $tok  = $body['access_token'] ?? null;

        if (! $resp->successful() || ! is_string($tok) || $tok === '') {
            throw new ProviderUnavailableException(
                'EcoExpress authentication failed: ' . ($body['error'] ?? ('HTTP ' . $resp->status()))
            );
        }

        $ttl = (int) ($body['expires_in'] ?? 1799);
        Cache::put($cacheKey, $tok, max(60, (int) floor($ttl * 0.8)));

        return $tok;
    }

    /** Authenticated JSON call against /connect/client/*. */
    private function call(ConnectionDTO $c, string $method, string $endpoint, array $payload = [])
    {
        $token = $this->token($c);

        return $this->http($method, $endpoint, $c, fn ($r) => $r
            ->withToken($token)
            ->withBody(json_encode($payload, JSON_UNESCAPED_SLASHES), 'application/json'),
            $payload);
    }

    /**
     * EcoExpress answers HTTP 200 even for business failures, signalling them
     * in the envelope instead: status 201 plus a populated errorList. Treating
     * the HTTP code as the outcome would make every rejection look like a
     * success, so the envelope is what gets checked.
     */
    private function envelopeError(array $body): ?string
    {
        $errors = $body['errorList'] ?? [];
        if (is_array($errors) && $errors !== []) {
            return implode('; ', array_map('strval', $errors));
        }
        $status = (int) ($body['status'] ?? 0);
        if ($status !== 0 && $status !== 200) {
            return (string) ($body['message'] ?? ('provider status ' . $status));
        }
        return null;
    }

    public function authenticate(ConnectionDTO $c): ConnectionDTO
    {
        $this->token($c);   // throws on bad credentials

        return $c;
    }

    public function testConnection(ConnectionDTO $c): TestResultDTO
    {
        $accountNo = trim((string) $c->setting('account_no', ''));
        if ($accountNo === '') {
            return TestResultDTO::fail('Account number is required.');
        }

        try {
            $this->token($c);
        } catch (\Throwable $e) {
            return TestResultDTO::fail($e->getMessage());
        }

        try {
            // /services doubles as an account check: it is the only endpoint
            // that validates the account number rather than just the token.
            // Field name is `accountNo` here — note createShipment uses
            // `account_No`; the inconsistency is EcoExpress's, confirmed live.
            $resp = $this->call($c, 'POST', '/connect/client/services', ['accountNo' => $accountNo]);
            $body = $resp->json() ?? [];

            if ($err = $this->envelopeError($body)) {
                return TestResultDTO::fail('EcoExpress rejected the account: ' . $err);
            }

            $services = array_map(
                fn ($s) => (string) ($s['code'] ?? '?'),
                is_array($body['data'] ?? null) ? $body['data'] : []
            );

            return TestResultDTO::ok(
                'Connected. Enabled services: ' . ($services ? implode(', ', $services) : 'none'),
                ['account_no' => $accountNo, 'services' => $services]
            );
        } catch (\Throwable $e) {
            return TestResultDTO::fail($e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Shipments
    // ------------------------------------------------------------------

    public function createShipment(ConnectionDTO $c, ShipmentDTO $s): ShipmentDTO
    {
        $accountNo = trim((string) $c->setting('account_no', ''));
        if ($accountNo === '') {
            throw new ProviderRejectedShipmentException('EcoExpress connection has no account number.');
        }

        $payload = ['data' => [ShipmentRequestMapper::map($c, $s, $accountNo)]];

        $resp = $this->call($c, 'POST', '/connect/client/order/shipment/create', $payload);
        $body = $resp->json() ?? [];

        if ($err = $this->envelopeError($body)) {
            throw new ProviderRejectedShipmentException('EcoExpress rejected the shipment: ' . $err);
        }

        // The create response wraps results in `data`, one entry per submitted
        // shipment. We submit exactly one, so the first entry is ours.
        $first = is_array($body['data'] ?? null) ? ($body['data'][0] ?? []) : [];
        $awb   = $first['awb_number'] ?? $first['awbNumber'] ?? $first['tracking_number'] ?? null;

        if (! $awb) {
            throw new ProviderRejectedShipmentException(
                'EcoExpress accepted the request but returned no AWB number.'
            );
        }

        return $s->withRemote((string) $awb, (string) $awb, null, $body);
    }

    /**
     * Not supported. EcoExpress publishes no cancellation endpoint — a
     * shipment has to be cancelled through their portal or by their support
     * desk, and the status comes back to us on the next tracking poll as SCB /
     * CSC. Throwing is the contract's way of saying "capability absent"; the
     * caller surfaces the reason rather than silently no-oping and leaving the
     * operator believing the shipment was cancelled.
     */
    public function cancelShipment(ConnectionDTO $c, string $remoteShipmentId): void
    {
        throw new ProviderUnavailableException(
            'EcoExpress does not expose a cancellation API. Cancel the shipment in the '
            . 'EcoExpress portal or via their support desk; the status will sync back here.'
        );
    }

    // ------------------------------------------------------------------
    // Tracking
    // ------------------------------------------------------------------

    public function getStatus(ConnectionDTO $c, string $remoteShipmentId): TrackingDTO
    {
        $events = $this->getTracking($c, $remoteShipmentId);

        if ($events === []) {
            return new TrackingDTO(
                remoteShipmentId: $remoteShipmentId,
                rawStatus:        'UNKNOWN',
                localStatus:      null,
                description:      'No tracking events yet.',
            );
        }

        // getTracking returns oldest-first; the latest event is the status.
        return $events[array_key_last($events)];
    }

    /** @return TrackingDTO[] oldest event first */
    public function getTracking(ConnectionDTO $c, string $remoteShipmentId): array
    {
        $resp = $this->call($c, 'POST', '/connect/client/order/shipment/track', [
            'trackingNumber' => $remoteShipmentId,
        ]);
        $body = $resp->json() ?? [];

        if ($err = $this->envelopeError($body)) {
            throw new ProviderUnavailableException('EcoExpress tracking failed: ' . $err);
        }

        $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
        $out  = [];

        foreach ($rows as $row) {
            // Their payload nests the event list under the shipment; tolerate
            // both a flat list and a nested one rather than assuming a shape
            // the sandbox could not be made to produce with a real AWB.
            $events = is_array($row['status'] ?? null) ? $row['status']
                : (is_array($row['events'] ?? null) ? $row['events'] : [$row]);

            foreach ($events as $e) {
                $code = (string) ($e['status_desc'] ?? $e['statusDesc'] ?? $e['code'] ?? '');
                $out[] = new TrackingDTO(
                    remoteShipmentId: $remoteShipmentId,
                    rawStatus:        $code,
                    localStatus:      StatusMapper::toLocal($code),
                    description:      (string) ($e['status_name'] ?? $e['statusName'] ?? $e['description'] ?? ''),
                    occurredAt:       $e['status_datetime'] ?? $e['statusDateTime'] ?? $e['date'] ?? null,
                    raw:              is_array($e) ? $e : [],
                );
            }
        }

        return $out;
    }

    /**
     * Not supported. EcoExpress returns no label bytes and publishes no label
     * endpoint; the AWB number comes back on create and the printed label is
     * produced on their side.
     */
    public function printAwb(ConnectionDTO $c, array $remoteShipmentIds): string
    {
        throw new ProviderUnavailableException(
            'EcoExpress does not return AWB labels over the API. Print from the EcoExpress portal.'
        );
    }

    /**
     * EcoExpress has no free-text area lookup. It does expose /states and
     * /cities, but both are code-driven rather than search-driven, so there is
     * nothing to bind an arbitrary query against.
     */
    public function searchVillages(ConnectionDTO $c, string $query): array
    {
        return [];
    }
}
