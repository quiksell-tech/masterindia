<?php

namespace App\Services\EwayBill;

use App\Repositories\Interfaces\SystemParametersInterface;
use App\Services\GuzzleService;
use App\Traits\GetConfig;

class MasterIndiaServiceBak implements EwayBillService
{

    use GetConfig;

    protected $cancellation_reasons = [
        'duplicate' => 'Duplicate',
        'order-cancelled' => 'Order Cancelled',
        'incorrect-details' => 'Data Entry mistake',
        'others' => 'Others'
    ];

    protected $extension_reasons = [
        'natural-calamity' => 'Natural Calamity',
        'law-order' => 'Law and Order Situation',
        'transshipment' => 'Transshipment',
        'accident' => 'Accident',
        'others' => 'Others'
    ];

    protected $vehicle_update_reason = [
      'break-down' => 'Due to Break Down',
      'transshipment' =>'Due to Transhipment',
      'others' => 'Others',
      'first-time' => 'First Time'
    ];

    public function __construct(GuzzleService $guzzleService, SystemParametersInterface  $systemParameters){
        $this->guzzleService = $guzzleService;
        $this->systemParameters = $systemParameters;
        $this->getSystemParams(['MasterIndia']);

        if(empty($this->AUTH_TIMESTAMP) || date('Y-m-d H:i:s') >= $this->AUTH_TIMESTAMP){
            if(!app()->runningInConsole()){
                $token = $this->authenticate();
                if($token){
                    $this->systemParameters->updateRecord([
                        'sysprm_provider'=>'MasterIndia',
                        'sysprm_name'=>'ACCESS_TOKEN'
                    ],
                        [
                            'sysprm_value'=>$token,
                        ]);
                    $this->systemParameters->updateRecord([
                        'sysprm_provider'=>'MasterIndia',
                        'sysprm_name'=>'AUTH_TIMESTAMP'
                    ],
                        [
                            'sysprm_value'=>date('Y-m-d H:i:s', strtotime('+50 minutes')),
                        ]);
                }
            }else{
                $token = '<artisan command>';// ignore when running artisan command
            }
        }else{
            $token = $this->ACCESS_TOKEN;
        }

        if(empty($token))
        {
            http_response_code(400);
            echo json_encode([
                'success'=>false,
                'message'=>'Access Token Cannot Be Generated'
            ]);
            die;
        }

        $this->ACCESS_TOKEN = $token;
    }

    public function refreshToken(){

        $token =  $this->authenticate();
        if(!$token)
        {
            return json_response(400, 'Access Token Cannot Be Generated');
        }

        $this->ACCESS_TOKEN = $token;
        $this->AUTH_TIMESTAMP = date('Y-m-d H:i:s', strtotime('+50 minutes'));

        $this->systemParameters->updateRecord([
            'sysprm_provider'=>'MasterIndia',
            'sysprm_name'=>'ACCESS_TOKEN'
        ],
            [
                'sysprm_value'=>$token,
            ]);
        $this->systemParameters->updateRecord([
            'sysprm_provider'=>'MasterIndia',
            'sysprm_name'=>'AUTH_TIMESTAMP'
        ],
            [
                'sysprm_value'=>$this->AUTH_TIMESTAMP,
            ]);

        return true;

    }

    public function authenticate(){

        $endpoint = $this->BASE_URL.'/oauth/access_token';

        $data =[
            'username' => $this->USERNAME,
            'password' => $this->PASSWORD,
            'client_id' => $this->CLIENT_ID,
            'client_secret' => $this->CLIENT_SECRET,
            'grant_type' => 'password'
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, [], 'MasterIndia', 'authorize' );
        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(!empty($response['access_token'])){
                return $response['access_token'];
            }
        }

        return null;

    }

    public function generateEwayBill($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/ewayBillsGenerate';

        if($data['igst_flag'] == 'Y'){
            $igst_total = $data['sell_invoice_gst_value'];
            $sgst_total=0;
            $cgst_total=0;
        }else{
            $igst_total = 0;
            $sgst_total=$data['sell_invoice_gst_value']/2;
            $cgst_total=$data['sell_invoice_gst_value']/2;
        }

        $items_list =[];
        $total_assessable_value =0;
        $total_other_charges =0;
        foreach($data['items_list'] as $item) {

            if($data['igst_flag'] == 'Y'){
                $igst_rate = $item['gst_rate'];
                $sgst_rate = 0;
                $cgst_rate = 0;
            }else{
                $igst_rate = 0;
                $sgst_rate = $item['gst_rate']/2;
                $cgst_rate = $item['gst_rate']/2;
            }

            $total_assessable_value += round($item['assessable_value'], 2);
            $total_other_charges += round($item['other_charge'],2);

            $items_list[] = [
                "product_name" => $item['product_name'],
                "product_description" => $item['product_description'],
                "hsn_code" => $item['product_hsn'],
                "unit_of_product" => "PCS",  // to be discussed
                "cgst_rate" => round($cgst_rate, 2),
                "sgst_rate" => round($sgst_rate,2),
                "igst_rate" => round($igst_rate, 2),
                "cess_rate" => 0,
                "quantity" => $item['product_quantity'],
                "cessNonAdvol" => 0,
                "taxable_amount" => round($item['assessable_value'], 2)
            ];
        }

        $params = [
                "access_token" => $this->ACCESS_TOKEN,
                "userGstin" => $data['company_gstin'],
                "supply_type" => "Outward",
                "sub_supply_type" => "Supply",
                //"sub_supply_description" => "sales from other country", // to be discussed
                "document_type" => "Tax Invoice",
                "document_number" => strtoupper($data['ext_invoice_ref_no']),
                "document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "gstin_of_consignor" => $data['company_gstin'],
                "legal_name_of_consignor" => $data['company_name'],
                "address1_of_consignor" => $data['company_address_1'],
                "address2_of_consignor" => $data['company_address_2'],
                "place_of_consignor" => strtoupper($data['company_city']),
                "pincode_of_consignor" => $data['company_pincode'],
                "state_of_consignor" => strtoupper($data['company_state']),
                "actual_from_state_name" => strtoupper($data['company_state']),  //to be discussed
                "gstin_of_consignee" => ($data['buyer_gstin'] == null || strlen($data['buyer_gstin']) < 15 )?'URP':$data['buyer_gstin'], //check for length less than 15 or null send URP
                "legal_name_of_consignee" => $data['party_name']??$data['party_legal_name'],//check for null value.if doesnt work with null pass party_name
                "address1_of_consignee" => $data['ship_to_address_line_1'],
                "address2_of_consignee" => $data['ship_to_address_line_2']??'',
                "place_of_consignee" => strtoupper($data['ship_to_city']),
                "pincode_of_consignee" => $data['ship_to_pincode'],
                "state_of_supply" => strtoupper($data['ship_to_state']),
                "actual_to_state_name" => strtoupper($data['ship_to_state']), //to be discussed
                "transaction_type" => 1,
                "other_value" => round($total_other_charges,2),
                "total_invoice_value" =>  round($data['sell_invoice_total_value'],2),
                "taxable_amount" =>  round($total_assessable_value,2),
                "cgst_amount" =>  $cgst_total,
                "sgst_amount" => $sgst_total,
                "igst_amount" =>  $igst_total,
                "cess_amount" => 0,
                "cess_nonadvol_value" => 0,
                //"transporter_id" => "05AAABB0639G1Z8", //if tracking partner is self pickup --> dont send this param. only use vehiclenum,type & transport_mode=road //else send only transporter id without model,vehicle_num,vehicle_type
                // table will be provided to fetch transporter_id

                //"transporter_name" => "", //to be discussed
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                //"transportation_mode" => "road", //to be discussed
                //"transportation_distance" => "656", //to be discussed
                //"vehicle_number" => $data['vehicle_number']??'', //to be discussed
                //"vehicle_type" => "Regular", //to be discussed
                //"generate_status" => 1, //to be discussed
                "data_source" => "erp", //to be discussed
                //"user_ref" => "1232435466sdsf234", //to be discussed
                //"location_code" => "XYZ", //to be discussed
                //"eway_bill_status" => "ABC", //to be discussed
                //"auto_print" => "Y", //to be discussed
                //"email" => "mayanksharma@mastersindia.co", //to be discussed
                "itemList" => $items_list
        ];

        if($data['tracking_partner_name']=='Self Pickup' && !empty($data['transporter_vehicle_number'])){
          $params['transportation_mode'] = 'road';
          $params['vehicle_number'] = $data['transporter_vehicle_number']??'';
          $params['vehicle_type'] = 'Regular';
        }else{
          $params['transporter_id'] =$data['transporter_id'];
        }

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'gen_e_bill', $data['sell_invoice_ref_no'] );
        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->generateEwayBill($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));

    }


    public function cancelEwayBill($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/ewayBillCancel';

        $params =[
            "access_token" => $this->ACCESS_TOKEN,
            "userGstin" => $data['company_gstin'],
            "eway_bill_number" => $data['eway_bill_no'],
            "reason_of_cancel" => $this->cancellation_reasons[$data['cancel_reason']]??'Others',
            "cancel_remark" => $data['cancel_remarks']??'',
            "data_source" => "erp"
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'can_e_bill', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->cancelEwayBill($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));

    }

    public function updateEwayBill($data){
        if($data['action'] == 'update-vehicle'){
            return $this->updateVehicleNumber($data);
        }else if($data['action'] == 'update-transporter'){
            return $this->updateTransporterID($data);
        }else if($data['action'] == 'extend-validity'){
            return $this->extendBillValidity($data);
        }else{
            return json_response(400, 'Invalid Update Action');
        }
    }

    private function updateVehicleNumber($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/updateVehicleNumber';

        $params = [
            "access_token" => $this->ACCESS_TOKEN,
            "userGstin" => $data['company_gstin'],
            "eway_bill_number" => $data['eway_bill_no'],
            "vehicle_number" => $data['transporter_vehicle_number'],
            "vehicle_type" => "Regular",
            "place_of_consignor" => strtoupper($data['company_city']),
            "state_of_consignor" => strtoupper($data['company_state']),
            "reason_code_for_vehicle_updation" => $this->vehicle_update_reason[$data['vehicle_update_reason']]??'Others',
            "reason_for_vehicle_updation" => $data['vehicle_update_remarks'],
            // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
            // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
            "mode_of_transport" => "road",
            "data_source" =>"erp"
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_vcle', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->updateVehicleNumber($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));

    }


    private function updateTransporterID($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/transporterIdUpdate';

        $transporter = $this->systemParameters->getTransporter($data['tracking_partner_name']??'');
        $transporter_id = $transporter->transporter_id??'';

        $params = [
            "access_token" => $this->ACCESS_TOKEN,
            "userGstin" => $data['company_gstin'],
            "eway_bill_number" => $data['eway_bill_no'],
            "transporter_id" => $transporter_id
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_trans', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->updateTransporterID($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));


    }

    public function extendBillValidity($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/ewayBillValidityExtend';

        $params = [
                'access_token' => $this->ACCESS_TOKEN,
                "userGstin" => $data['company_gstin'],
                "eway_bill_number" => $data['eway_bill_no'],
                "place_of_consignor" => strtoupper($data['company_city']),
                "pincode_of_consignor" => $data['company_pincode'],
                "state_of_consignor" => strtoupper($data['company_state']),
                "remaining_distance" => 250, // to be discused it is required
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "extend_validity_reason" => $this->extension_reasons[$data['extension_reason']]??'Others',
                "extend_remarks" => $data['extension_remarks'],
                "from_pincode" => $data['company_pincode'],
                "consignment_status" => "M", // not required for in movement status
                // "transit_type" => "Road", //Roan,Warehouse not required for consignment status M
                // "address_line1" => "Dehradun", // not required for consignment status M
                // "address_line2" => "Dehradun", // not required for consignment status M
                // "address_line3" => "Dehradun" // not required for consignment status M
            ];

        if($data['tracking_partner_name']=='Self Pickup'){
          $params["vehicle_number"] = $data['transporter_vehicle_number']; //to be discussed
          $params["mode_of_transport"] = "road";
        }else{
          //vehicle number is required to be discused
        }


        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_validity', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->extendBillValidity($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));
    }

    public function getEwayBillDetails($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/getEwayBillData';

        $params = [
            'action' => 'GetEwayBill',
            'access_token' => $this->ACCESS_TOKEN,
            "gstin" => $data['company_gstin'],
            'eway_bill_number' => $data['eway_bill_no']
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $params, [], [], 'MasterIndia', 'get_ebill_det', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->getEwayBillDetails($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));
    }

    public function getGSTINDetails($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/getEwayBillData';

        $params = [
            'action' => 'GetGSTINDetails',
            'access_token' => $this->ACCESS_TOKEN,
            "userGstin" => $data['company_gstin'],
            "gstin" => $data['buyer_gstin']
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $params, [], [], 'MasterIndia', 'get_gst_det', $data['sell_invoice_ref_no'] );

        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                if(isset($response['results']['message']['status'])){
                    if($response['results']['message']['status'] == 'ACT')
                        $response['results']['gstin_status'] = 'active';
                    else
                        $response['results']['gstin_status'] = 'not_active';
                    return $response['results'];
                }else{
                    return json_response(400, $response['results']['message']??'GSTIN Details Not Fetched');
                }

            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->getGSTINDetails($original_data);
        }

        return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));
    }


    public function getApiCounts($data){
      $original_data = $data;
      $endpoint = $this->BASE_URL.'/Userapis/apicount';

      $params = [
          'access_token' => $this->ACCESS_TOKEN,
          "account_email" => $data['account_email'],
          "from_date" => $data['from_date'],
          "to_date" => $data['to_date']
      ];

      $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'inv_api_count' );

      if($result['error']===false){
          $response = json_decode($result['data'], true);
          if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
              return $response;
          }
      }

      if(isset($result['header_status']) && $result['header_status'] == 401){
          $this->refreshToken();
          return $this->apiCount($original_data);
      }

      return json_response(400, ($response['results']['message']??$result['message']).' '.($response['results']['code']??''));

    }

}
