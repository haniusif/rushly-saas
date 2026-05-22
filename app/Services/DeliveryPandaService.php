<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeliveryPandaService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.deliverypanda.base_url');
        $this->apiKey = config('services.deliverypanda.key');
    }

    protected function request($endpoint, array $data)
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'API-KEY' => $this->apiKey,
        ])->post($this->baseUrl . $endpoint, $data)->json();
    }

    public function createAutoBooking(array $data)
    {
        return $this->request('CustomerBooking', $data);
    }

    public function createAgentBooking(array $data)
    {
        return $this->request('AgentBooking', $data);
    }

    public function createCustomerToCustomer(array $data)
    {
        return $this->request('CustomertoCustomerBooking', $data);
    }

    public function getTracking(array $awbNumbers)
    {
        return $this->request('GetTracking', ['AwbNumber' => $awbNumbers]);
    }
    
      public function getListTracking(array $awbNumbers)
    {
        return $this->request('GetTracking',  ['AwbNumber' => $awbNumbers]);
    }
}
