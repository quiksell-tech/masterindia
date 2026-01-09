<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs';

    protected $primaryKey = 'api_logs_id';

    protected $fillable = [
        'service',
        'api_action',
        'end_point',
        'request_data',
        'response_data',
        'header_status',
        'entity_id'
    ];

}
