<?php

namespace App\Http\Controllers\Api\V10\External;

use App\Http\Controllers\Controller;
use App\Salla\Services\ParcelCreationService;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SallaParcelController extends Controller
{
    use ApiReturnFormatTrait;

    public function store(Request $request, ParcelCreationService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'salla_merchant_id' => ['required', 'integer'],
            'salla_order_id'    => ['required', 'integer'],
            'merchant_id'       => ['required', 'integer'],
            'shop_id'           => ['required', 'integer'],
            'city_id'           => ['required', 'integer'],
            'category_id'       => ['required', 'integer'],
            'delivery_type_id'  => ['required', 'integer'],
            'customer_name'     => ['required', 'string', 'max:191'],
            'customer_address'  => ['required', 'string', 'max:191'],
            'customer_phone'    => ['required', 'string', 'max:191'],
            'cash_collection'   => ['nullable', 'numeric'],
            'meta'              => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->responseWithError('Validation failed', ['message' => $validator->errors()], 422);
        }

        try {
            $result = $service->create($validator->validated());
        } catch (RuntimeException $e) {
            return $this->responseWithError($e->getMessage(), [], 404);
        }

        $parcel = $result['parcel'];

        return $this->responseWithSuccess(
            $result['created'] ? 'Parcel created from Salla order' : 'Parcel already created',
            ['parcel_id' => $parcel->id, 'tracking_id' => $parcel->tracking_id],
            $result['created'] ? 201 : 200,
        );
    }
}
