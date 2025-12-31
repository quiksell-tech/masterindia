<?php


namespace App\Traits;


trait GetDB
{
    public function view_records($where=[], $select=[], $orderby=[], $groupby=[], $limit=1){

        $result=$this;

        if(!empty($where)){
            if(is_array($where)) {
                foreach ($where as $key => $val) {
                    if (is_array($val)) {
                        $result = $result->whereIn($key, $val);
                    } else {
                        $result = $result->where($key, $val);
                    }
                }
            }else{
                $result = $result->whereRaw($where);
            }
        }

        if(!empty($select)){
            $result=$result->select($select);
        }

        if(!empty($orderby)){
          if(is_array($orderby)){
              foreach($orderby as $column=>$sort_order){
                  $result=$result->orderBy($column, $sort_order);
              }
          }else{
              $result=$result->orderByRaw($orderby);
          }
        }

        if(!empty($groupby))
        {
          if(is_array($groupby)){
              $result=$result->groupBy($groupby);
          }else{
              $result=$result->groupBy($groupby);
          }
        }

        if($limit===1)
            $result=$result->first();
        else if($limit===0)
            $result=$result->get();
        else
            $result=$result->skip(0)->take($limit)->get();

        return $result;
    }
}
