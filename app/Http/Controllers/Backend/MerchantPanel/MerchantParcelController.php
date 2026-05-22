<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Imports\ParcelImport;
use App\Imports\MParcelImport;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\Area;

use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\MerchantShops;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use Illuminate\Http\Request;
use App\Http\Requests\MerchantPanel\Parcel\StoreRequest;
use App\Http\Requests\MerchantPanel\Parcel\UpdateRequest;
use App\Models\Backend\DeliveryMan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Enums\ParcelStatus;
use App\Exports\MerchantParcelExport;
use App\Http\Resources\MerchantParcelExportResource;
use App\Models\Backend\ParcelEvent; 
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


   use Illuminate\Support\Facades\Storage;
 
class MerchantParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $merchant;
    protected $repo;
    protected $shop;
    public function __construct(MerchantParcelInterface $repo, MerchantInterface $merchant, ShopsInterface $shop)
    {
        $this->merchant = $merchant;
        $this->repo = $repo;
        $this->shop = $shop;
    }
    public function index(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $parcels = $this->repo->all($merchant->id);
        return view('backend.merchant_panel.parcel.index',compact('parcels','request' ));
    }
    public function parcelBank(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $parcels = $this->repo->parcelBank($merchant->id);
        return view('backend.merchant_panel.parcel.parcel_bank',compact('parcels','request' ));
    }

    public function filter(Request $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        if($this->repo->filter($merchant->id,$request)){
            $parcels      = $this->repo->filter($merchant->id,$request);
            return view('backend.merchant_panel.parcel.index',compact('parcels','request' ));
        }else{
            return redirect()->back();
        }
    }

    public function create()
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        $shops = $this->repo->getShops($merchant->id);
       
        $merchantShop = $shops[0];
        $deliveryCategories = $this->repo->deliveryCategories();
        $deliveryCharges = $this->repo->deliveryCharges();
        $packagings = $this->repo->packaging();
        $deliveryTypes      = $this->repo->deliveryTypes();
        $cities      = $this->repo->cities();
        return view('backend.merchant_panel.parcel.create',compact('merchant','merchantShop','deliveryTypes','shops','deliveryCategories','deliveryCharges','packagings' , 'cities'));
    }

    public function store(StoreRequest $request)
    {
        
 
        if(Auth::user()->merchant->wallet_use_activation == Status::ACTIVE):
            $chargeDetails = json_decode($request->chargeDetails);
            if($chargeDetails->totalDeliveryChargeAmount > Auth::user()->merchant->wallet_balance):
                Toastr::error('You are low on balance. Please recharge', 'Error');
                return redirect()->route('merchant-panel.my.wallet.index');
            endif; 
        endif;

        
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        if($this->repo->store($request,$merchant->id)){
            Toastr::success(__('parcel.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    public function duplicateStore(StoreRequest $request)
    {
        $userID = Auth::user()->id;
        $merchant = $this->repo->getMerchant($userID);
        if($this->repo->duplicateStore($request,$merchant->id)){
            Toastr::success(__('parcel.added_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function show($id)
    {
        //
    }

    // Parcel logs
    public function logs($id)
    {
        $parcel       = $this->repo->get($id);
        $parcelevents = $this->repo->parcelEvents($id);
        return view('backend.merchant_panel.parcel.logs', compact('parcel','parcelevents'));
    }

    // Parcel duplicate
    public function duplicate($id)
    {
        $parcel          = $this->repo->get($id);
        $merchant        = $this->merchant->get($parcel->merchant_id);
        $shops           = $this->shop->all($parcel->merchant_id);
        $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();

        $deliveryCategories      = $this->repo->deliveryCategories();
        $deliveryCategoryCharges = $this->repo->deliveryCharges();

        $packagings    = $this->repo->packaging();
        $deliveryTypes = $this->repo->deliveryTypes();
        return view('backend.merchant_panel.parcel.duplicate',compact('parcel','merchant','deliveryTypes','shops','deliveryCategories','deliveryCategoryCharges','deliveryCharges','packagings'));
    }

    // Parcel details
    public function details($id)
    {
        // return $this->repo->details($id);
        $parcel       = $this->repo->details($id);
        $parcelevents = $this->repo->parcelEvents($id);
        return view('backend.merchant_panel.parcel.details',compact('parcel','parcelevents'));
    }

    public function edit($id)
    {
        $userID = Auth::user()->id;
        $parcel = $this->repo->get($id);
        if($parcel->status == ParcelStatus::PENDING){
            $merchant = $this->repo->getMerchant($userID);
            $shops = $this->repo->getShops($merchant->id);
            $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$parcel->category_id)->get();
            $deliveryCategories = $this->repo->deliveryCategories();
            $deliveryCategoryCharges = $this->repo->deliveryCharges();
            $packagings = $this->repo->packaging();
            $deliveryTypes      = $this->repo->deliveryTypes();
            return view('backend.merchant_panel.parcel.edit',compact('parcel','merchant','deliveryTypes','shops','deliveryCategories','deliveryCategoryCharges','deliveryCharges','packagings'));
        }
        else{
            Toastr::error(__('parcel.edit_error_message'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }

    }


    // Parcel update
    public function statusUpdate($id, $status_id)
    {
        $this->repo->statusUpdate($id, $status_id);
        Toastr::success(__('parcel.update_msg'),__('message.success'));
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function update(StoreRequest $request,$id)
    {
        $userID = Auth::user()->id;
        if($this->repo->update($id, $request,$userID)){
            Toastr::success(__('parcel.update_msg'),__('message.success'));
            return redirect()->route('merchant-panel.parcel.index');
        }else{
            Toastr::error(__('parcel.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }


    public function destroy($id)
    {
        $userID = Auth::user()->id;
        $parcel = $this->repo->get($id);
        if($parcel->status == ParcelStatus::PENDING){
            $this->repo->delete($id,$userID);
            Toastr::success(__('parcel.delete_msg'),__('message.success'));
            return back();
        }
        else{
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->route('merchant-panel.parcel.index');
        }
    }

    public function parcelImportExport()
    {
        $deliveryCategories = $this->repo->deliveryCategories();
        return view('backend.merchant_panel.parcel.import',compact('deliveryCategories'));
    }
    
    
 
public function m_parcelImport(Request $request)
{
    // ✅ Validate the uploaded file
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:5120',
    ]);

    // 📁 Store the uploaded file temporarily
    $path = $request->file('file')->store('imports');

    // 📊 Read the first sheet from the Excel file
    $sheet = Excel::toCollection(null, Storage::path($path))->first();

    if (blank($sheet) || $sheet->count() === 0) {
        return back()->withErrors(['file' => 'The uploaded file is empty or invalid.']);
    }

    // 1️⃣ Get raw headers (may include `*` for required fields)
    $rawHeaders = collect($sheet->first() ?? [])
        ->map(fn($h) => trim((string) $h))
        ->values();

    if ($rawHeaders->isEmpty()) {
        return back()->withErrors(['file' => 'No header row was found in the Excel file.']);
    }

    // 2️⃣ Detect required columns (any column name ending with `*`)
    $required = $rawHeaders
        ->filter(fn($h) => preg_match('/\*\s*$/u', $h))
        ->map(fn($h) => preg_replace('/\s*\*\s*$/u', '', $h))
        ->values();

    // 3️⃣ Clean headers (remove the `*`)
    $headers = $rawHeaders->map(fn($h) => preg_replace('/\s*\*\s*$/u', '', $h))->values();

    // 4️⃣ Define the expected columns (in the correct order, without `*`)
    $expected = collect([
        'Pickup point',
        'Pickup phone',
        'Pickup address',
        'COD',
        'Reference number',
        'Weight',
        'Customer Name',
        'Customer Phone',
        'City',
        'Area',
        'Customer Address',
        'Note',
    ]);

    // ❗ Check for missing columns
    $missing = $expected->diff($headers);
    if ($missing->isNotEmpty()) {
        return back()->withErrors([
            'file' => 'The Excel file is missing the following columns: ' . $missing->implode(', ')
        ]);
    }

    // 5️⃣ Remove empty rows
    $rows = $sheet->slice(1)->values()->filter(function ($row) {
        return collect($row)->filter(fn($c) => !is_null($c) && trim((string)$c) !== '')->isNotEmpty();
    })->values();

    // 6️⃣ Convert Arabic digits to English (if any)
    $toEnglishDigits = function ($value) {
        if ($value === null) return $value;
        $str = (string) $value;
        $nums = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٫','٬','،'];
        $rep  = ['0','1','2','3','4','5','6','7','8','9','.','',''];
        return str_replace($nums, $rep, $str);
    };

    // 7️⃣ Validate row data
    $errors = [];
    $normalizedRows = [];

    foreach ($rows as $index => $row) {
        // Combine column names with row values
        $assoc = $headers->combine($row)->map(function ($v, $k) use ($toEnglishDigits) {
            if (in_array($k, ['COD', 'Weight'])) {
                return $toEnglishDigits($v);
            }
            return is_string($v) ? trim($v) : $v;
        });

        $rowNumber = $index + 2; // +2 because row 1 is the header

        // ✅ Check required fields
        foreach ($required as $col) {
            $val = $assoc->get($col);
            if (is_null($val) || trim((string) $val) === '') {
                $errors[] = "Row {$rowNumber}: The field '{$col}' is required.";
            }
        }

        // ✅ Validate numeric fields
        if ($assoc->has('COD') && trim((string)$assoc['COD']) !== '' && !is_numeric($assoc['COD'])) {
            $errors[] = "Row {$rowNumber}: The field 'COD' must be a numeric value.";
        }
        if ($assoc->has('Weight') && trim((string)$assoc['Weight']) !== '' && !is_numeric($assoc['Weight'])) {
            $errors[] = "Row {$rowNumber}: The field 'Weight' must be a numeric value.";
        }

        // ✅ Basic phone validation (allows + and digits)
        foreach (['Pickup phone', 'Customer Phone'] as $phoneCol) {
            if ($assoc->has($phoneCol) && trim((string)$assoc[$phoneCol]) !== '') {
                $p = (string) $assoc[$phoneCol];
                if (!preg_match('/^\+?\d{7,20}$/', preg_replace('/\s+/', '', $p))) {
                    $errors[] = "Row {$rowNumber}: The phone number in '{$phoneCol}' is invalid.";
                }
            }
        }

        $normalizedRows[] = $assoc;
    }

    // ❗ Return errors if any
    if (!empty($errors)) {
        return back()->withErrors($errors);
    }

    // 📦 Store data in session for the confirmation step
    session([
        'm_import.path'    => $path,
        'm_import.headers' => $headers,
        'm_import.total'   => count($normalizedRows),
        // 'm_import.rows'  => collect($normalizedRows)->toArray(), // optional
    ]);

    // 📊 Preview the first 100 rows
    $previewRows = collect($normalizedRows)->take(100);

    return view('backend.merchant_panel.parcel.preview', [
        'headers'     => $headers,
        'previewRows' => $previewRows,
        'totalRows'   => count($normalizedRows),
        'expected'    => $expected,
    ]);
}




public function m_parcelImportConfirm(Request $request)
{
    $path = session('m_import.path');
    
 
    if (!$path || !Storage::exists($path)) {
        Toastr::error('انتهت صلاحية جلسة المعاينة أو الملف غير موجود. أعد الرفع.', 'خطأ');
        return back();
    }

     try {
        // نفّذ الاستيراد الفعلي بالاعتماد على كلاس الاستيراد الخاص بك
        // إن كنت تفضّل ParcelImport بدلاً من MParcelImport استبدله هنا:
        $import = new MParcelImport();
        $import->import(Storage::path($path));

        // تنظيف جلسة المعاينة والملف المؤقت
        Storage::delete($path);
        session()->forget(['m_import.path', 'm_import.headers', 'm_import.total']);

        Toastr::success(__('parcel.added_msg'), __('message.success'));
        return redirect()->route('merchant-panel.parcel.index');

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        $importErrors = [];
        foreach ($failures as $failure) {
            $importErrors[$failure->row()][] = $failure->errors()[0] ?? 'خطأ غير معروف في الصف';
        }
        return $importErrors;
        // لا نحذف الملف هنا كي يقدر يعيد التأكيد بعد التصحيح إن لزم
        return back()->with('importErrors', $importErrors);
    } catch (\Throwable $th) {


        Toastr::error('حدث خطأ أثناء الاستيراد: ' . $th->getMessage(), 'خطأ');
        return back();
    }
}

public function showImportForm()
{
    return view('backend.merchant_panel.parcel.import_form');
}





    public function parcelImport(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);
        try {
            $import = new ParcelImport();
            $import->import($request->file('file'));
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $importErrors = [];
            foreach ($failures as $failure) {
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $importErrors[$failure->row()][] = $failure->errors()[0];
            }
            return back()->with('importErrors', $importErrors);
        }
        Toastr::success(__('parcel.added_msg'),__('message.success'));
        return redirect()->route('merchant-panel.parcel.index');
    }

    public function merchantShops(Request $request)
    {
        if (request()->ajax()) {
            if ($request->id && $request->shop == 'true') {
                $merchantShops = [];
                $merchantShop = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::ACTIVE])->first();
                $merchantShops[]= $merchantShop;
                $merchantShopArray = MerchantShops::where(['merchant_id'=>$request->id,'default_shop'=>Status::INACTIVE])->get();
                if(!blank($merchantShopArray)){
                    foreach ($merchantShopArray as $shop){
                        $merchantShops[] = $shop;
                    }
                }
                if (!blank($merchantShops)) {
                    return view('backend.parcel.shops', compact('merchantShops'));
                }
                return '';
            }else {
                $merchantShop = MerchantShops::find($request->id);
                if (!blank($merchantShop)) {
                    return $merchantShop;
                }
                return '';
            }
        }
        return '';
    }

    public function deliveryCharge(Request $request)
    {
        if (request()->ajax()) {
            if ($request->merchant_id && $request->category_id && $request->weight !='0' && $request->delivery_type_id) {
                $charges = MerchantDeliveryCharge::where(['merchant_id'=>$request->merchant_id,'category_id'=>$request->category_id,'weight'=>$request->weight])->first();
                if (blank($charges)) {
                    $charges = DeliveryCharge::companywise()->where(['category_id'=>$request->category_id])->first();
                }
            } else {
                $charges = MerchantDeliveryCharge::where(['merchant_id'=>$request->merchant_id,'category_id'=>$request->category_id,'weight'=>$request->weight])->first();
                if (blank($charges)) {
                    $charges = DeliveryCharge::companywise()->where(['category_id'=>$request->category_id])->first();
                }
            }

            if (!blank($charges)) {
                if($request->delivery_type_id == '1'){
                    $chargeAmount = $charges->same_day;
                }elseif ($request->delivery_type_id == '2') {
                    $chargeAmount = $charges->next_day;
                }elseif ($request->delivery_type_id == '3') {
                    $chargeAmount = $charges->sub_city;
                }elseif ($request->delivery_type_id == '4') {
                    $chargeAmount = $charges->outside_city;
                }else {
                    $chargeAmount = 0;
                }
                return $chargeAmount;
            }
            return 0;
        }
        return 0;
    }

    public function deliveryWeight(Request $request)
    {
        if (request()->ajax()) {
            if ($request->category_id) {
                $deliveryCharges = DeliveryCharge::companywise()->where('category_id',$request->category_id)->get();

                if (!blank($deliveryCharges)) {
                    return view('backend.merchant_panel.parcel.deliveryWeight', compact('deliveryCharges'));
                }
                return '';
            }
        }
        return '';
    }

    public function parcelExport(Request $request){
        try {
            if($request->type && $request->type == 'csv'):
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-csv-file-'.Carbon::now()->format('d-m-Y His').'.csv',\Maatwebsite\Excel\Excel::CSV, [
                    'Content-Type' => 'text/csv',
                ]);
            else:
                return Excel::download(new MerchantParcelExport($this->repo->parcelExport($request)),'Parcels Export-excel-file-'.Carbon::now()->format('d-m-Y His').'.xlsx');
            endif;
        } catch (\Throwable $th) {
            Toastr::error(__('parcel.delete_error_message'),__('message.error'));
            return redirect()->back();
        }
    }
    
    
    
        public function getAreasByCity(Request $request)
{
    $areas = Area::where('city_id', $request->city_id)
                 ->where('is_active', 1)
                 ->orderBy('sorting')
                 ->get();

    return response()->json($areas);
}
}
