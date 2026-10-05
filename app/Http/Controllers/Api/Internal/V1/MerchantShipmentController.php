<?php

namespace App\Http\Controllers\Api\Internal\V1;

use App\Http\Controllers\Controller;
use App\Services\InternalApi\MerchantShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Internal (service-to-service) merchant shipment endpoints, consumed only by
 * the rushly-api façade. Guarded by VerifyInternalServiceToken. Thin: validates
 * shape, then delegates to MerchantShipmentService which owns the business
 * rules and the explicit company/merchant scoping.
 */
class MerchantShipmentController extends Controller
{
    public function __construct(private readonly MerchantShipmentService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'company_id'           => ['required', 'integer'],
            'merchant_id'          => ['required', 'integer'],
            'recipient.name'       => ['required', 'string'],
            'recipient.phone'      => ['required', 'string'],
            'address.country'      => ['required', 'string'],
            'address.city'         => ['required', 'string'],
            'address.address_line' => ['required', 'string'],
            'payment.method'       => ['required', 'in:cod,prepaid'],
            'payment.cod_amount'   => ['required_if:payment.method,cod', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($validation->fails()) {
            return $this->validationError($validation->errors()->toArray());
        }

        return $this->respond($this->service->create($request->all()));
    }

    public function index(Request $request): JsonResponse
    {
        if ($bad = $this->requireContext($request)) {
            return $bad;
        }

        return $this->respond($this->service->list($request->all()));
    }

    public function show(Request $request, string $tracking_number): JsonResponse
    {
        if ($bad = $this->requireContext($request)) {
            return $bad;
        }

        return $this->respond($this->service->get(
            (int) $request->input('company_id'),
            (int) $request->input('merchant_id'),
            $tracking_number,
        ));
    }

    public function cancel(Request $request, string $tracking_number): JsonResponse
    {
        if ($bad = $this->requireContext($request)) {
            return $bad;
        }

        return $this->respond($this->service->cancel(
            (int) $request->input('company_id'),
            (int) $request->input('merchant_id'),
            $tracking_number,
            $request->input('reason'),
            $request->input('note'),
        ));
    }

    public function tracking(Request $request, string $tracking_number): JsonResponse
    {
        if ($bad = $this->requireContext($request)) {
            return $bad;
        }

        return $this->respond($this->service->tracking(
            (int) $request->input('company_id'),
            (int) $request->input('merchant_id'),
            $tracking_number,
        ));
    }

    public function history(Request $request, string $tracking_number): JsonResponse
    {
        if ($bad = $this->requireContext($request)) {
            return $bad;
        }

        return $this->respond($this->service->history(
            (int) $request->input('company_id'),
            (int) $request->input('merchant_id'),
            $tracking_number,
        ));
    }

    private function requireContext(Request $request): ?JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'company_id'  => ['required', 'integer'],
            'merchant_id' => ['required', 'integer'],
        ]);

        return $validation->fails() ? $this->validationError($validation->errors()->toArray()) : null;
    }

    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid request.', 'details' => $errors],
        ], 422);
    }

    private function respond(array $result): JsonResponse
    {
        return response()->json($result['body'], $result['status']);
    }
}
