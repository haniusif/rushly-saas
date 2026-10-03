<?php

namespace App\Shipping\Providers\Logestechs;

use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\DTOs\AddressDTO;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;
use App\Shipping\DTOs\TestResultDTO;
use App\Shipping\DTOs\TrackingDTO;
use App\Shipping\Exceptions\ProviderRejectedShipmentException;
use App\Shipping\Exceptions\ProviderUnavailableException;
use App\Shipping\Providers\AbstractProvider;
use App\Shipping\Providers\Logestechs\Mappers\ShipmentRequestMapper;
use App\Shipping\Providers\Logestechs\Mappers\ShipmentResponseMapper;
use App\Shipping\Providers\Logestechs\Mappers\StatusMapper;

/**
 * Concrete implementation of ShippingProviderInterface for the Logestechs
 * platform.
 *
 * Auth model:
 *  - Every call sends a `company-id` header (the target Logestechs account).
 *  - Create + cancel additionally carry the customer's email + password inside
 *    the JSON body (Logestechs validates the customer per call).
 *  - getCompanyByDomain is the only un-tenanted endpoint — used during the
 *    "Add Integration" wizard before a connection exists.
 *
 * Endpoints (confirmed via temp/LogesTechs_postman_collection.json):
 *   POST /ship/request/by-email                     → createShipment
 *   GET  /addresses/villages?search=                → searchVillages
 *   GET  /guests/packages/status?barcode=           → getStatus
 *   GET  /guests/{cid}/packages/tracking?barcode=   → getTracking
 *   PUT  /guests/{cid}/packages/{id}/cancel         → cancelShipment
 *   POST /guests/{cid}/packages/pdf                 → printAwb
 *   GET  /guests/companies/info-by-domain?domain=   → resolveCompanyByDomain
 *   POST /auth/customer/login                       → authenticate (validate creds)
 */
class LogestechsProvider extends AbstractProvider implements ShippingProviderInterface
{
    public function code(): string
    {
        return 'logestechs';
    }

    // -----------------------------------------------------------------------
    // Pre-connection: resolve + validate
    // -----------------------------------------------------------------------

    public function resolveCompanyByDomain(string $domain): ?string
    {
        $resp = $this->http(
            method: 'GET',
            endpoint: '/guests/companies/info-by-domain',
            connection: null,
            build: fn ($r) => $r->withQueryParameters(['domain' => $domain]),
        );

        $data = $resp->json();
        if (! is_array($data)) return null;
        // Response shape: { id: 496, ... }
        $id = $data['id'] ?? $data['companyId'] ?? null;
        return $id !== null ? (string) $id : null;
    }

    public function testConnection(ConnectionDTO $c): TestResultDTO
    {
        if (! filled($c->remoteCompanyId)) {
            return TestResultDTO::fail('Missing remote company id — paste a Logestechs domain or company id first.');
        }

        try {
            // /addresses/villages is the cheapest reachability probe that
            // requires only the company-id header.
            $resp = $this->http(
                method: 'GET',
                endpoint: '/addresses/villages',
                connection: $c,
                build: fn ($r) => $r
                    ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                    ->withQueryParameters(['search' => '']),
            );

            $data = $resp->json();
            $count = is_array($data['data'] ?? null) ? count($data['data']) : 0;

            // If credentials supplied, validate them via customer login. This
            // is the only call that actually verifies email+password.
            $credCheck = null;
            if (filled($c->email) && filled($c->password)) {
                $credCheck = $this->validateLogin($c);
                if ($credCheck !== true) {
                    return TestResultDTO::fail("Reachable, but login failed: {$credCheck}");
                }
            }

            $msg = "Reachable — {$count} village(s) returned"
                 . ($credCheck === true ? '; credentials validated.' : ' (credentials not checked).');

            return TestResultDTO::ok($msg, ['villages_returned' => $count]);
        } catch (ProviderUnavailableException $e) {
            return TestResultDTO::fail($e->getMessage(), $e->payload);
        }
    }

    public function authenticate(ConnectionDTO $c): ConnectionDTO
    {
        // Logestechs has no token to persist — credentials are sent inline on
        // every shipment call. Authenticate becomes a no-op validation:
        // confirm the login endpoint accepts the creds, then return the DTO
        // unchanged (or with the resolved remote_company_id refreshed).
        if (filled($c->domain) && ! filled($c->remoteCompanyId)) {
            $resolved = $this->resolveCompanyByDomain($c->domain);
            $c = $c->withRemoteCompanyId($resolved);
        }

        $ok = $this->validateLogin($c);
        if ($ok !== true) {
            throw new ProviderRejectedShipmentException("Logestechs login failed: {$ok}");
        }

        return $c;
    }

    /** Returns true on success, or the error message on failure. */
    private function validateLogin(ConnectionDTO $c): true|string
    {
        try {
            $resp = $this->http(
                method: 'POST',
                endpoint: '/auth/customer/login',
                connection: $c,
                build: fn ($r) => $r->withBody(json_encode([
                    'email'     => $c->email,
                    'password'  => $c->password,
                    'companyId' => (int) $c->remoteCompanyId,
                    'device'    => ['operatingSystem' => 'API', 'UUID' => 'rushly-' . $c->companyId],
                ], JSON_UNESCAPED_UNICODE), 'application/json'),
                rawBody: ['email' => $c->email, 'password' => '***'],
            );
        } catch (ProviderUnavailableException $e) {
            return $e->getMessage();
        }

        if ($resp->successful()) {
            return true;
        }
        $body = $resp->json();
        return is_array($body) ? json_encode($body) : (string) $resp->body();
    }

    // -----------------------------------------------------------------------
    // Shipments
    // -----------------------------------------------------------------------

    public function createShipment(ConnectionDTO $c, ShipmentDTO $s): ShipmentDTO
    {
        if (! filled($c->remoteCompanyId)) {
            throw new ProviderRejectedShipmentException('Missing remote_company_id on connection.');
        }
        if (! filled($c->email) || ! filled($c->password)) {
            throw new ProviderRejectedShipmentException('Connection has no Logestechs credentials.');
        }

        // Resolve village from recipient area/city if not pre-supplied in extra
        if (! isset($s->extra['village'])) {
            $query = (string) ($s->recipient->area ?? $s->recipient->city ?? '');
            if ($query !== '') {
                $village = $this->resolveVillage($c, $query);
                if ($village !== null) {
                    $s = $s->withExtra(['village' => $village]);
                }
            }
        }

        $body = ShipmentRequestMapper::toBody($c, $s);

        $resp = $this->http(
            method: 'POST',
            endpoint: '/ship/request/by-email',
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withBody(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'application/json'),
            rawBody: array_replace_recursive($body, ['password' => '***']),
        );

        if (! $resp->successful()) {
            $bodyResp = $resp->json();
            throw new ProviderRejectedShipmentException(
                'Logestechs rejected shipment (HTTP ' . $resp->status() . ').',
                ['status' => $resp->status(), 'body' => $bodyResp],
            );
        }

        $payload = $resp->json();
        if (! is_array($payload) || empty($payload['id'])) {
            throw new ProviderRejectedShipmentException(
                'Logestechs response missing shipment id.',
                ['body' => $payload],
            );
        }

        return ShipmentResponseMapper::apply($s, $payload);
    }

    public function cancelShipment(ConnectionDTO $c, string $remoteShipmentId): void
    {
        if (! filled($c->remoteCompanyId)) {
            throw new ProviderRejectedShipmentException('Missing remote_company_id on connection.');
        }

        $endpoint = '/guests/' . rawurlencode($c->remoteCompanyId) . '/packages/' . rawurlencode($remoteShipmentId) . '/cancel';
        $body = ['email' => $c->email, 'password' => $c->password];

        $resp = $this->http(
            method: 'PUT',
            endpoint: $endpoint,
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withBody(json_encode($body, JSON_UNESCAPED_UNICODE), 'application/json'),
            rawBody: ['email' => $c->email, 'password' => '***'],
        );

        if (! $resp->successful()) {
            throw new ProviderRejectedShipmentException(
                'Logestechs cancel failed (HTTP ' . $resp->status() . ').',
                ['status' => $resp->status(), 'body' => $resp->json()],
            );
        }
    }

    // -----------------------------------------------------------------------
    // Tracking
    // -----------------------------------------------------------------------

    public function getStatus(ConnectionDTO $c, string $remoteShipmentId): TrackingDTO
    {
        $resp = $this->http(
            method: 'GET',
            endpoint: '/guests/packages/status',
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withQueryParameters(['barcode' => $remoteShipmentId]),
        );

        $payload = $resp->json();
        $payload = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);

        $raw = $this->pickFirst($payload, ['status', 'currentStatus', 'state', 'packageStatus', 'shipmentStatus']) ?? '';
        $when = $this->pickFirst($payload, ['statusDate', 'lastUpdateDate', 'updatedAt', 'updated_at', 'date']);

        return new TrackingDTO(
            remoteShipmentId: $remoteShipmentId,
            rawStatus:        (string) $raw,
            localStatus:      $raw !== '' ? StatusMapper::map((string) $raw) : null,
            description:      null,
            occurredAt:       $when ? (string) $when : null,
            raw:              is_array($payload) ? $payload : [],
        );
    }

    public function getTracking(ConnectionDTO $c, string $remoteShipmentId): array
    {
        $endpoint = '/guests/' . rawurlencode($c->remoteCompanyId ?? '') . '/packages/tracking';
        $resp = $this->http(
            method: 'GET',
            endpoint: $endpoint,
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withQueryParameters(['barcode' => $remoteShipmentId]),
        );

        $payload = $resp->json();
        $events  = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);
        if (! is_array($events)) return [];

        $out = [];
        foreach ($events as $e) {
            if (! is_array($e)) continue;
            $raw = (string) ($this->pickFirst($e, ['status', 'currentStatus', 'state']) ?? '');
            $out[] = new TrackingDTO(
                remoteShipmentId: $remoteShipmentId,
                rawStatus:        $raw,
                localStatus:      $raw !== '' ? StatusMapper::map($raw) : null,
                description:      (string) ($this->pickFirst($e, ['description', 'note', 'comments']) ?? ''),
                occurredAt:       (string) ($this->pickFirst($e, ['statusDate', 'updatedAt', 'date']) ?? ''),
                raw:              $e,
            );
        }
        return $out;
    }

    // -----------------------------------------------------------------------
    // AWB
    // -----------------------------------------------------------------------

    public function printAwb(ConnectionDTO $c, array $remoteShipmentIds): string
    {
        if (! filled($c->remoteCompanyId)) {
            throw new ProviderRejectedShipmentException('Missing remote_company_id on connection.');
        }

        $endpoint = '/guests/' . rawurlencode($c->remoteCompanyId) . '/packages/pdf';
        $body     = ['ids' => array_values($remoteShipmentIds)];

        $resp = $this->http(
            method: 'POST',
            endpoint: $endpoint,
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withBody(json_encode($body), 'application/json'),
            rawBody: $body,
        );

        if (! $resp->successful()) {
            throw new ProviderRejectedShipmentException(
                'Logestechs printAwb failed (HTTP ' . $resp->status() . ').',
                ['status' => $resp->status(), 'body' => $resp->body()],
            );
        }
        return $resp->body();
    }

    // -----------------------------------------------------------------------
    // Lookups
    // -----------------------------------------------------------------------

    public function searchVillages(ConnectionDTO $c, string $query): array
    {
        $resp = $this->http(
            method: 'GET',
            endpoint: '/addresses/villages',
            connection: $c,
            build: fn ($r) => $r
                ->withHeaders(['company-id' => (string) $c->remoteCompanyId])
                ->withQueryParameters(['search' => $query]),
        );

        $payload = $resp->json();
        $rows    = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return array_map(function ($v) {
            return new AddressDTO(
                area:   $v['englishName'] ?? $v['name'] ?? $v['arabicName'] ?? null,
                city:   $v['cityName'] ?? null,
                region: $v['regionName'] ?? null,
                extra:  [
                    'villageId'   => $v['id'] ?? null,
                    'englishName' => $v['englishName'] ?? null,
                    'arabicName'  => $v['arabicName'] ?? null,
                    'cityId'      => $v['cityId'] ?? null,
                    'regionId'    => $v['regionId'] ?? null,
                    'prefix'      => $v['prefix'] ?? null,
                ],
            );
        }, $rows);
    }

    /**
     * Internal helper used by createShipment when the caller doesn't pre-resolve
     * the village. Picks the first match.
     */
    private function resolveVillage(ConnectionDTO $c, string $query): ?array
    {
        $matches = $this->searchVillages($c, $query);
        if (empty($matches)) return null;
        return $matches[0]->extra + ['englishName' => $matches[0]->extra['englishName'] ?? null];
    }

    private function pickFirst(array $arr, array $keys)
    {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && $arr[$k] !== '') return $arr[$k];
        }
        return null;
    }
}
