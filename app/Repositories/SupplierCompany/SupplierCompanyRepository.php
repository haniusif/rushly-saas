<?php

namespace App\Repositories\SupplierCompany;

use App\Models\Backend\SupplierCompany;

class SupplierCompanyRepository implements SupplierCompanyInterface
{
    public function all()
    {
        return SupplierCompany::companywise()
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function get($id)
    {
        return SupplierCompany::companywise()->where('id', $id)->first();
    }

    public function store($request)
    {
        try {
            $row = new SupplierCompany();
            $row->company_id    = settings()->id;
            $row->name          = $request->name;
            $row->contact_phone = $request->contact_phone;
            $row->status        = (int) $request->status;
            $row->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $row = SupplierCompany::companywise()->where('id', $id)->firstOrFail();
            $row->name          = $request->name;
            $row->contact_phone = $request->contact_phone;
            $row->status        = (int) $request->status;
            $row->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        try {
            SupplierCompany::companywise()->where('id', $id)->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
