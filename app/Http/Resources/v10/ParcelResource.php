<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class ParcelResource extends JsonResource
{
    
/**
 * Format any phone number into proper international format.
 * - Keeps numbers starting with + as-is
 * - Adds + if starts with valid country code (like 966, 249, 20, etc.)
 * - Adds +971 (UAE) if number length is 9 or 10 (local format)
 */
private function formatUaePhone(?string $raw): ?string
{
    if (!$raw) return null;

    // إزالة أي رموز غير رقمية
    $digits = preg_replace('/\D+/', '', $raw);

    // إذا بدأ بـ + نعيده كما هو
    if (strpos($raw, '+') === 0) {
        return $raw;
    }

    // إذا بدأ بـ 0 أو 5 نضيف +971
    if (strpos($digits, '0') === 0 || strpos($digits, '5') === 0) {
        $digits = ltrim($digits, '0'); // إزالة الصفر في البداية إن وجد
        return '+971' . $digits;
    }

    // إذا بدأ برمز دولي معروف مثل 966 أو 249 أو 20 أو 1 نضيف فقط +
    if (preg_match('/^(?:[1-9]\d{1,2})\d+$/', $digits)) {
        return '+' . $digits;
    }

    // في الحالات الأخرى نضيف +971 كافتراضي
    return '+971' . $digits;
}





    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        
        $shipmentId = $this->tracking_id ?? $this->id;
        $merchantName = optional($this->merchant)->business_name;
        $customerName = $this->customer_name;
        $amountCOD = number_format($this->cash_collection, 2);
        
        $link = "https://admin.rushly-logistic.com/shipment-location/{$shipmentId}";

        $wa_msg = "Welcome – {$customerName},\n"
                . "I am the delivery agent from *Rushly Express*, responsible for delivering your shipment No. *{$shipmentId}* from the store *{$merchantName}*.\n\n"
                . "Your shipment will be delivered *today* to the following address:\n"
                . "{$link}\n\n"
                . "Please confirm your address either by sharing your *location* or any other method, and confirm your *availability at the delivery location today*.\n\n"
                . "*Shipment details:*\n"
                . "Collection amount:*{$amountCOD} AED*\n\n"
                . "*Rushly Express*\n"
                . "A safe and reliable logistics partner.";
                

        return [
            "id"                    => $this->id,
            "tracking_id"           => $this->tracking_id,
            "merchant_id"           => $this->merchant_id,
            "merchant_name"         => $this->merchant->business_name,
            "merchant_user_name"    => $this->merchant->user->name,
            "merchant_user_email"   => $this->merchant->user->email,
             "merchant_mobile"       => $this->merchant->user->mobile,
            "merchant_address"      => $this->merchant->address,
            "customer_name"         => $this->customer_name,
            "customer_phone" => $this->formatUaePhone($this->customer_phone),
            // "customer_phone"        => (string)$this->customer_phone,
            "customer_address"      => $this->customer_address,
            "invoice_no"            => (string) $this->invoice_no,
            "weight"                => (string) $this->weight. ' '.optional($this->deliveryCategory)->title,
            "total_delivery_amount" => $this->total_delivery_amount,
            "cod_amount"            => $this->cod_amount,
            "vat_amount"            => $this->vat_amount,
            "current_payable"       => $this->current_payable,
            "cash_collection"       => $this->cash_collection,
            "delivery_type_id"      => (int) $this->delivery_type_id,
            "deliveryType"          => trans("deliveryType.".$this->delivery_type_id),
            "status"                => (int) $this->status,
            "statusName"            => trans("parcelStatus.".$this->status),
            'pickup_date'           => dateFormat($this->pickup_date),
            'delivery_date'         => dateFormat($this->delivery_date),
            'created_at'            => $this->created_at->format('d M Y, h:i A'),
            'updated_at'            => $this->updated_at->format('d M Y, h:i A'),
            'parcel_date'           => dateFormat($this->created_at) ,
            'parcel_time'           => date('h:i a', strtotime($this->created_at)) ,
            
            'wa_msg'           => $wa_msg ?? "" ,
        ];
    }
    
    
    /**
     * إخفاء الرقم مع إظهار آخر 4 أرقام فقط، مع الحفاظ على +
     * أمثلة:
     * +971528340406 => +********0406
     * 0535097129    => *****7129
     */
    private function maskLast4(?string $raw): ?string
    {
        if (!$raw) return null;

        $hasPlus = strpos($raw, '+') === 0;                 // هل يبدأ بـ +
        $digits  = preg_replace('/\D+/', '', $raw) ?? '';    // الأرقام فقط

        $len = strlen($digits);
        if ($len <= 4) {
            return ($hasPlus ? '+' : '') . $digits;
        }

        $masked = str_repeat('*', $len - 4) . substr($digits, -4);

        return ($hasPlus ? '+' : '') . $masked;
    }

}
