<?php

namespace App\Services\EwayBill;

use App\Models\SystemParameter;
use App\Services\GuzzleService;
use Carbon\Carbon;

class MasterIndiaService
{
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
    protected  $systemParameters;

    protected  $ACCESS_TOKEN = null;
    protected  $AUTH_TIMESTAMP = null;
    protected  $guzzleService;

    public function __construct(GuzzleService $guzzleService, SystemParameter $systemParameters)
    {
        $this->systemParameters = $systemParameters;
        $this->guzzleService = $guzzleService;
        // Load system parameters
        $this->getSystemParams(['MasterIndia']);

        // Check token expiry
        if (
            empty($this->AUTH_TIMESTAMP) ||
            Carbon::now()->gte(Carbon::parse($this->AUTH_TIMESTAMP))
        ) {
            if (!app()->runningInConsole()) {

                $token = $this->authenticate();

                if ($token) {
                    // Save new access token
                    $this->systemParameters->updateRecord(
                        [
                            'sysprm_provider' => 'MasterIndia',
                            'sysprm_name'     => 'ACCESS_TOKEN',
                        ],
                        [
                            'sysprm_value' => $token,
                        ]
                    );

                    // Save new expiry timestamp (50 mins)
                    $this->systemParameters->updateRecord(
                        [
                            'sysprm_provider' => 'MasterIndia',
                            'sysprm_name'     => 'AUTH_TIMESTAMP',
                        ],
                        [
                            'sysprm_value' => Carbon::now()->addMinutes(50)->format('Y-m-d H:i:s'),
                        ]
                    );
                }

            } else {
                // Ignore token when running artisan
                $token = '<artisan command>';
            }

        } else {
            // Token still valid
            $token = $this->ACCESS_TOKEN;
        }

        if (empty($token)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Access Token Cannot Be Generated'
            ], 400));
        }

        $this->ACCESS_TOKEN = $token;
    }

    /**
     * Load system parameters into class properties
     */
    protected function getSystemParams(array $providers)
    {
        $params = $this->systemParameters->whereIn('sysprm_provider', $providers)
            ->where('current_flag', 'Y')
            ->get();

        foreach ($params as $param) {
            $this->{$param->sysprm_name} = $param->sysprm_value;
        }
    }

    /**
     * Authenticate with MasterIndia API
     * MUST return access token string
     */
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
    public function generateEwayBill($parameters)
    {
        $endpoint = $this->BASE_URL.'/ewayBillsGenerate';
        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $parameters, [], 'MasterIndia', 'gen_e_bill', $parameters['document_number'] );
        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->generateEwayBill($parameters);
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

    public function updateVehicleNumber($data){
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


    public function updateTransporterID($data){
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



}
