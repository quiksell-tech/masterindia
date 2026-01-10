<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\EwayBillController;

Route::post(
    'eway-bill/{order}/generate',
    [EwayBillController::class, 'generateEwayBill']
)->name('eway-bill.generate');
