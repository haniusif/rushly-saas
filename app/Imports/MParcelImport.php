<?php

namespace App\Imports;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantDeliveryCharge;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\Backend\City;      // optional
use App\Models\Backend\Area;      // optional
use App\Models\MerchantShops;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Enums\ParcelStatus;
use App\Enums\DeliveryTime;
use App\Traits\TrackingTrait;

class MParcelImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    use Importable, TrackingTrait;

    public function headingRow(): int
    {
        return 1; // first row is headers as-is
    }

    /** Normalize a single header: remove trailing star and snake_case it */
    private function normKey(string $key): string
    {
        // remove trailing " *"
        $clean = preg_replace('/\s*\*\s*$/u', '', $key);
        // to snake_case without Str helper (safe on older installs)
        $clean = trim($clean);
        $clean = str_replace(['-', '—'], ' ', $clean);
        $clean = preg_replace('/\s+/u', '_', $clean);
        return mb_strtolower($clean, 'UTF-8');
    }

    /** Normalize the entire row: strip stars, snake_case keys, and alias to unified names */
    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $nk = $this->normKey((string) $k);
            $out[$nk] = $v;
        }

        // Map known aliases from your Excel to unified keys
        $aliases = [
            'pickup_point'       => ['pickup_point', 'pickup_points'],
            'pickup_phone'       => ['pickup_phone'],
            'pickup_address'     => ['pickup_address'],
            'cod'                => ['cod', 'cod_amount', 'cash_collection'],
            'reference_number'   => ['reference_number', 'invoice_no', 'ref_no'],
            'weight'             => ['weight'],
            'customer_name'      => ['customer_name'],
            'customer_phone'     => ['customer_phone'],
            'city'               => ['city', 'customer_city', 'city_name'],
            'area'               => ['area', 'customer_area', 'area_name'],
            'customer_address'   => ['customer_address', 'address'],
            'note'               => ['note', 'remarks'],
            'shop_id'            => ['shop_id', 'merchant_shop_id'],
            'customer_city_id'   => ['customer_city_id', 'city_id'],
            'customer_area_id'   => ['customer_area_id', 'area_id'],
            'customer_lat'       => ['customer_lat', 'lat'],
            'customer_long'      => ['customer_long', 'long'],
            'selling_price'      => ['selling_price'],
            'pickup_lat'         => ['pickup_lat'],
            'pickup_long'        => ['pickup_long'],
        ];

        $final = [];
        foreach ($aliases as $target => $keys) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') {
                    $final[$target] = $out[$k];
                    break;
                }
            }
            if (!array_key_exists($target, $final)) {
                $final[$target] = null;
            }
        }

        // Arabic→English digits for numeric-like fields
        $toEng = function ($val) {
            if ($val === null) return null;
            $s = (string) $val;
            $nums = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','٫','٬','،'];
            $rep  = ['0','1','2','3','4','5','6','7','8','9','.','',''];
            return str_replace($nums, $rep, $s);
        };

        foreach (['cod','weight','selling_price','pickup_lat','pickup_long','customer_lat','customer_long'] as $nk) {
            if ($final[$nk] !== null && $final[$nk] !== '') {
                $final[$nk] = $toEng($final[$nk]);
            }
        }

        return $final;
    }

    public function model(array $row)
    {
        // Normalize incoming row keys/values
        $row = $this->normalizeRow($row);

        $user = Auth::user();
        $merchantId = $user->merchant->id ?? $user->id;
        $merchant = Merchant::with('user')->find($merchantId);
        if (!$merchant) return null;

        // Resolve shop id by pickup_point (shop name) or fallback to provided shop_id
        $shopId = null;
        if (!empty($row['pickup_point'])) {
            $shop = MerchantShops::where('merchant_id', $merchant->id)
                ->where('name', $row['pickup_point'])
                ->first();
            if ($shop) $shopId = $shop->id;
        }
        if (!$shopId && !empty($row['shop_id'])) {
            $shopId = (int) $row['shop_id'];
        }

        // Resolve city/area ids if only names provided (optional)
        $cityId = $row['customer_city_id'];
        $areaId = $row['customer_area_id'];
        if (!$cityId && !empty($row['city']) && class_exists(City::class)) {
            
                $cityId = City::where('name', $row['city'])
        ->orWhere('en_name', $row['city'])
        ->value('id');
        
        }
        if (!$areaId && !empty($row['area']) && class_exists(Area::class)) {
             
                $areaId = Area::where('name', $row['area'])
        ->orWhere('en_name', $row['area'])
        ->value('id');
        
        }

        // Defaults
        $category_id      = 1;
        $delivery_type_id = 2;
        $liquid_fragile   = null;
        $packaging_id     = null;

        // Map fields
        $pickup_phone     = $row['pickup_phone'];
        $pickup_address   = $row['pickup_address'];
        $pickup_lat       = $row['pickup_lat'];
        $pickup_long      = $row['pickup_long'];

        $cash_collection  = $row['cod'] !== null ? (float) $row['cod'] : 0.0;
        $ref_number       = $row['reference_number'];
        $weight           = $row['weight'] !== null ? (float) $row['weight'] : 0.0;

        $customer_name    = $row['customer_name'];
        $customer_phone   = $row['customer_phone'];
        $customer_address = $row['customer_address'];
        $customer_lat     = $row['customer_lat'];
        $customer_long    = $row['customer_long'];

        // Charges
        $deliveryChargeAmount = $this->deliveryCharge($merchant->id, $category_id, $weight, $delivery_type_id);
        $codChargeAmount      = $this->codCharge($merchant, $cash_collection, $delivery_type_id);
        $liquidFragileAmount  = $liquid_fragile ? (float) setting('fragile_liquid_charge', 0) : 0.0;
        $packagingAmount      = 0.0;
        if (!empty($packaging_id)) {
            $packagingAmount = (float) (Packaging::find($packaging_id)->price ?? 0);
        }

        $codAmount            = (float) ($codChargeAmount['codAmount'] ?? 0);
        $merchantCodCharge    = (float) ($codChargeAmount['merchantCodCharge'] ?? 0);
        $vat                  = (float) ($merchant->vat ?? 0);

        $totalParcelAmount    = ($deliveryChargeAmount + $codAmount + $liquidFragileAmount + $packagingAmount);
        $vatTextAmount        = $this->percentage($totalParcelAmount, $vat);
        $totalCurrentAmount   = ($cash_collection - $totalParcelAmount) - $vatTextAmount;

        // Pickup/delivery dates
        if (date('H') < DeliveryTime::LAST_TIME) {
            $pickupDate   = date('Y-m-d');
            $deliveryDate = date('Y-m-d');
        } else {
            $pickupDate   = date('Y-m-d', strtotime('+1 day'));
            $deliveryDate = date('Y-m-d', strtotime('+1 day'));
        }

        $parcels = [
            'company_id'            => settings()->id,
            'merchant_id'           => $merchant->id,
            'first_hub_id'          => $merchant->user->hub_id ?? null,
            'hub_id'                => $merchant->user->hub_id ?? null,

            'category_id'           => $category_id,
            'weight'                => $weight,

            'invoice_no'            => $row['reference_number'] ?? null, // keep if you want
            'reference_number'      => $ref_number,

            'cash_collection'       => $cash_collection,
            'selling_price'         => $row['selling_price'] ?? null,

            'merchant_shop_id'      => $shopId,
            'pickup_phone'          => $pickup_phone,
            'pickup_address'        => $pickup_address,
            'pickup_lat'            => $pickup_lat,
            'pickup_long'           => $pickup_long,

            'customer_name'         => $customer_name,
            'customer_phone'        => $customer_phone,
            'customer_address'      => $customer_address,
            'customer_lat'          => $customer_lat,
            'customer_long'         => $customer_long,

            'city_id'               => $cityId,
            'area_id'               => $areaId,

            'delivery_type_id'      => $delivery_type_id,
            'pickup_date'           => $pickupDate,
            'delivery_date'         => $deliveryDate,

            'vat'                   => $vat,
            'vat_amount'            => $vatTextAmount,
            'delivery_charge'       => $deliveryChargeAmount,
            'cod_charge'            => $merchantCodCharge,
            'cod_amount'            => $codAmount,
            'total_delivery_amount' => $totalParcelAmount,
            'current_payable'       => $totalCurrentAmount,

            'note'                  => $row['note'] ?? null,
            'packaging_id'          => $packaging_id,
            'packaging_amount'      => $packagingAmount,
            'liquid_fragile_amount' => $liquidFragileAmount,

            'status'                => ParcelStatus::PENDING,
            'created_at'            => now(),
            'updated_at'            => now(),
        ];

        $parcel = Parcel::create($parcels);

        // tracking id
        $parcel->update(['tracking_id' => $this->generateTrackingId($parcel->id)]);

        return $parcel;
    }

    /** Helpers (same as your original) */
private function deliveryCharge($merchant_id, $category_id, $weight, $delivery_type_id)
{
    // 🔹 Fetch the latest delivery charge record for the merchant and category
    $charges = MerchantDeliveryCharge::where([
        'merchant_id' => $merchant_id,
        'category_id' => $category_id
    ])->latest('id')->first();

    // 🔹 Return 0 if no charge record is found
    if (!$charges) {
        return 0.0;
    }

    // 🔹 Calculate extra weight charge (only if weight > 5kg)
    $extraWeight = max(0, $weight - 5);
    $extraCharge = $extraWeight * ($charges->extra_weight_price ?? 0);

    // 🔹 Map delivery type IDs to their corresponding charge fields
    $chargeMap = [
        1 => $charges->same_day ?? 0,
        2 => $charges->next_day ?? 0,
        3 => $charges->sub_city ?? 0,
        4 => $charges->outside_city ?? 0,
    ];

    $baseCharge = $chargeMap[$delivery_type_id] ?? 0;

    // 🔹 Return total charge (base + extra weight)
    return (float) ($baseCharge + $extraCharge);
}


    private function codCharge($merchant, $cash_collection, $delivery_type_id)
    {
        $data = ['merchantCodCharge' => 0, 'codAmount' => 0];

        if ($delivery_type_id !== '' && ($delivery_type_id == '1' || $delivery_type_id == '2')) {
            $data['merchantCodCharge'] = $merchant->cod_charges['inside_city'] ?? 0;
            $data['codAmount'] = $this->percentage($cash_collection, $data['merchantCodCharge']);
        } elseif ($delivery_type_id != '' && $delivery_type_id == '3') {
            $data['merchantCodCharge'] = $merchant->cod_charges['sub_city'] ?? 0;
            $data['codAmount'] = $this->percentage($cash_collection, $data['merchantCodCharge']);
        } elseif ($delivery_type_id != '' && $delivery_type_id == '4') {
            $data['merchantCodCharge'] = $merchant->cod_charges['outside_city'] ?? 0;
            $data['codAmount'] = $this->percentage($cash_collection, $data['merchantCodCharge']);
        } else {
            $data['merchantCodCharge'] = 0;
            $data['codAmount'] = $this->percentage($cash_collection, 0);
        }

        return $data;
    }

    private function percentage($cash_collection, $cod_charges)
    {
        $cash = (float) ($cash_collection ?? 0);
        $pct  = (float) ($cod_charges ?? 0);
        return $cash * ($pct / 100);
    }
}
