<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Hub;

class HubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hubs = [
            [
                'name'            =>'Olaya',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Al Malaz',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Al Murabba',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Diriyah',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Al Naseem',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Al Wurud',
                'phone'           =>'966535097129',
                'address'         =>'Riyadh',
                'current_balance' => '00'
            ],
        ];

        for($n = 0; $n < sizeof($hubs); $n++)
        {
            $hub                  = new Hub();
            $hub->company_id      = 2;
            $hub->name            = $hubs[$n]['name'];
            $hub->phone           = $hubs[$n]['phone'];
            $hub->address         = $hubs[$n]['address'];
            $hub->current_balance = $hubs[$n]['current_balance'];
            $hub->save();
        }
    }
}
