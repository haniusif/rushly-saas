<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Support;
use App\Models\Backend\Wms\WmsProduct;
use Illuminate\Http\Request;

/**
 * Backs the topbar typeahead in AdminLayout. Returns up to 5 hits per
 * group (parcel / driver / client / product / ticket) for a free-text
 * query. Caller renders the groups; each row carries a `url` so the
 * UI can navigate straight to the detail page.
 */
class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%' . $q . '%';
        $groups = [];

        // Parcels — tracking_id / awb_label / invoice_no / customer_name / customer_phone
        if (hasPermission('parcel_read')) {
            $parcels = Parcel::companywise()
                ->where(function ($w) use ($like) {
                    $w->where('tracking_id',    'like', $like)
                      ->orWhere('awb_label',     'like', $like)
                      ->orWhere('invoice_no',    'like', $like)
                      ->orWhere('customer_name', 'like', $like)
                      ->orWhere('customer_phone','like', $like);
                })
                ->limit(5)
                ->get(['id', 'tracking_id', 'awb_label', 'customer_name', 'customer_phone', 'status']);
            if ($parcels->count()) {
                $groups['parcel'] = [
                    'label' => __('parcel.title') ?: 'Parcels',
                    'rows'  => $parcels->map(fn ($p) => [
                        'id'       => $p->id,
                        'title'    => $p->tracking_id ?: ('#' . $p->id),
                        'subtitle' => trim(($p->customer_name ?? '') . ($p->customer_phone ? ' · ' . $p->customer_phone : '')),
                        'meta'     => $p->awb_label,
                        'url'      => route('parcel.details', $p->id),
                    ])->values(),
                ];
            }
        }

        // Drivers (deliverymen) — user.name / user.mobile / user.unique_id
        if (hasPermission('delivery_man_read')) {
            $drivers = DeliveryMan::companywise()
                ->whereHas('user', function ($u) use ($like) {
                    $u->where('name',      'like', $like)
                      ->orWhere('mobile',  'like', $like)
                      ->orWhere('email',   'like', $like)
                      ->orWhere('unique_id','like',$like);
                })
                ->with('user:id,name,mobile,email,unique_id')
                ->limit(5)
                ->get(['id', 'user_id', 'employee_number']);
            if ($drivers->count()) {
                $groups['driver'] = [
                    'label' => __('deliveryman.title') ?: 'Drivers',
                    'rows'  => $drivers->map(fn ($d) => [
                        'id'       => $d->id,
                        'title'    => optional($d->user)->name ?: ('#' . $d->id),
                        'subtitle' => trim((optional($d->user)->mobile ?? '') . (optional($d->user)->email ? ' · ' . $d->user->email : '')),
                        'meta'     => optional($d->user)->unique_id,
                        'url'      => route('deliveryman.edit', $d->id),
                    ])->values(),
                ];
            }
        }

        // Clients (merchants) — business_name / user.name / merchant_unique_id
        if (hasPermission('merchant_read')) {
            $merchants = Merchant::companywise()
                ->where(function ($w) use ($like) {
                    $w->where('business_name',     'like', $like)
                      ->orWhere('merchant_unique_id', 'like', $like)
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)
                                                       ->orWhere('mobile', 'like', $like)
                                                       ->orWhere('email',  'like', $like));
                })
                ->with('user:id,name,mobile,email')
                ->limit(5)
                ->get(['id', 'user_id', 'business_name', 'merchant_unique_id']);
            if ($merchants->count()) {
                $groups['client'] = [
                    'label' => __('merchant.title') ?: 'Clients',
                    'rows'  => $merchants->map(fn ($m) => [
                        'id'       => $m->id,
                        'title'    => $m->business_name ?: optional($m->user)->name ?: ('#' . $m->id),
                        'subtitle' => trim((optional($m->user)->name ?? '') . (optional($m->user)->mobile ? ' · ' . $m->user->mobile : '')),
                        'meta'     => $m->merchant_unique_id,
                        'url'      => route('merchant.view', $m->id),
                    ])->values(),
                ];
            }
        }

        // Products — sku / name / barcode
        if (hasPermission('wms_manage')) {
            $products = WmsProduct::companywise()
                ->where(function ($w) use ($like) {
                    $w->where('sku',     'like', $like)
                      ->orWhere('name',  'like', $like)
                      ->orWhere('barcode','like',$like);
                })
                ->limit(5)
                ->get(['id', 'sku', 'name', 'barcode']);
            if ($products->count()) {
                $groups['product'] = [
                    'label' => 'Products',
                    'rows'  => $products->map(fn ($p) => [
                        'id'       => $p->id,
                        'title'    => $p->name,
                        'subtitle' => 'SKU ' . $p->sku,
                        'meta'     => $p->barcode,
                        'url'      => route('wms.products.show', $p->id),
                    ])->values(),
                ];
            }
        }

        // Tickets (support) — subject / description / id. The supports table
        // has no company_id column, so scope via the owning user.
        if (hasPermission('support_read') || hasPermission('support_admin')) {
            $companyId = settings()->id;
            $tickets = Support::whereHas('user', fn ($u) => $u->where('company_id', $companyId))
                ->where(function ($w) use ($like, $q) {
                    if (ctype_digit($q)) $w->orWhere('id', (int) $q);
                    $w->where('subject',     'like', $like)
                      ->orWhere('description','like',$like);
                })
                ->with('user:id,name,email,company_id')
                ->limit(5)
                ->get(['id', 'user_id', 'subject', 'priority', 'status', 'date']);
            if ($tickets->count()) {
                $groups['ticket'] = [
                    'label' => __('support.title') ?: 'Tickets',
                    'rows'  => $tickets->map(fn ($t) => [
                        'id'       => $t->id,
                        'title'    => '#' . $t->id . ' ' . ($t->subject ?: '—'),
                        'subtitle' => optional($t->user)->name ?: optional($t->user)->email,
                        'meta'     => $t->priority,
                        'url'      => route('support.view', $t->id),
                    ])->values(),
                ];
            }
        }

        return response()->json(['groups' => array_values($groups)]);
    }
}
