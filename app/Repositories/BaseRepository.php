<?php


namespace App\Repositories;


use App\Traits\GetDB;

abstract class BaseRepository
{
    use GetDB;
    /**
     * Model Class used for this repository
     *
     * @var null
     **/
    protected $model;

    /**
     * Intialize base classess
     *
     * @return void
     **/
    public function __construct()
    {
        if (!is_null($this->modelName)) {
            $class =  'App\Models\PrivateDB\\' . $this->modelName;
            $this->model = new $class;
        }
        $this->auth = auth();
        //echo rand(1111, 9999).get_class($this)." created\n";
    }

    /**
     * Call directally model methods if it does not exists here.
     *
     * @return mix
     **/
    public function __call($method, $args)
    {
        if (!is_null($this->model)) {
            return call_user_func_array([$this->model, $method], $args);
        }
        throw new Exception("No model found!", 404);
    }


    public function view($where=[], $select=[], $orderby=[], $groupby=[], $limit=1){

        $result=$this;

        if(!empty($where))
            $result=$result->where($where);

        if(!empty($select)){
            $result=$result->select($select);
        }

        if(!empty($orderby)){
            foreach($select as $key=>$val){
                $result=$result->orderBy($key, $val);
            }
        }

        if(!empty($groupby))
        {
            $groupby=implode(',', $groupby);
            foreach($select as $key=>$val){
                $result=$result->groupBy($groupby);
            }
        }

        if($limit === 1)
            $result=$result->first();
        else if($limit === 0)
            $result=$result->get();
        else
            $result=$result->skip(0)->take($limit)->get();

        return $result;
    }

}
