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
        $users = $this->repo->users();
        return view('backend.account.create',compact('users'));
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
