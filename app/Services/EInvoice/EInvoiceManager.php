<?php

namespace App\Services\EInvoice;

use App\Repositories\Interfaces\CreditNoteDetailInterface;
use App\Repositories\Interfaces\CreditNoteTransctionInterface;
use App\Repositories\Interfaces\PartyDetailInterface;
use App\Repositories\Interfaces\PartySellOrderDetailInterface;
use App\Repositories\Interfaces\PartySellOrderSummaryInterface;
use App\Repositories\Interfaces\PartyAddressInterface;
use App\Repositories\Interfaces\EinvoiceDataInterface;
use App\Repositories\Interfaces\CompanyDetailInterface;
use App\Services\EwayBill\EwayBillService;
use App\Traits\GetConfig;
use Illuminate\Http\Response;

class EInvoiceManager
{

    use GetConfig;

    public function __construct(EinvoiceService $einvoiceService, EinvoiceDataInterface $eninvoiceData, EwayBillService $ewayBillService){
        $this->einvoiceService = $einvoiceService;
        $this->partySellOrderSummary = $partySellOrderSummary;
        $this->partySellOrderDetail = $partySellOrderDetail;
        $this->partyDetail = $partyDetail;
        $this->partyAddress = $partyAddress;
        $this->eninvoiceData = $eninvoiceData;
        $this->company_details = $company_details;
        $this->ewayBillService = $ewayBillService;
        $this->creditNoteTransction = $creditNoteTransction;
        $this->creditNoteDetail = $creditNoteDetail;
        $this->getSystemParams(['RecycleDevice']);
    }


    public function generateEInvoice($data){

        $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
        if($invoice)
            return json_response(400, 'Einvoice already created by '.($data['einvoice_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id', 'sell_order_summary_id', 'sell_invoice_ref_no', 'eway_bill_number', 'actual_weight', 'volume_weight', 'item_length', 'item_breadth', 'item_height', 'item_pieces', 'sell_invoice_total_value','invoice_date', 'sell_invoice_gst_value', 'shipment_warehouse', 'ship_to_contact_person_name', 'ship_to_contact_person_number', 'ship_to_company_name', 'ship_to_address_line_1', 'ship_to_address_line_2', 'ship_to_city', 'ship_to_state', 'ship_to_pincode', 'ext_invoice_ref_no', 'sell_invoice_discount_value', \DB::raw('private_db.get_invoice_igst_flag(sell_invoice_ref_no) as igst_flag'), 'purchaser_party_id']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        $party = $this->partyDetail->view_records(['party_id'=>$psos->purchaser_party_id]);
        if(empty($party->party_gstin))
            return json_response(400, 'Party GSTIN is not found');

        $party_address = $this->partyAddress->view_records(['party_id'=>$party->party_id, 'address_type'=>'Office'], ['party_address_line_1', 'party_address_line_2', 'party_city', 'party_state', 'party_pincode', \DB::raw('private_db.get_gst_state_code(party_state) as party_state_code')]);
        if(!$party_address)
          return json_response(400, 'Party address is not found');

        //check for same buyer state and ship to state
        if($party_address->party_state != $psos->ship_to_state){
            return json_response(400, 'Einvoice cannot be generated. Party state '.$party_address->party_state.' must be same as ship_to_state '.$psos->ship_to_state);
        }

        // if(stripos($psos->shipment_warehouse, 'noida')!==false)
        //     $company_details =[
        //         'company_name' => $this->COMPANY_NAME,
        //         'company_mobile' => $this->COMPANY_LOGISTICS_PHONE,
        //         'company_email' => $this->COMPANY_WH_EMAIL,
        //         'company_pincode' => $this->COMPANY_PINCODE,
        //         'company_address' => $this->COMPANY_ADDRESS,
        //         'company_gstin' => $this->COMPANY_GSTIN,
        //         //'api_user_gstin' => $this->COMPANY_GSTIN,
        //         'company_city' => $this->COMPANY_CITY,
        //         'company_state' => $this->COMPANY_STATE
        //     ];
        // else{
        //     $company_details =[
        //         'company_name' => $this->COMPANY_NAME,
        //         'company_mobile' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_LOGISTICS_PHONE'},
        //         'company_email' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_WH_EMAIL'},
        //         'company_pincode' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_PINCODE'},
        //         'company_address' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_ADDRESS'},
        //         'company_gstin' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'},
        //         //'api_user_gstin' => $this->COMPANY_GSTIN,
        //         'company_city' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_CITY'},
        //         'company_state' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_STATE'}
        //     ];
        // }

        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_details=[
          'company_name' => $company->party_name,
          'company_mobile' => $company->party_phone_number,
          'company_email' => $company->party_email,
          'company_pincode' => $company->party_pincode,
          'company_address_1' => $company->party_address_line_1,
          'company_address_2' => $company->party_address_line_2,
          'company_gstin' => $company->party_gstin,
          //'api_user_gstin' => $this->COMPANY_GSTIN,
          'company_city' => $company->party_city,
          'company_state' => $company->party_state
        ];

        $psod = $this->partySellOrderDetail->getInvoiceItemsDetail($data['sell_invoice_ref_no']);
        if(count($psod)==0)
            return json_response(400, 'No details found for Sell invoice reference number.');


        //check if gstin is valid if class_exists
        if(!empty($party->party_gstin) && strlen($party->party_gstin)>=15){
            $valid = $this->ewayBillService->getGSTINDetails([
                'buyer_gstin' => $party->party_gstin,
                'sell_invoice_ref_no' => $psos->sell_invoice_ref_no,
                'company_gstin'=> $company_details['company_gstin']
            ]);
            if($valid instanceof Response){
                // update psos for error
                $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
                    [
                        'irn_status' => 'E',
                        'irn_status_message' => json_decode($valid->getContent(), true)['message']??''
                    ]);
                return $valid;
            }

            if($valid['gstin_status']!='active'){

                // set error to skip this record for batch process
                $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
                    [
                        'irn_status' => 'E',
                        'irn_status_message' => 'GSTIN not active: '.($valid['gstin_status']??'unknown')
                    ]);
                return json_response(400, 'Buyer GSTIN is not active');
            }

        }


        $details = array_merge($company_details, $psos->toArray(),['buyer_gstin'=>$party->party_gstin, 'party_name'=>$party->party_name,'party_legal_name'=>$party->party_legal_name, 'party_mobile'=>$party->party_mobile], $party_address->toArray());

        $details['items_list'] = $psod;

        $response = $this->einvoiceService->generateEInvoice($details);

        if($response instanceof Response){
              //update psos for failure
              $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
              [
                'irn_status'=>'E',
                'irn_status_message'=>json_decode($response->getContent(), true)['message']??''
              ]);
              return $response;
        }


        if(!$this->eninvoiceData->saveEinvoiceData($details, $response))
            return json_response(500, 'Invoice created but failed to save response');

        // update psos for completion
        $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
        [
          'irn_status' => 'C',
          'irn_status_message' => 'Einvoice has been created by '.($data['einvoice_service']).':'.($response['display_message']??'')
        ]);
        return json_response(200, 'Einvoice has been created by '.($data['einvoice_service']).':'.($response['display_message']??''));

    }

    public function generateCreditNote($data){

        $credit_note = $this->creditNoteTransction->view_records([
            'credit_note_ref_no' => $data['credit_note_ref_no']
        ]);
        if(!$credit_note)
            return json_response(400, 'Credit note: invalid reference no ');
        if ($credit_note->credit_note_status == 'C')
            return json_response(400, 'Already Created');

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$credit_note->sell_invoice_ref_no], ['seller_party_id', 'sell_order_summary_id', 'sell_invoice_ref_no', 'eway_bill_number', 'actual_weight', 'volume_weight', 'item_length', 'item_breadth', 'item_height', 'item_pieces', 'sell_invoice_total_value','invoice_date', 'sell_invoice_gst_value', 'shipment_warehouse', 'ship_to_contact_person_name', 'ship_to_contact_person_number', 'ship_to_company_name', 'ship_to_address_line_1', 'ship_to_address_line_2', 'ship_to_city', 'ship_to_state', 'ship_to_pincode', 'ext_invoice_ref_no', 'sell_invoice_discount_value', \DB::raw('private_db.get_invoice_igst_flag(sell_invoice_ref_no) as igst_flag'), 'purchaser_party_id']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        $party = $this->partyDetail->view_records(['party_id'=>$psos->purchaser_party_id]);
        if(empty($party->party_gstin)) {
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'C',
                    'credit_note_status_message' => 'NO GSTIN',
                    'einvoice_no' => 'NA'
                ]);
            return json_response(400, 'Party GSTIN is not found');
        }

        $party_address = $this->partyAddress->view_records(['party_id'=>$party->party_id, 'address_type'=>'Office'], ['party_address_line_1', 'party_address_line_2', 'party_city', 'party_state', 'party_pincode', \DB::raw('private_db.get_gst_state_code(party_state) as party_state_code')]);
        if(!$party_address){
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => 'NO PARTY ADDRESS'
                ]);
            return json_response(400, 'Party address is not found');
        }


        //check for same buyer state and ship to state
        if($party_address->party_state != $psos->ship_to_state){
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => 'PARTY STATE SHIP_TO MISMATCH'
                ]);
            return json_response(400, 'Einvoice cannot be generated. Party state '.$party_address->party_state.' must be same as ship_to_state '.$psos->ship_to_state);
        }

        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company){
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => 'SELLER COMPANY NOT FOUND'
                ]);
            return json_response(400,'Company details are not found');
        }


        $company_details=[
            'company_name' => $company->party_name,
            'company_mobile' => $company->party_phone_number,
            'company_email' => $company->party_email,
            'company_pincode' => $company->party_pincode,
            'company_address_1' => $company->party_address_line_1,
            'company_address_2' => $company->party_address_line_2,
            'company_gstin' => $company->party_gstin,
            //'api_user_gstin' => $this->COMPANY_GSTIN,
            'company_city' => $company->party_city,
            'company_state' => $company->party_state
        ];

        $return_barcodes = [];
        $credit_note_details = $this->creditNoteDetail->view_records(['sell_invoice_ref_no' =>  $credit_note->sell_invoice_ref_no], [], [], [], 0);
        foreach ($credit_note_details as $cdn) {
            $return_barcodes[] = $cdn->ext_awb_number;
        }

        $psod = $this->partySellOrderDetail->getCreditNoteItemsDetail($credit_note->sell_invoice_ref_no, $return_barcodes);
        if(count($psod)==0) {
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => 'CREDIT NOT ITEM DETAILS NOT IN PSOD'
                ]);
            return json_response(400, 'No details found for Sell invoice reference number.');
        }

        $total_gst_value = 0;
        foreach($psod as $detail) {
            $total_gst_value +=  ($detail['igst_value']> 0 ? round($detail['igst_value'],2): (round($detail['sgst_value'],2) + round($detail['cgst_value'],2)));
        }


        //check if gstin is valid if class_exists
        if(!empty($party->party_gstin) && strlen($party->party_gstin)>=15){
            $valid = $this->ewayBillService->getGSTINDetails([
                'buyer_gstin' => $party->party_gstin,
                'sell_invoice_ref_no' => $credit_note->sell_invoice_ref_no,
                'company_gstin'=> $company_details['company_gstin']
            ]);
            if($valid instanceof Response){
                // update psos for error
                $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                    [
                        'credit_note_status' => 'E',
                        'credit_note_status_message' => json_decode($valid->getContent(), true)['message']??''
                    ]);
                return $valid;
            }

            if($valid['gstin_status']!='active'){
                // set error to skip this record for batch process
                $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                    [
                        'credit_note_status' => 'C',
                        'credit_note_status_message' => 'GSTIN NOT ACTIVE'
                    ]);
                return json_response(400, 'Buyer GSTIN is not active');
            }

        }

        $details = array_merge($company_details, $psos->toArray(),['buyer_gstin'=>$party->party_gstin, 'party_name'=>$party->party_name,'party_legal_name'=>$party->party_legal_name, 'party_mobile'=>$party->party_mobile], $party_address->toArray(), ['credit_note_date'=>$credit_note->credit_note_date, 'credit_note_ref_no' => $credit_note->credit_note_ref_no, 'sell_invoice_gst_value' => $total_gst_value]);

        $details['items_list'] = $psod;

        $response = $this->einvoiceService->generateCreditNote($details);

        if($response instanceof Response){
            //update psos for failure
            if (stripos(json_decode($response->getContent(), true)['message']??'', 'IRN already generated') === false) {
            $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => json_decode($response->getContent(), true)['message']??''
                ]);
            }
            return $response;
        }

        $this->creditNoteTransction->updateRecord(['credit_note_ref_no' => $data['credit_note_ref_no']],
            [
            'credit_note_status' => 'C',
            'credit_note_status_message' => 'Credit note has been created by '.($data['einvoice_service']).':'.($response['display_message']??''),
             'einvoice_no' => $response['message']['AckNo']??'',
             'creditnote_pdf_url' => $response['message']['EinvoicePdf']??''
        ]);

        return json_response(200, 'Credit Note has been created by '.($data['einvoice_service']).':'.($response['display_message']??''));

    }


    public function cancelEInvoice($data){

        $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
        if(!$invoice)
            return json_response(400, 'Einvoice does not exist '.($data['einvoice_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id','sell_order_summary_id', 'sell_invoice_ref_no',  'shipment_warehouse']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        // if(stripos($psos->shipment_warehouse, 'noida')!==false)
        //         $company_gstin = $this->COMPANY_GSTIN;
        // else{
        //         $company_gstin = $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'};
        // }
        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_gstin = $company->party_gstin;

        $data = array_merge($data, $invoice->toArray(), ['company_gstin' => $company_gstin]);

        $response = $this->einvoiceService->cancelEInvoice($data);

        if($response instanceof Response)
            return $response;

        // update psos for cancellation
        $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
        [
          'irn_status' => 'X',
          'irn_status_message' => 'Einvoice has been cancelled at '.($data['einvoice_service'])
        ]);

        if(!$this->eninvoiceData->cancelInvoice($invoice, $data, $response))
          return json_response(500, 'Invoice cancelled but failed to save response');

        return json_response(200, 'Einvoice has been cancelled at '.($data['einvoice_service']));
    }

    public function getEInvoice($data){
        $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
        if(!$invoice)
            return json_response(400, 'Einvoice does not exist '.($data['einvoice_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id','sell_order_summary_id', 'sell_invoice_ref_no',  'shipment_warehouse']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        // if(stripos($psos->shipment_warehouse, 'noida')!==false)
        //         $company_gstin = $this->COMPANY_GSTIN;
        // else{
        //         $company_gstin = $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'};
        // }
        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_gstin = $company->party_gstin;

        $data = array_merge($invoice->toArray(), ['company_gstin' => $company_gstin]);

        return $this->einvoiceService->getEInvoice($data);
    }

//    public function getGSTINDetails($data){
//
//        $company = $this->company_details->view_records(['party_id'=>187]);
//        if(!$company)
//          return json_response(400,'Company details are not found');
//
//        $data = array_merge($data, ['company_gstin' => $this->COMPANY_GSTIN]);
//
//        return $this->einvoiceService->getGSTINDetails($data);
//    }

    public function getGSTINDetails($data){

        $valid = $this->ewayBillService->getGSTINDetails([
            'buyer_gstin' => $data['gstin_number'],
            'sell_invoice_ref_no' => null,
            'company_gstin'=> $this->COMPANY_GSTIN
        ]);
        if($valid instanceof Response){
            return $valid;
        }

        if($valid['gstin_status']!='active'){
            return json_response(400, 'Buyer GSTIN is not active');
        }

        return [
                'message' => [
                    'AddrBno' => $valid['message']['address1']??'',
                    'AddrPncd' => $valid['message']['pincode']??'',
                    'AddrFlno' => $valid['message']['address2']??'',
                    'AddrSt' => $valid['message']['state_name']??'',
                    'TradeName' => $valid['message']['trade_name']??'',
                    'LegalName' => $valid['message']['legal_name_of_business']??'',
                    'Gstin' => $valid['message']['gstin_of_taxpayer']??'',
                    'Status' => 'ACT'
                ]
        ];

    }

    public function syncGSTINDetails($data){

        $company = $this->company_details->view_records(['party_id'=>187]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_gstin = $company->party_gstin;

        $data = array_merge($data, ['company_gstin' => $this->COMPANY_GSTIN]);

        return $this->einvoiceService->syncGSTINDetails($data);
    }

    /**
    * Get Api Usage count
    */
    public function getApiCounts($data){
        $res = $this->einvoiceService->getApiCounts($data);
        if($res instanceof Response)
          return $res;

        return json_response(200, $res);
    }


    // public function generateBulkEInvoice($data){
    //   $data =[
    //     'company_gstin' => '09AAAPG7885R002',
    //   ];
    //   return $this->einvoiceService->generateBulkEInvoice($data);
    // }


    /**
    * Apis related to eway bill using einvoice IRL starts from here
    * Not Being used for the time
    *
    */

    // public function generateEwayBillByIRN($data){
    //     $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
    //     if(!$invoice)
    //         return json_response(400, 'Einvoice does not exist '.($data['einvoice_service']));
    //
    //     $data =[
    //       'company_gstin' => '09AAAPG7885R002',
    //     ];
    //
    //     $data = array_merge($invoice->toArray(), $data);
    //
    //     return $this->einvoiceService->generateEwayBillByIRN($data);
    // }
    //
    // public function getEwayBillDetailsyIRN($data){
    //     $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
    //     if(!$invoice)
    //         return json_response(400, 'Einvoice does not exist '.($data['einvoice_service']));
    //
    //     $data =[
    //       'company_gstin' => '09AAAPG7885R002',
    //     ];
    //     return $this->einvoiceService->getEwayBillDetailsyIRN($data);
    // }
    //
    // public function cancelEwayBillByIRN($data){
    //
    //     $invoice = $this->eninvoiceData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
    //     if(!$invoice)
    //         return json_response(400, 'Einvoice does not exist '.($data['einvoice_service']));
    //
    //     $data =[
    //       'company_gstin' => '09AAAPG7885R002',
    //     ];
    //     return $this->einvoiceService->cancelEwayBillByIRN($data);
    // }

}
