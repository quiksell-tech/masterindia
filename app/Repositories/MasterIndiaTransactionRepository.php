<?php


namespace App\Repositories;


use App\Traits\CreateUpdateDB;
use App\Repositories\Interfaces\MasterIndiaTransactionInterface;
use App\Repositories\Interfaces\EinvoiceDataInterface;

class MasterIndiaTransactionRepository extends BaseRepository implements MasterIndiaTransactionInterface, EinvoiceDataInterface
{
    use CreateUpdateDB;

    protected $modelName = 'MasterIndiaTransaction';

    public function __construct(){
        parent::__construct();
        //echo "Calling OrderKey Constructor\n";
    }



    public function saveEinvoiceData($details,  $response){
        return $this->createRecord([
          'sell_invoice_ref_no' => $details['sell_invoice_ref_no'],
          'ack_no' => $response['message']['AckNo'],
          'ack_date' => $response['message']['AckDt'],
          'irn_no' => $response['message']['Irn'],
          'qrcode_url' => $response['message']['QRCodeUrl'],
          'einvoice_pdf_url' => $response['message']['EinvoicePdf'] ,
          'status_received' => $response['message']['Status'],
          'alert_message' => $response['message']['alert'],
          'request_id' => $response['requestId'],
          'invoice_status'=>'Created'
        ]);
    }


    public function cancelInvoice($invoice, $data, $response){

        return $invoice->update([
          'invoice_status' => 'Cancelled',
          'cancellation_reason' => $data['cancel_reason'],
          'cancellation_remarks' =>$data['cancel_remarks'],
          'status_received' => 'CNL'
        ]);
    }

}
