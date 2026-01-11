<?php

namespace App\Services;

class Msg91Service
{
    protected string $authKey;
    protected string $templateId;

    public function __construct()
    {
        $this->authKey   = config('services.msg91.auth_key');
        $this->templateId = config('services.msg91.template_id');
    }

    /**
     * Send OTP via MSG91 Flow
     */
    public function sendOtp(string $mobile, string $otp)
    {
        $payload = [
            "template_id" => $this->templateId,
            "short_url" => "0",
            "recipients" => [
                [
                    "mobiles" => $mobile,
                    "VAR1" => $otp
                ]
            ]
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://control.msg91.com/api/v5/flow",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authkey: {$this->authKey}",
                "content-type: application/json",
            ],
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => $error
            ];
        }

        return [
            'success' => true,
            'response' => json_decode($response, true)
        ];
    }
    public function sendOtpNew(string $mobile, string $otp)
    {
        $url="https://control.msg91.com/api/v5/otp?template_id=$this->templateId&mobile=$mobile&authkey=$this->authKey&otp=$otp";
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [

                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        var_dump($response);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return [
                'success' => false,
                'message' => $error
            ];
        }

        return [
            'success' => true,
            'response' => json_decode($response, true)
        ];
    }
}
