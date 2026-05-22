<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ParcelController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/parcels/{parcel}/details', [ParcelController::class, 'details'])
        ->name('parcels.details');
});
