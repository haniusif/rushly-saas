<?php

namespace App\Repositories\OperationalArea;

use App\Models\Backend\OperationalArea;

class OperationalAreaRepository implements OperationalAreaInterface
{
    public function all()
    {
        return OperationalArea::companywise()
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function get($id)
    {
        return OperationalArea::companywise()->where('id', $id)->first();
    }

    public function store($request)
    {
        try {
            $row = new OperationalArea();
            $row->company_id = settings()->id;
            $row->name       = $request->name;
            $row->code       = $request->code;
            $row->status     = (int) $request->status;
            $row->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $row = OperationalArea::companywise()->where('id', $id)->firstOrFail();
            $row->name   = $request->name;
            $row->code   = $request->code;
            $row->status = (int) $request->status;
            $row->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        try {
            OperationalArea::companywise()->where('id', $id)->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
