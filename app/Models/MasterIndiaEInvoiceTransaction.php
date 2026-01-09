<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIndiaEInvoiceTransaction extends BaseModel
{
    use HasFactory;

    protected $table = 'masterindia_einvoice_transaction';

    protected $primaryKey = 'masterindia_transaction_id';

    protected $fillable = [
      'order_id',
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
