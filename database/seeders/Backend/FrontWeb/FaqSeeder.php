<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run()
    {
        $faqs = [
            [
                'question' => 'What is Rushly Logistic?',
                'answer'   => 'Rushly Logistic is a same-day and next-day parcel delivery service that helps merchants ship orders to customers across the country with real-time tracking, automated status updates, and integrated cash-on-delivery handling.',
            ],
            [
                'question' => 'How do I sign up as a merchant?',
                'answer'   => 'Visit rushly-logistic.com, click "Sign up", choose the Merchant plan that fits your monthly order volume, complete your business profile, and our team will verify your account within one business day.',
            ],
            [
                'question' => 'Which areas do you deliver to?',
                'answer'   => 'We currently cover all major cities and most secondary cities. You can check coverage instantly by entering a destination postcode on the rate calculator on our homepage before booking.',
            ],
            [
                'question' => 'How fast is delivery?',
                'answer'   => 'Same-city pickups booked before 2 PM are delivered the same day. Intercity orders typically arrive within 24-48 hours, and remote-area deliveries may take 2-4 business days.',
            ],
            [
                'question' => 'How much does shipping cost?',
                'answer'   => 'Pricing depends on parcel weight, destination zone, and your subscription tier. Merchants can see live rates in the dashboard before confirming each shipment, and volume discounts apply automatically above 100 parcels per month.',
            ],
            [
                'question' => 'Can I track my parcel in real time?',
                'answer'   => 'Yes. Every parcel gets a unique tracking ID; both you and your customer can follow live status updates via the tracking page, WhatsApp notifications, and SMS at each handover point.',
            ],
            [
                'question' => 'Do you handle cash on delivery (COD)?',
                'answer'   => 'Yes. We collect cash from the recipient on your behalf and settle the funds to your registered bank account on a weekly cycle (or daily for Pro and Enterprise plans), minus the standard COD handling fee.',
            ],
            [
                'question' => 'What happens if a parcel is lost or damaged?',
                'answer'   => 'File a claim from your dashboard within 7 days of the incident. Verified claims are reimbursed up to the declared parcel value, or up to the standard liability cap for parcels without declared value.',
            ],
            [
                'question' => 'Can I integrate Rushly Logistic with my online store?',
                'answer'   => 'Yes. We provide native integrations for Salla, Zid, and WooCommerce, plus a REST API and webhooks so orders sync to Rushly automatically and tracking flows back to your store.',
            ],
            [
                'question' => 'How do I contact support?',
                'answer'   => 'Reach us at info@rushly-logistic.com, via the live-chat widget on rushly-logistic.com, or through the in-app support button. Merchant support is available Sunday-Thursday, 9 AM - 6 PM, with on-call coverage for active deliveries 24/7.',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            $row             = new Faq();
            $row->company_id = 1;
            $row->question   = $faq['question'];
            $row->answer     = $faq['answer'];
            $row->position   = $i + 1;
            $row->save();
        }
    }
}
