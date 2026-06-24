<?php

namespace App\Salla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Salla\Models\Merchant;
use App\Salla\Models\Settings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Salla\OAuth2\Client\Provider\Salla;
use Throwable;

class OAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $provider = $this->provider();
        $url = $provider->getAuthorizationUrl(['scope' => 'offline_access']);
        $request->session()->put('oauth2state', $provider->getState());

        Log::info('salla.oauth.redirect', [
            'state'        => $provider->getState(),
            'redirect_uri' => config('salla.oauth.redirect_uri'),
        ]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        Log::info('salla.oauth.callback.received', $request->query());

        if ($request->filled('error')) {
            return $this->fail('Salla returned an error', [
                'error'             => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ], 400);
        }

        // Easy Mode: the `app.store.authorize` webhook is authoritative for token
        // delivery. The browser callback is just a landing page — Salla may send
        // it with code+state (when initiated from a session) or with no params at
        // all (direct install from the app marketplace). Either way, skip the
        // CSRF/state check and route the user to the dashboard.
        if (config('salla.authorization_mode') === 'easy') {
            Log::info('salla.oauth.callback.easy_mode_landing', $request->query());
            $request->session()->forget('oauth2state');
            return redirect($this->landingUrl())->with('status',
                'Install received from Salla. Tokens are delivered via webhook — refresh in a moment if your store isn\'t connected yet.');
        }

        $sessionState = $request->session()->pull('oauth2state');

        if (! $request->filled('code') && ! $request->filled('error') && empty($request->query())) {
            Log::info('salla.oauth.callback.easy_mode_landing');
            return redirect($this->landingUrl())->with('status',
                'Install received from Salla (Easy Mode). Waiting for app.store.authorize webhook to deliver tokens — refresh in a moment.');
        }

        if (! $request->filled('code')) {
            return $this->fail('Missing authorization code from Salla', $request->query(), 400);
        }
        if ($sessionState === null) {
            return $this->fail('OAuth state cookie missing — session was lost between redirect and callback. Check that browser cookies are enabled and APP_URL matches the host you visited.', [], 400);
        }
        if (! hash_equals($sessionState, (string) $request->input('state'))) {
            return $this->fail('OAuth state mismatch — possible CSRF or stale session.', [
                'session_state' => $sessionState,
                'query_state'   => $request->input('state'),
            ], 400);
        }

        try {
            $token = $this->provider()->getAccessToken('authorization_code', [
                'code' => $request->input('code'),
            ]);
        } catch (IdentityProviderException $e) {
            return $this->fail('Token exchange rejected by Salla', [
                'response' => $e->getResponseBody(),
            ], 502);
        }

        try {
            $owner = $this->provider()->getResourceOwner($token);
            $data  = $owner->toArray();
        } catch (Throwable $e) {
            return $this->fail('Fetched token but failed to read merchant profile', [
                'message' => $e->getMessage(),
            ], 502);
        }

        $sallaMerchantId = $data['merchant']['id'] ?? $owner->getId();
        $merchant = Merchant::updateOrCreate(
            ['salla_merchant_id' => $sallaMerchantId],
            [
                'store_name'       => $data['merchant']['name'] ?? null,
                'store_domain'     => $data['merchant']['domain'] ?? null,
                'owner_email'      => $data['email'] ?? null,
                'access_token'     => $token->getToken(),
                'refresh_token'    => $token->getRefreshToken(),
                'token_expires_at' => $token->getExpires() ? Carbon::createFromTimestamp($token->getExpires()) : null,
                'installed'        => true,
                'uninstalled_at'   => null,
            ],
        );
        Settings::firstOrCreate(['salla_merchant_id' => $merchant->id]);

        Log::info('salla.oauth.callback.success', [
            'salla_merchant_id' => $sallaMerchantId,
            'store_name'        => $merchant->store_name,
        ]);

        return redirect($this->landingUrl())
            ->with('status', "Connected to {$merchant->store_name} (Salla ID {$sallaMerchantId})");
    }

    private function provider(): Salla
    {
        return new Salla([
            'clientId'     => config('salla.oauth.client_id'),
            'clientSecret' => config('salla.oauth.client_secret'),
            'redirectUri'  => config('salla.oauth.redirect_uri'),
        ]);
    }

    private function landingUrl(): string
    {
        return (string) (config('salla.landing_url') ?: url('/'));
    }

    private function fail(string $message, array $context, int $status)
    {
        Log::error('salla.oauth.callback.failed', ['message' => $message] + $context);
        abort($status, $message);
    }
}
