<?php

namespace App\Shipping\Providers\EcoExpress\Mappers;

/**
 * Local place names -> the state / city codes EcoExpress accepts.
 *
 * The two models do not line up, which is the whole reason this exists:
 *
 *   ours          theirs
 *   cities   ->   states     (Dubai, Abu Dhabi, Sharjah … i.e. emirates)
 *   areas    ->   cities     (Umm Ramool, Al Barsha … districts)
 *
 * So a parcel's CITY becomes their STATE, and a parcel's AREA becomes their
 * CITY. Sending our city as their city — which is what a naive field-for-field
 * mapping does — is what produced "Shipper city is not valid, Shipper state is
 * not valid, Consignee state is not valid".
 *
 * Our cities table already carries a `city_code`, and three of them disagree
 * with EcoExpress's spelling of the same emirate:
 *
 *   ours  DXD  ->  theirs  DXB   (Dubai)
 *   ours  AAN  ->  theirs  AIN   (Al Ain)
 *   ours  FJR  ->  theirs  FUJ   (Fujairah)
 *
 * AUH, SHJ, AJM, RAK and UAQ match already.
 *
 * Saudi cities have no city_code at all and no EcoExpress equivalent —
 * EcoExpress publishes UAE emirates only — so they resolve to null and the
 * caller refuses the shipment with a clear reason instead of posting a payload
 * that will be rejected on the wire.
 */
final class LocationMapper
{
    /** Where our city_code differs from EcoExpress's state code. */
    private const CODE_FIXUPS = [
        'DXD' => 'DXB',   // Dubai
        'AAN' => 'AIN',   // Al Ain
        'FJR' => 'FUJ',   // Fujairah
    ];

    /** Every state code EcoExpress accepts, from POST /states {"code":"UAE"}. */
    private const VALID_STATES = [
        'AUH', 'DXB', 'SHJ', 'AJM', 'AIN', 'UAQ', 'RAK', 'FUJ', 'FAD', 'UAE', 'OMK', 'DUQ',
    ];

    /**
     * Fallback for names we can resolve without a code — hubs, for instance,
     * are free text ("Dubai", "AbuDhabi") with no city_id to look up.
     */
    private const NAME_TO_STATE = [
        'dubai'          => 'DXB',
        'abudhabi'       => 'AUH',
        'abu dhabi'      => 'AUH',
        'sharjah'        => 'SHJ',
        'ajman'          => 'AJM',
        'alain'          => 'AIN',
        'al ain'         => 'AIN',
        'ummalquwain'    => 'UAQ',
        'umm al quwain'  => 'UAQ',
        'rasalkhaimah'   => 'RAK',
        'ras al khaimah' => 'RAK',
        'fujairah'       => 'FUJ',
    ];

    /**
     * Resolve an EcoExpress state code from a local city model, or from a
     * free-text place name when there is no city row (a hub).
     *
     * @param  object|null  $city  a Backend\City, or null
     */
    public static function stateCode(?object $city, ?string $fallbackName = null): ?string
    {
        if ($city) {
            $code = strtoupper(trim((string) ($city->city_code ?? '')));
            $code = self::CODE_FIXUPS[$code] ?? $code;

            if ($code !== '' && in_array($code, self::VALID_STATES, true)) {
                return $code;
            }

            // No usable code — try the name before giving up.
            $byName = self::fromName((string) ($city->en_name ?? $city->name ?? ''));
            if ($byName) {
                return $byName;
            }
        }

        return $fallbackName ? self::fromName($fallbackName) : null;
    }

    /**
     * Their CITY is our AREA. Sent by name: /cities returns names alongside
     * codes, and the create endpoint matches on the name, as the working test
     * shipment ("UMM RAMOOL") confirmed.
     */
    public static function cityName(?object $area, ?string $fallback = null): ?string
    {
        if ($area) {
            $name = trim((string) ($area->en_name ?? $area->name ?? ''));
            if ($name !== '') {
                return strtoupper($name);
            }
        }

        return $fallback ? strtoupper(trim($fallback)) : null;
    }

    private static function fromName(string $name): ?string
    {
        $key = strtolower(trim($name));
        if ($key === '') {
            return null;
        }

        if (isset(self::NAME_TO_STATE[$key])) {
            return self::NAME_TO_STATE[$key];
        }

        // Tolerate spacing differences ("AbuDhabi" vs "Abu Dhabi").
        $squashed = preg_replace('/\s+/', '', $key) ?? $key;
        foreach (self::NAME_TO_STATE as $candidate => $code) {
            if ((preg_replace('/\s+/', '', $candidate) ?? $candidate) === $squashed) {
                return $code;
            }
        }

        return null;
    }
}
