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
 * NOT SUPPORTED — EcoExpress publishes no cancellation endpoint, so
 * cancelShipment() throws ProviderUnavailableException with a reason, which is
 * the contract's documented way to express a missing capability.
 *
 * LABELS DO EXIST, but only as a `pdfURL` on the create response — there is no
 * endpoint to ask for a label by shipment id afterwards. printAwb() therefore
 * reads the URL persisted at create time and downloads it.
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
     * Held in memory for the life of the request, NOT in the cache.
     *
     * stancl/tenancy's CacheTenancyBootstrapper wraps the cache in per-tenant
     * TAGS, and this deployment runs cache.default=file, which cannot do tags.
     * So any Cache:: call inside a tenant web request throws "This cache store
     * does not support tagging" — which is exactly what Test Connection
     * returned. It passed under tinker only because CLI has no tenant context,
     * so the facade was the plain file store: a bug that could not be
     * reproduced from the console.
     *
     * The provider instance is resolved once per request by the factory, so an
     * instance property gives reuse within a request — the common case, where
     * a test or a batch makes several calls — without depending on a store at
     * all. The cost is one extra auth round-trip per request, which beats
     * being coupled to the cache driver.
     *
     * The key includes the secret as well as the client id: keying on the id
     * alone means rotating a secret leaves the key unchanged, so a stale token
     * keeps being served and the connection reports healthy on credentials
     * that no longer work.
     *
     * @var array<string, array{token: string, expires: int}>
     */
    private array $tokenCache = [];

    /**
     * Mint or reuse a bearer token.
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

        $cacheKey = ($c->id ?? 'adhoc') . ':' . sha1($clientId . ':' . $clientSecret);

        $hit = $this->tokenCache[$cacheKey] ?? null;
        if ($hit && $hit['expires'] > time()) {
            return $hit['token'];
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

        // Expire at 80% of the advertised lifetime so a token can never lapse
        // mid-flight on a slow call.
        $ttl = (int) ($body['expires_in'] ?? 1799);
        $this->tokenCache[$cacheKey] = [
            'token'   => $tok,
            'expires' => time() + max(60, (int) floor($ttl * 0.8)),
        ];

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

        // Refuse anything outside EcoExpress's UAE-only coverage BEFORE calling
        // them, with a reason an operator can act on. Otherwise the failure
        // arrives as "Shipper state is not valid" from the carrier, which says
        // nothing about the actual cause — that the parcel is not a UAE
        // shipment at all.
        if ($reason = ShipmentRequestMapper::unmappableReason($s)) {
            throw new ProviderRejectedShipmentException($reason);
        }

        $payload = ['data' => [ShipmentRequestMapper::map($c, $s, $accountNo)]];

        $resp = $this->call($c, 'POST', '/connect/client/order/shipment/create', $payload);
        $body = $resp->json() ?? [];

        if ($err = $this->envelopeError($body)) {
            throw new ProviderRejectedShipmentException('EcoExpress rejected the shipment: ' . $err);
        }

        // The create response wraps results in `data`, one entry per submitted
        // shipment. We submit exactly one, so the first entry is ours.
        //
        // Field names are from the live response, not the spec: the AWB comes
        // back as `awbNo` (not awb_number), and `pdfURL` carries a ready-made
        // label. The alternatives are kept as fallbacks in case their naming
        // settles differently between environments.
        $first = is_array($body['data'] ?? null) ? ($body['data'][0] ?? []) : [];
        $awb   = $first['awbNo'] ?? $first['awb_number'] ?? $first['awbNumber'] ?? null;
        $pdf   = $first['pdfURL'] ?? $first['pdf_url'] ?? null;

        // Their own shipment id, which is what /track keys on. Falls back to
        // the AWB when absent so the caller always has a handle.
        $remoteId = $first['id'] ?? $awb;

        if (! $awb) {
            throw new ProviderRejectedShipmentException(
                'EcoExpress accepted the request but returned no AWB number.'
            );
        }

        return $s->withRemote((string) $remoteId, (string) $awb, $pdf ? (string) $pdf : null, $body);
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
        // trackingNumber is an ARRAY, even for one shipment. Sending a bare
        // string returns HTTP 400 with no explanation.
        $resp = $this->call($c, 'POST', '/connect/client/order/shipment/track', [
            'trackingNumber' => [$remoteShipmentId],
        ]);
        $body = $resp->json() ?? [];

        if ($err = $this->envelopeError($body)) {
            throw new ProviderUnavailableException('EcoExpress tracking failed: ' . $err);
        }

        $rows = is_array($body['data'] ?? null) ? $body['data'] : [];
        $out  = [];

        foreach ($rows as $row) {
            // The event history is `activities`, each entry carrying the SHORT
            // code the StatusMapper keys on ("SIR"), the human name and a
            // timestamp. The row's own top-level `status` is only the latest
            // event rendered as a full NAME, not a code, so it cannot be mapped
            // and is deliberately ignored.
            $events = is_array($row['activities'] ?? null) ? $row['activities'] : [];

            foreach ($events as $e) {
                $code = (string) ($e['code'] ?? '');
                $desc = trim((string) ($e['desc'] ?? ''));
                $name = (string) ($e['name'] ?? '');

                $out[] = new TrackingDTO(
                    remoteShipmentId: $remoteShipmentId,
                    rawStatus:        $code,
                    localStatus:      StatusMapper::toLocal($code),
                    // `desc` carries the failure reason when there is one and is
                    // empty otherwise, so fall back to the status name. This is
                    // where the detail lost by the many-to-one status mapping
                    // survives.
                    description:      $desc !== '' ? $desc : $name,
                    occurredAt:       $e['date'] ?? null,
                    raw:              is_array($e) ? $e : [],
                );
            }
        }

        return $out;
    }

    /**
     * EcoExpress does return labels, but as a URL on the CREATE response
     * (`pdfURL`) rather than from a print endpoint — there is no way to ask for
     * a label by shipment id later. The URL is persisted on the shipment at
     * create time, so fetching bytes here means reading it back and
     * downloading, which is what this does.
     *
     * Only single-shipment printing is supported: their labels come one PDF per
     * shipment and merging PDFs is not something to do inside a provider.
     */
    public function printAwb(ConnectionDTO $c, array $remoteShipmentIds): string
    {
        $ids = array_values(array_filter($remoteShipmentIds));

        if (count($ids) !== 1) {
            throw new ProviderUnavailableException(
                'EcoExpress returns one label per shipment; batch printing is not supported.'
            );
        }

        $url = \App\Shipping\Models\Shipment::query()
            ->where('company_id', $c->companyId)
            ->where('remote_shipment_id', (string) $ids[0])
            ->value('awb_pdf_url');

        if (! $url) {
            throw new ProviderUnavailableException(
                'No stored label URL for this shipment. EcoExpress only returns the label '
                . 'when the shipment is created, so it cannot be re-fetched afterwards.'
            );
        }

        $resp = \Illuminate\Support\Facades\Http::timeout((int) $this->config('timeout', 30))->get($url);

        if (! $resp->successful()) {
            throw new ProviderUnavailableException(
                'EcoExpress label download failed: HTTP ' . $resp->status()
            );
        }

        return $resp->body();
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
