<?php

namespace App\Services\EInvoice;

use App\Models\SystemParameter;
use App\Services\GuzzleService;
use App\Traits\GetConfig;
use App\Repositories\Interfaces\SystemParametersInterface;
use Carbon\Carbon;

class MasterIndiaService implements EinvoiceService
{

    protected $cancellation_reasons = [
      "duplicate" => "1", //Duplicate
      "incorrect-details" => "2" //Data Entry Mistake
    ];
    protected  $systemParameters;

    protected  $ACCESS_TOKEN = null;
    protected  $AUTH_TIMESTAMP = null;
    protected  $guzzleService;
    public function __construct(GuzzleService $guzzleService, SystemParameter $systemParameters){

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

    /*
     * Function to generate einvoice
     */
    public function generateEInvoice($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/generateEinvoice';



        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'gen_e_inv', $data['sell_invoice_ref_no'] );
        if($result['error']===false){
           $response = json_decode($result['data'], true);
           if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
              if(stripos($response['results']['message']['alert'], 'IRN already generated')!==false)
                    return json_response(400, $response['results']['message']['alert']);
              $response['results']['display_message'] = $response['results']['message']['alert'];
              return $response['results'];
           }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->generateEInvoice($original_data);
        }

        return json_response(400, $response['results']['errorMessage']??$result['message']);

    }

    /*
     * Function to generate credit note
     */
    public function generateCreditNote($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/generateEinvoice';

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
        $i=1;
        $total_assessable_value =0;
        $total_invoice_value = 0;
        foreach($data['items_list'] as $item){

            $total_assessable_value += round($item['assessable_value'], 2);
            $total_invoice_value += round($item['product_total_value'],2);
            $items_list[] = [
                "item_serial_number" => $i++,
                "product_description" => $item['product_name'],
                "is_service" => (strtolower($item['inventory_source']) == 'service')?'Y':'N',
                "hsn_code" => $item['product_hsn'],
                "bar_code" => $item['product_barcode']??'',
                "quantity" => $item['product_quantity'],
                "unit" => "PCS",
                "unit_price" => round($item['unit_value'], 2),
                "total_amount" => round($item['unit_value'] * $item['product_quantity'],2),
                "discount" => 0,
                "other_charge" => round($item['other_charge'],2),
                "assessable_value" => round($item['assessable_value'], 2),
                "gst_rate" => $item['gst_rate'],
                "igst_amount" => round($item['igst_value'],2),
                "cgst_amount" => round($item['cgst_value'],2),
                "sgst_amount" => round($item['sgst_value'],2),
                "total_item_value" => round($item['product_total_value'],2) ,
            ];
        }

        $params =[
            "access_token" =>$this->ACCESS_TOKEN,
            "user_gstin" => $data['company_gstin'] ,
            "data_source" => "erp",
            "transaction_details"=> [
                "supply_type" => "B2B",
                "charge_type" => "N",
                "igst_on_intra" => "N",
                "ecommerce_gstin" => ""
            ],
            "document_details"=> [
                "document_type" => "CRN",
                "document_number" => strtoupper($data['credit_note_ref_no']),
                "document_date" => date('d/m/Y', strtotime($data['credit_note_date']))
            ],
            "seller_details" => [
                "gstin" => $data['company_gstin'],
                "legal_name" => $data['company_name'],
                // "trade_name" => "MastersIndia UP",
                "address1" => $data['company_address_1'],
                "address2" => $data['company_address_2'],
                // "address2" => "Vila",
                "location" => strtoupper($data['company_city']),
                "pincode" => $data['company_pincode'],
                "state_code" => strtoupper($data['company_state']),
                "phone_number" => $data['company_mobile'],
                // "email" => ""
            ],
            "buyer_details" => [
                "gstin" => $data['buyer_gstin'],
                "legal_name" => $data['party_legal_name'],
                "trade_name" => $data['party_name'],
                "address1" => $data['party_address_line_1'],
                "address2" => $data['party_address_line_2']??'',
                "location" => strtoupper($data['party_city']),
                "pincode" => $data['party_pincode'],
                "place_of_supply" => $data['party_state_code'],
                "state_code"=> strtoupper($data['party_state']),
                "phone_number" => $data['party_mobile'],
                // "email" => ""
            ],
            "dispatch_details" => [
                "company_name" => $data['company_name'],
                "address1" => $data['company_address_1'],
                "address2" => $data['company_address_2'],
                // "address2" => "Vila",
                "location" => strtoupper($data['company_city']),
                "pincode" => $data['company_pincode'],
                "state_code" => strtoupper($data['company_state'])
            ],
            "ship_details" => [
                // "gstin" => "05AAAPG7885R002",
                "legal_name" => $data['party_legal_name'],
                "trade_name" => $data['party_name'],
                "address1" => $data['ship_to_address_line_1'],
                "address2" => $data['ship_to_address_line_2']??'',
                "location" => strtoupper($data['ship_to_city']),
                "pincode" => $data['ship_to_pincode'],
                "state_code" => strtoupper($data['ship_to_state'])
            ],

            "reference_details" => [
                "preceding_document_details" => [[
                    "reference_of_original_invoice" => strtoupper($data['ext_invoice_ref_no']),
                    "preceding_invoice_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                    // "other_reference" => "2334"
                ]],
            ],
            "value_details" => [
                "total_assessable_value" => round($total_assessable_value,2),
                "total_cgst_value" => round($cgst_total,2),
                "total_sgst_value" => round($sgst_total,2),
                "total_igst_value" => round($igst_total,2),
                "total_invoice_value" => round($total_invoice_value,2),
            ],
            "item_list" => $items_list
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'gen_cr_note', $data['sell_invoice_ref_no'] );
        if($result['error']===false){
            $response = json_decode($result['data'], true);
            if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
                if(stripos($response['results']['message']['alert'], 'IRN already generated')!==false)
                    return json_response(400, $response['results']['message']['alert']);
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if((isset($result['header_status']) && $result['header_status'] == 401) || json_decode($result['data'], true)['results']['message']??'' == 'The access token provided is invalid.'){
            $this->refreshToken();
            return $this->generateCreditNote($original_data);
        }

        return json_response(400, $response['results']['errorMessage']??$result['message']);

    }

    public function cancelEInvoice($data){
        $original_data = $data;
        $endpoint = $this->BASE_URL.'/cancelEinvoice';

        $params =[
          "access_token" =>$this->ACCESS_TOKEN,
          "user_gstin" => $data['company_gstin'],
          "irn" => $data['irn_no'],
          "cancel_reason" => $this->cancellation_reasons[$data['cancel_reason']??'']??"2",
          "cancel_remarks" => $data['cancel_remarks']??'Wrong Entry'
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'can_e_inv', $data['sell_invoice_ref_no'] );
        if($result['error']===false){
           $response = json_decode($result['data'], true);
           if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
              return $response['results'];
           }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->cancelEInvoice($original_data);
        }

        return json_response(400, $response['results']['errorMessage']??$result['message']);


    }

    public function getEInvoice($data){
        $original_data = $data;
      $endpoint = $this->BASE_URL.'/getEinvoiceData';

      $params =[
        "access_token" =>$this->ACCESS_TOKEN,
        "gstin" => $data['company_gstin'],
        "irn" => $data['irn_no'],
      ];

      $result = $this->guzzleService->request($endpoint, 'GET', '', $params, [], [], 'MasterIndia', 'get_e_inv',$data['sell_invoice_ref_no'] );
      if($result['error']===false){
         $response = json_decode($result['data'], true);
         if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
            return $response['results'];
         }
      }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->getEInvoice($original_data);
        }

      return json_response(400, $response['results']['errorMessage']??$result['message']);
    }

//    public function getGSTINDetailsOld($data){
//        $original_data = $data;
//      $endpoint = $this->BASE_URL.'/gstinDetails';
//
//      $params =[
//        "access_token" =>$this->ACCESS_TOKEN,
//        "user_gstin" => $data['company_gstin'],
//        "gstin" => $data['gstin_number'],
//      ];
//
//      $result = $this->guzzleService->request($endpoint, 'GET', '', $params, [], [], 'MasterIndia', 'get_gstin_det' );
//      if($result['error']===false){
//         $response = json_decode($result['data'], true);
//         if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
//            return $response['results'];
//         }
//      }
//
//        if(isset($result['header_status']) && $result['header_status'] == 401){
//            $this->refreshToken();
//            return $this->getGSTINDetails($original_data);
//        }
//
//      return json_response(400, $response['results']['errorMessage']??$result['message']);
//    }

    public function getGSTINDetails($data){
        $original_data = $data;
        $endpoint = $this->MI_COMMON_API.'/searchgstin';

        $headers = [
            'Authorization' => 'Bearer '.$this->ACCESS_TOKEN,
            'client_id' => $this->CLIENT_ID
        ];

        $query =[
            "gstin" => $data['gstin_number'],
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $query, [], $headers, 'MasterIndia', 'get_gstin_det' );

        if($result['error']===false) {
            $response = json_decode($result['data'], true);
            if (isset($response['error']) && $response['error'] === false) {
                return [
                    "message" => [
                        "Gstin" => $response['data']['gstin'] ?? null,
                        "TradeName" => $response['data']['tradeNam'] ?? null,
                        "LegalName" => $response['data']['lgnm'] ?? null,
                        "AddrBnm" => $response['data']['pradr']['addr']['bnm'] ?? null,
                        "AddrBno" => $response['data']['pradr']['addr']['bno'] ?? null,
                        "AddrFlno" => $response['data']['pradr']['addr']['flno'] ?? null,
                        "AddrSt" => $response['data']['pradr']['addr']['st'] ?? null,
                        "AddrLoc" => $response['data']['pradr']['addr']['loc'] ?? null,
                        "StateCode" => $response['data']['pradr']['addr']['stcd'] ?? null,
                        "AddrPncd" => $response['data']['pradr']['addr']['pncd'] ?? null,
                        "TxpType" => $response['data']['dty']??null,
                        "Status" => ($response['data']['sts'] ?? null) == 'Active' ? 'ACT' : 'IACT',
                        "BlkStatus" => "U",
                        "DtReg" => $response['data']['rgdt'] ?? null,
                        "DtDReg" => $response['data']['pradr']['ntr'] ?? null
                    ],
                    "errorMessage" => $response['message'] ?? null,
                    "InfoDtls" => "",
                    "status" => "Success",
                    "code" => 200,
                    'original_data' => $response,
                ];
            }
        }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->getGSTINDetails($original_data);
        }

        return json_response(400, $response['message']??$result['message']);
    }



    public function syncGSTINDetails($data){
        $original_data = $data;
      $endpoint = $this->BASE_URL.'/syncGstinDetails';

      $params =[
        "access_token" =>$this->ACCESS_TOKEN,
        "user_gstin" => $data['company_gstin'],
        "gstin" => $data['gstin_number'],
      ];

      $result = $this->guzzleService->request($endpoint, 'GET', '', $params, [], [], 'MasterIndia', 'syn_gstin_det' );
      if($result['error']===false){
         $response = json_decode($result['data'], true);
         if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
            return $response['results'];
         }
      }

        if(isset($result['header_status']) && $result['header_status'] == 401){
            $this->refreshToken();
            return $this->syncGSTINDetails($original_data);
        }

      return json_response(400, $response['results']['errorMessage']??$result['message']);
    }


    public function getApiCounts($data){
      $original_data = $data;
      $endpoint = $this->BASE_URL.'/einvoice/apicount';

      $params = [
          'access_token' => $this->ACCESS_TOKEN,
          "account_email" => $data['account_email'],
          "from_date" => $data['from_date'],
          "to_date" => $data['to_date']
      ];

      $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'ebill_api_count' );

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

    // public function generateBulkEInvoice($data){
    //   $endpoint = $this->BASE_URL.'/bulkEinvoiceGenerate';
    //
    //   $data =[
    //     "access_token" =>$this->ACCESS_TOKEN,
    //     "einvoice_list"=> [[
    //       "user_gstin" => $data['api_user_gstin'],
    //       "data_source" => "erp",
    //       "transaction_details" => [
    //         "supply_type" => "B2B",
    //         "charge_type" => "N",
    //         "igst_on_intra" => "N",
    //         "ecommerce_gstin" => ""
    //       ],
    //       "document_details" => [
    //       "document_type" => "INV",
    //       "document_number" => "BMW/0160",
    //       "document_date" => "05/03/2020"
    //       ],
    //       "seller_details" => [
    //         "gstin" => "09AAAPG7885R002",
    //         "legal_name" => "MastersIndia UP",
    //         "trade_name" => "MastersIndia UP",
    //         "address1" => "Vila",
    //         "address2" => "Vila",
    //         "location" => "Noida",
    //         "pincode" => 201301,
    //         "state_code" => "UTTAR PRADESH",
    //         "phone_number" => 9876543231,
    //         "email" => ""
    //       ],
    //       "buyer_details" => [
    //         "gstin" => "05AAAPG7885R002",
    //         "legal_name" => "MastersIndia UT",
    //         "trade_name" => "MastersIndia UT",
    //         "address1" => "Kila",
    //         "address2" => "Kila",
    //         "location" => "Nainital",
    //         "pincode" => 110010,
    //         "place_of_supply" => "9",
    //         "state_code" => "UTTARAKHAND",
    //         "phone_number" => 9876543231,
    //         "email" => ""
    //       ],
    //       "dispatch_details" => [
    //         "company_name" => "MastersIndia UP",
    //         "address1" => "Vila",
    //         "address2" => "Vila",
    //         "location" => "Noida",
    //         "pincode" => 201301,
    //         "state_code" => "UTTAR PRADESH"
    //       ],
    //       "ship_details" => [
    //         "gstin" => "05AAAPG7885R002",
    //         "legal_name" => "MastersIndia UT",
    //         "trade_name" => "MastersIndia UT",
    //         "address1" => "Kila",
    //         "address2" => "Kila",
    //         "location" => "Nainital",
    //         "pincode" => 110010,
    //         "state_code" => "UTTARAKHAND"
    //       ],
    //       "export_details" => [
    //         "ship_bill_number" => "qwe1233",
    //         "ship_bill_date" => "08/02/2020",
    //         "country_code" => "IN",
    //         "foreign_currency" => "INR",
    //         "refund_claim" => "N",
    //         "port_code" => "232434",
    //         "export_duty" => 2534.34
    //       ],
    //       "payment_details" => [
    //         "bank_account_number" => "Account Details",
    //         "paid_balance_amount" => 100,
    //         "credit_days" => 2,
    //         "credit_transfer" => "Credit Transfer",
    //         "direct_debit" => "Direct Debit",
    //         "branch_or_ifsc" => "KKK000180",
    //         "payment_mode" => "CASH",
    //         "payee_name" => "Payee Name",
    //         "payment_due_date" => "08/02/2020",
    //         "payment_instruction" => "Payment Instruction",
    //         "payment_term" => "Terms of Payment"
    //       ],
    //       "reference_details" => [
    //         "invoice_remarks" => "Invoice Remarks",
    //         "document_period_details" => [
    //           "invoice_period_start_date" => "2020-03-06",
    //           "invoice_period_end_date" => "2020-03-07"
    //         ],
    //         "preceding_document_details" => [[
    //           "reference_of_original_invoice" => "CFRT/0006",
    //             "preceding_invoice_date"=> "08/02/2020",
    //             "other_reference" => "2334"
    //           ]
    //         ],
    //         "contract_details" => [[
    //           "receipt_advice_number" => "aaa",
    //           "receipt_advice_date"=> "10/02/2020",
    //           "batch_reference_number" => "2334",
    //           "contract_reference_number" => "2334",
    //           "other_reference" => "2334",
    //           "project_reference_number" => "2334",
    //           "vendor_po_reference_number" => "233433454545",
    //           "vendor_po_reference_date" => "10/02/2020"
    //         ]]
    //       ],
    //       "additional_document_details" => [[
    //         "supporting_document_url" => "",
    //         "supporting_document" => "india",
    //         "additional_information" => "india"
    //       ]],
    //       "value_details" => [
    //         "total_assessable_value" => 1,
    //         "total_cgst_value" => 0,
    //         "total_sgst_value" => 0,
    //         "total_igst_value" => 0.01,
    //         "total_cess_value" => 0,
    //         "total_cess_value_of_state" => 0,
    //         "total_discount" => 0,
    //         "total_other_charge" => 0,
    //         "total_invoice_value" => 1.01,
    //         "round_off_amount" => 0,
    //         "total_invoice_value_additional_currency" => 0
    //       ],
    //       "ewaybill_details" => [
    //         "transporter_id" => "05AAABB0639G1Z8",
    //         "transporter_name" => "Jay Trans",
    //         "transportation_mode" => "1",
    //         "transportation_distance" => "120",
    //         "transporter_document_number" => "1230",
    //         "transporter_document_date" => "08/02/2020",
    //         "vehicle_number" => "PQR1234",
    //         "vehicle_type" => "R"
    //       ],
    //       "item_list" => [[
    //         "item_serial_number" => "8965",
    //         "product_description" => "Wheat desc",
    //         "is_service" => "N",
    //         "hsn_code" => "1001",
    //         "bar_code" => "1212",
    //         "quantity" => 1,
    //         "free_quantity" => 0,
    //         "unit" => "KGS",
    //         "unit_price" => 1,
    //         "total_amount" => 1,
    //         "pre_tax_value" => 0,
    //         "discount" => 0,
    //         "other_charge" => 0,
    //         "assessable_value" => 1,
    //         "gst_rate" => 0,
    //         "igst_amount" => 0,
    //         "cgst_amount" => 1,
    //         "sgst_amount" => 0,
    //         "cess_rate" => 0,
    //         "cess_amount" => 0,
    //         "cess_nonadvol_amount" => 0,
    //         "state_cess_rate" => 0,
    //         "state_cess_amount" => 0,
    //         "state_cess_nonadvol_amount" => 0,
    //         "total_item_value" => 1,
    //         "country_origin" => "52",
    //         "order_line_reference" => "5236",
    //         "product_serial_number" => "14785",
    //         "batch_details" => [
    //           "name" => "aaa",
    //           "expiry_date" => "10/02/2020",
    //           "warranty_date" => "20/02/2020"
    //         ],
    //         "attribute_details" => [[
    //           "item_attribute_details" => "aaa",
    //           "item_attribute_value" => "147852"
    //         ]]
    //       ]]
    //     ]],
    //   ];
    //
    //   $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, [], 'MasterIndia', 'gen_bul_e_inv' );
    //   if($result['error']===false){
    //      $response = json_decode($result['data'], true);
    //      if(isset($response['results'])){
    //         return $response['results'];
    //      }
    //   }
    //
    //   return json_response(400, $response['results']['errorMessage']??$result['message']);
    // }


    /**
    * Apis related to eway bill using einvoice IRL starts from here
    * Not Being used for the time
    *
    */

    // public function generateEwayBillByIRN($data){
    //   $endpoint = $this->BASE_URL.'/generateEwaybillByIrn';
    //
    //   $data =[
    //     "access_token" =>$this->ACCESS_TOKEN,
    //     "user_gstin" => $data['api_user_gstin'],
    //     "irn" => $data['irn_no'],
    //     "transporter_id" => "",
    //     "transportation_mode" => "1",
    //     "transporter_document_number" => "J12345",
    //     "transporter_document_date" => "17/12/2020",
    //     "vehicle_number" => "KA01AB1234",
    //     "distance" => 0,
    //     "vehicle_type" => "R",
    //     "transporter_name" => "Jay Trans",
    //     "data_source" => "erp",
    //     "ship_details" => [
    //         "address1" => "Kila 1",
    //         "address2" => "Kila 1",
    //         "location" => "Nainital",
    //         "pincode" => 110007,
    //         "state_code" => "Delhi"
    //     ],
    //     "dispatch_details" => [
    //         "company_name" => "MastersIndia UP",
    //         "address1" => "Vila 1",
    //         "address2" => "Vila 1",
    //         "location" => "Noida",
    //         "pincode" => 122007,
    //         "state_code" => "Haryana"
    //     ]
    //   ];
    //
    //   $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, [], 'MasterIndia', 'generate_ewaybyirn' );
    //   if($result['error']===false){
    //      $response = json_decode($result['data'], true);
    //      if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
    //         return $response['results'];
    //      }
    //   }
    //
    //   return json_response(400, $response['results']['errorMessage']??$result['message']);
    // }


    // public function getEwayBillDetailsyIRN($data){
    //   $endpoint = $this->BASE_URL.'/getEwaybillDetailsThroughIrn';
    //
    //   $data =[
    //     "access_token" =>$this->ACCESS_TOKEN,
    //     "user_gstin" => $data['company_gstin'],
    //     "irn" => $data['irn_no'],
    //   ];
    //
    //   $result = $this->guzzleService->request($endpoint, 'GET', '', $data, [], [], 'MasterIndia', 'get_ebill_det_by_irn' );
    //   if($result['error']===false){
    //      $response = json_decode($result['data'], true);
    //      if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
    //         return $response['results'];
    //      }
    //   }
    //
    //   return json_response(400, $response['results']['errorMessage']??$result['message']);
    // }
    //
    // public function cancelEwayBillByIRN($data){
    //   $endpoint = $this->BASE_URL.'/getEwaybillDetailsThroughIrn';
    //
    //   $data =[
    //     "access_token" =>$this->ACCESS_TOKEN,
    //     "user_gstin" => $data['company_gstin'],
    //     "irn" => $data['irn_no'],
    //     "cancel_reason" => $this->cancellation_reasons[$data['cancel_reason']??'']??"2",
    //     "cancel_remarks" => $data['cancel_remarks']??'Wrong Entry',
    //     "ewaybill_cancel" => $this->cancellation_reasons[$data['cancel_reason']??'']??"2",
    //   ];
    //
    //   $result = $this->guzzleService->request($endpoint, 'GET', '', $data, [], [], 'MasterIndia', 'get_ebill_det_by_irn' );
    //   if($result['error']===false){
    //      $response = json_decode($result['data'], true);
    //      if(isset($response['results']['status']) && strtolower($response['results']['status']) == 'success'){
    //         return $response['results'];
    //      }
    //   }
    //
    //   return json_response(400, $response['results']['errorMessage']??$result['message']);
    // }
}
