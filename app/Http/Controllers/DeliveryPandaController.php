<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeliveryPandaService;

use App\Models\Backend\Parcels_3pl;

use App\Enums\ParcelStatus;
use App\Repositories\Parcel\ParcelInterface;


class DeliveryPandaController extends Controller
{
    protected $deliveryPanda;
    protected $repo;

    public function __construct(DeliveryPandaService $deliveryPanda , ParcelInterface $repo)
    {
        $this->deliveryPanda = $deliveryPanda;
        $this->repo = $repo;
    }
    
    
    
  
public function schudule_tracking(Request $request)
{
    // 1️⃣ Get unique AWB numbers from parcels_3pl (linked to existing parcels)
    $awbNumbers = Parcels_3pl::where('parcel_3pl_name', 'panda')
        ->whereNotNull('awb_number')
        ->whereHas('parcel')
        ->distinct()
        ->pluck('awb_number')
        ->toArray();
        
      

    if (empty($awbNumbers)) {
        return response()->json(['message' => 'No AWB numbers found'], 404);
    }

    // 2️⃣ Call Panda API
    $response = $this->deliveryPanda->getListTracking($awbNumbers);
    
      
 
    // 3️⃣ Validate response structure before proceeding
    if (
        isset($response['success']) && $response['success'] == 1 &&
        isset($response['TrackResponse']) && is_array($response['TrackResponse'])
    ) {
        $updatedCount = 0;
        $deliveredCount = 0;

        foreach ($response['TrackResponse'] as $item) {
            if (isset($item['Shipment'])) {
                $awb       = $item['Shipment']['awb_number'] ?? null;
                $status    = $item['Shipment']['current_status'] ?? null;
                $datetime  = $item['Shipment']['status_datetime'] ?? null;
                
 
                if ($awb) {
                    $parcel3pl = Parcels_3pl::with(['parcel' => function ($q) {
                            $q->select('id', 'status');
                        }])
                        ->where('awb_number', $awb)
                        ->first();
                        
                        

                    if ($parcel3pl) {
                        $parcel_status = $parcel3pl->parcel->status ?? null;
                        
                        $parcel_id = $parcel3pl->parcel_id;
                        
                        
                         $normalizedStatus = strtoupper(trim($status));
                         
                        // 🟢 Update tracking info
                        $parcel3pl->update([
                            'current_status'  => $normalizedStatus,
                            'status_datetime' => $datetime,
                        ]);

                        

                        // 🟢 If delivered → trigger parcelDelivered()
                        if ($normalizedStatus === 'DELIVERED' && $parcel_status == ParcelStatus::DELIVERY_MAN_ASSIGN) {
                            try {
                                // call existing delivery logic
                                $this->repo->parcelDelivered($parcel_id, new Request([
                                    'note' => 'Auto updated from P-tracking',
                                    'send_sms_customer' => 'off',
                                    'send_sms_merchant' => 'off',
                                ]));
                                
                       

                                $deliveredCount++;
                            } catch (\Throwable $e) {
                                \Log::error("❌ Failed to auto-deliver parcel {$parcel3pl->parcel->id}: " . $e->getMessage());
                            }
                        }

                        $updatedCount++;
                    }
                }
            }
        }

        \Log::info("✅ Updated $updatedCount tracking records; $deliveredCount marked as delivered.");

        return response()->json([
            'message'        => 'Tracking statuses updated successfully ✅',
            'updated_count'  => $updatedCount,
            'delivered_auto' => $deliveredCount,
        ]);
    }

    // 4️⃣ Handle invalid API response
    return response()->json(['message' => 'Invalid response from DeliveryPanda API'], 500);
}




    public function schudule_tracking22(Request $request)
    {
        $awbNumbers = Parcels_3pl::where('parcel_3pl_name', 'panda')
            ->whereNotNull('awb_number')
            ->pluck('awb_number')
            ->toArray();

        if (empty($awbNumbers)) {
            return response()->json(['message' => 'No AWB numbers found'], 404);
        }

        $response = $this->deliveryPanda->getListTracking($awbNumbers);

        if (
            isset($response['success']) && $response['success'] == 1 &&
            isset($response['TrackResponse']) && is_array($response['TrackResponse'])
        ) {
            foreach ($response['TrackResponse'] as $item) {
                if (isset($item['Shipment'])) {
                    $awb = $item['Shipment']['awb_number'] ?? null;
                    $status = $item['Shipment']['current_status'] ?? null;
                    $datetime = $item['Shipment']['status_datetime'] ?? null;

                    if ($awb) {

                        Parcels_3pl::where('awb_number', $awb)->update([
                            'current_status' => $status,
                            'status_datetime' => $datetime,
                        ]);
                    }
                }
            }

            \Log::info(count($response['TrackResponse']));
            return response()->json([
                'message' => 'Tracking statuses updated successfully ✅',
                'updated_count' => count($response['TrackResponse'])
            ]);
        }

        return response()->json(['message' => 'Invalid response from DeliveryPanda API'], 500);
    }



    /**
     * 1. Create shipment (Auto-generated AWB)
     */
    public function createShipment(Request $request)
    {
        $data = [
            "ToCompany" => $request->input('to_company'),
            "ToAddress" => $request->input('to_address'),
            "ToCity" => $request->input('to_city'),
            "ToLocation" => $request->input('to_location'),
            "ToCountry" => $request->input('to_country'),
            "ToCperson" => $request->input('to_contact_person'),
            "ToContactno" => $request->input('to_contact_number'),
            "ToMobileno" => $request->input('to_mobile'),
            "ReferenceNumber" => $request->input('reference_number'),
            "CompanyCode" => $request->input('company_code', ''),
            "Weight" => $request->input('weight'),
            "Pieces" => $request->input('pieces'),
            "PackageType" => $request->input('package_type'),
            "CurrencyCode" => $request->input('currency_code', 'SAR'),
            "NcndAmount" => $request->input('amount'),
            "ItemDescription" => $request->input('description'),
            "SpecialInstruction" => $request->input('special_instruction'),
            "BranchName" => $request->input('branch_name')
        ];

        $response = $this->deliveryPanda->createAutoBooking($data);
        return response()->json($response);
    }

    /**
     * 2. Create shipment using agent-supplied AWB number
     */
    public function createAgentShipment(Request $request)
    {
        $data = [
            "AwbNumber" => $request->input('awb_number'),
            "ToCompany" => $request->input('to_company'),
            "ToAddress" => $request->input('to_address'),
            "ToCity" => $request->input('to_city'),
            "ToLocation" => $request->input('to_location'),
            "ToCountry" => $request->input('to_country'),
            "ToCperson" => $request->input('to_contact_person'),
            "ToContactno" => $request->input('to_contact_number'),
            "ToMobileno" => $request->input('to_mobile'),
            "ReferenceNumber" => $request->input('reference_number'),
            "Weight" => $request->input('weight'),
            "Pieces" => $request->input('pieces'),
            "PackageType" => $request->input('package_type'),
            "CurrencyCode" => $request->input('currency_code', 'SAR'),
            "NcndAmount" => $request->input('amount'),
            "ItemDescription" => $request->input('description'),
            "SpecialInstruction" => $request->input('special_instruction'),
            "BranchName" => $request->input('branch_name')
        ];

        $response = $this->deliveryPanda->createAgentBooking($data);
        return response()->json($response);
    }

    /**
     * 3. Create customer-to-customer shipment
     */
    public function createCustomerToCustomerShipment(Request $request)
    {
        $data = [
            "FromCompany" => $request->input('from_company'),
            "FromAddress" => $request->input('from_address'),
            "FromCity" => $request->input('from_city'),
            "FromLocation" => $request->input('from_location'),
            "FromCountry" => $request->input('from_country'),
            "FromCperson" => $request->input('from_contact_person'),
            "FromContactno" => $request->input('from_contact_number'),
            "FromMobileno" => $request->input('from_mobile'),

            "ToCompany" => $request->input('to_company'),
            "ToAddress" => $request->input('to_address'),
            "ToCity" => $request->input('to_city'),
            "ToLocation" => $request->input('to_location'),
            "ToCountry" => $request->input('to_country'),
            "ToCperson" => $request->input('to_contact_person'),
            "ToContactno" => $request->input('to_contact_number'),
            "ToMobileno" => $request->input('to_mobile'),

            "ReferenceNumber" => $request->input('reference_number'),
            "Weight" => $request->input('weight'),
            "Pieces" => $request->input('pieces'),
            "PackageType" => $request->input('package_type'),
            "CurrencyCode" => $request->input('currency_code', 'SAR'),
            "NcndAmount" => $request->input('amount'),
            "ItemDescription" => $request->input('description'),
            "SpecialInstruction" => $request->input('special_instruction'),
            "BranchName" => $request->input('branch_name')
        ];

        $response = $this->deliveryPanda->createCustomerToCustomer($data);
        return response()->json($response);
    }

    /**
     * 4. Track shipment using AWB number(s)
     */
    public function trackShipment(Request $request)
    {
        $awbNumbers = $request->input('awb_numbers');

        if (!is_array($awbNumbers)) {
            return response()->json([
                'success' => 0,
                'message' => 'AWB numbers must be an array'
            ], 422);
        }

        $response = $this->deliveryPanda->getTracking($awbNumbers);
        return response()->json($response);
    }
}
