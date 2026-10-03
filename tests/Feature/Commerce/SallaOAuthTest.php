<?php

namespace Tests\Feature\Commerce;

use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\Providers\Salla\SallaProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 3.5 — Salla OAuth wiring. Covers:
 *   - buildAuthorizationUrl reads tenant client_id via sallaCreds
 *   - handleOAuthCallback exchanges code → tokens + fetches store info → DTO
 *   - refreshAccessToken swaps tokens using refresh_token grant
 *
 * Uses Http::fake() to exercise the real HTTP path without hitting
 * Salla. `integration_settings` is hand-built for sqlite so
 * `sallaCreds()` resolves.
 */
class SallaOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal integration_settings + general_settings so sallaCreds
        // + settings() have something to resolve.
        Schema::create('integration_settings', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('platform');
            $t->text('meta')->nullable();
            $t->timestamps();
        });
        Schema::create('general_settings', function ($t) {
            $t->id();
            $t->string('name')->nullable();
            $t->unsignedTinyInteger('status')->default(1);   // Status::ACTIVE = 1
            $t->unsignedBigInteger('rxlogo')->nullable();
            $t->unsignedBigInteger('rxfavicon')->nullable();
            $t->string('par_track_prefix')->nullable();
            $t->string('currency_code')->nullable();
        });
        DB::table('general_settings')->insert(['id' => 1, 'name' => 'Test tenant', 'status' => 1]);
        DB::table('integration_settings')->insert([
            'company_id' => 1,
            'platform'   => 'salla',
            'meta'       => json_encode([
                'oauth_client_id'     => 'test-client-id',
                'oauth_client_secret' => 'test-client-secret',
                'oauth_redirect_uri'  => 'https://example.test/admin/commerce/connections/salla/oauth/callback',
                'webhook_secret'      => 'test-webhook-secret',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function provider(): SallaProvider
    {
        return $this->app->make(SallaProvider::class);
    }

    /** @test */
    public function build_authorization_url_includes_client_id_and_state(): void
    {
        $url = $this->provider()->buildAuthorizationUrl(['state' => 'STATE123']);

        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('state=STATE123',            $url);
        $this->assertStringContainsString('response_type=code',        $url);
        $this->assertStringContainsString('scope=offline_access',      $url);
        // Redirect URI (url-encoded)
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://example.test/admin/commerce/connections/salla/oauth/callback'), $url);
    }

    /** @test */
    public function handle_oauth_callback_exchanges_code_and_returns_populated_dto(): void
    {
        Http::fake([
            'accounts.salla.sa/oauth2/token' => Http::response([
                'access_token'  => 'ACCESS-TOKEN-XYZ',
                'refresh_token' => 'REFRESH-TOKEN-ABC',
                'expires_in'    => 3600,
                'token_type'    => 'Bearer',
            ], 200),
            'api.salla.dev/admin/v2/store/info' => Http::response([
                'data' => [
                    'id'   => 99999999,
                    'name' => 'Test Store',
                    'url'  => 'https://teststore.salla.sa',
                ],
            ], 200),
        ]);

        $request = Request::create('/callback', 'GET', ['code' => 'AUTH-CODE-123']);

        /** @var CommerceConnectionDTO $dto */
        $dto = $this->provider()->handleOAuthCallback($request);

        $this->assertInstanceOf(CommerceConnectionDTO::class, $dto);
        $this->assertSame('salla', $dto->providerCode);
        $this->assertSame('ACCESS-TOKEN-XYZ',  $dto->accessToken);
        $this->assertSame('REFRESH-TOKEN-ABC', $dto->refreshToken);
        $this->assertNotNull($dto->tokenExpiresAt);
        $this->assertSame('99999999', $dto->remoteStoreId);
        $this->assertSame('https://teststore.salla.sa', $dto->domain);
        $this->assertSame('Test Store', $dto->connectionName);
        $this->assertSame('test-webhook-secret', $dto->webhookSecret);
    }

    /** @test */
    public function refresh_access_token_swaps_tokens(): void
    {
        Http::fake([
            'accounts.salla.sa/oauth2/token' => Http::response([
                'access_token'  => 'NEW-ACCESS',
                'refresh_token' => 'NEW-REFRESH',
                'expires_in'    => 7200,
            ], 200),
        ]);

        $original = new CommerceConnectionDTO(
            id:              1,
            companyId:       1,
            providerCode:    'salla',
            connectionName:  'test',
            remoteStoreId:   '999',
            domain:          null,
            merchantId:      null,
            accessToken:     'OLD-ACCESS',
            refreshToken:    'OLD-REFRESH',
            tokenExpiresAt:  now()->subMinute()->toIso8601String(),
            apiKey:          null,
            apiSecret:       null,
            webhookSecret:   null,
        );

        $refreshed = $this->provider()->refreshAccessToken($original);

        $this->assertSame('NEW-ACCESS',  $refreshed->accessToken);
        $this->assertSame('NEW-REFRESH', $refreshed->refreshToken);
        $this->assertNotSame($original->tokenExpiresAt, $refreshed->tokenExpiresAt);

        // Verify the refresh_token grant was sent, not authorization_code
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->method() === 'POST'
                && str_contains($request->url(), '/oauth2/token')
                && ($data['grant_type']    ?? null) === 'refresh_token'
                && ($data['refresh_token'] ?? null) === 'OLD-REFRESH';
        });
    }

    /** @test */
    public function callback_rejects_missing_code(): void
    {
        $request = Request::create('/callback', 'GET', []);  // no code

        $this->expectException(\App\Commerce\Exceptions\ProviderRejectedRequestException::class);
        $this->expectExceptionMessage('Missing authorization code');

        $this->provider()->handleOAuthCallback($request);
    }

    /** @test */
    public function callback_surfaces_salla_error_param(): void
    {
        $request = Request::create('/callback', 'GET', [
            'error' => 'access_denied',
            'error_description' => 'The user denied the request.',
        ]);

        $this->expectException(\App\Commerce\Exceptions\ProviderRejectedRequestException::class);
        $this->expectExceptionMessage('access_denied');

        $this->provider()->handleOAuthCallback($request);
    }
}
