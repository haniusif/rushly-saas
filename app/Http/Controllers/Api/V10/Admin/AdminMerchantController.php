<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMerchantController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $query = Merchant::query()->with('user')->latest();

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('business_name', 'like', "%$q%")
                  ->orWhere('unique_id', 'like', "%$q%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%$q%")
                                                   ->orWhere('mobile', 'like', "%$q%"));
            });
        }
        if (!is_null($status = $request->query('status'))) {
            $query->whereHas('user', fn ($u) => $u->where('status', (int) $status));
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $merchants = $query->paginate($per);

        return $this->responseWithSuccess('admin.merchants', [
            'merchants' => $merchants->through(fn ($m) => $this->transform($m)),
        ], 200);
    }

    public function show($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $merchant = Merchant::with('user')->findOrFail($id);

        $totals = Parcel::where('merchant_id', $merchant->id)
            ->select(
                DB::raw('count(*) as parcels'),
                DB::raw('coalesce(sum(cash_collection),0) as cod_total'),
                DB::raw('coalesce(sum(current_payable),0) as payable_total')
            )
            ->first();

        return $this->responseWithSuccess('admin.merchant', [
            'merchant' => $this->transform($merchant),
            'totals'   => [
                'parcels'       => (int) $totals->parcels,
                'cod_total'     => (float) $totals->cod_total,
                'payable_total' => (float) $totals->payable_total,
            ],
        ], 200);
    }

    public function toggleActive($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $merchant = Merchant::with('user')->findOrFail($id);
        if (!$merchant->user) {
            return $this->responseWithError('admin.merchant.no_user', [], 422);
        }

        $merchant->user->status = $merchant->user->status === 1 ? 0 : 1;
        $merchant->user->save();

        return $this->responseWithSuccess('admin.merchant.toggled', [
            'merchant_id' => $merchant->id,
            'status'      => (int) $merchant->user->status,
        ], 200);
    }

    /**
     * Onboarding queue: merchants whose applyStore submission is awaiting
     * admin review. `merchant.status = 0` is the pending marker set in
     * MerchantRepository::applyStore. Approved rows go to status = 1 and
     * fall out of this list; rejected rows go to status = 2 (also out).
     */
    public function pending(Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $query = Merchant::query()->with('user')
            ->where('status', 0)
            ->latest();

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('business_name', 'like', "%$q%")
                  ->orWhere('unique_id', 'like', "%$q%")
                  ->orWhere('cr_number', 'like', "%$q%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%$q%")
                                                   ->orWhere('mobile', 'like', "%$q%"));
            });
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $merchants = $query->paginate($per);

        return $this->responseWithSuccess('admin.merchants.pending', [
            'merchants' => $merchants->through(fn ($m) => $this->transformPending($m)),
        ], 200);
    }

    public function approve($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $merchant = Merchant::with('user')->findOrFail($id);
        if ((int) $merchant->status !== 0) {
            return $this->responseWithError('admin.merchant.not_pending', [
                'status' => (int) $merchant->status,
            ], 422);
        }
        if (!$merchant->user) {
            return $this->responseWithError('admin.merchant.no_user', [], 422);
        }

        DB::transaction(function () use ($merchant) {
            $merchant->status = 1;
            $merchant->save();

            $merchant->user->status = 1;
            $merchant->user->verification_status = 1;
            $merchant->user->save();
        });

        return $this->responseWithSuccess('admin.merchant.approved', [
            'merchant_id' => $merchant->id,
            'status'      => (int) $merchant->status,
        ], 200);
    }

    public function reject($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $merchant = Merchant::with('user')->findOrFail($id);
        if ((int) $merchant->status !== 0) {
            return $this->responseWithError('admin.merchant.not_pending', [
                'status' => (int) $merchant->status,
            ], 422);
        }

        $merchant->status = 2;
        $merchant->save();
        if ($merchant->user) {
            $merchant->user->status = 0;
            $merchant->user->save();
        }

        return $this->responseWithSuccess('admin.merchant.rejected', [
            'merchant_id' => $merchant->id,
            'status'      => (int) $merchant->status,
        ], 200);
    }

    private function transform(Merchant $m): array
    {
        return [
            'id'            => $m->id,
            'unique_id'     => $m->unique_id,
            'business_name' => $m->business_name,
            'address'       => $m->address,
            'user' => [
                'id'     => optional($m->user)->id,
                'name'   => optional($m->user)->name,
                'email'  => optional($m->user)->email,
                'phone'  => (string) optional($m->user)->mobile,
                'status' => (int) optional($m->user)->status,
            ],
        ];
    }

    /**
     * Extends transform() with the KYC payload the review screen needs.
     */
    private function transformPending(Merchant $m): array
    {
        return array_merge($this->transform($m), [
            'created_at'                  => optional($m->created_at)->toIso8601String(),
            'cr_number'                   => $m->cr_number,
            'cr_expiry'                   => optional($m->cr_expiry)->toDateString(),
            'tax_number'                  => $m->tax_number,
            'owner_id_number'             => $m->owner_id_number,
            'classification'              => $m->classification,
            'delivery_type'               => $m->delivery_type,
            'expected_daily_shipments'    => $m->expected_daily_shipments,
            'national_address_short_code' => $m->national_address_short_code,
            'iban'                        => $m->iban,
            'bank_name'                   => $m->bank_name,
            'swift_code'                  => $m->swift_code,
            'services'                    => $m->services ?? [],
            'files' => [
                'cr_file'               => $m->cr_file_url,
                'contract_file'         => $m->contract_file_url,
                'owner_id_file'         => $m->owner_id_file_url,
                'national_address_file' => $m->national_address_file_url,
                'iban_file'             => $m->iban_file_url,
            ],
        ]);
    }

    private function guardSuperOrAdmin($user): void
    {
        $type = (int) $user->user_type;
        if (!in_array($type, [UserType::ADMIN, UserType::SUPER_ADMIN], true)) {
            abort(403, 'Hub roles cannot manage merchants');
        }
    }
}
