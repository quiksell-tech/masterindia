<?php

use App\Http\Controllers\Admin\CompanyAddressController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\MiCompanyController;
use App\Http\Controllers\Admin\MiPartyController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderItemController;
use App\Http\Controllers\Admin\TransporterController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', [AdminAuthController::class, 'loginPage']);
Route::post('/send-otp', [AdminAuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp']);
Route::get('/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware('admin.auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('companies')->group(function () {
        Route::get('/', [MiCompanyController::class, 'index'])->name('companies.index');
        Route::get('/create', [MiCompanyController::class, 'create'])->name('companies.create');
        Route::post('/store', [MiCompanyController::class, 'store'])->name('companies.store');
        Route::get('/{id}/edit', [MiCompanyController::class, 'edit'])->name('companies.edit');
        Route::post('/{id}/update', [MiCompanyController::class, 'update'])->name('companies.update');
    });

    Route::prefix('party')->group(function () {
        Route::get('/', [MiPartyController::class, 'index'])->name('party.index');
        Route::get('/create', [MiPartyController::class, 'create'])->name('party.create');
        Route::post('/store', [MiPartyController::class, 'store'])->name('party.store');
        Route::get('/{id}/edit', [MiPartyController::class, 'edit'])->name('party.edit');
        Route::post('/{id}/update', [MiPartyController::class, 'update'])->name('party.update');
    });

    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('items.index');
        Route::get('/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/store', [ItemController::class, 'store'])->name('items.store');
        Route::get('/{id}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::post('/{id}/update', [ItemController::class, 'update'])->name('items.update');
    });

    Route::get('company-addresses', [CompanyAddressController::class, 'index'])
        ->name('company-addresses.index');

    Route::get('company-addresses/create', [CompanyAddressController::class, 'create'])
        ->name('company-addresses.create');

    Route::post('company-addresses/store', [CompanyAddressController::class, 'store'])
        ->name('company-addresses.store');

    Route::get('company-addresses/{id}/edit', [CompanyAddressController::class, 'edit'])
        ->name('company-addresses.edit');

    Route::post('company-addresses/{id}/update', [CompanyAddressController::class, 'update'])
        ->name('company-addresses.update');


    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/store', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::post('/{order}/update', [OrderController::class, 'update'])->name('orders.update');

        Route::post('/{order}/items', [OrderItemController::class, 'saveItems'])
            ->name('orders.items.save');
        Route::delete('/items/{item}', [OrderItemController::class, 'destroy'])
            ->name('orders.items.delete');
        Route::get('/{order}/invoice-data', [OrderController::class, 'invoiceData']);
        Route::get('/{order}/generate-invoice', [OrderController::class, 'generateInvoice']);

        // AJAX
        Route::get('/party-search', [OrderController::class, 'searchParty'])->name('party.search');
        Route::get('/company-addresses/{companyId}', [OrderController::class, 'companyAddresses']);
    });

    Route::prefix('transporters')->group(function () {
        Route::get('/', [TransporterController::class, 'index'])
            ->name('transporters.index');
        Route::get('/create', [TransporterController::class, 'create'])
            ->name('transporters.create');
        Route::post('/store', [TransporterController::class, 'store'])
            ->name('transporters.store');
        Route::get('/{id}/edit', [TransporterController::class, 'edit'])
            ->name('transporters.edit');
        Route::post('/{id}/update', [TransporterController::class, 'update'])
            ->name('transporters.update');
    });
    Route::get('fetch-pincode/{pincode}', [CompanyAddressController::class, 'fetchPincode']);
    Route::get('/items/search', [OrderItemController::class, 'searchItems']);
    Route::get('/get-parties', [CompanyAddressController::class, 'getParty'])
        ->name('get.parties');

});
