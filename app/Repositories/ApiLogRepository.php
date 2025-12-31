<?php


namespace App\Repositories;

use App\Repositories\Interfaces\ApiLogInterface;

use App\Traits\CreateUpdateDB;

class ApiLogRepository extends BaseRepository implements ApiLogInterface
{
    use CreateUpdateDB;

    protected $modelName = 'ApiLog';

    public function __construct(){
        parent::__construct();
        //echo "Calling OrderKey Constructor\n";
    }
}
