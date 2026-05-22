<?php

namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class WMSController extends Controller
{
    public function index()        { return view('backend.wms.dashboard'); }
    public function products()     { return view('backend.wms.products'); }
    public function inventory()    { return view('backend.wms.inventory'); }
    public function receiving()    { return view('backend.wms.receiving'); }
    public function shipping()     { return view('backend.wms.shipping'); }
    public function locations()    { return view('backend.wms.locations'); }
    public function adjustments()  { return view('backend.wms.adjustments'); }
    public function reports()      { return view('backend.wms.reports'); }
}
