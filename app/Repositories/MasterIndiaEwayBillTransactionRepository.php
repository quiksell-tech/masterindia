<?php

namespace App\Repositories;

use App\Traits\CreateUpdateDB;
use App\Repositories\Interfaces\EwayBillDataInterface;

class MasterIndiaEwayBillTransactionRepository extends BaseRepository implements EwayBillDataInterface
{
    use CreateUpdateDB;

    protected $modelName = 'MasterIndiaEwayBillTransaction';

    public function __construct(){
        parent::__construct();
        //echo "Calling OrderKey Constructor\n";
    }


    public function saveEwayBillData($details, $response){
        return $this->createRecord([
            'sell_invoice_ref_no' => $details['sell_invoice_ref_no'],
            'eway_bill_no' => $response['message']['ewayBillNo'],
            'eway_bill_date' => date('Y-m-d H:i:s', strtotime(str_replace('/','-', $response['message']['ewayBillDate']))),
            'valid_upto' => date('Y-m-d H:i:s', strtotime(str_replace('/','-', $response['message']['validUpto']))),
            'eway_bill_url' => $response['message']['url'],
            'ebill_status' => 'Created',
            'alert_message' => $response['message']['alert'],
            'request_id' => $response['requestId'],
        ]);
    }

    public function cancelEwayBill($ewaybill, $details, $response){
        return $ewaybill->update([
            'ebill_status' => 'Cancelled',
            'cancellation_reason' => $details['cancel_reason'],
            'cancellation_remarks' => $details['cancel_remarks']
        ]);
    }
}
