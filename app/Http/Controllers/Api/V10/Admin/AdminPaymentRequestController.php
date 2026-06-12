<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Payment;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin view of merchant payment requests.
 *
 * The existing schema doesn't have a dedicated status column — a "request"
 * is considered pending while `transaction_id` is null. We treat that as
 * the approval gate:
 *   - approve: fill transaction_id + from_account
 *   - reject:  delete and stamp the reason into description.
 */
class AdminPaymentRequestController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $query = Payment::query()
            ->with(['merchant.user', 'merchantAccount'])
            ->latest();

        $filter = $request->query('filter', 'pending');
        match ($filter) {
            'approved' => $query->whereNotNull('transaction_id'),
            'pending'  => $query->whereNull('transaction_id'),
            default    => null,
        };

        if ($q = $request->query('q')) {
            $query->whereHas('merchant', fn ($m) =>
                $m->where('business_name', 'like', "%$q%")
                  ->orWhere('unique_id', 'like', "%$q%")
            );
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $rows = $query->paginate($per);

        return $this->responseWithSuccess('admin.payment_requests', [
            'payment_requests' => $rows->through(fn ($p) => $this->transform($p)),
        ], 200);
    }

    public function approve($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:120',
            'from_account'   => 'required|integer|exists:accounts,id',
            'description'    => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.payment_request.approve', ['message' => $validator->errors()], 422);
        }

        $payment = Payment::findOrFail($id);
        if (!is_null($payment->transaction_id)) {
            return $this->responseWithError('admin.payment_request.already_approved', [], 409);
        }

        $payment->transaction_id = $request->transaction_id;
        $payment->from_account   = (int) $request->from_account;
        if ($request->filled('description')) {
            $payment->description = $request->description;
        }
        $payment->save();

        return $this->responseWithSuccess('admin.payment_request.approved', [
            'id'             => $payment->id,
            'transaction_id' => $payment->transaction_id,
        ], 200);
    }

    public function reject($id, Request $request)
    {
        $this->guardSuperOrAdmin($request->user());

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.payment_request.reject', ['message' => $validator->errors()], 422);
        }

        $payment = Payment::findOrFail($id);
        if (!is_null($payment->transaction_id)) {
            return $this->responseWithError('admin.payment_request.already_approved', [], 409);
        }

        // Preserve the rejection reason on the activity log via the model's
        // logged description field, then remove the row.
        $payment->description = trim(($payment->description ?? '') . "\n[REJECTED] " . $request->reason);
        $payment->save();
        $payment->delete();

        return $this->responseWithSuccess('admin.payment_request.rejected', [
            'id' => (int) $id,
        ], 200);
    }

    private function transform(Payment $p): array
    {
        return [
            'id'             => $p->id,
            'merchant_id'    => $p->merchant_id,
            'merchant_name'  => optional($p->merchant)->business_name,
            'amount'         => (float) $p->amount,
            'transaction_id' => $p->transaction_id,
            'status'         => $p->transaction_id ? 'approved' : 'pending',
            'description'    => $p->description,
            'account'        => $p->merchantAccount ? [
                'id'           => $p->merchantAccount->id,
                'method'       => $p->merchantAccount->payment_method,
                'bank'         => $p->merchantAccount->bank_name,
                'holder'       => $p->merchantAccount->holder_name,
                'account_no'   => $p->merchantAccount->account_no,
            ] : null,
            'created_at'     => optional($p->created_at)->toIso8601String(),
        ];
    }

    private function guardSuperOrAdmin($user): void
    {
        $type = (int) $user->user_type;
        if (!in_array($type, [UserType::ADMIN, UserType::SUPER_ADMIN], true)) {
            abort(403, 'Hub roles cannot manage payment requests');
        }
    }
}
