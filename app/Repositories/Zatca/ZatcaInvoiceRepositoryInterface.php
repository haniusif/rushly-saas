<?php

namespace App\Repositories\Zatca;

use App\Models\Backend\Zatca\ZatcaInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface ZatcaInvoiceRepositoryInterface
{
    public function paginate(Request $request, ?int $merchantId = null): LengthAwarePaginator;

    public function stats(?int $merchantId = null): array;

    public function findOrFail(int $id): ZatcaInvoice;
}
