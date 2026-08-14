<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\AccountHeads;
use App\Http\Controllers\Controller;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class StatementsController extends Controller
{
    public function index()
    {
        return $this->respond(new Request(), null);
    }

    public function filter(Request $request)
    {
        $parcelID = $request->parcel_tracking_id
            ? Parcel::where('tracking_id', $request->parcel_tracking_id)->first()
            : null;
        return $this->respond($request, $parcelID);
    }

    private function respond(Request $request, $parcelID)
    {
        $merchantId = auth()->user()->merchant->id;
        $paginator  = MerchantStatement::where('merchant_id', $merchantId)
            ->orderByDesc('id')
            ->where(function ($query) use ($request, $parcelID) {
                if ($request->date) {
                    $date = explode('To', $request->date);
                    if (is_array($date) && count($date) === 2) {
                        $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                        $to   = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                        $query->whereBetween('created_at', [$from, $to]);
                    }
                }
                if ($request->type) {
                    $query->where('type', $request->type);
                }
                if (!blank($parcelID)) {
                    $query->where(['parcel_id' => $parcelID->id]);
                }
                if ($request->parcel_tracking_id && blank($parcelID)) {
                    $query->where(['parcel_id' => 0]);
                }
            })
            ->paginate(10)
            ->withQueryString();

        $i = (($paginator->currentPage() - 1) * $paginator->perPage()) + 1;

        $rows = collect($paginator->items())->map(function ($s) use (&$i) {
            return [
                'serial'   => $i++,
                'id'       => $s->id,
                'note'     => $s->note,
                'date'     => $s->date ? dateFormat($s->date) : null,
                'type'     => (int) $s->type,
                'type_label' => $s->type == AccountHeads::INCOME
                    ? (__('AccountHeads.' . AccountHeads::INCOME) ?: 'Income')
                    : (__('AccountHeads.' . AccountHeads::EXPENSE) ?: 'Expense'),
                'is_income'=> (int) $s->type === AccountHeads::INCOME,
                'amount'   => (float) ($s->amount ?? 0),
            ];
        })->values();

        return Inertia::render('Merchant/Accounts/Statements/Index', [
            'rows'       => $rows,
            'currency'   => settings()->currency,
            'filters'    => [
                'date'                => $request->date,
                'type'                => $request->type,
                'parcel_tracking_id'  => $request->parcel_tracking_id,
            ],
            'lookups'    => [
                'types' => [
                    ['value' => (string) AccountHeads::INCOME,  'label' => __('AccountHeads.' . AccountHeads::INCOME) ?: 'Income'],
                    ['value' => (string) AccountHeads::EXPENSE, 'label' => __('AccountHeads.' . AccountHeads::EXPENSE) ?: 'Expense'],
                ],
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => $paginator->linkCollection()->map(fn ($l) => [
                    'url'    => $l['url'],
                    'label'  => $l['label'],
                    'active' => (bool) $l['active'],
                ])->values(),
            ],
            'urls' => [
                'filter' => route('merchant.accounts.statements.filter'),
                'reset'  => route('merchant.accounts.statements.index'),
            ],
            't' => [
                'title'         => __('menus.statements') ?: 'Statements',
                'list'          => __('levels.list') ?: 'List',
                'dashboard'     => __('levels.dashboard') ?: 'Dashboard',
                'id'            => __('levels.id') ?: 'ID',
                'details'       => __('statements.details') ?: 'Details',
                'date_label'    => __('statements.date') ?: 'Date',
                'amount'        => __('statements.amount') ?: 'Amount',
                'type'          => __('levels.type') ?: 'Type',
                'type_ph'       => __('merchantPlaceholder.type') ?: 'All types',
                'date_filter'   => __('parcel.date') ?: 'Date',
                'date_ph'       => __('merchantPlaceholder.date') ?: 'YYYY-MM-DD To YYYY-MM-DD',
                'tracking_id'   => __('levels.track_id') ?: 'Tracking ID',
                'tracking_ph'   => __('merchantPlaceholder.tracking_id') ?: 'Tracking ID',
                'filter'        => __('levels.filter') ?: 'Filter',
                'clear'         => __('levels.clear') ?: 'Clear',
                'empty'         => __('levels.no_data_found') ?: 'No statements found.',
            ],
        ]);
    }
}
