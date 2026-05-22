<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\DeliverymanUserResource;
use App\Http\Resources\v10\ParcelResource;
use App\Http\Resources\v10\UserResource;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelImage;
use App\Models\User;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
 



class DeliverymanController extends Controller
{
    use ApiReturnFormatTrait;

    protected $parcel;
    protected $deliveryman;
    public function __construct(ParcelInterface $parcel,DeliveryManInterface $deliveryman){
        $this->parcel  = $parcel;
        $this->deliveryman = $deliveryman;
    }

    //dashboard
    public function dashboard(){

        try {

            $data         = [];
            $data['deliveryman_assign']      = ParcelResource::collection($this->parcel->deliverymanStatusParcel(ParcelStatus::DELIVERY_MAN_ASSIGN));
            $data['deliveryman_re_schedule'] = ParcelResource::collection($this->parcel->deliverymanStatusParcel(ParcelStatus::DELIVERY_RE_SCHEDULE));
            $data['return_to_courier']       = ParcelResource::collection($this->parcel->deliverymanStatusParcel(ParcelStatus::RETURN_TO_COURIER));
           // $data['delivered']               = [];
           $data['delivered']               = ParcelResource::collection($this->parcel->deliverymanStatusParcel(ParcelStatus::DELIVERED));

            return $this->responseWithSuccess(__('dashboard.delivery_man'),$data,200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    //profile
    public function profile(){
        
        
       
        try {

            $data                         = [];
            $data['user']                 = new  DeliverymanUserResource(Auth::user());
            $data['current_balance']      = $data['user']->deliveryman->current_balance;
            $data['deliveryman_earn']     = $this->deliveryman->deliverymanEarn(StatementType::INCOME)->sum('amount');
            $data['total_cod']            = number_format($this->deliveryman->totalCOD(StatementType::EXPENSE)->sum('amount') - $this->deliveryman->totalCOD(StatementType::INCOME)->sum('amount'),2) ;
            $data['delivery_in_progress'] = $this->parcel->TodayDeliverymanStatusParcel(ParcelStatus::DELIVERY_MAN_ASSIGN)->count();
            $data['completed_delivered']  = $this->parcel->TodayDeliverymanStatusParcel(ParcelStatus::DELIVERED)->count() ?? 0;
            $data['canceled_delivered']   = $this->deliveryman->totalCOD(StatementType::INCOME)->groupBy('parcel_id')->count();

            return $this->responseWithSuccess(__('dashboard.delivery_man'),$data,200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }


    public function paymentLogs(){
        try {

            $data = [];
            $data['income'] = $this->deliveryman->paymentLogs()['income'];
            $data['expense'] = $this->deliveryman->paymentLogs()['expense'];
            return $this->responseWithSuccess(__('dashboard.delivery_man'),$data,200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    public function parcelPaymentLogs(){
        try {

            $data = [];
            $data['parcel_payment_logs'] = $this->deliveryman->parcelPaymentLogs();
            return $this->responseWithSuccess(__('dashboard.delivery_man'),$data,200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    public function parcelStatus(){
        try {
            $data    = [];
            $data [] = trans('ApiParcelStatus');

            return $this->responseWithSuccess(__('parcel.status'),$data,200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }

    public function parcelLocationUpdate(Request $request){
        try {

            $user = User::find($request->deliveryID)->deliveryman->id;
            $parcelEvents = ParcelEvent::where('delivery_man_id',$user)->get();
            if(!blank($parcelEvents)) {
                foreach ($parcelEvents as $parcelEvent) {
                    $parcelEvent->delivery_lat = $request->lat;
                    $parcelEvent->delivery_long = $request->long;
                    $parcelEvent->save();
                }
            }

            return $this->responseWithSuccess(__('parcel.status'),[],200);
        } catch (\Throwable $th) {
            return $this->responseWithError(__('parcel.error_msg'), [],500);
        }
    }
    public function parcelStatusUpdate(Request $request){

        switch ($request->status_action) {
            //return to qourier
            case ParcelStatus::RETURN_TO_COURIER:
                if($this->parcel->returntoQourier($request->parcel_id, $request)):
                    return $this->responseWithSuccess(__('parcel.return_to_qourier_success'),[],200);
                else:
                    return $this->responseWithError(__('parcel.error_msg'), [],500);
                endif;
                break;

            //partial delivered
            case ParcelStatus::PARTIAL_DELIVERED:
                if($this->parcel->parcelPartialDelivered($request->parcel_id, $request)):
                    return $this->responseWithSuccess(__('parcel.partial_delivered_success'),[],200);
                else:
                    return $this->responseWithError(__('parcel.error_msg'), [],500);
                endif;
                break;

            //delivered
            case ParcelStatus::DELIVERED:
                if($this->parcel->parcelDelivered($request->parcel_id, $request)):
                    return $this->responseWithSuccess(__('parcel.delivered_success'),[],200);
                else:
                    return $this->responseWithError(__('parcel.error_msg'), [],500);
                endif;
                break;
            default:
                return $this->responseWithError(__('parcel.error_msg'), [],500);
                break;

        }
    }
    
//             public function parcelDelivered(Request $request)

// {
    
//     return $this->responseWithSuccess(__('parcel.return_to_qourier_success'),[],200);
//     return $request->all();
    
// }


//           public function parcelNotDelivered(Request $request)

// {
    
//     return $this->responseWithSuccess(__('parcel.return_to_qourier_success'),[],200);
    
//     return $request->all();
    
// }

    
    //     public function parcelNotDelivered(Request $request){
    //     if($this->parcel->returntoQourier($request->parcel_id, $request)){
    //       return $this->responseWithSuccess(__('parcel.return_to_qourier_success'),[],200);
    //     }else{
    //       return $this->responseWithError(__('parcel.error_msg'), [],500);
    //     }

    // }
    
    
 
 
    /* ============================================================
       PARCEL DELIVERED
    ============================================================ */



public function parcelDelivered(Request $request)
{
    $validator = Validator::make($request->all(), [
        'tracking_id' => 'required|exists:parcels,tracking_id',
        'note'        => 'nullable|string',
        'otp'         => 'nullable|string',
        'images'      => 'required|array|min:1',
        'images.*'    => 'image|max:20480'
    ]);

    if ($validator->fails()) {
        return $this->responseWithError(
            $validator->errors()->first(),
            [],
            422
        );
    }

    DB::beginTransaction();

    try {

        $parcel = Parcel::where('tracking_id', $request->tracking_id)
            ->lockForUpdate()
            ->firstOrFail();

        /* ===============================
           Prevent Duplicate Delivery
        =============================== */

        if ($parcel->status == ParcelStatus::DELIVERED) {
            return $this->responseWithError(
                __('Parcel already delivered'),
                [],
                400
            );
        }

        /* ===============================
           OTP Validation (if needed)
        =============================== */

        // if ($parcel->cod_amount == 0 && $parcel->otp_code) {
        //     if ($request->otp !== $parcel->otp_code) {
        //         return $this->responseWithError(
        //             __('Invalid OTP'),
        //             [],
        //             400
        //         );
        //     }
        // }

        /* ===============================
           Create Delivery Event
        =============================== */

        $event = $this->parcel->parcelDelivered($parcel->id, $request);

        if (!$event) {
            throw new \Exception('Failed to create delivery event');
        }

        /* ===============================
           Save Images (linked to event)
        =============================== */

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('parcel_images', 'public');

                ParcelImage::create([
                    'parcel_id'       => $parcel->id,
                    // 'parcel_event_id' => $event->id,
                    'image_path'      => $path,
                    'type'            => 'delivered',
                    'created_by'      => auth()->id(),
                ]);
            }
        }

        DB::commit();

        return $this->responseWithSuccess(
            __('Parcel delivered successfully'),
            [],
            200
        );

    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::error('Parcel Delivered Error', [
            'message'     => $e->getMessage(),
            'tracking_id' => $request->tracking_id,
            'user_id'     => auth()->id()
        ]);

        return $this->responseWithError(
            __('Operation failed'),
            [],
            500
        );
    }
}


    /* ============================================================
       PARCEL NOT DELIVERED
    ============================================================ */


public function parcelNotDelivered(Request $request)
{
    $validator = Validator::make($request->all(), [
        'tracking_id'         => 'required|exists:parcels,tracking_id',
        'rejection_reason_id' => 'required|exists:rejection_reasons,id',
        'note'                => 'nullable|string',
        'images'              => 'required|array|min:1',
        'images.*'            => 'image|max:20480'
    ]);

    if ($validator->fails()) {
        return $this->responseWithError(
            $validator->errors(),
            [],
            422
        );
    }

    DB::beginTransaction();

    try {

        $parcel = Parcel::where('tracking_id', $request->tracking_id)
            ->lockForUpdate()
            ->firstOrFail();

        // 🔥 تحقق من ملكية الشحنة
        // if ($parcel->delivery_man_id !== auth()->id()) {
        //     throw new \Exception('Unauthorized action');
        // }

        $event = $this->parcel->returntoQourier($parcel->id, $request);

        if (!$event) {
            throw new \Exception('Failed to create return event');
        }

        // 🔥 حفظ الصور بأمان
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('parcel_images', 'public');

                ParcelImage::create([
                    'parcel_id'        => $parcel->id,
                    // 'parcel_event_id'  => $event->id,
                    'image_path'       => $path,
                    'type'             => 'not_delivered',
                    'created_by'       => auth()->id(),
                ]);
            }
        }

        DB::commit();

        return $this->responseWithSuccess(
            __('Parcel marked as not delivered'),
            [],
            200
        );

    } catch (\Throwable $e) {

        DB::rollBack();

        // 🔥 سجل الخطأ في logs
        \Log::error('Parcel Not Delivered Error', [
            'message' => $e->getMessage(),
            'user_id' => auth()->id(),
            'tracking_id' => $request->tracking_id
        ]);

        return $this->responseWithError(
            __('Operation failed'),
            [],
            500
        );
    }
}

}
