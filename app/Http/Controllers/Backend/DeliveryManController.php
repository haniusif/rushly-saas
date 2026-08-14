<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use Illuminate\Http\Request;
use App\Http\Requests\DeliveryMan\DeliveryManRequest;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Nationality;
use App\Models\Backend\OperationalArea;
use App\Models\Backend\SupplierCompany;
use App\Models\User;
use App\Enums\UserType;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;
class DeliveryManController extends Controller
{
    protected $repo;
    public function __construct(DeliveryManInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $paginator = $this->repo->all();
        return $this->renderIndex($paginator, $request);
    }
    public function filter(Request $request)
    {
        $paginator = $this->repo->filter($request);
        return $this->renderIndex($paginator, $request);
    }

    /**
     * Shape the paginator into a flat list the React table can render. Pulls
     * everything the row template reads (user.name, user.image, user.hub.name,
     * status badge) up to the top level so the React side has no nested guards.
     */
    private function renderIndex($paginator, Request $request)
    {
        $rows = collect($paginator->items())->map(function ($d) {
            return [
                'id'               => $d->id,
                'unique_id'        => optional($d->user)->unique_id,
                'name'             => optional($d->user)->name,
                'email'            => optional($d->user)->email,
                'image'            => optional($d->user)->image,
                'hub_name'         => optional(optional($d->user)->hub)->name,
                'delivery_charge'  => $d->delivery_charge,
                'pickup_charge'    => $d->pickup_charge,
                'return_charge'    => $d->return_charge,
                'current_balance'  => $d->current_balance,
                'opening_balance'  => $d->opening_balance,
                'status'           => (int) optional($d->user)->status,
                'edit_url'         => route('deliveryman.edit', $d->id),
            ];
        })->values();

        return Inertia::render('Admin/Deliveryman/Index', [
            'rows'        => $rows,
            'pagination'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'filters'     => [
                'name'  => $request->input('name', ''),
                'email' => $request->input('email', ''),
                'phone' => $request->input('phone', ''),
            ],
            'permissions' => [
                'create' => hasPermission('delivery_man_create'),
                'update' => hasPermission('delivery_man_update'),
                'delete' => hasPermission('delivery_man_delete'),
            ],
            'currency'    => settings()->currency,
            'urls'        => [
                'filter' => route('deliveryman.filter'),
                'index'  => route('deliveryman.index'),
                'create' => route('deliveryman.create'),
            ],
            't'           => $this->deliverymanLabels(),
        ]);
    }

    public function create()
    {
        $hubs              = $this->repo->hubs();
        $supplierCompanies = SupplierCompany::companywise()->where('status', 1)->orderBy('name')->get();
        $operationalAreas  = OperationalArea::companywise()->where('status', 1)->orderBy('name')->get();
        $nationalities     = Nationality::active()
            ->orderBy('sorting')
            ->orderBy('name')
            ->get(['id', 'name', 'en_name', 'code']);
        // Direct-manager candidates: admins + incharges + hub users on this tenant.
        $managers = User::whereIn('user_type', [
                UserType::ADMIN, UserType::INCHARGE, UserType::HUB,
            ])
            ->where('company_id', settings()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'user_type']);

        return Inertia::render('Admin/Deliveryman/Create', [
            'hubs'              => $hubs->map->only(['id', 'name'])->values(),
            'supplierCompanies' => $supplierCompanies->map->only(['id', 'name'])->values(),
            'operationalAreas'  => $operationalAreas->map->only(['id', 'name'])->values(),
            'managers'          => $managers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values(),
            'nationalities'     => $nationalities,
            't'                 => $this->deliverymanLabels(),
        ]);
    }

    /**
     * Flat translation map consumed by the React wizard. Mirrors lang/{locale}/deliveryman.php
     * plus the few cross-cutting keys the page reads.
     */
    private function deliverymanLabels(): array
    {
        $keys = [
            'title','create_deliveryman','edit_deliveryman',
            'section_basic','section_id','section_address','section_employment',
            'section_license','section_bank','section_documents',
            'full_name','name_en','mobile','alt_mobile','email','password',
            'gender','gender_male','gender_female','dob','nationality','nationality_empty',
            'id_type','id_type_national','id_type_iqama','id_number','id_expiry','id_image','file_help',
            'address','district','short_national_address','short_national_address_placeholder',
            'driver_type','driver_type_freelancer','driver_type_outsourced','driver_type_company_courier',
            'employee_number','supplier_company','supplier_company_empty','joining_date',
            'contract_end_date','contract_expiry_hint',
            'status','status_active','status_suspended','status_leave','status_terminated',
            'hub','direct_manager','operational_area','salary',
            'license_number','license_expiry','iqama_expiry',
            'bank_account_no','iban','iban_placeholder',
            'personal_photo','license_photo','iqama_photo','contract_photo','promissory_note_photo',
            'wizard_next','wizard_prev','wizard_submit','wizard_step_of',
        ];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = __("deliveryman.$k");
        }
        $out['delivery_charge'] = __('levels.delivery_charge');
        $out['pickup_charge']   = __('levels.pickup_charge');
        $out['return_charge']   = __('levels.return_charge');
        $out['opening_balance'] = __('levels.opening_balance');
        $out['cancel']          = __('levels.cancel');
        $out['list']            = __('levels.list');
        $out['add']             = __('levels.add');
        $out['edit']            = __('levels.edit');
        $out['actions']         = __('levels.actions');
        $out['filter']          = __('levels.filter');
        $out['clear']           = __('levels.clear');
        $out['name_label']      = __('levels.name');
        $out['email_label']     = __('levels.email');
        $out['phone_label']     = __('levels.phone');
        $out['unique_id']       = __('levels.unique_id');
        $out['user_label']      = __('levels.user');
        $out['current_balance'] = __('levels.current_balance');
        $out['showing_results'] = __('levels.showing').' :from – :to '.__('of').' :total';
        $out['no_rows']         = __('levels.no_data_found') ?: 'No couriers found';
        return $out;
    }


    public function store(DeliveryManRequest $request)
    {

        $deliveryman_count = DeliveryMan::companywise()->count();
        if(!settings()->subscription): 
            Toastr::error('Something went wrong!', 'Error');
            return redirect()->back()->withInput($request->all());
        elseif(settings()->subscription && settings()->subscription->deliveryman_count <= $deliveryman_count ):
            Toastr::error('You have limited deliveryman manage. Please upgrade your package.', 'Error');
            return redirect()->back()->withInput($request->all()); 
        endif;
 
        if($this->repo->store($request)){
            Toastr::success(__('deliveryman.added_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $dm = $this->repo->get($id);
        if (!$dm) {
            return redirect()->route('deliveryman.index');
        }
        $dm->loadMissing('user');

        $hubs              = $this->repo->hubs();
        $supplierCompanies = SupplierCompany::companywise()->where('status', 1)->orderBy('name')->get();
        $operationalAreas  = OperationalArea::companywise()->where('status', 1)->orderBy('name')->get();
        $nationalities     = Nationality::active()
            ->orderBy('sorting')
            ->orderBy('name')
            ->get(['id', 'name', 'en_name', 'code']);
        $managers = User::whereIn('user_type', [
                UserType::ADMIN, UserType::INCHARGE, UserType::HUB,
            ])
            ->where('company_id', settings()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'user_type']);

        return Inertia::render('Admin/Deliveryman/Create', [
            'mode' => 'edit',
            'deliveryman' => [
                'id'                     => $dm->id,
                'name'                   => optional($dm->user)->name,
                'name_en'                => $dm->name_en,
                'mobile'                 => optional($dm->user)->mobile,
                'alt_mobile'             => $dm->alt_mobile,
                'email'                  => optional($dm->user)->email,
                'gender'                 => $dm->gender,
                'dob'                    => optional($dm->dob)->toDateString ?? $dm->dob,
                'nationality'            => $dm->nationality,
                'id_type'                => $dm->id_type,
                'id_number'              => $dm->id_number,
                'id_expiry'              => $dm->id_expiry,
                'address'                => optional($dm->user)->address ?? $dm->address,
                'district'               => $dm->district,
                'short_national_address' => $dm->short_national_address,
                'driver_type'            => $dm->driver_type ?: 'company_courier',
                'employee_number'        => $dm->employee_number,
                'supplier_company_id'    => $dm->supplier_company_id,
                'joining_date'           => optional($dm->user)->joining_date ?? $dm->joining_date,
                'contract_end_date'      => $dm->contract_end_date,
                'status'                 => $dm->status ?? 1,
                'hub_id'                 => $dm->hub_id ?? optional($dm->user)->hub_id,
                'direct_manager_id'      => $dm->direct_manager_id,
                'operational_area_id'    => $dm->operational_area_id,
                'salary'                 => optional($dm->user)->salary,
                'delivery_charge'        => $dm->delivery_charge,
                'pickup_charge'          => $dm->pickup_charge,
                'return_charge'          => $dm->return_charge,
                'opening_balance'        => $dm->opening_balance,
                'license_number'         => $dm->license_number,
                'license_expiry'         => $dm->license_expiry,
                'iqama_expiry'           => $dm->iqama_expiry,
                'bank_account_no'        => $dm->bank_account_no,
                'iban'                   => $dm->iban,
            ],
            'hubs'              => $hubs->map->only(['id', 'name'])->values(),
            'supplierCompanies' => $supplierCompanies->map->only(['id', 'name'])->values(),
            'operationalAreas'  => $operationalAreas->map->only(['id', 'name'])->values(),
            'managers'          => $managers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values(),
            'nationalities'     => $nationalities,
            't'                 => $this->deliverymanLabels() + [
                'password_keep_hint' => __('deliveryman.password_keep_hint') ?: 'Leave blank to keep the current password.',
            ],
        ]);
    }

    public function update(DeliveryManRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('deliveryman.update_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('deliveryman.delete_msg'),__('message.success'));
        return back();
    }
}
