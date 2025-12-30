<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiItem extends Model
{
    protected $table = 'mi_items';

    protected $primaryKey = 'item_id';

    public $timestamps = true;

    protected $fillable = [
        'item_name',
        'item_description',
        'item_code',
        'hsn_code',
        'tax_percentage',
        'is_active',
        'is_active',
    ];

}
