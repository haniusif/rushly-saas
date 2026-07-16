<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\CashReceivedFromDeliveryman;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Powers the "Hub cash" reconciliation flow on the admin mobile app.
 *
 * The web equivalent lives in `Backend/HubPanel/ReceivedFromDeliverymanController`.
 * We delegate write-side work to the same `ReceivedRepository` so both surfaces
 * hit the same balance / statement / bank-transaction plumbing.
 */
class AdminHubCashController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(private ReceivedInterface $repo) {}

    /**
     * Drivers under this user's hub with their current balance (negative
     * means the driver is holding uncollected COD for the hub).
     */
    public function drivers(Request $request)
    {
        $query = DeliveryMan::query()->with(['user:id,name,mobile,status', 'hub:id,name']);
        $this->applyHubScope($query, $request);

        $rows = $query->limit(500)->get();
        return $this->responseWithSuccess('admin.hub_cash.drivers', [
            'drivers' => $rows->map(fn (DeliveryMan $d) => [
                'id'              => $d->id,
                'name'            => optional($d->user)->name,
                'phone'           => (string) optional($d->user)->mobile,
                'hub_id'          => $d->hub_id,
                'hub_name'        => optional($d->hub)->name,
                'current_balance' => (float) ($d->current_balance ?? 0),
            ])->values(),
        ], 200);
    }

    /**
     * The caller's own bank/cash accounts, used as the deposit target.
     */
    public function accounts(Request $request)
    {
        $rows = Account::companywise()
            ->where('user_id', $request->user()->id)
            ->get();

        return $this->responseWithSuccess('admin.hub_cash.accounts', [
            'accounts' => $rows->map(fn (Account $a) => [
                'id'                  => $a->id,
                'account_holder_name' => $a->account_holder_name,
                'account_no'          => $a->account_no,
                'gateway'             => $a->gateway,
                'balance'             => (float) ($a->balance ?? 0),
            ])->values(),
        ], 200);
    }

    /**
     * Recent reconciliation entries. Hub-scoped for HUB/INCHARGE; admins
     * can pass `hub_id` to focus, otherwise sees all hubs.
     */
    public function index(Request $request)
    {
        $query = CashReceivedFromDeliveryman::query()
            ->with(['user:id,name', 'deliveryman.user:id,name', 'account:id,account_no,account_holder_name'])
            ->latest();

        $this->applyHubScope($query, $request);

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $entries = $query->paginate($per);

        return $this->responseWithSuccess('admin.hub_cash.index', [
            'entries' => $entries->through(fn ($e) => $this->transform($e)),
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (!in_array($type, [UserType::HUB, UserType::INCHARGE], true) || !$user->hub_id) {
            return $this->responseWithError('admin.hub_cash.hub_only', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'delivery_man_id' => 'required|integer|exists:delivery_man,id',
            'account_id'      => 'required|integer|exists:accounts,id',
            'amount'          => 'required|numeric|min:0.01',
            'date'            => 'nullable|date',
            'note'            => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.hub_cash.validation', ['message' => $validator->errors()], 422);
        }

        // Guard matches the web controller: driver must be holding at
        // least `amount` in outstanding COD (stored as a negative balance).
        $driver = DeliveryMan::find($request->delivery_man_id);
        if (!$driver || $driver->hub_id !== (int) $user->hub_id) {
            return $this->responseWithError('admin.hub_cash.wrong_hub', [], 403);
        }
        if ($driver->current_balance >= 0 || $driver->current_balance > -$request->amount) {
            return $this->responseWithError('admin.hub_cash.not_enough_balance', [
                'driver_balance' => (float) $driver->current_balance,
            ], 422);
        }

        // Ensure `date` defaults to today so the repo doesn't hit a null.
        if (!$request->has('date') || blank($request->date)) {
            $request->merge(['date' => now()->toDateString()]);
        }

        // Ensure `receipt` is at least null so the repo's `file('', ...)` call
        // (which uploads a fresh file when non-null) treats this as "no receipt".
        $request->merge(['receipt' => null]);

        $ok = $this->repo->store($request);
        if (!$ok) {
            return $this->responseWithError('admin.hub_cash.store_failed', [], 500);
        }

        return $this->responseWithSuccess('admin.hub_cash.stored', [], 200);
    }

    private function transform(CashReceivedFromDeliveryman $e): array
    {
        return [
            'id'              => $e->id,
            'amount'          => (float) $e->amount,
            'date'            => optional($e->date)->toDateString(),
            'note'            => $e->note,
            'hub_id'          => $e->hub_id,
            'delivery_man_id' => $e->delivery_man_id,
            'driver_name'     => optional(optional($e->deliveryman)->user)->name,
            'received_by'     => optional($e->user)->name,
            'account' => $e->account ? [
                'id'                  => $e->account->id,
                'account_no'          => $e->account->account_no,
                'account_holder_name' => $e->account->account_holder_name,
            ] : null,
            'created_at' => optional($e->created_at)->toIso8601String(),
        ];
    }

    private function applyHubScope($query, Request $request): void
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
            return;
        }
        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }
    }
}
