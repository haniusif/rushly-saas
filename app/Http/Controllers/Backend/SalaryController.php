<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Salary\StoreRequest;
use App\Http\Requests\Salary\UpdateRequest;
use App\Models\Backend\Salary;
use App\Models\User;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Repositories\Account\AccountInterface;
use App\Repositories\Salary\SalaryInterface;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
class SalaryController extends Controller
{
    protected $repo;
    public function __construct(SalaryInterface $repo,AccountInterface $accounts)
    {
        $this->repo      = $repo;
        $this->accounts  = $accounts;
    }
    public function index(Request $request){
        return $this->renderIndex($this->repo->all(), $request);
    }

    public function salaryFilter(Request $request){
        return $this->renderIndex($this->repo->salaryFilter($request), $request);
    }

    private function renderIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(fn ($s) => [
            'id'           => $s->id,
            'user_name'    => optional($s->user)->name,
            'user_email'   => optional($s->user)->email,
            'user_image'   => optional($s->user)->image,
            'from_account' => optional($s->account)->account_holder_name,
            'month'        => (string) $s->month,
            'date'         => $s->date ? dateFormat($s->date) : null,
            'note'         => $s->note,
            'amount'       => (float) ($s->amount ?? 0),
            'urls' => [
                'edit'     => route('salary.edit', $s->id),
                'delete'   => route('salary.delete', $s->id),
                'pay_slip' => route('salary.pay.slip', $s->id),
            ],
        ])->values();

        return \Inertia\Inertia::render('Admin/Salary/Index', [
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
                'user_id' => (string) ($request->user_id ?? ''),
                'month'   => (string) ($request->month ?? date('Y-m')),
            ],
            'currency' => settings()->currency,
            'permissions' => [
                'create' => hasPermission('salary_create'),
                'update' => hasPermission('salary_update'),
                'delete' => hasPermission('salary_delete'),
            ],
            'urls' => [
                'index'       => route('salary.index'),
                'filter'      => route('salary.filter'),
                'create'      => route('salary.create'),
                'user_search' => route('salary.users'),
            ],
            't' => [
                'title'        => __('salary.title') ?: 'Salaries',
                'list'         => __('levels.list') ?: 'List',
                'add'          => __('levels.add') ?: 'Add',
                'edit'         => __('levels.edit') ?: 'Edit',
                'delete'       => __('levels.delete') ?: 'Delete',
                'actions'      => __('levels.actions') ?: 'Actions',
                'user'         => __('levels.user') ?: 'User',
                'from_account' => __('levels.from_account') ?: 'From account',
                'month'        => __('salary.month') ?: 'Month',
                'date'         => __('levels.date') ?: 'Date',
                'note'         => __('levels.note') ?: 'Note',
                'amount'       => __('levels.amount') ?: 'Amount',
                'pay_slip'     => __('salary.pay_slip') ?: 'Pay slip',
                'filter'       => __('levels.filter') ?: 'Filter',
                'clear'        => __('levels.clear') ?: 'Clear',
                'select_user'  => (__('menus.select') ?: 'Select') . ' ' . (__('levels.user') ?: 'user'),
                'no_rows'      => 'No salary entries yet.',
                'delete_confirm' => 'Delete this salary entry?',
                'prev'         => 'Prev',
                'next'         => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ]);
    }

    public function create(){
        $accounts = $this->accounts->all();
        // Mirror the legacy view's filter: only admin-type accounts; build a
        // user-friendly label per gateway.
        $accountOptions = collect($accounts->items() ?? $accounts)
            ->filter(fn ($a) => (int) $a->type === \App\Enums\AccountType::ADMIN)
            ->map(function ($a) {
                $label = match ((int) $a->gateway) {
                    1       => optional($a->user)->name . ' (Cash)',
                    3       => 'bKash · ' . $a->account_holder_name . ' (' . $a->account_no . ' ' . $a->mobile . ')',
                    4       => 'Rocket · ' . $a->account_holder_name . ' (' . $a->account_no . ' ' . $a->mobile . ')',
                    5       => 'Nagad · ' . $a->account_holder_name . ' (' . $a->account_no . ' ' . $a->mobile . ')',
                    default => $a->account_holder_name . ' (' . $a->account_no . ' ' . $a->branch_name . ')',
                };
                return [
                    'value'   => (string) $a->id,
                    'label'   => $label,
                    'balance' => (float) ($a->balance ?? 0),
                ];
            })->values();

        return \Inertia\Inertia::render('Admin/Salary/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => ['accounts' => $accountOptions],
            'currency' => settings()->currency,
            'urls' => [
                'submit'       => route('salary.store'),
                'cancel'       => route('salary.index'),
                'user_search'  => route('salary.users'),
            ],
            't' => [
                'title'       => (__('levels.create') ?: 'Create') . ' ' . (__('salary.title') ?: 'salary'),
                'list_title'  => __('salary.title') ?: 'Salaries',
                'month'       => __('salary.month') ?: 'Month',
                'user'        => __('levels.user') ?: 'User',
                'date'        => __('levels.date') ?: 'Date',
                'from_account'=> __('levels.from_account') ?: 'From account',
                'amount'      => __('levels.amount') ?: 'Amount',
                'note'        => __('levels.note') ?: 'Note',
                'select_user' => (__('menus.select') ?: 'Select') . ' ' . (__('user.title') ?: 'user'),
                'select_account' => (__('menus.select') ?: 'Select') . ' ' . (__('levels.from_account') ?: 'account'),
                'balance_label'  => 'Account balance',
                'not_enough_balance' => __('salary.not_enough_balance') ?: 'Amount exceeds account balance.',
                'placeholder_amount' => __('placeholder.Enter_Amount') ?: 'Enter amount',
                'placeholder_desc'   => __('placeholder.Enter_description') ?: 'Note',
                'save'        => __('levels.save') ?: 'Save',
                'cancel'      => __('levels.cancel') ?: 'Cancel',
                'back'        => __('levels.back') ?: 'Back',
            ],
        ]);
    }

    public function store(StoreRequest $request){

        if((double) $request->amount > (double) $request->account_balance){
            Toastr::warning(__('salary.not_enough_balance'),__('message.warning'));
            return back()->withInput();
        }
        $salary = $this->repo->store($request);
        if($salary):
            Toastr::success('Salary successfully paid.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }
    public function edit($id){
        $singleSalary   = $this->repo->edit($id);
        $accounts       = $this->accounts->all();
        return view('backend.salary.edit',compact('singleSalary','accounts'));
    }
    public function update(UpdateRequest $request){
        $salary    = Salary::find($request->id);
        $account  = $this->accounts->get($salary->account_id);
        $total_balance = $account->balance+$salary->amount;
         if((double) $total_balance < (double) $request->amount):
            Toastr::warning(__('salary.not_enough_balance'),__('message.warning'));
            return back()->withInput();
         endif;
        if($this->repo->update($request->id,$request)):
            Toastr::success('Salary successfully updated paid.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }
    public function delete($id){

        if($this->repo->delete($id)):
            Toastr::success('Salary successfully deleted.',__('message.success'));
            return redirect()->route('salary.index');
        else:
            Toastr::error('Something went wrong.',__('message.error'));
            return redirect()->back();
        endif;
    }

    public function salaryGet(Request $request){
        $salaryAmount = SalaryGenerate::where('user_id',$request->user_id)->where('month',$request->month)->first();

        if($salaryAmount):
           $salary  = $salaryAmount->amount;
        else:
           $salary  = 0;
        endif;
        return $salary;
    }

    public function paySlip($id){
        $salary      = $this->repo->get($id);
        $month_salary=$this->repo->monthSalary($salary);

        return view('backend.salary.pay_slip',compact('salary','month_salary'));
    }

    public function Users(Request $request){
        if($request->ajax()):
            $users = User::companywise()->where('name','like','%'.$request->search.'%')->whereNot('user_type',UserType::MERCHANT)->paginate(10);
            $response = [];
            foreach ($users as  $user) {
                $response [] = [
                    'id'  => $user->id,
                    'text'=> $user->name
                ];
            }
            return response()->json($response);
        endif;
    }
}
