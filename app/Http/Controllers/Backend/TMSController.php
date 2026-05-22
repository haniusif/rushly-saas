<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\DeliveryMan\DeliveryManInterface;
use Illuminate\Http\Request;
use App\Http\Requests\DeliveryMan\DeliveryManRequest;
use App\Models\Backend\DeliveryMan;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use App\Repositories\Hub\HubInterface;
use App\Repositories\Parcel\ParcelInterface;
use App\Enums\ParcelStatus;



class TMSController extends Controller
{
    protected $repo;
    protected $hub_repo;
    protected $parcel_repo;
    public function __construct(ParcelInterface $parcel_repo, DeliveryManInterface $repo , HubInterface $hub_repo)
    {
        $this->repo = $repo;
        $this->hub_repo = $hub_repo;
        $this->parcel_repo = $parcel_repo;
        
    }



 
public function print_runsheet(Request $request, $driver_id)
{
    $date = is_array($request->date)
        ? Carbon::today()
        : Carbon::parse($request->date ?? Carbon::today());

    $format = $request->format ?? 'excel';

    $data = $this->repo->shipments_by_driver($date, $driver_id);
    $assignedShipments = $data->assignedShipments ?? [];

    return view('backend.tms.print_runsheet', compact('assignedShipments', 'driver_id', 'date', 'format'));
}



public function tms(Request $request)
{
   $date = $request->date ?? Carbon::today();
    
   $today_parcels =  $this->parcel_repo->get_by_date($date);
   
   $grouped = $today_parcels->groupBy(function ($parcel) {
    return $parcel->status_name ?? 'Unknown';
});

   
  
    $stats = [
        'New Shipments' => $today_parcels->where('status', ParcelStatus::PENDING)->count(),
        'Ready for pick-up' => $today_parcels->whereIn('status', [
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN,
           // ParcelStatus::RECEIVED_WAREHOUSE
        ])->count(),
        'OFD' => $today_parcels->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count(),
        'Picked' => $today_parcels->where('status', ParcelStatus::RECEIVED_BY_PICKUP_MAN)->count(),
        'Not Delivered' => $today_parcels->whereIn('status', [
            ParcelStatus::RETURN_TO_COURIER,
            // ParcelStatus::RETURNED_MERCHANT,
            // ParcelStatus::RETURN_RECEIVED_BY_MERCHANT
        ])->count(),
       
        'Delivered' => $today_parcels->whereIn('status', [
            ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED
        ])->count(),
    ];
    
 
    // Get all deliverymen with their shipment stats
    $deliverymen = $this->repo->shipments($date);

    // 🗺️ Prepare map locations
    $locations = $deliverymen->map(fn($dm) => [
        'name'   => $dm->user->name ?? 'Unnamed',
        'mobile' => $dm->user->mobile ?? '',
        'status' => $dm->status ?? 0,
        'lat'    => (float) $dm->delivery_lat,
        'lng'    => (float) $dm->delivery_long,
    ]);

    // ✅ Drivers WITH shipments (show all stats)
    $with_shipments = $deliverymen
        ->filter(fn($dm) => $dm->total_assigned > 0)
        ->map(fn($dm) => [
            'driver_id'             => $dm->id,
            'name'             => $dm->user->name ?? 'Unnamed',
            'mobile'           => $dm->user->mobile ?? '',
            'shipment_count'   => $dm->total_assigned,
            'total_pending'    => $dm->total_pending ?? 0,
            'total_delivered'  => $dm->total_delivered ?? 0,
        ])
        ->values();

    // 🔴 Drivers WITHOUT shipments
    $without_shipments = $deliverymen
        ->filter(fn($dm) => $dm->total_assigned == 0)
        ->map(fn($dm) => [
            'name'   => $dm->user->name ?? 'Unnamed',
            'mobile' => $dm->user->mobile ?? '',
        ])
        ->values();

    // 📊 Online / Offline counters
    $onlineCount  = $locations->where('status', 1)->count();
    $offlineCount = $locations->where('status', '!=', 1)->count();
    $totalCount   = $locations->count();

    // 🏢 Hubs
    $hubs = $this->hub_repo->hubs()->map(fn($h) => [
        'id'       => $h->id,
        'name'     => $h->name,
        'hub_lat'  => (float) $h->hub_lat,
        'hub_long' => (float) $h->hub_long,
    ]);

    return view('backend.tms.map', compact(
        'deliverymen',
        'locations',
        'with_shipments',
        'without_shipments',
        'onlineCount',
        'offlineCount',
        'totalCount',
        'hubs',
        'grouped','stats'
    ));
}






    public function filter(Request $request)
    {
        $deliveryMans = $this->repo->filter($request);
        return view('backend.deliveryman.index',compact('deliveryMans','request'));
    }

    public function create()
    {
        $hubs         = $this->repo->hubs();
      return view('backend.deliveryman.create',compact('hubs'));
    }


    public function store(DeliveryManRequest $request)
    {

        $deliveryman_count = DeliveryMan::companywise()->count();
        if(!settings()->subscription): 
            Toastr::error('Something went wrong!', 'Error');
            return redirect()->back()->withInput($request->all());
        elseif(settings()->subscription && settings()->subscription->deliveryman_count <= $deliveryman_count ):
            Toastr::error('You have limited deliveryman manage. Please upgrade your package.', 'Error');
            return redirect()->back()->withInput($request->all()); 
        endif;
 
        if($this->repo->store($request)){
            Toastr::success(__('deliveryman.added_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $hubs         = $this->repo->hubs();
        $deliveryman = $this->repo->get($id);
        return view('backend.deliveryman.edit',compact('deliveryman','hubs'));
    }

    public function update(DeliveryManRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            Toastr::success(__('deliveryman.update_msg'),__('message.success'));
            return redirect()->route('deliveryman.index');
        }else{
            Toastr::error(__('deliveryman.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success(__('deliveryman.delete_msg'),__('message.success'));
        return back();
    }
}
