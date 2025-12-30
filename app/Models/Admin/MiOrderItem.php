<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiOrderItem extends Model
{
    protected $table = 'mi_order_items';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'order_id',
        'item_id',
        'total_item_quantity',
        'item_unit',
        'price_per_unit',
        'taxable_amount',
        'after_tax_value',
        'item_name',
        'item_description',
        'item_code',
        'hsn_code',
        'tax_percentage',
        'is_active',
    ];
}
