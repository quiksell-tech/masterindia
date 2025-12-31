<?php

use Illuminate\Support\Facades\Validator;

function json_response(){
    // Variables created to avoid undefined error
    $data = $status = $message = $action=null;

    // Get all the passed parameters as an array
    $args = func_get_args();

    // Loop through the arguments
    foreach ($args as $arg) {

        // Set data if any array is passed in to the function
        if (is_null($data))
            if (is_array($arg)) {
                $data = $arg;
                continue;
            }

        // Set status code if any numeric value is passed in to the function
        if (is_null($status))
            if (is_numeric($arg)) {
                $status = $arg;
                continue;
            }

        // Set message if any string value is passed in to the function
        if (is_null($message)){
            if (is_string($arg) && stripos($arg,'action:')!==0) {
                $message = $arg;
                continue;
            }
        }

        if (is_null($action)){
            if (is_string($arg) && stripos($arg,'action:')===0) {
                //die('abcd');
                $action = str_replace('action:', '', $arg);
                continue;
            }
        }




    }

    // Prepare return array
    $return = [
        'success' => (!isset($data['error_code'])&&($status == 200))?true:false,
        'message' => ($message == null) ? (($status == 200) ? 'Success' :($status==422?'Invalid or missing parameters':'Unexpected error occured') ) : $message,
        //'device_action'=>$action??''
    ];

    if(is_array($data))
        $return = array_merge($return, $data);

    // Set status code in header if status is set

    if (is_numeric($status))
        return response($return, $status);

    return response($return);
}

function result_data(){
    // Variables created to avoid undefined error
    $data = $status = $message = null;

    // Get all the passed parameters as an array
    $args = func_get_args();

    // Loop through the arguments
    foreach ($args as $arg) {

        // Set data if any array is passed in to the function
        if (is_null($data))
            if (is_array($arg)) {
                $data = $arg;
                continue;
            }

        // Set status code if any numeric value is passed in to the function
        if (is_null($status))
            if (is_numeric($arg)) {
                $status = $arg;
                continue;
            }

        // Set message if any string value is passed in to the function
        if (is_null($message)){
            if (is_string($arg)) {
                $message = $arg;
                continue;
            }
        }
    }

    // Prepare return array
    return [
        'status' => $status,
        'message'=> $message??'',
        'data'=> $data
    ];
}

/**
 * @param string $url
 * @param string|array $body
 * @param string|array $headers
 * @param int $type 0 for POST, 1 for GET
 * @param bool $is_json true for JSON, false for array
 * @return array [error=true/false, message=success/error_message, result=response]
 */
function fetch_from_url($url, $body = '', $headers = '', $type = 0, $is_json = true)
{
    $result = array();
    if (strlen($url) > 0) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($type == 0) {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $result['error'] = true;
            $result['message'] = curl_error($ch);
        } else {
            $result['error'] = false;
            $result['message'] = 'success';
            if (!$is_json) {
                $result['result'] = json_decode($response, true);
            } else {
                $result['result'] = $response;
            }
        }
    } else {
        $result['error'] = true;
        $result['message'] = 'empty_url';
    }
    return $result;
}

function image_uploader($request, $param_name, $store_path,
                        $file_type='file',
                        $allowed_types = false,
                        $max_size = false,
                        $max_width = false,
                        $max_height = false){

    $rules="required|$file_type";
    if($allowed_types)
        $rules.="|mimes:$allowed_types";
    if($max_size)
        $rules.="|max:$max_size";
    if($max_width)
        $rules.="|max_width:$max_width";
    if($max_height)
        $rules.="|max_height:$max_height";

    $errors=Validator::make($request->all(),
        [
            $param_name => $rules,
        ],
        [
            $param_name.'.*' => 'Unsupported file format',
        ])
        ->errors()
        ->toArray();

    if (count($errors)) {
        return $errors;
    }

    $path=explode('/', $store_path);
    $file_name=$path[count($path)-1];

    $target_path=str_replace($file_name, '', $store_path);
    //die;
    $file=$request->file($param_name);

    if($file){
        //receive file locally
        $temp_file_name = uniqid().'-temp-'.$file_name;
        $contents = file_get_contents($file);
        \Storage::disk('temp')->put($temp_file_name, $contents);

        // send file to different server
        $fileuploader=app('App\Services\FileUpload');
        $result=$fileuploader->upload($file_name,
            str_replace('\\', '/', \Storage::disk('temp')->path($temp_file_name)),
            $target_path, $request->watermark??false, $request->adjust??false);

        //delete local file
        \Storage::disk('temp')->delete($temp_file_name);

        if(empty($result) || $result['error']=='true'){
            return $result['message']??'File upload error';
        }

        return true;
    }

}

function getserverlink(){
    if(App::environment('local')){
        $api_server = 'https://qaapi.recycledevice.com/';
    }else{
        $api_server = 'https://api.recycledevice.com/';

    }
    return $api_server;
}

function getcdnserverlink(){
    if(App::environment('local')){
        $api_server = 'https://qacdn.recycledevice.com/';
    }else{
        $api_server = 'https://cdn.recycledevice.com/';

    }
    return $api_server;
}


function isExcluded($name, $exclude_array){

    foreach ($exclude_array as $ex){
        if(stripos($name, $ex)!==false){
            return true;
        }
    }

    return false;

}


function generateUniqueNumber($length) {
    // Combine microtime, random bytes, and process ID for uniqueness
    $data = microtime(true) . random_int(100000, 999999) . getmypid();

    // Hash and extract numeric characters only
    $hash = md5($data);
    $numeric = preg_replace('/\D/', '', base_convert($hash, 16, 10));

    // Ensure exactly 32 digits
    return str_pad(substr($numeric, 0, $length), $length, '0', STR_PAD_RIGHT);
}
