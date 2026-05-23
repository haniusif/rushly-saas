<?php
namespace App\Enums;

interface NdrFailureReason
{
    const CUSTOMER_ABSENT     = 'customer_absent';
    const WRONG_ADDRESS       = 'wrong_address';
    const REFUSED_DELIVERY    = 'refused_delivery';
    const CUSTOMER_POSTPONED  = 'customer_postponed';
    const ACCESS_DENIED       = 'access_denied';
    const PAYMENT_ISSUE       = 'payment_issue';
    const DAMAGED_SHIPMENT    = 'damaged_shipment';
    const INCOMPLETE_ADDRESS  = 'incomplete_address';
    const OTHER               = 'other';
}
