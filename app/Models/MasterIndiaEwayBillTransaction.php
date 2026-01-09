<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIndiaEwayBillTransaction extends Model
{
    use HasFactory;

    protected $table = 'masterindia_ewaybill_transaction';

    protected $primaryKey = 'masterindia_ewaybill_transaction_id';

    protected $fillable = [
        'order_id',
        'eway_bill_no',
        'eway_bill_date',
        'eway_bill_url',
        'valid_upto',
        'ebill_status',
        'cancellation_reason',
        'cancellation_remarks',
        'alert_message',
        'request_id'
    ];
}
