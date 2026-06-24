<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Account\StoreRequest;
use App\Http\Requests\Account\UpdateRequest;
use App\Repositories\Account\AccountInterface;
use Brian2694\Toastr\Facades\Toastr;
class AccountController extends Controller
{
    protected $repo;
    public function __construct(AccountInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        return $this->renderIndex($this->repo->all(), $request);
    }
    public function filter(Request $request)
    {
        return $this->renderIndex($this->repo->filter($request), $request);
    }

    private function renderIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(fn ($a) => [
            'id'                  => $a->id,
            'gateway'             => $a->gateway,
            'bank'                => $a->bank,
            'branch_name'         => $a->branch_name,
            'account_holder_name' => $a->account_holder_name,
            'account_no'          => $a->account_no,
            'balance'             => (float) ($a->balance ?? 0),
            'opening_balance'     => (float) ($a->opening_balance ?? 0),
            'urls' => [
                'edit'   => route('accounts.edit', $a->id),
                'view'   => route('accounts.view', $a->id),
                'delete' => route('accounts.delete', $a->id),
            ],
        ])->values();

        return \Inertia\Inertia::render('Admin/Account/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters' => [
                'holder_name' => (string) ($request->holder_name ?? ''),
                'account_no'  => (string) ($request->account_no ?? ''),
                'bank'        => (string) ($request->bank ?? ''),
            ],
            'currency' => settings()->currency,
            'permissions' => [
                'create' => hasPermission('account_create'),
                'update' => hasPermission('account_update'),
                'delete' => hasPermission('account_delete'),
            ],
            'urls' => [
                'index'  => route('accounts.index'),
                'filter' => route('accounts.filter'),
                'create' => route('accounts.create'),
            ],
            't' => [
                'title'       => __('account.title') ?: 'Accounts',
                'list'        => __('levels.list') ?: 'List',
                'add'         => __('levels.add') ?: 'Add',
                'edit'        => __('levels.edit') ?: 'Edit',
                'delete'      => __('levels.delete') ?: 'Delete',
                'view'        => __('levels.view') ?: 'View',
                'actions'     => __('levels.actions') ?: 'Actions',
                'bank'        => __('levels.bank') ?: 'Bank',
                'holder'      => __('levels.holder_name') ?: 'Holder name',
                'account_no'  => __('levels.account_no') ?: 'Account #',
                'balance'     => __('levels.balance') ?: 'Balance',
                'opening_balance' => __('levels.opening_balance') ?: 'Opening',
                'filter'      => __('levels.filter') ?: 'Filter',
                'clear'       => __('levels.clear') ?: 'Clear',
                'placeholder_holder' => __('placeholder.holder_name') ?: 'Holder name',
                'placeholder_acct'   => __('placeholder.account_no') ?: 'Account no',
                'placeholder_bank'   => __('placeholder.Bank_name') ?: 'Bank',
                'no_rows'     => 'No accounts yet.',
                'delete_confirm' => 'Delete this account?',
                'prev'        => 'Prev',
                'next'        => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('Admin/Account/Form', [
            'mode'   => 'create',
            'entity' => null,
            'lookups' => $this->formLookups(),
            'urls' => [
                'submit' => route('accounts.store'),
                'cancel' => route('accounts.index'),
            ],
            't' => $this->formLabels(__('account.create_account') ?: 'Create account'),
        ]);
    }

    private function formLookups(): array
    {
        $users = $this->repo->users();
        return [
            'users'    => collect($users)->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values(),
            'types'    => collect((array) config('rxcourier.account_type'))->map(fn ($v) => [
                'value' => (string) $v,
                'label' => trans('AccountType.' . $v) ?: (string) $v,
            ])->values(),
            'gateways' => [
                ['value' => '1', 'label' => 'Cash'],
                ['value' => '2', 'label' => 'Bank'],
                ['value' => '3', 'label' => 'bKash'],
                ['value' => '4', 'label' => 'Rocket'],
                ['value' => '5', 'label' => 'Nagad'],
            ],
            'banks' => [
                ['value' => '1', 'label' => 'BB'],
                ['value' => '2', 'label' => 'DBBL'],
                ['value' => '3', 'label' => 'IB'],
            ],
            'account_types' => [
                ['value' => '1', 'label' => __('merchant.title') ?: 'Merchant'],
                ['value' => '2', 'label' => __('placeholder.persional') ?: 'Personal'],
            ],
            'statuses' => collect((array) trans('status'))->map(fn ($v, $k) => [
                'value' => (string) $k,
                'label' => (string) $v,
            ])->values(),
        ];
    }

    private function formLabels(string $title): array
    {
        return [
            'title'        => $title,
            'list_title'   => __('account.title') ?: 'Accounts',
            'type'         => __('levels.type') ?: 'Type',
            'user'         => __('levels.user') ?: 'User',
            'gateway'      => __('levels.gateway') ?: 'Gateway',
            'opening_balance'      => __('levels.opening_balance') ?: 'Opening balance',
            'account_holder_name'  => __('levels.account_holder_name') ?: 'Account holder name',
            'account_no'   => __('levels.account_no') ?: 'Account number',
            'bank'         => __('levels.bank') ?: 'Bank',
            'branch_name'  => __('levels.branch_name') ?: 'Branch',
            'mobile'       => __('levels.mobile') ?: 'Mobile',
            'account_type' => __('levels.account_type') ?: 'Account type',
            'status'       => __('levels.status') ?: 'Status',
            'select'       => __('menus.select') ?: 'Select',
            'save'         => __('levels.save') ?: 'Save',
            'cancel'       => __('levels.cancel') ?: 'Cancel',
            'back'         => __('levels.back') ?: 'Back',
            'placeholder_balance'   => __('placeholder.Opening_Balance') ?: 'Opening balance',
            'placeholder_holder'    => __('placeholder.Account_Holder_Name') ?: 'Account holder name',
            'placeholder_account_no'=> __('placeholder.Enter_account_no') ?: 'Enter account no',
            'placeholder_branch'    => __('placeholder.Enter_branch_name') ?: 'Enter branch name',
            'placeholder_opening'   => __('placeholder.Enter_opening_balance') ?: 'Enter opening balance',
            'placeholder_mobile'    => __('placeholder.Enter_mobile') ?: 'Enter mobile',
            'gateway_help'          => 'Pick a gateway first — the rest of the form adjusts to its required fields.',
        ];
    }

    public function store(StoreRequest $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('account.added_msg_'),__('message.success'));
            return redirect()->route('accounts.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $users = $this->repo->users();
        $account = $this->repo->get($id);
        return view('backend.account.edit',compact('account','users'));
    }

    public function update($id, UpdateRequest $request)
    {
        if($this->repo->update($id, $request)){
            Toastr::success(__('account.update_msg'),__('message.success'));
            return redirect()->route('accounts.index');
        }else{
            Toastr::error(__('account.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('account.delete_msg'),__('message.success'));
        return back();
    }

    public function currentBalance(Request $data)
    {
        return $this->repo->currentBalance($data);
    }


}
