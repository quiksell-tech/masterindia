<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\EwayBillController;
use App\Http\Controllers\Services\EInvoiceController;

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
    'eway-bill/{order_id}/eway-bill-details',
    [EwayBillController::class, 'getEwayBillDetails']
)->name('eway-bill.details');

// Einvoice
Route::post(
    'einvoce/{order_id}/creditnote-generate',
    [EInvoiceController::class, 'generateCreditNote']
)->name('einvoce.creditnote.generate');

Route::post(
    'einvoce/{order_id}/creditnote-cancel',
    [EInvoiceController::class, 'cancelCreditNote']
)->name('einvoce.creditnote.cancel');

Route::post(
    'einvoce/{order_id}/cancel',
    [EInvoiceController::class, 'cancelEInvoice']
)->name('einvoice.cancel');

Route::post(
    'einvoce/{order_id}/generate',
    [EInvoiceController::class, 'generateEInvoice']
)->name('einvoice.generate');


// To insert Data Into masterindia_creditnote_transactions and Items
Route::post(
    'creditnote-data/{order_id}/insert',
    [EInvoiceController::class, 'insertCreditNoteData']
)->name('einvoce.creditnote');

