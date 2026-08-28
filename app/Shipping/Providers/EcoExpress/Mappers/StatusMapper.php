<?php

namespace App\Shipping\Providers\EcoExpress\Mappers;

use App\Enums\ParcelStatus;

/**
 * EcoExpress status code -> local ParcelStatus.
 *
 * EcoExpress publishes ~200 status codes. They are far more granular than our
 * lifecycle: a dozen of theirs ("mobile switched off", "bad address", "office
 * closed", "language barrier") are all *reasons a delivery attempt failed*,
 * which for us is a single state. Mapping is therefore many-to-one and lossy
 * by design — the specific reason is preserved in TrackingDTO::$description
 * and the raw payload, not in the status.
 *
 * Codes are matched on the SHORT CODE (status_desc, e.g. "OFD"), because the
 * numeric ids are the ones most likely to be renumbered between their
 * environments. Where a short code is ambiguous — EcoExpress reuses "STA",
 * "RTC", "SC", "RTL", "RTA", "RTC", "WRN", "MSO", "CNNR" and others for two
 * different states — the mapping picks the reading that is safe to act on,
 * and the ambiguity is noted inline.
 *
 * Anything unmapped returns null. Callers treat null as "record the event,
 * leave the local status alone" rather than guessing, so an unrecognised code
 * can never silently move a shipment into a wrong state.
 */
final class StatusMapper
{
    /**
     * Terminal-ish and in-flight states we actively mirror.
     *
     * @var array<string, int>
     */
    private const MAP = [
        // ---- created / accepted -------------------------------------------
        'SIR'   => ParcelStatus::PENDING,              // shipment information received
        'SDE'   => ParcelStatus::PENDING,              // shipment data edited

        // ---- collected from shipper ---------------------------------------
        'SC1'   => ParcelStatus::RECEIVED_WAREHOUSE,   // picked up by rider
        'SAH'   => ParcelStatus::RECEIVED_WAREHOUSE,   // arrived at hub facility
        'RAO'   => ParcelStatus::RECEIVED_WAREHOUSE,   // received at operations
        'SINT'  => ParcelStatus::RECEIVED_WAREHOUSE,   // arrived at intl hub facility
        'ARF'   => ParcelStatus::RECEIVED_WAREHOUSE,   // arrived at airport facility

        // ---- in transit ----------------------------------------------------
        'SIT'   => ParcelStatus::TRANSFER_TO_HUB,      // in transit
        'SAT'   => ParcelStatus::TRANSFER_TO_HUB,      // added to transit manifest
        'STC'   => ParcelStatus::TRANSFER_TO_HUB,      // transit manifest closed
        'STA'   => ParcelStatus::TRANSFER_TO_HUB,      // AMBIGUOUS: EcoExpress uses STA for both
                                                       // "manifest arrived at destination" (27) and
                                                       // "manifest assigned to rider" (40). Both are
                                                       // in-transit for us, so the collision is harmless.
        'SAD'   => ParcelStatus::RECEIVED_BY_HUB,      // arrived at delivery facility
        'SFD'   => ParcelStatus::RECEIVED_BY_HUB,      // sorted for delivery
        'STMH'  => ParcelStatus::TRANSFER_TO_HUB,      // manifest handed to rider
        'STMB'  => ParcelStatus::TRANSFER_TO_HUB,      // manifest handed to branch
        'HOA'   => ParcelStatus::TRANSFER_TO_HUB,      // handed over to airlines

        // ---- out for delivery ----------------------------------------------
        'ATD'   => ParcelStatus::DELIVERY_MAN_ASSIGN,  // assigned to rider
        'OFD'   => ParcelStatus::DELIVERY_MAN_ASSIGN,  // out for delivery
        'OFR'   => ParcelStatus::DELIVERY_MAN_ASSIGN,  // out for reception delivery
        'DSMC'  => ParcelStatus::DELIVERY_MAN_ASSIGN,  // delivery manifest created

        // ---- delivered -------------------------------------------------------
        'DYD'   => ParcelStatus::DELIVERED,
        'S2'    => ParcelStatus::DELIVERED,            // delivered by receptionist
        'SC'    => ParcelStatus::DELIVERED,            // AMBIGUOUS: "self collection" (36 and 63).
                                                       // The consignee took the parcel, so delivered.

        // ---- failed attempt / rescheduled ------------------------------------
        // Everything here is one delivery attempt that did not complete. The
        // reason survives in the event description, not in the status.
        'CNA'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // consignee not available
        'MSO'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // mobile switched off
        'NRC'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // no response
        'BDA'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // bad address
        'MNW'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // number not reachable
        'CNNR'  => ParcelStatus::DELIVERY_RE_SCHEDULE, // contact number not reachable
        'WRN'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // wrong number
        'CNR'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // COD not ready
        'FD'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // future delivery
        'FDR'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // future delivery request
        'LC'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // location change
        'MR'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // miss route
        'FRA'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // far area
        'FARC'  => ParcelStatus::DELIVERY_RE_SCHEDULE, // far area scheduled
        'OFC'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // office closed
        'DOFC'  => ParcelStatus::DELIVERY_RE_SCHEDULE,
        'VB'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // vehicle breakdown
        'CCT'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // consignee out of country
        'CNOC'  => ParcelStatus::DELIVERY_RE_SCHEDULE,
        'BDW'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // bad weather
        'AWC'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // adverse weather
        'BWO'   => ParcelStatus::DELIVERY_RE_SCHEDULE,
        'D2'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // agent rejected the delivery
        'R2'    => ParcelStatus::DELIVERY_RE_SCHEDULE, // returned to hub after failed attempt
        'DSC'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // delivery scheduled
        'CDS'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // customer delivery schedule
        'CSS'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // customer self schedule
        'RCT'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // come tomorrow
        'IAD'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // incomplete address
        'UNC'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // uncontactable
        'DUNC'  => ParcelStatus::DELIVERY_RE_SCHEDULE,
        'SOH'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // shipment on hold
        'MAT'   => ParcelStatus::DELIVERY_RE_SCHEDULE, // maximum attempts

        // ---- returning to shipper --------------------------------------------
        'RFR'   => ParcelStatus::RETURN_TO_COURIER,    // ready for RTO
        'OFRT'  => ParcelStatus::RETURN_TO_COURIER,    // out for RTO
        'RIP'   => ParcelStatus::RETURN_TO_COURIER,    // return in progress
        'RBB'   => ParcelStatus::RETURN_TO_COURIER,    // RTO in transit
        'RAD'   => ParcelStatus::RETURN_TO_COURIER,    // RTO assigned to rider
        'RSR'   => ParcelStatus::RETURN_TO_COURIER,    // RTO returned to hub
        'D1'    => ParcelStatus::RETURN_TO_COURIER,    // rider collected return shipment
        'R1'    => ParcelStatus::RETURN_TO_COURIER,    // reception collected return shipment
        'CS'    => ParcelStatus::RETURN_TO_COURIER,    // RTS collected

        // ---- returned to shipper (terminal) ----------------------------------
        'RTO'   => ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,

        // ---- cancelled / lost (terminal) -------------------------------------
        'SCB'   => ParcelStatus::CANCELLED,            // cancelled by user
        'CSC'   => ParcelStatus::CANCELLED,            // customer request to cancel
        'CRTC'  => ParcelStatus::CANCELLED,
        'CNC'   => ParcelStatus::CANCELLED,            // consignee request to cancel
        'SRC'   => ParcelStatus::CANCELLED,            // shipper request to cancel
        'INVS'  => ParcelStatus::CANCELLED,            // invalid shipment
        'SL'    => ParcelStatus::CANCELLED,            // shipment lost — terminal, but NOT the
                                                       // same thing as cancelled. Flagged: there is
                                                       // no local "lost" state, so it lands here and
                                                       // the reason is only visible in the event note.
    ];

    /**
     * Codes we deliberately do NOT map. They are informational — customs
     * milestones, WhatsApp notification receipts, internal bank/passport-room
     * movements, CS call logs — and moving the local status on them would be
     * wrong. Listed explicitly so the "unknown code" logging stays meaningful.
     *
     * @var string[]
     */
    private const INFORMATIONAL = [
        'NOT', 'CCS', 'ISU', 'OTP', 'C1', 'C2', 'CSX', 'CSN', 'CTNR', 'SNR', 'SCR',
        'WIM', 'WOM', 'WDM', 'DVST', 'SHR', 'CUD', 'QFB', 'BR',
        'CCI', 'CRL', 'UCC', 'CH', 'CHC', 'CHM', 'CHP', 'SRCC', 'CDCU', 'CHNA',
        'RBH', 'OBH', 'BHO', 'BHR', 'RCB', 'BHI', 'BHM', 'PTO', 'PSTR', 'OPP',
        'MSMC', 'STMO', 'RSTO', 'RSTH', 'RSHB', 'SRT', 'RST', 'CRA',
    ];

    /**
     * @return int|null ParcelStatus constant, or null when the code is unknown
     *                  or deliberately informational.
     */
    public static function toLocal(?string $code): ?int
    {
        $key = strtoupper(trim((string) $code));
        if ($key === '') {
            return null;
        }

        return self::MAP[$key] ?? null;
    }

    /** True when the code is known but intentionally does not move our status. */
    public static function isInformational(?string $code): bool
    {
        return in_array(strtoupper(trim((string) $code)), self::INFORMATIONAL, true);
    }

    /** True when we neither map nor recognise the code — worth logging once. */
    public static function isUnknown(?string $code): bool
    {
        return self::toLocal($code) === null && ! self::isInformational($code);
    }
}
