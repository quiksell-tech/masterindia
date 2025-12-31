<?php


namespace App\Services\EwayBill;


use App\Repositories\Interfaces\SystemParametersInterface;
use App\Services\GuzzleService;
use App\Traits\GetConfig;
use Illuminate\Http\Response;


/**
 * This class implements apis for ewaybill operations using govt portal.
 * HttP Request creation & response parsing is handled by this class
 *
 * @author     Pankaj Sengar
 */
class EwayBillGovtService implements EwayBillService
{
    use GetConfig;

    public function __construct(GuzzleService $guzzleService, SystemParametersInterface $systemParameters){
        $this->guzzleService = $guzzleService;
        $this->systemParameters = $systemParameters;
        $this->getSystemParams(['Ewaybill']);
        if(!empty($this->AUTH_TIMESTAMP)){
            if(date('Y-m-d H:i:s') > date('Y-m-d', strtotime('+5 minutes', strtotime($this->AUTH_TIMESTAMP)))){
                $response = $this->authenticate();
                if($response instanceof Response)
                    return $response;

                $this->systemParameters->updateRecord([
                    'sysprm_provider'=>'EwayBill',
                    'sysprm_name'=>'AUTHTOKEN'
                ],
                    [
                        'sysprm_value'=>$response['auth_token'],
                    ]);
                $this->systemParameters->updateRecord([
                    'sysprm_provider'=>'EwayBill',
                    'sysprm_name'=>'AUTH_TIMESTAMP'
                ],
                    [
                        'sysprm_value'=>date('Y-m-d H:i:s'),
                    ]);
                $this->systemParameters->updateRecord([
                    'sysprm_provider'=>'EwayBill',
                    'sysprm_name'=>'SESSION_KEY'
                ],
                    [
                        'sysprm_value'=>$response['session_key']
                    ]);
            }
        }

    }

    /**
     *
     * Generates Auth Token
     *
     * @return mixed Response | Array
     */

    public function authenticate(){

        $endpoint = $this->API_URL . '/auth/';

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN
        ];

        $payload = [
            'action'=>'ACCESSTOKEN',
            'username'=>'',
            'password'=>'',
            'app_key'=>'KKKKKKKKKKKKKKKKKKKKKKKKKKKKKKKK',
        ];

        $json = json_encode($payload);
        $base64 = base64_encode($json);

        $encrypted="";

        openssl_public_encrypt($base64, $encrypted, $this->PUBLIC_KEY);

        $data = [
            'data'=>$encrypted
        ];

        $response = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, $headers, 'EwayBill', 'auth');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);

        $data= json_decode($response['data'], true);

        //aes-256-ecb algorithm



        if(isset($data['authtoken']) && isset($data['sek']))
            return [
                'session_key'=>$this->decrypt($data['sek'], $payload['app_key']),
                'auth_token'=>$data['authtoken']
            ];
        return $response;

    }


    /**
     *
     * Generates EwayBill
     *
     * @return mixed Response | Array
     */
    public function generateEwayBill($order_summary, $items, $fromparty, $toparty){
        $endpoint = $this->API_URL . '/ewayapi/';

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $itemsList=[];
        foreach ($items as $item) {
            $itemsList =[
                "productName" => $items['product_name'], //need to ask
                "productDesc" => $items['product_description'],
                "hsnCode" => $items['product_hsn'],
                "quantity" => $items['product_quantity'],
                "igstRate" => $items['product_gst_perc'],
                "taxableAmount" => $items['product_unit_value']*$items['product_quantity']
            ];
        }

        $payload=[
            "supplyType" => "O",
            "subSupplyType" => "1",
            "subSupplyDesc" => "",
            "docType" => "INV",
            "docNo" => "", // to be discussed
            "docDate" => "",// to be discussed
            "toGstin" => $fromparty['toGstin'],
            "toTrdName" => $fromparty['toTrdName'],
            "toAddr1" => $fromparty['toAddr1']??"",
            "toAddr2" => $fromparty['toAddr2']??"",
            "toPlace" => $fromparty['toPlace']??"",
            "toPincode" => $fromparty['toPincode'],
            "actToStateCode" => $fromparty['actToStateCode'],
            "toStateCode" => $fromparty['toStateCode'],
            "toGstin" => $toparty['toGstin'],
            "toTrdName" => $toparty['toTrdName'],
            "toAddr1" => $toparty['toAddr1']??"",
            "toAddr2" => $toparty['toAddr2']??"",
            "toPlace" => $toparty['toPlace']??"",
            "toPincode" => $toparty['toPincode'],
            "actToStateCode" => $toparty['actToStateCode'],
            "toStateCode" => $toparty['toStateCode'],
            "transactionType" => 1,
            "otherValue" => "0",
            "totalValue" => $order_summary['sell_invoice_total_value'],
            "cgstValue" => 0,
            "sgstValue" => 0,
            "igstValue" => 300.67,
            "cessValue" => 0,
            "cessNonAdvolValue" => 0,
            "totInvValue" => $order_summary['sell_invoice_total_value'], //need to ask
            "transporterId" => "",
            "transporterName" => "",
            "transDocNo" => "",
            "transMode" => "1",
            //"transDistance" => "100",
            "transDocDate" => "",
            "vehicleNo" => "PVC1234",
            "vehicleType" => "R",
            "itemList" => $items
        ];

        $data = [
            'action' => 'GENEWAYBILL',
            'data' => $this->encrypt($payload),
        ];

        $response = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, $headers, 'EwayBill', 'auth');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);


        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;


        $response = $this->decrypt($response['data']);
        $response=base64_decode($response);
        $response = json_decode($response, true);

        return [
            'ewayBillNo'=>$response['ewayBillNo'],
            'ewayBillDate'=>$response['ewayBillDate'],
            'validUpto'=>$response['validUpto'],
            'alert'=>$response['alert']
        ] ;

    }

    /**
     *
     * Cancels EwayBill
     *
     * @return mixed Response | Array
     */

    public function cancelEwayBill($data){
        $endpoint = $this->API_URL . '/ewayapi/';

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $payload = [
            "ewbNo" => $data['ewb_no'],
            "cancelRsnCode" => $data['reason_code'],
            "cancelRmrk" => $data['cancel_remarks']
        ];

        $data = [
            'action'=>'CANEWB',
            'data'=>$this->encrypt($payload)
        ];

        $response = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, $headers, 'EwayBill', 'cancelbill');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);

        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;

        return true;

    }


    /**
     * Get GSTIN Details
     *
     */
    public function getGSTINDetails(){
        $endpoint = $this->API_URL . '/Master/GetGSTINDetails';

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $response = $this->guzzleService->request($endpoint, 'GET', 'json', [], [], $headers, 'EwayBill', 'gstindetails');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);



        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;

        $rek = $this->decrypt($response['rek']);

        $decrypted = $this->decrypt($response['data'], $rek);
        $data = base64_decode($decrypted);
        $data = json_decode($data);

        return [
            "gstin" => $data['gstin'],
            "tradeName" => $data["tradeName"],
            "legalName" => $data["legalName"],
            "address1" => $data["address1"],
            "address2" => $data["address2"],
            "stateCode" => $data["stateCode"],
            "pinCode" => $data["pinCode"],
            "txpType" => $data["txpType"],
            "status" => $data["status"],
            "blkStatus" => $data["blkStatus"]
        ];

    }


    /**
     *
     * Get Errors List
     */
    public function getErrorList(){
        $endpoint = $this->API_URL . '/Master/GetErrorList';

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $response = $this->guzzleService->request($endpoint, 'GET', 'json', [], [], $headers, 'EwayBill', 'errorlist');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);

        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;

        $rek = $this->decrypt($response['rek']);

        $decrypted = $this->decrypt($response['data'], $rek);
        $data = base64_decode($decrypted);
        $data = json_decode($data);

        return $data;

    }

    /**
     * Get HSN Details
     *
     */

    public function getHSNDetails($hsncode){
        $endpoint = $this->API_URL . '/Master/GetHsnDetailsByHsnCode?hsncode='.$hsncode;

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $response = $this->guzzleService->request($endpoint, 'GET', 'json', [], [], $headers, 'EwayBill', 'hsndetails');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);

        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;

        $rek = $this->decrypt($response['rek']);

        $decrypted = $this->decrypt($response['data'], $rek);
        $data = base64_decode($decrypted);
        $data = json_decode($data);

        return [
            "hsnCode"=>$data['hsnCode'],
            "hsnDesc"=>$data['hsnDesc']
        ];

    }

    /**
     * Get EwayBill Details
     *
     */
    public function getEwayBillDetails($ewbno){
        $endpoint = $this->API_URL . '/ewayapi/GetEwayBill?ewbNo='.$ewbno;

        $headers = [
            'client-id'=>$this->CLIENT_ID,
            'client-secret'=>$this->CLIENT_SECRET,
            'gstin'=>$this->GSTIN,
            'authtoken'=>$this->AUTHTOKEN
        ];

        $response = $this->guzzleService->request($endpoint, 'GET', 'json', [], [], $headers, 'EwayBill', 'billdetails');

        if($response['error'])
            return json_response($response['status'], $response['message'], ['error_data'=>$response['data']]);


        $response = $this->checkResponse($response['data']);
        if($response instanceof Response)
            return $response;

        $rek = $this->decrypt($response['rek']);

        $decrypted = $this->decrypt($response['data'], $rek);
        $data = base64_decode($decrypted);
        $data = json_decode($data);

        return $data;
    }


    // Helper Function: check response for failure
    private function checkResponse($data){

        $data = json_decode($data, true);
        if($data['status']==0)
            return json_response('400', $data['info']??'');

        return $data;

    }

    //Helper Function: Encrytion Request Payload
    private function encryption($data, $key=''){
        $json = json_encode($data);
        $base64=base64_encode($json);
        if($key)
            $encrypted = openssl_encrypt($base64, 'aes-256-ecb', $key);
        else {
            $encrypted = openssl_encrypt($base64, 'aes-256-ecb', $this->SESSION_KEY);
        }
        return $encrypted;
    }


    //Helper Function: Data Decryption
    private function decrypt($data, $key=''){
        if($key)
            $decrypted = openssl_decrypt($data, 'aes-256-ecb', $key);
        else {
            $decrypted = openssl_decrypt($data, 'aes-256-ecb', $this->SESSION_KEY);
        }
        return $decrypted;
    }




}
