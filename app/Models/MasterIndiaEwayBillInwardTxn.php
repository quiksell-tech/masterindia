<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIndiaEwayBillInwardTxn extends Model
{
    use HasFactory;

    protected $table = 'masterindia_ewaybill_inward_txn';

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
        'request_id',
        'created_at',
        'updated_at',
    ];
}
