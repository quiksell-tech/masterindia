<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiOrder extends Model
{

    protected $table = 'mi_order';
    protected $primaryKey = 'order_id';
    public $timestamps = true;
    protected $appends=['total_sale_value','total_tax','total_after_tax'];
    protected $fillable = [
        'order_invoice_number',
        'supply_type',
        'sub_supply_type',
        'document_type',
        'order_invoice_date',
        'transporter_id',
        'transporter_name',
        'transportation_mode',
        'vehicle_type',
        'bill_from_address_id',
        'bill_to_address_id',
        'ship_to_address_id',
        'dispatch_from_address_id',
        'bill_from_party_id',
        'ship_to_party_id',
        'bill_to_party_id',
        'dispatch_from_party_id',
        'vehicle_no',
        'is_active',
    ];
    public function billFromParty()
    {
        return $this->belongsTo(MiParty::class, 'bill_from_party_id', 'party_id');
    }

    public function billToParty()
    {
        return $this->belongsTo(MiParty::class, 'bill_to_party_id', 'party_id');
    }

    public function shipToParty()
    {
        return $this->belongsTo(MiParty::class, 'ship_to_party_id', 'party_id');
    }

    public function dispatchFromParty()
    {
        return $this->belongsTo(MiParty::class, 'dispatch_from_party_id', 'party_id');
    }

    public function billFromAddress()
    {
        return $this->belongsTo(MiCompanyAddress::class, 'bill_from_address_id', 'address_id');
    }

    public function billToAddress()
    {
        return $this->belongsTo(MiCompanyAddress::class, 'bill_to_address_id', 'address_id');
    }

    public function shipToAddress()
    {
        return $this->belongsTo(MiCompanyAddress::class, 'ship_to_address_id', 'address_id');
    }

    public function dispatchFromAddress()
    {
        return $this->belongsTo(MiCompanyAddress::class, 'dispatch_from_address_id', 'address_id');
    }
    public function items()
    {
        return $this->hasMany(MiOrderItem::class, 'order_id', 'order_id');
    }
    public function getTotalSaleValueAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalSaleValue;
    }
    public function getTotalTaxAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalTax;
    }

    public function getTotalAfterTaxAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalAfterTax;
    }

}
