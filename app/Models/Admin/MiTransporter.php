<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiTransporter extends Model
{
    protected $table = 'mi_transporters';
    protected $primaryKey = 'transporter_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'transporter_gstn',
        'is_active',
    ];
}
