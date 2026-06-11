<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run()
    {
        $blogs = [
            [
                'title' => '5 Ways to Cut Last-Mile Delivery Costs Without Hurting Customer Experience',
                'description' => <<<'HTML'
<p>Last-mile delivery is the most expensive leg of any shipment — often accounting for more than half of total fulfillment cost. For e-commerce merchants and 3PLs running tight margins, even small efficiency gains here translate directly to the bottom line. The challenge is that the levers most commonly used to reduce cost — slower service, fewer attempts, longer windows — also reduce customer satisfaction.</p><p>Below are five tactics we have seen consistently work in real operations, without compromising the experience the customer sees.</p><h3>1. Smart route batching</h3><p>Most dispatchers still build routes manually or by city zone. Modern routing engines that look at real road graphs, traffic, parcel volume per stop, and driver capacity routinely cut total kilometers by 15–25%. The win is not just fuel — it is more deliveries per shift, which lowers the per-parcel cost.</p><h3>2. First-attempt success rate</h3><p>Every failed delivery is paid for twice: the original attempt and the redelivery. Pre-delivery SMS with an accurate window, customer-facing tracking, and a reschedule link cut failures by 30–50% in markets where it has been measured.</p><h3>3. Address quality at order capture</h3><p>Bad addresses are the single biggest source of redeliveries. Validating against a known address database at checkout, asking for landmarks in markets where street addressing is weak, and surfacing past-failure flags catches problems before the driver leaves the hub.</p><h3>4. Hub-to-area driver assignment</h3><p>Drivers who repeatedly cover the same zone learn the buildings, the security guards, the office hours, and the customers. Stickiness of assignment is almost always cheaper than the perceived flexibility of round-robin dispatch.</p><h3>5. COD where COD is actually needed</h3><p>Cash-on-delivery is sometimes a non-negotiable expectation, sometimes a hangover from a market that has since moved to digital wallets. Audit the actual COD rate by area; in many regions it is well below 50% and shrinking. Offering a small discount for prepaid orders is a faster way to reduce handling cost than rebuilding the cash collection workflow.</p><p>None of these are silver bullets. But run together, they compound — and unlike service degradation, the customer feels them as a better experience, not a worse one.</p>
HTML,
            ],
            [
                'title' => 'The COD Reconciliation Playbook for E-commerce Merchants',
                'description' => <<<'HTML'
<p>Cash-on-delivery still represents a meaningful share of online orders across the Middle East, South Asia, and parts of Africa. It works for the customer, but it puts a serious operational burden on the merchant: every delivered parcel becomes a claim on cash that has to be tracked, collected from the courier, and reconciled against the invoice. When the process is loose, money goes missing — not stolen, just lost in a spreadsheet.</p><p>This is a playbook for getting COD under control.</p><h3>Treat the courier as an accounts-receivable customer</h3><p>The day a parcel is marked delivered, the courier owes you money. That is a receivable. Track it as one. Every COD-eligible parcel should show up in an aging report against the courier, with status (delivered, returned, in-transit) and expected payout date.</p><h3>Reconcile at the parcel level, not the batch level</h3><p>Most disputes happen because merchants accept a courier statement that says "Settlement: 24,500" without verifying which parcels make up that total. Insist on parcel-level breakdowns. Reconcile each tracking number against your own delivered list. Anything missing on the courier statement is either undelivered, returned, or — rarely but importantly — uncollected.</p><h3>Time-bound your payout windows</h3><p>"We pay when we pay" is the worst possible arrangement. Agree a payout cadence in writing: T+3, T+7, weekly on Tuesdays — whatever works. Then track adherence. A courier that misses payout windows is a courier that is using your cash to fund their float.</p><h3>Reconcile returns separately</h3><p>A returned parcel does not produce cash, but it produces a return-handling charge and may carry damages or COD-mismatch flags. Separate the return ledger from the delivered ledger so reversed cash flows are unambiguous.</p><h3>Automate the boring parts</h3><p>The reconciliation work that takes a finance team a full day every week — exporting the courier file, joining it to the order file, flagging mismatches — is exactly the work that does not need a human. A simple script or platform integration eliminates most of it. The remaining 5% of cases, where there is a real dispute, deserve the full attention of someone who can resolve it.</p><p>Get this right and COD stops being a finance headache. Get it wrong and it quietly eats your margin.</p>
HTML,
            ],
            [
                'title' => 'From Pickup to Doorstep: Mapping the Modern Parcel Journey',
                'description' => <<<'HTML'
<p>To the customer, a parcel is one event: it was ordered, and then it arrived. To the logistics operator, the same parcel passes through 8–12 distinct states before it reaches the customer's door. Understanding those states — and what can go wrong at each — is the difference between a courier that runs on instinct and one that runs on data.</p><h3>1. Order capture</h3><p>The parcel does not yet exist physically, but it exists as a record. This is where address quality, customer phone validity, and product weight estimates are locked in. Mistakes here propagate through every subsequent stage.</p><h3>2. Pickup assigned</h3><p>A driver is dispatched to collect the parcel from the merchant. Same-day, scheduled, or routed via existing delivery runs — the choice impacts the merchant's perceived service quality more than they often realize.</p><h3>3. In transit to hub</h3><p>The first leg of the actual journey. For city-only operations this may be 30 minutes; for cross-region shipments it can be days. Real-time visibility here is the visibility the merchant cares about most, because they no longer have the parcel.</p><h3>4. Received at hub / sorted</h3><p>The hub is where parcels move from "many origins, many destinations" to "one destination region, one route". Sortation accuracy, hub dwell time, and capacity headroom are the leading indicators of on-time performance for the whole network.</p><h3>5. Assigned for delivery</h3><p>A driver in the destination region is given the parcel as part of their runsheet. The quality of route planning at this step largely determines whether the customer's expected delivery window will be met.</p><h3>6. Out for delivery</h3><p>The parcel is now in a moving vehicle. The customer sees this and starts watching the door. Any update from this point — successful, delayed, attempted — needs to reach the customer fast.</p><h3>7. Delivered (or attempted)</h3><p>Success is binary at this state, but failures have many causes: customer not home, address incorrect, refused, COD short. Each cause needs a distinct downstream workflow.</p><h3>8. Cash settled and reconciled</h3><p>For COD parcels, the journey is not over until the cash is back in the merchant's account. This is the stage that traditional shipment dashboards forget about, and it is where most disputes live.</p><p>A modern logistics platform makes every one of these states queryable, time-stampable, and exportable. That is what separates a courier business from a logistics business: not the trucks, but the ability to know exactly where every parcel is, at every moment, and what is supposed to happen next.</p>
HTML,
            ],
        ];

        foreach ($blogs as $i => $blog) {
            $row              = new Blog();
            $row->company_id  = 1;
            $row->title       = $blog['title'];
            $row->description = $blog['description'];
            $row->position    = $i + 1;
            $row->created_by  = 1;
            $row->save();
        }
    }
}
