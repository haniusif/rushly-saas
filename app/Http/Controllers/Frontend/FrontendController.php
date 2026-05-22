<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Repositories\DeliveryCharge\DeliveryChargeInterface;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use App\Repositories\FrontWeb\Partner\PartnerInterface;
use App\Repositories\FrontWeb\Service\ServiceInterface;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Repositories\Role\RoleInterface;
use App\Repositories\Superadmin\Plan\PlanInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

use App\Models\Backend\Area;
use App\Models\Backend\Parcel;



class FrontendController extends Controller
{
    protected $serviceRepo,$whycourierRepo,$deliveryChargeRepo,$partnerRepo,$parcelRepo,$pageRepo,$faqRepo,$MerchantParcelRepo,$blogRepo,$planRepo,$roleRepo;
    public function __construct(
        ServiceInterface        $serviceRepo,
        WhyCourierInterface     $whycourierRepo,
        DeliveryChargeInterface $deliveryChargeRepo,
        PartnerInterface        $partnerRepo,
        ParcelInterface         $parcelRepo,
        PagesInterface          $pageRepo,
        FaqInterface            $faqRepo,
        MerchantParcelInterface $MerchantParcelRepo,
        BlogsInterface          $blogRepo,
        PlanInterface           $planRepo,
        RoleInterface           $roleRepo
        )
    {
        $this->serviceRepo        = $serviceRepo;
        $this->whycourierRepo     = $whycourierRepo;
        $this->deliveryChargeRepo = $deliveryChargeRepo;
        $this->partnerRepo        = $partnerRepo;
        $this->parcelRepo         = $parcelRepo;
        $this->pageRepo           = $pageRepo;
        $this->faqRepo            = $faqRepo;
        $this->MerchantParcelRepo = $MerchantParcelRepo;
        $this->blogRepo           = $blogRepo;
        $this->planRepo           = $planRepo;
        $this->roleRepo           = $roleRepo;
    }
    
    
    

public function updateLocation($id , Request $request)
{
    $request->validate([
        'customer_address'   => 'required|string|max:255',
        'area_id'            => 'nullable|integer|exists:areas,id',
        'customer_lat'       => 'nullable|numeric|between:-90,90',
        'customer_long'      => 'nullable|numeric|between:-180,180',
        'delivery_date'      => ['nullable', 'date', 'after_or_equal:today', function ($attribute, $value, $fail) {
            if ($value) {
                $dayOfWeek = date('w', strtotime($value)); // 0=Sunday, 6=Saturday
                if ($dayOfWeek == 0) {
                    $fail(__('Delivery is not available on Sundays. Please choose between Monday and Saturday.'));
                }
            }
        }],
        'delivery_time'      => ['nullable', 'date_format:H:i', function ($attribute, $value, $fail) {
            if ($value) {
                [$hour, $minute] = explode(':', $value);
                $hour = (int)$hour;
                $minute = (int)$minute;

                // التحقق من الوقت بين 10:00 إلى 19:00
                if ($hour < 10 || ($hour >= 19 && $minute > 0)) {
                    $fail(__('Delivery time must be between 10:00 AM and 7:00 PM.'));
                }
            }
        }],
        'additional_phone'   => 'nullable|string|max:30'
    ]);

    $parcel = Parcel::findOrFail($id);

    $parcel->update([
        'area_id' => $request->area_id,
        'reschedule_area_id' => $request->area_id,
        'customer_address' => $request->customer_address,
        'customer_lat' => $request->customer_lat,
        'customer_long' => $request->customer_long,
        'reschedule_delivery_date' => $request->delivery_date,
        'reschedule_delivery_time' => $request->delivery_time,
        'additional_phone' => $request->additional_phone,
    ]);
    
  

    $shipmentNumber = $parcel->tracking_id;
    $date = $request->delivery_date ? date('d M Y', strtotime($request->delivery_date)) : __('Not specified');
    $time = $request->delivery_time ? date('h:i A', strtotime($request->delivery_time)) : __('Not specified');

    $msg = __('Shipment #:number rescheduled successfully for :date at :time', [
        'number' => $shipmentNumber,
        'date' => $date,
        'time' => $time
    ]);

    return back()->with('success', $msg);
}


       
       
       public function shipmentLocation($shipment){ 
        
        
        $parcel         = $this->parcelRepo->parcelByTracking( $shipment);
        
        $areas = Area::where('city_id' , $parcel->city_id)->get();
        
            
        $parcelevents   = $this->parcelRepo->parcelEvents($parcel->id?? null);
        
        return view('frontend.shipmentLocation' , compact('areas' , 'parcel'));
        
    }
    
    public function account_delete(){ 
        
        return view('account_delete');
        
    }
    
    public function index(){ 
       
        $data = [];
        $data['services']     = $this->serviceRepo->getAll(); 
        $data['whycouriers']  = $this->whycourierRepo->getAll();
        $data['plans']        = $this->planRepo->getActive(); 
        $data['pricing']      = $this->deliveryChargeRepo->getAllCharge(); 
        $data['partners']     = $this->partnerRepo->getAll(); 
        $data['blogs']        = $this->blogRepo->getActive(3); 
        $allModules           = $this->roleRepo->adminPermissionsModules();
        $data['allmodules']   = array_slice( $allModules->toArray(), 0, 10);   
       
        return view('frontend.home',$data);
    }

    public function tracking(Request $request){  
        $parcel         = $this->parcelRepo->parcelTracking($request);
        $parcelevents   = $this->parcelRepo->parcelEvents($parcel->id?? null);
        return view('frontend.pages.tracking',compact('parcelevents','parcel','request'));
    }

    public function aboutUs(){
        $page = $this->pageRepo->get('about_us');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
    public function privacyPolicy(){
        $page = $this->pageRepo->get('privacy_policy');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
 
    public function termsOfCondition(){
        $page = $this->pageRepo->get('terms_conditions');
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.page',compact('page'));
    }
    public function faq(){
        $page = $this->pageRepo->get('faq');
        $faqs = $this->faqRepo->getActive();
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.faq',compact('page','faqs'));
    }

    public function subscribe(Request $request){
        try { 
        
            $validator  = Validator::make($request->all(),[
                'email'=>['required','email']
            ]);
            if($validator->fails()):
                Toastr::error(__('parcel.error_msg'),__('message.error'));  
                return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
            endif;
            if($this->MerchantParcelRepo->subscribe($request) === true):
                Toastr::success(__('levels.successfully_subscribed'),__('message.success')); 
                return redirect()->back();
            elseif($this->MerchantParcelRepo->subscribe($request) == 1):
                Toastr::error(__('levels.already_subscribed'),__('message.error'));  
                return redirect()->back()->withInput($request->all());
            else:
                Toastr::error(__('parcel.error_msg'),__('message.error'));
                return redirect()->back();
            endif;
         } catch (\Throwable $th) { 
            Toastr::error(__('parcel.error_msg'),__('message.error'));  
            return redirect()->back()->withInput($request->all());
         }
    }


    public function contactSendPage(){
        $page = $this->pageRepo->get('contact'); 
        if(!$page):
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        endif;
        return view('frontend.pages.contact',compact('page'));
    }

    public function contactMessageSend(Request $request){
        try { 
            $validator    =  Validator::make($request->all(),[
                'name'    => ['required'],
                'email'   => ['required','email'],
                'subject' => ['required'],
                'message' => ['required','min:10']
            ]);
            if($validator->fails()):
                Toastr::error(__('parcel.error_msg'),__('message.error'));  
                return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
            endif;
            Mail::send(new ContactMail($request->all()));
            Toastr::success(__('levels.message_sended_successfully'),__('message.success')); 
            return redirect()->back(); 
        } catch (\Throwable $th) { 
            Toastr::error(__('parcel.error_msg'),__('message.error'));  
            return redirect()->back()->withInput($request->all());
        }
    }

    public function blogs(){ 
        $blogs         = $this->blogRepo->getActive(); 
        return view('frontend.pages.blogs_page',compact('blogs'));
    }
    public function blogDetails($id){
        $this->blogRepo->viewcount($id);
        $blog         = $this->blogRepo->getFind($id);
        $latest_blogs = $this->blogRepo->getLatestBlogs();
        return view('frontend.pages.blog_details',compact('blog','latest_blogs'));
    }

    public function serviceDetails($id){
        $service         = $this->serviceRepo->getFind($id);
        $latest_services = $this->serviceRepo->latest_services(); 
        return view('frontend.pages.service_details',compact('service','latest_services'));
    }
}
