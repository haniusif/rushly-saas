<?php

namespace App\Commerce\Contracts;

use App\Commerce\DTOs\CommerceConnectionDTO;
use Illuminate\Http\Request;

/**
 * Marker for providers that use OAuth 2.0 to authorize a storefront install
 * (Salla, Zid, Shopify). Providers without OAuth (WooCommerce plugin,
 * Custom REST) skip this interface entirely.
 *
 * The OAuth lifecycle has three operations that don't fit on the base
 * interface because they're meaningless for non-OAuth providers.
 */
interface SupportsOAuth
{
    /**
     * Build the provider's authorization URL for the embedded OAuth start
     * flow. The admin UI redirects the user here.
     */
    public function buildAuthorizationUrl(array $params): string;

    /**
     * Handle the provider's redirect back to us after the user authorized.
     * Returns a populated CommerceConnectionDTO with access_token /
     * refresh_token / token_expires_at + remote_store_id resolved.
     */
    public function handleOAuthCallback(Request $request): CommerceConnectionDTO;

    /**
     * Exchange the stored refresh_token for a fresh access_token. Returns
     * an updated CommerceConnectionDTO. Called by the scheduled refresh
     * job before token_expires_at lapses.
     */
    public function refreshAccessToken(CommerceConnectionDTO $connection): CommerceConnectionDTO;
}
