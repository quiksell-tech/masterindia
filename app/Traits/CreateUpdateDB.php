<?php

/**
 * This trait provides update & create functions for repository models
 */

namespace App\Traits;


trait CreateUpdateDB
{
    public function updateRecord($where, $params){

        $result=$this->where($where)
            ->update($params);
        if(is_numeric($result))
            return true;
        else
            return false;

    }

    public function createRecord($params){
        return $this->create($params);
    }

    public function deleteRecords($where){
        $result=$this->where($where)->delete();
        if(is_numeric($result))
            return true;
        return false;
    }
}
