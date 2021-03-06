<?php
function sendRequest($URL,$method,$header,$content){
    $options = ['http' => ['method' => $method, 'header' => $header, 'content' => $content]];
    $context = stream_context_create($options);
    $content =  file_get_contents("http://$URL", false, $context);
    if ($code = getHeaderValue($http_response_header, 'App-Exception')) {
        throwExceptionByCode($code);
    }
    return $content;
}

function sendRequestJSON($URL,$method,$header,$content){
    $options = ['http' => ['method' => $method, 'header' => $header, 'content' => $content]];
    $context = stream_context_create($options);
    $content =  file_get_contents("http://$URL", false, $context);
    if ($code = getHeaderValue($http_response_header, 'App-Exception')) {
        throwExceptionByCode($code);
    }
    return json_decode($content,true);
}

function sendFile($URL,$file_path,$file_name,$callback=null){
    $mime = mime_content_type($file_path);
    $server = "http://$URL";
    $curl = curl_init($server);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    $data = ['userfile' => curl_file_create($file_path,$mime,$file_name)];
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $response = parseResponse($response);
    if ($code = getHeaderValue($response['headers'], 'App-Exception')) {
       throwExceptionByCode($code);
    }
    return $response['body'];
}

function parseResponse($response_string){
    $exp = explode("\r\n\r",$response_string);
    $headers = explode(PHP_EOL,$exp[1]);
    array_shift($headers);
    array_shift($headers);
    if (isset($exp[2]))
        $body = trim($exp[2]);
    return ["headers"=>$headers, "body"=>$body];
}

function checkAuth(){
    return 'eeec1e618690fba21fd416df610da961';
    /*if (!isset($_COOKIE['token'])) {
        $header = HeadersController::getInstance();
        $auth = Config::get('auth_url');
        $host = Config::get('host_url');
        $back = urlencode("http://$host".$_SERVER['REQUEST_URI']);
        $url = "http://$auth?redirect_uri=$back";
        $header->respondLocation(['value'=>$url]);
        exit;
    } else {
        $auth = Config::get('auth_url');
        $authcheck = sendRequest("$auth/token/check",'POST',
            'Content-type: application/x-www-form-urlencoded',
            http_build_query(['token'=>$_COOKIE['token']]));
        switch ($authcheck['is_valid']){
            case true: return $authcheck['user_id'];break;
            case false:
                $header = HeadersController::getInstance();
                $host = Config::get('host_url');
                $back = urlencode("http://$host".$_SERVER['REQUEST_URI']);
                $url = "http://$auth?redirect_uri=$back";
                $header->respondLocation(['value'=>$url]);
                exit;
        }
    }
    */
}

function throwException (array $exception,$redirect_uri = null){
    header("App-Exception: {$exception['code']}");
    if ($redirect_uri){
        $header = HeadersController::getInstance();
        $header->respondLocation(['value'=>"$redirect_uri?err={$exception['code']}"]);
    }
    ob_clean();
    exit();
}

function throwExceptionByCode ($code){
    header("App-Exception: ".(int)$code);
    ob_clean();
    exit();
}

function getHeaderValue($headers_array, $header) {
    foreach ($headers_array as $value) {
        $parsed_array = explode(':', $value, 2);
        if ($parsed_array[0] === $header) {
            return trim($parsed_array[1]);
        }
    }
    return false;
}
