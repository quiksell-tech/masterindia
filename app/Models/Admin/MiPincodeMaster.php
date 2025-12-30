<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiPincodeMaster extends Model
{
    protected $table = 'mi_pincode_master';

    protected $primaryKey = 'pincode_id';

    public $timestamps = false; // assuming no created_at / updated_at

    protected $fillable = [
        'pincode',
        'city_name',
        'state_name',
        'state_code',
        'is_active',
    ];
}
