<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class WmsKnowledgeBaseController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Wms/KnowledgeBase/Index', [
            'urls' => [
                'dashboard'    => route('wms.dashboard'),
                'products'     => route('wms.products.index'),
                'locations'    => route('wms.locations.index'),
                'stock'        => route('wms.stock.index'),
                'grn'          => route('wms.grn.index'),
                'adjustments'  => route('wms.adjustments.index'),
                'cycle_counts' => route('wms.cycle-counts.index'),
                'damage'       => route('wms.damage.index'),
                'fulfillment'  => route('wms.fulfillment.index'),
                'outbound'     => route('wms.outbound.index'),
            ],
        ]);
    }
}
