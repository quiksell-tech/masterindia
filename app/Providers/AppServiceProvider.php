<?php

namespace App\Providers;

use App\Repositories\Interfaces\ApiLogInterface;
use App\Repositories\Interfaces\EinvoiceDataInterface;
use App\Repositories\Interfaces\EwayBillDataInterface;
use App\Repositories\Interfaces\SystemParametersInterface;
use App\Repositories\ApiLogRepository;
use App\Repositories\SystemParameterRepository;
use App\Services\EInvoice\EinvoiceService;
use App\Services\EwayBill\EwayBillService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->scoped(ApiLogInterface::class, ApiLogRepository::class);
        $this->app->scoped(SystemParametersInterface::class, SystemParameterRepository::class);

        $this->app->scoped(EwayBillService::class, function($app){
            if(app()->runningInConsole()){
                request()->merge(['eway_service'=>'MasterIndia']);
            }
            if(request('eway_service')){
                //choose service from request
                if(class_exists('App\Services\EwayBill\\'.request('eway_service').'Service'))
                    return $app->make('App\Services\EwayBill\\'.request('eway_service').'Service');
            }else{
                return $app->make('App\Services\EwayBill\MasterIndiaService');
            }
            http_response_code(400);
            echo json_encode([
                'success'=>false,
                'message'=>'Service is not found'
            ]);
            die;
        });

        $this->app->scoped(EwayBillDataInterface::class, function($app){
            if(app()->runningInConsole()){
                request()->merge(['eway_service'=>'MasterIndia']);
            }
            if(request('eway_service')){
                //choose service from request
                if(class_exists('App\Repositories\\'.request('eway_service').'EwayBillTransactionRepository'))
                    return $app->make('App\Repositories\\'.request('eway_service').'EwayBillTransactionRepository');
            }
            http_response_code(400);
            echo json_encode([
                'success'=>false,
                'message'=>'Service is not found1'
            ]);
            die;
        });

        $this->app->scoped(EinvoiceService::class, function($app){
            if(app()->runningInConsole()){
                request()->merge(['einvoice_service'=>'MasterIndia']);
            }
            if(request('einvoice_service')){
                //die('App\Services\EInvoice\\'.request('einvoice_service'));
                //choose service from request
                if(class_exists('App\Services\EInvoice\\'.request('einvoice_service').'Service'))
                    return $app->make('App\Services\EInvoice\\'.request('einvoice_service').'Service');
            }
            http_response_code(400);
            echo json_encode([
                'success'=>false,
                'message'=>'Service is not found'
            ]);
            die;
        });

        $this->app->scoped(EinvoiceDataInterface::class, function($app){
            if(app()->runningInConsole()){
                request()->merge(['einvoice_service'=>'MasterIndia']);
            }
            if(request('einvoice_service')){
                //choose service from request
                if(class_exists('App\Repositories\\'.request('einvoice_service').'TransactionRepository')){
                    return $app->make('App\Repositories\\'.request('einvoice_service').'TransactionRepository');
                }
            }
            http_response_code(400);
            echo json_encode([
                'success'=>false,
                'message'=>'Service is not found'
            ]);
            die;
        });

    }
}
