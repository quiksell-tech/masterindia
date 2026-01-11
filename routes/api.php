<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\EwayBillController;

Route::post(
    'eway-bill/{order_id}/generate',
    [EwayBillController::class, 'generateEwayBill']
)->name('eway-bill.generate');

Route::post(
    'eway-bill/{order_id}/cancel',
    [EwayBillController::class, 'cancelEwayBill']
)->name('eway-bill.cancel');

Route::post(
    'eway-bill/{order_id}/update',
    [EwayBillController::class, 'updateEwayBill']
)->name('eway-bill.update');

Route::post(
    'einvoce/{order_id}/creditnote',
    [EwayBillController::class, 'generateCreditNote']
)->name('einvoce.creditnote');

Route::post(
    'einvoce/{order_id}/cancel',
    [EwayBillController::class, 'cancelEInvoice']
)->name('einvoice.cancel');

Route::post(
    'einvoce/{order_id}/generate',
    [EwayBillController::class, 'generateEInvoice']
)->name('einvoice.generate');
