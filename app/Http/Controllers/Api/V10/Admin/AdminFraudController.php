<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Fraud;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminFraudController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $query = Fraud::query()->latest();

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('phone', 'like', "%$q%")
                  ->orWhere('name',  'like', "%$q%")
                  ->orWhere('tracking_id', 'like', "%$q%");
            });
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $rows = $query->paginate($per);

        return $this->responseWithSuccess('admin.fraud', [
            'fraud' => $rows->through(fn ($f) => [
                'id'          => $f->id,
                'name'        => $f->name,
                'phone'       => (string) $f->phone,
                'tracking_id' => $f->tracking_id,
                'details'     => $f->details,
                'date'        => optional($f->created_at)->format('d M Y'),
            ]),
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'       => 'required|string|max:30',
            'name'        => 'nullable|string|max:120',
            'tracking_id' => 'nullable|string|max:120',
            'details'     => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.fraud.store', ['message' => $validator->errors()], 422);
        }

        $fraud = Fraud::create([
            'created_by'  => auth()->id(),
            'phone'       => $request->phone,
            'name'        => $request->name,
            'tracking_id' => $request->tracking_id,
            'details'     => $request->details,
        ]);

        return $this->responseWithSuccess('admin.fraud.stored', ['id' => $fraud->id], 201);
    }

    public function destroy($id)
    {
        $fraud = Fraud::findOrFail($id);
        $fraud->delete();
        return $this->responseWithSuccess('admin.fraud.deleted', ['id' => (int) $id], 200);
    }
}
