<?php


namespace App\Http\Services;

use App\Models\Backend\GeneralSettings;
use Illuminate\Support\Facades\Http;

class PurchaseVerify
{
    public function purchaseVerify(){
        try {      
            $settings = GeneralSettings::find(1);
            if($this->PurchaseVerification($settings->purchase_code) == 200):
                return true;
            else:
                try { 
                    $post_data=[
                        'domain'        => request()->getHost(),
                        'purchase_code' => $settings->purchase_code
                    ];
                    $response = Http::get('https://apps.wemaxdevs.com/api/v10/wecourier-saas/customer/installation', $post_data);  
                } catch (\Throwable $th) {  
        
                } 
             return false;
            endif;
        } catch (\Throwable $th) {
             return false;
        }
    }

 
    public function PurchaseVerification($code) { 
        try { 
                $personalToken = "V5yV9o9ZkDkdFBIuesLEXqZNANZblTtu"; 
                // Surrounding whitespace can cause a 404 error, so trim it first
                $code = trim($code); 
                // Make sure the code looks valid before sending it to Envato
                // This step is important - requests with incorrect formats can be blocked!
                if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $code)) {
                    return "Invalid purchase code";
                } 
                $ch = curl_init();
                curl_setopt_array($ch, array( 
                    CURLOPT_URL => "https://api.envato.com/v3/market/author/sale?code={$code}", 
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer {$personalToken}",
                        "User-Agent: Purchase code verification script"
                    )
                )); 
                $response     = @curl_exec($ch);
                $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
                if (curl_errno($ch) > 0) {
                    return  "Failed to connect: " . curl_error($ch); 
                } 
                switch ($responseCode) {
                    case 404: return "Invalid purchase code";
                    case 403: return "The wemaxdevs token is missing the required permission for this script. Please contact to wemaxdevs.";
                    case 401: return "The wemaxdevs token is missing the required permission for this script. Please contact to wemaxdevs.";
                } 
                if ($responseCode !== 200) {
                   return "Got status {$responseCode}, try again shortly";
                } 
                $body = @json_decode($response); 
                if ($body === false && json_last_error() !== JSON_ERROR_NONE) {
                   return "Error parsing response, try again";
                } 
                // return $responseCode;

               if( !empty($response) ):
                    $result = json_decode($response,true); 
                    if(isset($result['buyer']) && isset($result['item']['id'])): 
                        if($result['item']['id'] == '51166784'):
                            return $responseCode;
                        else:
                            return false;
                        endif;
                    endif;
                endif;

                return false;

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    } 

}