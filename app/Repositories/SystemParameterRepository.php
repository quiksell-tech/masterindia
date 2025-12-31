<?php


namespace App\Repositories;

use App\Repositories\Interfaces\SystemParametersInterface;
use App\Traits\CreateUpdateDB;

class SystemParameterRepository extends BaseRepository implements SystemParametersInterface
{

    use CreateUpdateDB;

    protected  $modelName='SystemParameter';

    public function __construct(){
        //echo rand(1111,9999)."creating repository\n";
        parent::__construct();
    }

    function get_my_config($provider = false)
    {
        if ($provider) {
            $data = $this->getparam($provider);
        } else {
            $data = $this->getparam();
        }

        if ($data) {
            return $data;
        } else {
            return false;
        }
    }


    public function getparam($provider = false)
    {

        $params=[];
        if (is_array($provider)) {
            $params=$this->whereIn('sysprm_provider', $provider);
            $params=$params->where('current_flag', 'Y')
                ->get();
        } else if ($provider) {
            $params=$this->where('sysprm_provider', $provider);
            $params=$params->where('current_flag', 'Y')
                ->get();
        }


        return $params;
    }


    public function getParamByName($param_name)
    {

        $params=[];
        if (is_array($param_name)) {
            $params=$this->whereIn('sysprm_name', $param_name);
            $params=$params->where('current_flag', 'Y')
                ->get();
        } else if ($param_name) {
            $params=$this->where('sysprm_name', $param_name);
            $params=$params->where('current_flag', 'Y')
                ->get();
        }


        return $params;
    }

    public function getTransporter($tracking_partner_name){
      return \DB::table('private_db.logistics_transporter')
          ->where('transporter_name', $tracking_partner_name)
          ->first();

    }

}
