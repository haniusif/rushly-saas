<?php

namespace App\Shipping\Providers\EcoExpress\Mappers;

use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\DTOs\ShipmentDTO;

/**
 * ShipmentDTO -> the object EcoExpress expects inside `data[]`.
 *
 * Field names follow /order/shipment/create as published. Note `account_No`
 * here versus `accountNo` on /services and /product — that inconsistency is
 * EcoExpress's, confirmed against the live sandbox, not a typo.
 */
final class ShipmentRequestMapper
{
    /** UAE is the only origin the account is provisioned for. */
    private const DEFAULT_COUNTRY = 'UNITED ARAB EMIRATES';

    public static function map(ConnectionDTO $c, ShipmentDTO $s, string $accountNo): array
    {
        $sender    = $s->sender;
        $recipient = $s->recipient;

        // Service and product default to the only pair the sandbox account has
        // enabled (NDD / non-document). Both are overridable per shipment via
        // ShipmentDTO::$extra so a tenant with more services enabled can use
        // them without a code change.
        $service = (string) ($s->extra['service_type'] ?? $c->setting('service_type', 'NDD'));
        $product = (string) ($s->extra['product_type'] ?? $c->setting('product_type', 'NDOC'));

        return [
            'account_No'          => $accountNo,
            'service_type'        => $service,
            'product_type'        => $product,
            'reference_number'    => $s->referenceNumber,

            'cod'                 => round((float) $s->codAmount, 2),
            'cod_currency'        => $s->currency ?: 'AED',
            'special_instruction' => (string) ($s->extra['special_instruction'] ?? ''),

            // EcoExpress validates invoice_value against COD for dutiable
            // lanes. Falling back to the COD amount keeps a domestic NDD
            // shipment consistent rather than declaring a zero value.
            'invoice_value'       => round((float) ($s->extra['invoice_value'] ?? $s->codAmount), 2),
            'invoice_currency'    => $s->currency ?: 'AED',
            'invoice_date'        => (string) ($s->extra['invoice_date'] ?? now()->format('Y-m-d')),

            'shipper_details'     => self::party($sender, $s->extra['shipper_company'] ?? null),
            'consignee_details'   => self::party($recipient, $s->extra['consignee_company'] ?? null),

            'package_details'     => [[
                'description' => $s->description ?: 'Parcel',
                'quantity'    => max(1, (int) $s->quantity),
                'weight'      => max(0.1, (float) $s->weight),
            ]],

            // Only meaningful on EXP/IMP lanes. Sent empty for domestic rather
            // than fabricating HS codes we do not hold.
            'invoice_items'       => $s->extra['invoice_items'] ?? [],
        ];
    }

    /**
     * EcoExpress wants country/state/city as NAMES, not ISO codes — /countries
     * returns "UNITED ARAB EMIRATES" with code "UAE", and the create endpoint
     * matches on the name.
     */
    private static function party($addr, ?string $companyName): array
    {
        return [
            'company_name' => (string) ($companyName ?? $addr->name ?? ''),
            'name'         => (string) ($addr->name ?? ''),
            'address'      => trim(implode(', ', array_filter([$addr->line1 ?? null, $addr->line2 ?? null]))),
            'country'      => (string) ($addr->country ?: self::DEFAULT_COUNTRY),
            'state'        => (string) ($addr->region ?? ''),
            'city'         => (string) ($addr->city ?? ''),
            'mobile_no_1'  => self::phone($addr->phone ?? ''),
            'mobile_no_2'  => '',
        ];
    }

    /**
     * Their examples carry local UAE numbers with no country code and no
     * punctuation ("526928838"), so strip formatting and drop a leading 971 or
     * 00971 rather than sending a shape their validator has not been shown.
     */
    private static function phone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $digits = preg_replace('/^(00971|971)/', '', $digits) ?? $digits;

        return ltrim($digits, '0');
    }
}
