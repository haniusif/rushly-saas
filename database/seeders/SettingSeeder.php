<?php

namespace Database\Seeders;

use App\Models\Backend\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        //social login settings
        //facebook
        Setting::create(['company_id'=>1,'key' => 'facebook_client_id',     'value' => '']);
        Setting::create(['company_id'=>1,'key' => 'facebook_client_secret', 'value' => '']);
        Setting::create(['company_id'=>1,'key' => 'facebook_status',        'value' => 1]);
        //google
        Setting::create(['company_id'=>1,'key' => 'google_client_id',     'value' => '']);
        Setting::create(['company_id'=>1,'key' => 'google_client_secret', 'value' => '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']);
        Setting::create(['company_id'=>1,'key' => 'google_status',        'value' => 1]);


        $this->supperAdminPaymentGateway();

        //===== payment setup ===
        //stripe
        Setting::create(['company_id'=>2,'key' => 'stripe_publishable_key',     'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'stripe_secret_key',          'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'stripe_status',              'value' => 1]);

        //Razorpay
        Setting::create(['company_id'=>2,'key' => 'razorpay_key',               'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'razorpay_secret',            'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'razorpay_status',            'value' => 1]);

        //sslcommerz
        Setting::create(['company_id'=>2,'key' => 'sslcommerz_store_id',        'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'sslcommerz_store_password',  'value' => '@ssl']);
        Setting::create(['company_id'=>2,'key' => 'sslcommerz_testmode',        'value' => 1]);
        Setting::create(['company_id'=>2,'key' => 'sslcommerz_status',          'value' => 1]);

        //paypal
        Setting::create(['company_id'=>2,'key' => 'paypal_client_id',              'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'paypal_client_secret',          'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'paypal_mode',                   'value' => 'sendbox']);
        Setting::create(['company_id'=>2,'key' => 'paypal_status',                 'value' => 1]);

        //skrill
        Setting::create(['company_id'=>2,'key' => 'skrill_merchant_email',         'value' => 'demoqco@sun-fish.com']);
        Setting::create(['company_id'=>2,'key' => 'skrill_status',                 'value' => 1]);


        // //bkash
        Setting::create(['company_id'=>2,'key' => 'bkash_app_id',              'value' => 'application id']);
        Setting::create(['company_id'=>2,'key' => 'bkash_app_secret',          'value' => 'application secret key']);
        Setting::create(['company_id'=>2,'key' => 'bkash_username',            'value' => 'username']);
        Setting::create(['company_id'=>2,'key' => 'bkash_password',            'value' => 'password']);
        Setting::create(['company_id'=>2,'key' => 'bkash_test_mode',           'value' => 1]);
        Setting::create(['company_id'=>2,'key' => 'bkash_status',              'value' => 1]);


        //aamar pay
        Setting::create(['company_id'=>2,'key' => 'aamarpay_store_id',        'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'aamarpay_signature_key',   'value' => '']);
        Setting::create(['company_id'=>2,'key' => 'aamarpay_sendbox_mode',    'value' => 1]);
        Setting::create(['company_id'=>2,'key' => 'aamarpay_status',          'value' => 1]);
          
        //=====payment setup===
 

    }
  
    public function supperAdminPaymentGateway(){
        //===== payment setup ===
        //stripe
        Setting::create(['company_id'=>1,'key' => 'stripe_publishable_key',     'value' => '']);
        Setting::create(['company_id'=>1,'key' => 'stripe_secret_key',          'value' => '']);
        Setting::create(['company_id'=>1,'key' => 'stripe_status',              'value' => 1]);
 
 
    }

}
