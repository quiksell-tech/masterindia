<?php


namespace App\Services;

use App\Repositories\Interfaces\ApiLogInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;

class GuzzleService
{

    public function __construct(Client $client, ApiLogInterface $apiLog){
        $this->client=$client;
        $this->apiLog = $apiLog;
    }

    /**
     * @param $url string
     * @param $request_type string GET|POST|PUT|DELETE
     * @param $body_type string json|form|multipart|raw
     * @param $query_data array Query Parameters
     * @param $data array Request body
     * @param $headers array
     */

    public function request($url, $request_type, $body_type, $query_data, $data, $headers, $service='', $action='', $entity_id = null){
        //die($url);
        //setting headers

        $log_data=json_encode(compact('query_data', 'data', 'headers'));

        $body=[];
        //$body['debug']=fopen('php://stderr', 'w');
        if(!empty($headers) && is_array($headers)){

            if(isset($headers['auth_data'])){
                $this->generateAuthenticationBody($body, $headers['auth_data']);
                unset($headers['auth_data']);
            }
            if(!empty($headers))
                $body[RequestOptions::HEADERS]=$headers;
        }

        // setting query data
        if(!empty($query_data) && is_array($query_data)){
            $body[RequestOptions::QUERY]=$query_data;
        }

        //ssl verify false
        $body[RequestOptions::VERIFY] = false;

        try{
            switch(strtolower($request_type))
            {
                case 'get':
                case 'delete':
                    break;
                case 'post':
                case 'put':
                    $this->generateBody($body, $body_type, $data);
                    //return $body;
                    break;
                default:
                    return [
                        'status'=>400,
                        'error'=>true,
                        'message'=>'Unsupported Request Method',
                        'data'=>''
                    ];
            }

            //return $body;
            $response=$this->client->request(strtoupper($request_type), $url, $body);

            $data = $response->getBody()->getContents();
            $status_code = $response->getStatusCode();

            if(!empty($service) && !empty($action) && config('constants.ENABLE_API_LOGS') == true){

                $log = $this->apiLog->createRecord([
                    'service'=>$service,
                    'api_action'=>$action,
                    'end_point'=>$url,
                    'request_data'=>$log_data,
                    'response_data'=>$data??'',
                    'header_status'=>$status_code,
                    'entity_id' => $entity_id
                ]);

                // dispatch log listener event for further action based on log
                //event(new ApiLogs($log));

            }

            return [
                'status'=>200,
                'error'=>false,
                'message'=>'Success',
                'data'=>$data
            ];
        }catch(ClientException $e){
            // 400 level status errors handling
            $response=$e->getResponse();
            //echo $response->getStatusCode();die;
            $status_code=$response->getStatusCode();
            switch($status_code){
                case 400:
                    $message='Bad Request';
                    break;
                case 401:
                    $message = 'Unauthorized Request';
                    break;
                case 404:
                    $message = 'Resource Not Found';
                    break;
                default:
                    $message='Client Error';
            }

            $data = $response->getBody()->getContents();

            if(!empty($service) && !empty($action) && config('constants.ENABLE_API_LOGS') == true){
                $log = $this->apiLog->createRecord([
                    'service'=>$service,
                    'api_action'=>$action,
                    'end_point'=>$url,
                    'request_data'=>$log_data,
                    'response_data'=>$data??'',
                    'header_status'=>$status_code,
                    'entity_id' => $entity_id
                ]);

                // dispatch log listener event for further action based on log
                //event(new ApiLogs($log));
            }

            return [
                'error'=>true,
                'status'=>$status_code,
                'message'=>$message,
                'data'=>$data
            ];
        }catch(ServerException $e){
            // 500 level status errors handling
            $response=$e->getResponse();

            $status_code=$response->getStatusCode();
            $data = $response->getBody()->getContents();

            if(!empty($service) && !empty($action) && config('constants.ENABLE_API_LOGS') == true){

                $log = $this->apiLog->createRecord([
                    'service'=>$service,
                    'api_action'=>$action,
                    'end_point'=>$url,
                    'request_data'=>$log_data,
                    'response_data'=>$data??'',
                    'header_status'=>$status_code,
                    'entity_id' => $entity_id
                ]);

                // dispatch log listener event for further action based on log
                //event(new ApiLogs($log));
            }

            return [
                'error'=>true,
                'status'=>500,
                'message'=>'Server Error',
                'data'=>$data
            ];
        }catch(TransferException $e){
            // other network error
            if(!empty($service) && !empty($action) && config('constants.ENABLE_API_LOGS') == true){
              $log = $this->apiLog->createRecord([
                  'service'=>$service,
                  'api_action'=>$action,
                  'end_point'=>$url,
                  'request_data'=>$log_data,
                  'response_data'=>'',
                  'header_status'=>500,
                  'entity_id' => $entity_id
              ]);

                // dispatch log listener event for further action based on log
                //event(new ApiLogs($log));
            }
            return [
                'error'=>true,
                'status'=>500,
                'message'=>'Network Error',
                'data'=>'Network Error'
            ];
        }
    }


    private function generateBody(&$body, $body_type, $data){
        switch(strtolower($body_type)){
            case 'json':
                $body[RequestOptions::JSON]=$data;
                break;
            case 'form':
                $body[RequestOptions::FORM_PARAMS]=$data;
                break;
            case 'multipart':
                $multipart=$data;
                $body[RequestOptions::MULTIPART]=$multipart;
                break;
            case 'raw':
                $body[RequestOptions::BODY]=$data;
                break;
        }
    }

    private function generateAuthenticationBody(&$body, $auth_data){
        switch($auth_data['auth_type']){
            case 'basic':

                $body[RequestOptions::AUTH]=[$auth_data['username'], $auth_data['password']];

                break;
            default:break;
        }

    }

}
