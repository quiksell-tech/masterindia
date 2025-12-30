<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiCompany extends Model
{
    protected $table = 'mi_companies';
    protected $primaryKey = 'company_id';

    protected $fillable = [
        'name',
        'legal_name',
        'is_active'
    ];

}
