<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiCreditnoteItem extends Model
{
    protected $table = 'creditnote_items';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'creditnote_id',
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

    ];

}
