<?php

namespace App\Services\EwayBill;
use App\Repositories\Interfaces\EwayBillDataInterface;
use App\Repositories\Interfaces\PartyAddressInterface;
use App\Repositories\Interfaces\PartyDetailInterface;
use App\Repositories\Interfaces\PartySellOrderDetailInterface;
use App\Repositories\Interfaces\PartySellOrderSummaryInterface;
use App\Repositories\Interfaces\SystemParametersInterface;
use App\Repositories\Interfaces\CompanyDetailInterface;
use App\Traits\GetConfig;
use Illuminate\Http\Response;

/**
 * This class implement business login for createing eway bill.
 * All database read & update operartions performed Here
 *
 * @author Pankaj Sengar
 **/

class EwayBillManager
{
    use GetConfig;

    public function __construct(EwayBillService $ewayBillService, EwayBillDataInterface $ewayBillData, SystemParametersInterface $systemParameters){
        $this->ewayBillService = $ewayBillService;
        $this->partySellOrderSummary = $partySellOrderSummary;
        $this->partySellOrderDetail = $partySellOrderDetail;
        $this->partyDetail = $partyDetail;
        $this->partyAddress = $partyAddress;
        $this->ewayBillData = $ewayBillData;
        $this->systemParameters = $systemParameters;
        $this->company_details = $company_details;
        $this->getSystemParams(['RecycleDevice']);
    }

    public function generateEwayBill($data){

        $ewaybill = $this->ewayBillData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no'], 'ebill_status'=>'Created']);
        if($ewaybill)
            return json_response(400, 'Eway bill already created by '.($data['eway_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id', 'sell_order_summary_id', 'sell_invoice_ref_no', 'eway_bill_number', 'actual_weight', 'volume_weight', 'item_length', 'item_breadth', 'item_height', 'item_pieces', 'sell_invoice_total_value','invoice_date', 'sell_invoice_gst_value', 'shipment_warehouse', 'ship_to_contact_person_name', 'ship_to_contact_person_number', 'ship_to_company_name', 'ship_to_address_line_1', 'ship_to_address_line_2', 'ship_to_city', 'ship_to_state', 'ship_to_pincode', 'ext_invoice_ref_no', 'sell_invoice_discount_value', \DB::raw('private_db.get_invoice_igst_flag(sell_invoice_ref_no) as igst_flag'), 'purchaser_party_id', 'tracking_partner_name', 'transporter_vehicle_number']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        if($psos->tracking_partner_name!=='Self Pickup'){
          $transporter = $this->systemParameters->getTransporter($psos->tracking_partner_name);
          $transporter_id = $transporter->transporter_id??'';
        }

        $party = $this->partyDetail->view_records(['party_id'=>$psos->purchaser_party_id]);
        // if(empty($party->party_gstin))
        //     return json_response(400, 'Party GSTIN is not found');

        // $party_address = $this->partyAddress->view_records(['party_id'=>$party->party_id, 'address_type'=>'Office'], ['party_address_line_1', 'party_address_line_2', 'party_city', 'party_state', 'party_pincode', \DB::raw('private_db.get_gst_state_code(party_state) as party_state_code')]);
        // if(!$party_address)
        //     return json_response(400, 'Party address is not found');

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

        $psod = $this->partySellOrderDetail->getEwayBillItemsDetail($data['sell_invoice_ref_no']);
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
                      'eway_status' => 'E',
                      'eway_status_message' => json_decode($valid->getContent(), true)['message']??''
                  ]);
              return $valid;
          }

          if($valid['gstin_status']!='active'){

              // set error to skip this record for batch process
              $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
                  [
                      'eway_status' => 'E',
                      'eway_status_message' => 'GSTIN not active: '.($valid['gstin_status']??'unknown')
                  ]);
              return json_response(400, 'Buyer GSTIN is not active');
          }

        }


        $details = array_merge($company_details, $psos->toArray(),['buyer_gstin'=>$party->party_gstin, 'party_name'=>$party->party_name, 'party_legal_name'=>$party->party_legal_name, 'party_mobile'=>$party->party_mobile], ['transporter_id'=>$transporter_id??'']);

        $details['items_list'] = $psod;

        //$details['company_gstin']='05AAABB0639G1Z8';
        $response = $this->ewayBillService->generateEwayBill($details);

        if($response instanceof Response){
              // update psos for error
              $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
              [
                'eway_status' => 'E',
                'eway_status_message' => json_decode($response->getContent(), true)['message']??''
              ]);
              return $response;
        }

        if(!$this->ewayBillData->saveEwayBillData($details, $response))
            return json_response(500, 'Ewaybill created but failed to save response');

        // update psos for completion
        $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
        [
          'eway_status' => 'C',
          'eway_status_message' => 'Ewaybill has been created by '.($data['eway_service']).':'.($response['display_message']??'')
        ]);

        return json_response(200, 'EwayBill has been created by '.($data['eway_service']).':'.($response['display_message']??''));

    }

    public function cancelEwayBill($data){

        $ewaybill = $this->ewayBillData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no'], 'ebill_status'=>'Created']);
        if(!$ewaybill)
            return json_response(400, 'Ewaybill is not created at '.($data['eway_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id', 'sell_order_summary_id', 'sell_invoice_ref_no',  'shipment_warehouse']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        // if(stripos($psos->shipment_warehouse, 'noida')!==false)
        //     $company_gstin = $this->COMPANY_GSTIN;
        // else{
        //     $company_gstin = $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'};
        // }

        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_gstin = $company->party_gstin;

        $details = array_merge($data, $ewaybill->toArray(), ['company_gstin' => $company_gstin]);

        //$details['company_gstin']='05AAABB0639G1Z8';
        $response = $this->ewayBillService->cancelEwayBill($details);

        if($response instanceof Response)
            return $response;

        if(!$this->ewayBillData->cancelEwayBill($ewaybill, $details, $response))
            return json_response(500, 'Eway Bill cancelled but failed to save response');

        // update psos for cancellation
        $this->partySellOrderSummary->updateRecord(['sell_order_summary_id'=>$psos->sell_order_summary_id],
        [
          'eway_status' => 'X',
          'eway_status_message' => 'Ewaybill has been cancelled at '.($data['eway_service'])
        ]);

        return json_response(200, 'Eway bill has been cancelled at '.($data['eway_service']));

    }


    public function getEwayBillDetails($data){
        $ewaybill = $this->ewayBillData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']]);
        if(!$ewaybill)
            return json_response(400, 'Eway bill does not exist '.($data['eway_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id','sell_order_summary_id', 'sell_invoice_ref_no',  'shipment_warehouse']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

        // if(stripos($psos->shipment_warehouse, 'noida')!==false)
        //     $company_gstin = $this->COMPANY_GSTIN;
        // else{
        //     $company_gstin = $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'};
        // }
        $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
        if(!$company)
          return json_response(400,'Company details are not found');

        $company_gstin = $company->party_gstin;

        $details = array_merge($ewaybill->toArray(), ['company_gstin' => $company_gstin]);

        //$details['company_gstin']='05AAABB0639G1Z8';
        return $this->ewayBillService->getEwayBillDetails($details);
    }

    public function updateEwayBill($data){

        $ewaybill = $this->ewayBillData->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no'], 'ebill_status'=>'Created']);
        if(!$ewaybill)
            return json_response(400, 'Eway bill does not exist '.($data['eway_service']));

        $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id','sell_order_summary_id', 'sell_invoice_ref_no',  'shipment_warehouse', 'ext_invoice_ref_no', 'invoice_date','tracking_partner_name', 'transporter_vehicle_number']);
        if(!$psos)
            return json_response(400, 'Sell invoice reference number does not exist');

            // if(stripos($psos->shipment_warehouse, 'noida')!==false)
            //     $company_details =[
            //         'company_pincode' => $this->COMPANY_PINCODE,
            //         'company_gstin' => $this->COMPANY_GSTIN,
            //         //'api_user_gstin' => $this->COMPANY_GSTIN,
            //         'company_city' => $this->COMPANY_CITY,
            //         'company_state' => $this->COMPANY_STATE
            //     ];
            // else{
            //     $company_details =[
            //         'company_pincode' => $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_PINCODE'},
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
            'company_pincode' => $company->party_pincode,
            'company_gstin' => $company->party_gstin,
            //'api_user_gstin' => $this->COMPANY_GSTIN,
            'company_city' => $company->party_city,
            'company_state' => $company->party_state
        ];

        $details = array_merge($data, $ewaybill->toArray(), $company_details, $psos->toArray());

        //$details['company_gstin']='05AAABB0639G1Z8';
        return $this->ewayBillService->updateEwayBill($details);
    }

    /**
    * Get Api Usage count
    */
    public function getApiCounts($data){
        $res = $this->ewayBillService->getApiCounts($data);
        if($res instanceof Response)
          return $res;

        return json_response(200, $res);
    }

    // public function getGSTINDetails($data){
    //     $psos = $this->partySellOrderSummary->view_records(['sell_invoice_ref_no'=>$data['sell_invoice_ref_no']], ['seller_party_id','sell_invoice_ref_no', 'shipment_warehouse', 'purchaser_party_id']);
    //     if(!$psos)
    //         return json_response(400, 'Sell invoice reference number does not exist');
    //
    //     $party = $this->partyDetail->view_records(['party_id'=>$psos->purchaser_party_id]);
    //     if(empty($party->party_gstin))
    //         return json_response(400, 'Party GSTIN is not found');
    //
    //     // if(stripos($psos->shipment_warehouse, 'noida')!==false)
    //     //     $company_gstin = $this->COMPANY_GSTIN;
    //     // else{
    //     //     $company_gstin = $this->{'COMPANY_'.strtoupper($psos->shipment_warehouse).'_GSTIN'};
    //     // }
    //     $company = $this->company_details->view_records(['party_id'=>$psos->seller_party_id]);
    //     if(!$company)
    //       return json_response(400,'Company details are not found');
    //
    //     $company_gstin = $company->party_gstin;
    //
    //     $details = array_merge($data, $psos->toArray(), ['buyer_gstin'=>$party->party_gstin, 'company_gstin' => $company_gstin]);
    //
    //     //$details['company_gstin'] = '05AAABB0639G1Z8';
    //     //$details['buyer_gstin'] = '05AAABC0181E1ZE';
    //     return $this->ewayBillService->getGSTINDetails($details);
    // }

}
