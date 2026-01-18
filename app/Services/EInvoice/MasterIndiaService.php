<?php

namespace App\Services\EInvoice;

use App\Models\SystemParameter;
use App\Services\GuzzleService;
use Carbon\Carbon;

class MasterIndiaService
{

    protected $cancellation_reasons = [
        "duplicate" => "1", //Duplicate
        "incorrect-details" => "2" //Data Entry Mistake
    ];
    protected $systemParameters;

    protected $ACCESS_TOKEN = null;
    protected $AUTH_TIMESTAMP = null;
    protected $guzzleService;

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

                $token = $this->authenticateNew();

                if ($token) {
                    $this->systemParameters
                        ->where('sysprm_provider', 'MasterIndia')
                        ->where('sysprm_name', 'ACCESS_TOKEN')
                        ->update([
                            'sysprm_value' => $token,
                        ]);
                    // Save new expiry timestamp (50 mins)
                    $this->systemParameters
                        ->where('sysprm_provider', 'MasterIndia')
                        ->where('sysprm_name', 'AUTH_TIMESTAMP')
                        ->update([
                            'sysprm_value' => Carbon::now()->addMinutes(50)->format('Y-m-d H:i:s'),
                        ]);
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
    public function refreshToken()
    {

        $token = $this->authenticateNew();
        if (!$token) {
            return json_response(400, 'Access Token Cannot Be Generated');
        }

        $this->ACCESS_TOKEN = $token;
        $this->AUTH_TIMESTAMP = date('Y-m-d H:i:s', strtotime('+50 minutes'));

        $this->systemParameters->updateRecord([
            'sysprm_provider' => 'MasterIndia',
            'sysprm_name' => 'ACCESS_TOKEN'
        ],
            [
                'sysprm_value' => $token,
            ]);
        $this->systemParameters->updateRecord([
            'sysprm_provider' => 'MasterIndia',
            'sysprm_name' => 'AUTH_TIMESTAMP'
        ],
            [
                'sysprm_value' => $this->AUTH_TIMESTAMP,
            ]);

        return true;

    }
    public function authenticateNew()
    {

        //$endpoint = $this->BASE_URL . '/api/v1/token-auth/';
        $endpoint = $this->BASE_URL . '/token-auth/';
        $data = [
            'username' => $this->USERNAME,
            'password' => $this->PASSWORD,
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, [], 'MasterIndia', 'authorize');

        if ($result['error'] === false) {

            $response = json_decode($result['data'], true);
            if (!empty($response['token'])) {
                return $response['token'];
            }
        }

        return null;

    }

    public function authenticate()
    {

        $endpoint = $this->BASE_URL . '/oauth/access_token';

        $data = [
            'username' => $this->USERNAME,
            'password' => $this->PASSWORD,
            'client_id' => $this->CLIENT_ID,
            'client_secret' => $this->CLIENT_SECRET,
            'grant_type' => 'password'
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $data, [], 'MasterIndia', 'authorize');
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (!empty($response['access_token'])) {
                return $response['access_token'];
            }
        }

        return null;

    }

    /*
     * Function to generate einvoice
     */
    public function generateEInvoice($data,$params)
    {

        $original_data = $params;
        $endpoint = $this->BASE_URL . '/einvoice/';
        $headers = [
            'Authorization' => 'JWT ' . $this->ACCESS_TOKEN
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, $headers, 'MasterIndia', 'gen_e_inv', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                if (stripos($response['results']['message']['alert'], 'IRN already generated') !== false)
                    return json_response(400, $response['results']['message']['alert']);
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->generateEInvoice($data,$original_data);
        }

        return json_response(400, $response['results']['errorMessage'] ?? $result['message']);

    }

    /*
     * Function to generate credit note
     */
    public function generateCreditNote($data,$params)
    {

        $original_data = $params;
        $endpoint = $this->BASE_URL . '/einvoice/';
        $headers = [
            'Authorization' => 'JWT ' . $this->ACCESS_TOKEN
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, $headers, 'MasterIndia', 'gen_cr_note', $data['creditnote_invoice_no']);
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                if (stripos($response['results']['message']['alert'], 'IRN already generated') !== false)
                    return json_response(400, $response['results']['message']['alert']);
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if ((isset($result['header_status']) && $result['header_status'] == 401) || json_decode($result['data'], true)['results']['message'] ?? '' == 'The access token provided is invalid.') {
            $this->refreshToken();
            return $this->generateCreditNote($data,$original_data);
        }

        return json_response(400, $response['results']['errorMessage'] ?? $result['message']);

    }

    public function cancelEInvoice($data,$params)
    {

        $original_data = $params;
        $endpoint = $this->BASE_URL . '/cancel-einvoice/';
        $headers = [
            'Authorization' => 'JWT ' . $this->ACCESS_TOKEN
        ];

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, $headers, 'MasterIndia', 'can_e_inv', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->cancelEInvoice($data,$original_data);
        }

        return json_response(400, $response['results']['errorMessage'] ?? $result['message']);


    }

    public function getEInvoice($data,$params)
    {

        $original_data = $params;
        $endpoint = $this->BASE_URL . '/getEinvoiceData';
        $params['access_token'] = $this->ACCESS_TOKEN;

        $result = $this->guzzleService->request($endpoint, 'GET', '', $params, [], [], 'MasterIndia', 'get_e_inv', $data['order_invoice_number']);
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->getEInvoice($data,$original_data);
        }

        return json_response(400, $response['results']['errorMessage'] ?? $result['message']);
    }


    public function getGSTINDetails($data)
    {

        $original_data = $data;
        $endpoint = $this->MI_COMMON_API . '/searchgstin';
        $endpoint = $this->BASE_URL . '/get-gstin-details';
        $headers = [
            'Authorization' => 'JWT ' . $this->ACCESS_TOKEN
        ];
        $query = [
            "gstin" => $data['buyer_gstin'],
            "user_gstin" => $data['company_gstin'],
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $query, [], $headers, 'MasterIndia', 'get_gstin_det');

        if ($result['error'] === false) {
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
                        "TxpType" => $response['data']['dty'] ?? null,
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

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->getGSTINDetails($original_data);
        }

        return json_response(400, $response['message'] ?? $result['message']);
    }
    public function getGSTINDetailsNew($data)
    {
        $original_data = $data;
        $endpoint = $this->BASE_URL . '/getEwayBillData';

        $params = [
            'action' => 'GetGSTINDetails',
            "userGstin" => $data['company_gstin'],
            "gstin" => $data['buyer_gstin']
        ];
        $headers = [
            'Authorization' => 'JWT ' . $this->ACCESS_TOKEN
        ];
        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $params, [],$headers, 'MasterIndia', 'get_gst_det', $data['sell_invoice_ref_no']);
        // request($url, $request_type, $body_type, $query_data, $data, $headers, $service='', $action='', $entity_id = null)
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                if (isset($response['results']['message']['status'])) {
                    if ($response['results']['message']['status'] == 'ACT')
                        $response['results']['gstin_status'] = 'active';
                    else
                        $response['results']['gstin_status'] = 'not_active';
                    return $response['results'];
                } else {
                    return json_response(400, $response['results']['message'] ?? 'GSTIN Details Not Fetched');
                }

            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->getGSTINDetailsNew($original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));
    }

    public function syncGSTINDetails($data)
    {
        $original_data = $data;
        $endpoint = $this->BASE_URL . '/syncGstinDetails';

        $params = [
            "access_token" => $this->ACCESS_TOKEN,
            "user_gstin" => $data['company_gstin'],
            "gstin" => $data['gstin_number'],
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', '', $params, [], [], 'MasterIndia', 'syn_gstin_det');
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->syncGSTINDetails($original_data);
        }

        return json_response(400, $response['results']['errorMessage'] ?? $result['message']);
    }





}
