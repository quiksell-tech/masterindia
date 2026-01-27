<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\EwayBillController;
use App\Http\Controllers\Services\InwardEInvoiceController;
use App\Http\Controllers\Services\InwardEwayBillController;
use App\Http\Controllers\Services\EInvoiceController;

// For Outward Orders

// E-way Bill
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


// For Inward Orders


// E-Way Bill Routes
Route::prefix('inward-eway-bill/{order_id}')
    ->name('inward.eway-bill.')
    ->group(function () {

        Route::post('generate', [InwardEwayBillController::class, 'generateEwayBill'])
            ->name('generate');

        Route::post('cancel', [InwardEwayBillController::class, 'cancelEwayBill'])
            ->name('cancel');

        Route::post('update', [InwardEwayBillController::class, 'updateEwayBill'])
            ->name('update');

        Route::post('eway-bill-details', [InwardEwayBillController::class, 'getEwayBillDetails'])
            ->name('details');
    });


// E-Invoice Routes
Route::prefix('inward-einvoce/{order_id}')
    ->name('inward.einvoice.')
    ->group(function () {

        Route::post('generate', [InwardEInvoiceController::class, 'generateEInvoice'])
            ->name('generate');

        Route::post('cancel', [InwardEInvoiceController::class, 'cancelEInvoice'])
            ->name('cancel');

        Route::post('creditnote-generate', [InwardEInvoiceController::class, 'generateCreditNote'])
            ->name('creditnote.generate');

        Route::post('creditnote-cancel', [InwardEInvoiceController::class, 'cancelCreditNote'])
            ->name('creditnote.cancel');
    });

