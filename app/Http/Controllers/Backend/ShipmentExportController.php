<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Exports\ShipmentsWorkbookExport;
use App\Exports\ShipmentTemplateExport;
use App\Repositories\MerchantPanel\Shops\ShopsInterface;
use App\Models\Backend\City;
use App\Models\Backend\Area; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ShipmentExportController extends Controller
{
    public function __construct(private ShopsInterface $shops) {}

    /**
     * GET /exports/shipment-template
     *
     * Query params (all optional):
     * - empty=1           -> export Shipments sheet without sample rows
     * - with_cities=0     -> skip Cities sheet (not recommended)
     * - with_areas=0      -> skip Areas sheet (not recommended)
     * - city_lang=en      -> make Shipments sample row use en city name (default uses Arabic name)
     */
    public function download(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403, 'Unauthorized');

        // ---------- 1) Build Shipments sample row (or empty) ----------
        $merchantId = $user->merchant->id ?? $user->id;
        $shop = $this->shops->firstForMerchant($merchantId);

        $pickupPoints  = trim((string)($shop->name ?? 'HQ'));
        $pickupPhone   = trim((string)($shop->contact_no ?? '+966500000000'));
        $pickupAddress = trim((string)($shop->address ?? 'Riyadh - HQ'));

        // A zero has to be written as a STRING. Maatwebsite's FromArray hands
        // the rows to Worksheet::fromArray($rows, $nullValue = null), which
        // skips a cell with a LOOSE comparison - and 0.0 == null is true in
        // PHP, so a numeric zero silently produced an empty cell. COD is a
        // required column, so that empty cell failed the import.
        $codInput  = $request->filled('cod') ? (float) $request->input('cod') : 150.0;
        $codSample = $codInput == 0.0 ? '0' : $codInput;

        $citySample = $request->string('city')->toString();
        $areaSample = $request->string('area')->toString();

        // If you prefer English city name in the sample row, pass ?city_lang=en
        if ($request->boolean('empty')) {
            $shipmentRows = [];
        } else {
            $shipmentRows = [[
                trim((string)$request->input('reference_number', '')), // A: Reference number
                $pickupPoints,                                 // B: Pickup point
                $pickupPhone,                                  // C: Pickup phone
                $pickupAddress,                                // D: Pickup address
                trim((string)$request->input('customer_name', 'Hani Yousif')),   // E: Customer Name *
                trim((string)$request->input('customer_phone', '966535097129')), // F: Customer Phone *
                $citySample !== '' ? $citySample : ($request->boolean('city_lang')
                    ? 'Dubai' // when ?city_lang=en, sample stays the same unless you want to switch to en list
                    : 'دبي'),                                     // G: City *
                $areaSample !== '' ? $areaSample : 'Hind City 1',  // H: Area
                trim((string)$request->input('customer_address', '399R+F7P Dubai - UAE')), // I: Customer Address *
                // Every REQUIRED column carries a usable value, so the file
                // imports as-is straight after download. Weight was blank and
                // COD was zero, which meant the first thing a merchant saw was
                // two validation errors on a sheet they had not touched.
                $request->filled('weight') ? (float)$request->input('weight') : 1,  // J: Weight *
                $codSample,                                                          // K: COD *
                trim((string)$request->input('note', '')),         // L: Note
            ]];
        }

        // ---------- 2) Build Cities sheet rows ----------
        $withCities = $request->has('with_cities') ? $request->boolean('with_cities') : true;
        $citiesRows = [];
        if ($withCities) {
            // Only required columns for our validation logic
            $citiesRows = City::query()
                ->orderBy('sorting') // or ->orderBy('name')
                ->get(['id','name','en_name','city_code'])
                ->map(fn($c) => [$c->id, $c->name, $c->en_name, $c->city_code])
                ->toArray();
        }

        // ---------- 3) Build Areas sheet rows ----------
        $withAreas = $request->has('with_areas') ? $request->boolean('with_areas') : true;
        $areasRows = [];
        if ($withAreas) {
            $areasRows = Area::query()
                ->orderBy('city_id')->orderBy('sorting')->orderBy('id')
                ->get(['id','area_code','name','en_name','city_id'])
                ->map(fn($a) => [$a->id, $a->area_code, $a->name, $a->en_name, $a->city_id])
                ->toArray();
        }

        // ---------- 4) Export workbook (Cities → Areas → Shipments) ----------
        // Named for the person downloading it, not for how it is assembled.
        // The date is ISO so the file sorts correctly in a downloads folder.
        $fileName = 'shipments-import-template-'.date('Y-m-d').'.xlsx';

        return Excel::download(
            new ShipmentsWorkbookExport($shipmentRows, $citiesRows, $areasRows),
            $fileName
        );
    }

    /**
     * If you still want the old single-sheet export for quick templates,
     * keep this endpoint.
     */
    public function downloadSingle(Request $request)
    {
        if ($request->boolean('empty')) {
            return Excel::download(new ShipmentTemplateExport([]), 'shipment-template-empty.xlsx');
        }

        $rows = [[
            'HQ', '+966500000000', 'Riyadh - HQ', 0, '', '', 'Hani Yousif',
            '966535097129', 'Dubai', 'Hind City 1', '399R+F7P Dubai - UAE', ''
        ]];

        return Excel::download(new ShipmentTemplateExport($rows), 'shipment-template-'.date('Ymd').'.xlsx');
    }
}
