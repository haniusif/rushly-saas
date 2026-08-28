<?php

namespace App\Shipping\Services;

use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\TestResultDTO;
use App\Shipping\Exceptions\ConnectionTestFailedException;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Models\ShippingProvider;
use App\Shipping\Repositories\ShippingConnectionRepository;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle of a tenant's shipping connection: create, test, set default,
 * deactivate, delete. Business logic lives here; controllers stay thin.
 */
class ConnectionService
{
    public function __construct(
        private readonly ShippingProviderFactory $factory,
        private readonly ShippingConnectionRepository $repo,
    ) {}

    /**
     * Resolve the remote company id from a domain, returning null if no match.
     * Doesn't persist anything — used by the "Add integration" wizard.
     */
    public function resolveCompanyByDomain(string $providerCode, string $domain): ?string
    {
        return $this->factory->make($providerCode)->resolveCompanyByDomain($domain);
    }

    /**
     * Validate a candidate connection. Always returns a TestResultDTO so
     * callers can render the diagnostic uniformly; doesn't throw on logical
     * failure (caller can decide how to surface).
     */
    public function test(ConnectionDTO $dto): TestResultDTO
    {
        $provider = $this->factory->make($dto->providerCode);
        return $provider->testConnection($dto);
    }

    /**
     * End-to-end "add integration" path:
     *   1. Resolve company id from domain (if not pre-supplied).
     *   2. Run a connection test.
     *   3. On success → persist (encrypted creds, set default if first for this provider).
     *   4. On failure → throw with the diagnostic so the controller can return 422.
     */
    public function store(int $companyId, string $providerCode, array $input): ShippingConnection
    {
        $provider = ShippingProvider::query()->where('code', $providerCode)->firstOrFail();

        $domain          = trim((string) ($input['domain']   ?? '')) ?: null;
        $email           = trim((string) ($input['email']    ?? '')) ?: null;
        $password        = (string) ($input['password']      ?? '');
        $remoteCompanyId = trim((string) ($input['remote_company_id'] ?? '')) ?: null;
        $connectionName  = trim((string) ($input['connection_name']   ?? '')) ?: 'Default';

        // 1. Resolve company id if needed
        if (! $remoteCompanyId && $domain) {
            $remoteCompanyId = $this->resolveCompanyByDomain($providerCode, $domain);
        }

        // 2. Test
        $candidate = new ConnectionDTO(
            id:              null,
            companyId:       $companyId,
            providerCode:    $providerCode,
            connectionName:  $connectionName,
            remoteCompanyId: $remoteCompanyId,
            domain:          $domain,
            email:           $email,
            password:        $password !== '' ? $password : null,
            settings:        (array) ($input['settings'] ?? []),
        );

        $result = $this->test($candidate);
        if (! $result->ok) {
            throw new ConnectionTestFailedException($result->message, $result->diagnostics);
        }

        // 3. Persist
        return DB::transaction(function () use ($candidate, $provider, $companyId) {
            $isFirst = ! ShippingConnection::query()
                ->where('company_id', $companyId)
                ->where('provider_id', $provider->id)
                ->exists();

            $conn = new ShippingConnection();
            $conn->company_id        = $companyId;
            $conn->provider_id       = $provider->id;
            $conn->connection_name   = $candidate->connectionName;
            $conn->remote_company_id = $candidate->remoteCompanyId;
            $conn->domain            = $candidate->domain;
            $conn->email             = $candidate->email;
            // Triggers the 'encrypted' cast — plaintext goes in, ciphertext on disk.
            $conn->password_encrypted = $candidate->password;
            $conn->settings          = $candidate->settings;
            $conn->status            = 'active';
            $conn->is_default        = $isFirst;
            $conn->last_tested_at    = now();
            $conn->save();

            return $conn->load('provider');
        });
    }

    public function update(ShippingConnection $conn, array $input): ShippingConnection
    {
        $conn->connection_name   = trim((string) ($input['connection_name'] ?? $conn->connection_name)) ?: $conn->connection_name;
        $conn->domain            = trim((string) ($input['domain'] ?? '')) ?: null;
        $conn->email             = trim((string) ($input['email'] ?? '')) ?: null;
        if (array_key_exists('password', $input) && (string) $input['password'] !== '' && ! str_starts_with((string) $input['password'], '••')) {
            $conn->password_encrypted = (string) $input['password'];
        }
        if (array_key_exists('remote_company_id', $input)) {
            $conn->remote_company_id = trim((string) $input['remote_company_id']) ?: null;
        }
        if (array_key_exists('settings', $input)) {
            // MERGE, never replace. Secret settings (an OAuth client secret,
            // say) are deliberately not sent back to the browser, so the form
            // posts them blank unless the operator retypes one. A wholesale
            // assignment would read that blank as "clear it" and destroy the
            // stored credential the first time someone edited an unrelated
            // field. Blank values are dropped and the existing value survives;
            // a non-blank value overwrites as expected.
            //
            // Clearing a setting on purpose is therefore not something the form
            // can express — that is the right trade here, since silently losing
            // a credential is far worse than needing a deliberate action to
            // unset one.
            // The '••••••' mask is also dropped. The edit form renders it in
            // place of a stored secret, so it comes straight back on save;
            // taking it at face value would overwrite a real client secret with
            // six bullet characters. Same guard the password branch above uses.
            $incoming = array_filter(
                (array) $input['settings'],
                fn ($v) => $v !== null && $v !== '' && ! (is_string($v) && str_starts_with($v, '••')),
            );

            $conn->settings = array_merge(
                is_array($conn->settings) ? $conn->settings : [],
                $incoming,
            );
        }
        if (array_key_exists('status', $input)) {
            $conn->status = (string) $input['status'];
        }
        $conn->save();
        return $conn->load('provider');
    }

    public function setDefault(ShippingConnection $conn): void
    {
        $this->repo->setDefault($conn);
    }

    public function deactivate(ShippingConnection $conn): void
    {
        $conn->status = 'paused';
        $conn->save();
    }
}
