<?php

namespace App\Http\Controllers\Api\V10\External;

use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use App\Models\Backend\ZidOrderLink;
use App\Traits\ApiReturnFormatTrait;
use App\Traits\TrackingTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZidParcelController extends Controller
{
    use ApiReturnFormatTrait, TrackingTrait;

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Zid IDs are strings (per their OpenAPI spec); never coerce to int.
            'zid_store_id'      => ['required', 'string', 'max:64'],
            'zid_order_id'      => ['required', 'string', 'max:64'],
            'merchant_id'       => ['required', 'integer'],
            'shop_id'           => ['required', 'integer'],
            'city_id'           => ['required', 'integer'],
            'category_id'       => ['required', 'integer'],
            'delivery_type_id'  => ['required', 'integer'],
            'customer_name'     => ['required', 'string', 'max:191'],
            'customer_address'  => ['required', 'string', 'max:191'],
            'customer_phone'    => ['required', 'string', 'max:191'],
            'cash_collection'   => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return $this->responseWithError('Validation failed', ['message' => $validator->errors()], 422);
        }

        $existing = ZidOrderLink::where('zid_store_id', $request->zid_store_id)
            ->where('zid_order_id', $request->zid_order_id)
            ->first();

        if ($existing && $existing->parcel_id) {
            $parcel = Parcel::find($existing->parcel_id);
            return $this->responseWithSuccess('Parcel already created', [
                'parcel_id'   => $parcel?->id,
                'tracking_id' => $parcel?->tracking_id,
            ], 200);
        }

        $parcel = Parcel::create([
            'tracking_id'         => $this->trackingId(),
            'merchant_id'         => $request->merchant_id,
            'merchant_shop_id'    => $request->shop_id,
            'city_id'             => $request->city_id,
            'deliverycategory_id' => $request->category_id,
            'delivery_type_id'    => $request->delivery_type_id,
            'customer_name'       => $request->customer_name,
            'customer_address'    => $request->customer_address,
            'customer_phone'      => $request->customer_phone,
            'cash_collection'     => (float) ($request->cash_collection ?? 0),
            'status'              => 'pending',
        ]);

        ZidOrderLink::create([
            'zid_store_id' => $request->zid_store_id,
            'zid_order_id' => $request->zid_order_id,
            'merchant_id'  => $request->merchant_id,
            'parcel_id'    => $parcel->id,
            'meta'         => $request->input('meta'),
        ]);

        return $this->responseWithSuccess('Parcel created from Zid order', [
            'parcel_id'   => $parcel->id,
            'tracking_id' => $parcel->tracking_id,
        ], 201);
    }
}
