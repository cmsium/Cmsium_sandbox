<?php


function createInSandbox(){
    $owner_user_id = checkAuth();
    if (!empty($_FILES)) {
        $validator = Validator::getInstance();
        $redirect_uri = false;
        if (isset($_POST['redirect_uri'])) {
            $redirect_uri = $validator->Check('URL', $_POST['redirect_uri'], []);
        }
        $file_data = $validator->ValidateAllByMask($_FILES['userfile'], 'fileUploadMask');
        if ($file_data === false) {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri";
                $header->respondLocation(['value'=>$url]);
                throwException(DATA_FORMAT_ERROR);
            } else
                throwException(DATA_FORMAT_ERROR);
        }
        if (!checkMime($_FILES['userfile']['tmp_name'],end(explode('.',$_FILES['userfile']['name'])))) {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri";
                $header->respondLocation(['value'=>$url]);
                throwException(WRONG_FILE_TYPE);
            } else
                throwException(WRONG_FILE_TYPE);
        }
        $size = filesize($_FILES['userfile']['tmp_name']);
        if (($file_data["size"] > MAX_FILE_UPLOAD_SIZE) or ($size > MAX_FILE_UPLOAD_SIZE)) {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri";
                $header->respondLocation(['value'=>$url]);
                throwException(TOO_LARGE_FILE);
            } else
                throwException(TOO_LARGE_FILE);
        }
        $path = ROOTDIR."/".STORAGE;
        $fullpath = "$path/".$validator->transliterate(unicode_decode($_FILES['userfile']['name']));
        if (upload($_FILES['userfile']['tmp_name'],$fullpath)) {
            $id = md5_file($fullpath);
            $controller = Config::get('controller_url');
            $response = sendRequest("$controller/registerSandboxFile?id=$id&file=$fullpath&user=$owner_user_id",'GET',null,null);
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $header->respondLocation(['value'=>$redirect_uri]);
                throwException(FILE_CREATE_SUCCESS);
            } else
                echo $response;
        } else {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri";
                $header->respondLocation(['value'=>$url]);
                throwException(FILE_CREATE_ERROR);
            } else
                throwException(FILE_CREATE_ERROR);
        }
    }
}


function create(){
    $owner_user_id = checkAuth();
    if (!empty($_FILES)) {
        $validator = Validator::getInstance();
        $file_data = $validator->ValidateAllByMask($_FILES['userfile'], 'fileUploadMask');
        if ($file_data === false) {
            throwException(DATA_FORMAT_ERROR);
        }
        if (!checkMime($_FILES['userfile']['tmp_name'], end(explode('.', $_FILES['userfile']['name'])))) {
            throwException(WRONG_FILE_TYPE);
        }
        $size = filesize($_FILES['userfile']['tmp_name']);
        if (($file_data["size"] > MAX_FILE_UPLOAD_SIZE) or ($size > MAX_FILE_UPLOAD_SIZE)) {
            throwException(TOO_LARGE_FILE);
        }
        $path = ROOTDIR . "/" . STORAGE;
        $fullpath = "$path/" . $validator->transliterate(unicode_decode($_FILES['userfile']['name']));
        if (upload($_FILES['userfile']['tmp_name'], $fullpath)) {
            $id = md5_file($fullpath);
            $controller = Config::get('controller_url');
            $response = sendRequest("$controller/create?id=$id&file=$fullpath&user=$owner_user_id", 'GET', null, null);
            echo $response;
        } else {
            throwException(FILE_CREATE_ERROR);
        }
    }
}

function getFile($path,$name){
    $validator = Validator::getInstance();
    $link = $validator->Check('Path',$path,[]);
    if ($link === false){
        throwException(DATA_FORMAT_ERROR);
    }
    $name = $validator->Check('fileName',$name,['min'=>1,'max'=>255,'types'=>FILES_ALLOWED_TYPES]);
    if ($name === false){
        throwException(DATA_FORMAT_ERROR);
    }
    readFileWithSpeed($path,$name);
}

function copyFile($server,$path,$file_id){
    $validator = Validator::getInstance();
    $file_id = $validator->Check('Md5Type',$file_id,[]);
    if ($file_id === false){
        throwException(DATA_FORMAT_ERROR);
    }
    $server = $validator->Check('Path',$server,[]);
    if ($server === false){
        throwException(DATA_FORMAT_ERROR);
    }
    $path = $validator->Check('Path',$path,[]);
    if ($path === false){
        throwException(DATA_FORMAT_ERROR);
    }
    $name = @end(explode('/',$path));
    $created_at = date('Y-m-d H:i:s');
    $file_name = generateFileName($file_id,$created_at,$name);
    $response = SendFile($server."/createFile?file_name=$file_name",$path,$name);
    $file_path = $response;
    unlink($path);
    echo $file_path;
    return;
}

function deleteFile($path){
    $validator = Validator::getInstance();
    $path = $validator->Check('Path',$path,[]);
    if ($path === false){
        throwException(DATA_FORMAT_ERROR);
    }
    if (!unlink($path)){
        throwException(DELETE_FILE_ERROR);
    }
    return;
}

function serverStatus(){
    echo disk_free_space(STORAGE);
}

function testFileForm(){
    $server = Config::get('host_url');
    echo "
<html>
    <body>
        <form action='http://$server/create' method='post' enctype=\"multipart/form-data\" accept-charset='UTF-8'>
            <input type='file' name='userfile'>
            <input type='submit'>
        </form>
    </body>
</html>
";
    return;
}


