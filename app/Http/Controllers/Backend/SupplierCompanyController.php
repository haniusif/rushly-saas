<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierCompany\StoreRequest;
use App\Http\Requests\SupplierCompany\UpdateRequest;
use App\Repositories\SupplierCompany\SupplierCompanyInterface;
use Brian2694\Toastr\Facades\Toastr;

class SupplierCompanyController extends Controller
{
    protected $repo;

    public function __construct(SupplierCompanyInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $suppliers = $this->repo->all();
        return view('backend.supplier_company.index', compact('suppliers'));
    }

    public function create()
    {
        return view('backend.supplier_company.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Supplier company added.', __('message.success'));
            return redirect()->route('supplier_companies.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $supplier = $this->repo->get($id);
        if (!$supplier) {
            Toastr::error('Not found.', __('message.error'));
            return redirect()->route('supplier_companies.index');
        }
        return view('backend.supplier_company.edit', compact('supplier'));
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Supplier company updated.', __('message.success'));
            return redirect()->route('supplier_companies.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Supplier company deleted.', __('message.success'));
        return back();
    }
}
