<?php

/**
 * This trait provide function for reading configurations from system_parameters table
 */

namespace App\Traits;


use App\Facades\Config;
use App\Repositories\Interfaces\SystemParametersInterface;

trait GetConfig
{

    /*
     * This function uses singleton object  to capture complete parameters list
     * required by application.
     */
    public function getSystemParams($providers_list=[]){
        $params=Config::all();

        $params=array_filter($params, function($k) use ($providers_list) {
            return in_array($k, $providers_list);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($params as $key=>$val) {
            foreach($val as $prm_name=>$prm_val){
                $x = $prm_name;
                $this->{$x} = $prm_val;
            }
        }

    }

    /**
     * This function reads database to fetch configuration for given providers
     * @param $params array|string List of providers
     */
    public function getSystemParametersByProvider($providers){
        $systemParameters=app('App\Repositories\Interfaces\SystemParametersInterface');
        $this->systemParameters=$systemParameters;
        $const = $this->systemParameters->getParam($providers);
        foreach ($const as $c) {
            $x = $c->sysprm_name;
            $this->{$x} = $c->sysprm_value;
        }
    }

    /**
     * This function reads database to fetch configuration for given sysprm_name list
     * @param $params array|string List of sysprm_name
     */
    public function getSystemParametersByName($params_name){
        $systemParameters=app('App\Repositories\Interfaces\SystemParametersInterface');
        $this->systemParameters=$systemParameters;
        $const = $this->systemParameters->getParamByName($params_name);
        foreach ($const as $c) {
            $x = $c->sysprm_name;
            $this->{$x} = $c->sysprm_value;
        }
    }


}
