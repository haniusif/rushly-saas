<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\StatementType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Parcel;
use App\Repositories\Parcel\ParcelInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryManParcelController extends Controller
{

    use ApiReturnFormatTrait;

    protected $repo;
    public function __construct(ParcelInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index(Request $request)
    {
        try {
            $parcels = $this->repo->deliveryManParcel();
            return $this->responseWithSuccess(__('parcel.title'), ['parcels'=>$parcels], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.title'), [], 500);

        }
    }


    public function details($id)
    {

        try {
            $parcel       = $this->repo->details($id);
            $parcelEvents = $this->repo->parcelEvents($id);
            return $this->responseWithSuccess(__('parcel.parcel_details'), ['parcel'=>$parcel,'parcelEvents'=>$parcelEvents], 200);
        }catch (\Exception $exception){
            return $this->responseWithError(__('parcel.parcel_details'), [], 500);

        }
    }

    /**
     * GET /deliveryman/parcel/by-tracking/{tracking_id}
     *
     * Look up a parcel by tracking id and return its full details in the
     * same shape as /details/{id}. Feeds the driver app's barcode scan
     * → open parcel flow. Guards that the parcel is actually assigned to
     * the caller — a scanner in the field can be pointed at anything.
     */
    public function findByTracking($trackingId, Request $request)
    {
        try {
            $parcel = Parcel::where('tracking_id', $trackingId)->first();
            if (!$parcel) {
                return $this->responseWithError(__('parcel.not_found'),
                    ['tracking_id' => $trackingId], 404);
            }
            // A parcel's driver lives on parcel_events, not parcels — there is
            // no parcels.delivery_man_id column, so reading it here always
            // yielded null and 403'd every caller. Resolve via the latest
            // driver-bearing event instead, and require BOTH ids to be real so
            // an unassigned parcel (0) can't match a driverless user (0).
            $assignedDriverId = (int) optional($parcel->lastDeliveryMan)->delivery_man_id;
            $callerDriverId   = (int) optional($request->user()->deliveryMan)->id;
            if ($assignedDriverId === 0 || $assignedDriverId !== $callerDriverId) {
                return $this->responseWithError(__('parcel.not_assigned'),
                    ['tracking_id' => $trackingId], 403);
            }
            $parcelDetails = $this->repo->details($parcel->id);
            $parcelEvents  = $this->repo->parcelEvents($parcel->id);
            return $this->responseWithSuccess(__('parcel.parcel_details'), [
                'parcel'       => $parcelDetails,
                'parcelEvents' => $parcelEvents,
            ], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.parcel_details'),
                ['error' => $exception->getMessage()], 500);
        }
    }
    
    
    
 
    
       public function parcelByTrackDelivered($tracking_id,Request $request)
    {
        
        try {
            $parcel       = Parcel::where('tracking_id',$tracking_id)->first();
            $this->repo->parcelDelivered($parcel->id, $request);
            return $this->responseWithSuccess(__('parcel.delivered_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }

    public function parcelDelivered($id,Request $request)
    {
        try {
            $this->repo->parcelDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.delivered_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }

    public function deliveryIncomeExpense($id,Request $request)
    {
        $d_income       = DeliverymanStatement::where('type',StatementType::INCOME)->whereBetween('created_at',$this->repo->FromTo($request))->sum('amount');
        $d_expense      = DeliverymanStatement::where('type',StatementType::EXPENSE)->whereBetween('created_at',$this->repo->FromTo($request))->sum('amount');
        try {
            $this->repo->parcelDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.delivered_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);
        }
    }

    public function parcelPartialDelivered($id, Request $request)
    {
        $validator = Validator::make($request->all(),[
            'cash_collection'       => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('parcel.required'), ['message' => $validator->errors()], 422);
        }

        try {
            $this->repo->parcelPartialDelivered($id, $request);
            return $this->responseWithSuccess(__('parcel.partial_delivered_success'), [], 200);
        }catch (\Exception $exception) {
            return $this->responseWithError(__('parcel.error_msg'), [], 500);

        }
    }


}
