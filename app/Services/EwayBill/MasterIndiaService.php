<?php

namespace App\Services\EwayBill;

use App\Models\SystemParameter;
use App\Services\GuzzleService;
use Carbon\Carbon;

class MasterIndiaService
{

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
                    // Save new access token
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
                'message' => 'Access Token Cannot Be Generated1'
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

        $this->systemParameters->update([
            'sysprm_provider' => 'MasterIndia',
            'sysprm_name' => 'ACCESS_TOKEN'
        ],
            [
                'sysprm_value' => $token,
            ]);
        $this->systemParameters->update([
            'sysprm_provider' => 'MasterIndia',
            'sysprm_name' => 'AUTH_TIMESTAMP'
        ],
            [
                'sysprm_value' => $this->AUTH_TIMESTAMP,
            ]);

        return true;

    }

    public function authenticate()
    {

        echo $endpoint = $this->BASE_URL . '/oauth/access_token';

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
    public function authenticateNew()
    {

        $endpoint = $this->BASE_URL . '/api/v1/token-auth/';

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

    public function generateEwayBill($data,$parameters)
    {
        $endpoint = $this->BASE_URL . '/ewayBillsGenerate';
        $parameters['access_token'] = $this->ACCESS_TOKEN;
        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $parameters, [], 'MasterIndia', 'gen_e_bill', $data['order_invoice_number']);
        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                $response['results']['display_message'] = $response['results']['message']['alert'];
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->generateEwayBill($data,$parameters);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));
    }

    public function cancelEwayBill($data,$params)
    {
        $original_data = $params;
        $endpoint = $this->BASE_URL . '/ewayBillCancel';
        $params['access_token'] = $this->ACCESS_TOKEN;
        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'can_e_bill', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->cancelEwayBill($data,$original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));

    }

    public function updateVehicleNumber($data,$params)
    {
        $original_data = $params;
        $endpoint = $this->BASE_URL . '/updateVehicleNumber';
        $params['access_token'] = $this->ACCESS_TOKEN;

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_vcle', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->updateVehicleNumber($data,$original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));

    }


    public function updateTransporterID($data,$params)
    {
        $original_data = $params;
        $endpoint = $this->BASE_URL . '/transporterIdUpdate';
        $params['access_token'] = $this->ACCESS_TOKEN;

        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_trans', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->updateTransporterID($data,$original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));


    }

    public function extendBillValidity($data,$params)
    {
        $original_data = $params;
        $endpoint = $this->BASE_URL . '/ewayBillValidityExtend';
        $params['access_token'] = $this->ACCESS_TOKEN;


        $result = $this->guzzleService->request($endpoint, 'POST', 'json', [], $params, [], 'MasterIndia', 'update_validity', $data['sell_invoice_ref_no']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->extendBillValidity($data,$original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));
    }

    public function getEwayBillDetails($data,$params)
    {
        $original_data = $params;
        $endpoint = $this->BASE_URL . '/getEwayBillData';
        $params['access_token'] = $this->ACCESS_TOKEN;

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $params, [], [], 'MasterIndia', 'get_ebill_det', $data['order_invoice_number']);

        if ($result['error'] === false) {
            $response = json_decode($result['data'], true);
            if (isset($response['results']['status']) && strtolower($response['results']['status']) == 'success') {
                return $response['results'];
            }
        }

        if (isset($result['header_status']) && $result['header_status'] == 401) {
            $this->refreshToken();
            return $this->getEwayBillDetails($data,$original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));
    }

    public function getGSTINDetails($data)
    {
        $original_data = $data;
        $endpoint = $this->BASE_URL . '/getEwayBillData';

        $params = [
            'action' => 'GetGSTINDetails',
            'access_token' => $this->ACCESS_TOKEN,
            "userGstin" => $data['company_gstin'],
            "gstin" => $data['buyer_gstin']
        ];

        $result = $this->guzzleService->request($endpoint, 'GET', 'json', $params, [], [], 'MasterIndia', 'get_gst_det', $data['sell_invoice_ref_no']);

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
            return $this->getGSTINDetails($original_data);
        }

        return json_response(400, ($response['results']['message'] ?? $result['message']) . ' ' . ($response['results']['code'] ?? ''));
    }


}
