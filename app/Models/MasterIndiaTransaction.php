<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIndiaTransaction extends BaseModel
{
    use HasFactory;

    protected $table = 'masterindia_transaction';

    protected $primaryKey = 'masterindia_transaction_id';

    protected $fillable = [
      'sell_invoice_ref_no',
      'ack_no',
      'ack_date',
      'irn_no',
      'qrcode_url',
      'einvoice_pdf_url',
      'status_received',
      'alert_message',
      'request_id',
      'invoice_status',
      'cancellation_reason',
      'cancellation_remarks'
    ];
}
