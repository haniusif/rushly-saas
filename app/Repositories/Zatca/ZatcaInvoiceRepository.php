<?php

namespace App\Repositories\Zatca;

use App\Enums\Zatca\ZatcaInvoiceStatus;
use App\Models\Backend\Zatca\ZatcaInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ZatcaInvoiceRepository implements ZatcaInvoiceRepositoryInterface
{
    public function paginate(Request $request, ?int $merchantId = null): LengthAwarePaginator
    {
        $q = ZatcaInvoice::query()->companywise();

        if ($merchantId) {
            $q->where('merchant_id', $merchantId);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('type')) {
            $q->where('invoice_type', $request->string('type'));
        }
        if ($request->filled('q')) {
            $needle = trim((string) $request->string('q'));
            $q->where(function ($w) use ($needle) {
                $w->where('invoice_number', 'like', "%{$needle}%")
                  ->orWhere('buyer_name', 'like', "%{$needle}%")
                  ->orWhere('uuid', 'like', "%{$needle}%");
            });
        }
        if ($request->filled('date_from')) {
            $q->whereDate('issued_at', '>=', $request->string('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('issued_at', '<=', $request->string('date_to'));
        }

        return $q->latest('issued_at')->paginate(20)->withQueryString();
    }

    public function stats(?int $merchantId = null): array
    {
        $base = ZatcaInvoice::query()->companywise();
        if ($merchantId) {
            $base->where('merchant_id', $merchantId);
        }

        return [
            'total'      => (int) (clone $base)->count(),
            'generated'  => (int) (clone $base)->where('status', ZatcaInvoiceStatus::Generated->value)->count(),
            'failed'     => (int) (clone $base)->where('status', ZatcaInvoiceStatus::Failed->value)->count(),
            'vat_amount' => (float) (clone $base)->where('status', '!=', ZatcaInvoiceStatus::Failed->value)->sum('vat_amount'),
        ];
    }

    public function findOrFail(int $id): ZatcaInvoice
    {
        return ZatcaInvoice::query()->companywise()->findOrFail($id);
    }
}
