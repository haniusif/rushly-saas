<?php

namespace App\Commerce\Contracts;

/**
 * Pure marker (no methods). Declares that calling
 * CommerceProviderInterface::pushOrderUpdate() on this provider will not
 * throw ProviderUnavailableException for the "not supported" reason.
 *
 * Read-only sources (ERP CSV importer, archival feeds) skip this marker;
 * the FulfillmentService checks for it before attempting a writeback.
 */
interface SupportsOrderWriteback
{
}
