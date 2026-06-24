<?php


namespace App\Http\Services;

use App\Enums\Status;
use App\Models\Backend\SmsSetting;
use http\Client;
use Twilio\Rest\Client as TwilioClient;
class SmsService
{
    public function sendOtp($userPhone,$otpCode)
    {

        $smsSetting = smsSettings('reve_status');
        $smsTwilioSetting = smsSettings('twilio_status');
        $smsMsegatSetting = smsSettings('msegat_status');
        $smsTaqnyatSetting = smsSettings('taqnyat_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('otp',$userPhone,$otpCode);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('otp',$userPhone,$otpCode);
        }
        if($smsMsegatSetting == Status::ACTIVE){
            $this->msegatSms('otp',$userPhone,$otpCode);
        }
        if($smsTaqnyatSetting == Status::ACTIVE){
            $this->taqnyatSms('otp',$userPhone,$otpCode);
        }

    }

    public function sendSms($userPhone,$msg)
    {

        $smsSetting       = smsSettings('reve_status');
        $smsTwilioSetting = smsSettings('twilio_status');
        $smsNexmoSetting  = smsSettings('nexmo_status');
        $smsMsegatSetting = smsSettings('msegat_status');
        $smsTaqnyatSetting = smsSettings('taqnyat_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('sms',$userPhone,$msg);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('sms',$userPhone,$msg);
        }
        if($smsNexmoSetting == Status::ACTIVE){
            $this->nexmoSms('sms',$userPhone,$msg);
        }
        if($smsMsegatSetting == Status::ACTIVE){
            $this->msegatSms('sms',$userPhone,$msg);
        }
        if($smsTaqnyatSetting == Status::ACTIVE){
            $this->taqnyatSms('sms',$userPhone,$msg);
        }

    }

    private function reveSms ($type,$userPhone,$userMsg){
      
            try {
                    $api_key    = smsSettings('reve_api_key');
                    $api_secret = smsSettings('reve_secret_key');
                    $api_url    = smsSettings('reve_api_url');
                    $callerID   = settings()->name;
                    if($type == 'otp') {
                        $message = $userMsg . ' is your ' . settings()->name . ' verification code.';
                    }else {
                        $message = $userMsg;
                    }

                    $params = [
                        "apikey" => $api_key,
                        "secretkey" => $api_secret,
                        "callerID" => $callerID,
                        "toUser" => $userPhone,
                        "messageContent" => $message
                    ];

                    $url = $api_url . '?' . http_build_query($params);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 80); 
                    $response = curl_exec($ch);
                    curl_close($ch);  
                    return $response;
            } catch (\Exception $exception) {
                return $exception;
            }

    }

    private function twilioSms($type,$receiverNumber,$message){

        try {

            $account_sid = smsSettings('twilio_sid');
            $auth_token = smsSettings('twilio_token');
            $twilio_number = smsSettings('twilio_from');

            $client = new TwilioClient($account_sid, $auth_token); 
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);  
        return true;
        } catch (\Exception $exception) { 
            return $exception;
        }
    }

    /**
     * Taqnyat (Saudi SMS gateway) — Bearer-auth REST endpoint.
     * Docs: https://dev.taqnyat.sa/ar/doc/sms/
     */
    private function taqnyatSms($type, $receiverNumber, $message)
    {
        try {
            $token  = smsSettings('taqnyat_token');
            $sender = smsSettings('taqnyat_sender') ?: settings()->name;

            if ($type === 'otp') {
                $body = $message . ' is your ' . settings()->name . ' verification code.';
            } else {
                $body = $message;
            }

            // Taqnyat expects an array of integer phone numbers under `recipients`.
            $recipients = is_array($receiverNumber) ? $receiverNumber : [$receiverNumber];
            $recipients = array_values(array_map(fn ($n) => (string) $n, $recipients));

            $payload = [
                'recipients' => $recipients,
                'body'       => $body,
                'sender'     => $sender,
            ];

            $ch = curl_init('https://api.taqnyat.sa/v1/messages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    /**
     * MSEGAT (Saudi SMS gateway) — modern apiKey POST endpoint.
     * Docs: https://documenter.getpostman.com/view/39158411/2sBXwqqqD2
     */
    private function msegatSms($type, $receiverNumber, $message)
    {
        try {
            $userName = smsSettings('msegat_user_name');
            $apiKey   = smsSettings('msegat_api_key');
            $sender   = smsSettings('msegat_sender') ?: settings()->name;

            if ($type === 'otp') {
                $body = $message . ' is your ' . settings()->name . ' verification code.';
            } else {
                $body = $message;
            }

            $payload = [
                'userName'   => $userName,
                'apiKey'     => $apiKey,
                'numbers'    => $receiverNumber,
                'userSender' => $sender,
                'msg'        => $body,
            ];

            $ch = curl_init('https://www.msegat.com/gw/sendsms.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    private function nexmoSms($type,$receiverNumber,$message) {

        try {
            $nexmoKey = smsSettings('nexmo_key');
            $nexmoSecretKey = smsSettings('nexmo_secret_key');
            $basic  = new \Vonage\Client\Credentials\Basic($nexmoKey, $nexmoSecretKey);
            $client = new \Vonage\Client($basic);
            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($receiverNumber, settings()->name, $message)
            );
            $message = $response->current();

            if ($message->getStatus() == 0) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            return $e;
        }
    }


}
