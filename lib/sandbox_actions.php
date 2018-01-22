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
                $url = "$redirect_uri?message=Wrong file format: {$_FILES['userfile']['name']}";
                $header->respondLocation(['value'=>$url]);
            } else
                echo json_encode(["status" => "error", "message" => "Wrong file format: {$_FILES['userfile']['name']}"]);
            return;
        }
        if (!checkMime($_FILES['userfile']['tmp_name'],end(explode('.',$_FILES['userfile']['name'])))) {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri?message=Wrong file type";
                $header->respondLocation(['value'=>$url]);
            } else
                echo json_encode(["status" => "error", "message" => "Wrong file type"]);
            return;
        }
        $size = filesize($_FILES['userfile']['tmp_name']);
        if (($file_data["size"] > MAX_FILE_UPLOAD_SIZE) or ($size > MAX_FILE_UPLOAD_SIZE)) {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri?message=File is too large";
                $header->respondLocation(['value'=>$url]);
            } else
                echo json_encode(["status" => "error", "message" => "File is too large"]);
            return;
        }
        $path = ROOTDIR."/".STORAGE;
        $fullpath = "$path/".$validator->transliterate(unicode_decode($_FILES['userfile']['name']));
        if (upload($_FILES['userfile']['tmp_name'],$fullpath)) {
            $id = md5_file($fullpath);
            $controller = Config::get('controller_url');
            $response = sendRequest("$controller/registerSandboxFile?id=$id&file=$fullpath&user=$owner_user_id",'GET',null,null);
            switch ($response['status']){
                case 'error':
                    if ($redirect_uri){
                        $header = HeadersController::getInstance();
                        $url = "$redirect_uri?message={$response['message']}";
                        $header->respondLocation(['value'=>$url]);
                    } else
                        echo json_encode(["status" => "error", "message" => $response['message']]);break;
                case 'ok':
                    if ($redirect_uri){
                        $header = HeadersController::getInstance();
                        $url = "$redirect_uri?message=File was created";
                        $header->respondLocation(['value'=>$url]);
                    } else
                        echo json_encode(["status" => "ok", "id" => $response['id']]);break;
                default:
                    if ($redirect_uri){
                        $header = HeadersController::getInstance();
                        $url = "$redirect_uri?message=$response";
                        $header->respondLocation(['value'=>$url]);
                    } else
                        var_dump($response);
            }
        } else {
            if ($redirect_uri){
                $header = HeadersController::getInstance();
                $url = "$redirect_uri?message=Could not create file";
                $header->respondLocation(['value'=>$url]);
            } else
                echo json_encode(["status" => "error", "message" => "Could not create file"]);
        }
    }
}


function create(){
    $owner_user_id = checkAuth();
    if (!empty($_FILES)) {
        $validator = Validator::getInstance();
        $file_data = $validator->ValidateAllByMask($_FILES['userfile'], 'fileUploadMask');
        if ($file_data === false) {
            echo json_encode(["status" => "error", "message" => "Wrong file format: {$_FILES['userfile']['name']}"]);
            return;
        }
        if (!checkMime($_FILES['userfile']['tmp_name'],end(explode('.',$_FILES['userfile']['name'])))) {
            echo json_encode(["status" => "error", "message" => "Wrong file type"]);
            return;
        }
        $size = filesize($_FILES['userfile']['tmp_name']);
        if (($file_data["size"] > MAX_FILE_UPLOAD_SIZE) or ($size > MAX_FILE_UPLOAD_SIZE)) {
            echo json_encode(["status" => "error", "message" => "File is too large"]);
            return;
        }
        $path = ROOTDIR."/".STORAGE;
        $fullpath = "$path/".$validator->transliterate(unicode_decode($_FILES['userfile']['name']));
        if (upload($_FILES['userfile']['tmp_name'],$fullpath)) {
            $id = md5_file($fullpath);
            $controller = Config::get('controller_url');
            $response = sendRequest("$controller/create?id=$id&file=$fullpath&user=$owner_user_id",'GET',null,null);
            switch ($response['status']){
                case 'error': echo json_encode(["status" => "error", "message" => $response['message']]);break;
                case 'ok': echo json_encode(["status" => "ok", "id" => $response['id']]);break;
                default:var_dump($response);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Could not create file"]);
        }
    }
}

function getFile($path,$name){
    $validator = Validator::getInstance();
    $link = $validator->Check('Path',$path,[]);
    if ($link === false){
        echo json_encode(["status" => "error", "message" => "Wrong file path format"]);
        return;
    }
    $name = $validator->Check('fileName',$name,['min'=>1,'max'=>255,'types'=>FILES_ALLOWED_TYPES]);
    if ($name === false){
        echo json_encode(["status" => "error", "message" => "Wrong file name"]);
        return;
    }
    readFileWithSpeed($path,$name);
}

function copyFile($server,$path,$file_id){
    $validator = Validator::getInstance();
    $file_id = $validator->Check('Md5Type',$file_id,[]);
    if ($file_id === false){
        echo json_encode(["status" => "error", "message" => "Wrong file id format"]);
        exit;
    }
    $server = $validator->Check('Path',$server,[]);
    if ($server === false){
        echo json_encode(["status" => "error", "message" => "Wrong server format"]);
        exit;
    }
    $path = $validator->Check('Path',$path,[]);
    if ($path === false){
        echo json_encode(["status" => "error", "message" => "Wrong file path format"]);
        return;
    }
    $name = @end(explode('/',$path));
    $created_at = date('Y-m-d H:i:s');
    $file_name = generateFileName($file_id,$created_at,$name);
    $response = SendFile($server."/createFile?file_name=$file_name",$path,$name);
    switch ($response['status']){
        case 'error':
            echo json_encode(["status" => "error", "message" => $response['message']]);
            exit;
        case 'ok':
            $file_path = $response['path'];
            break;
        default:
            echo json_encode(["status" => "error", "message" => $path]);
            exit;
    }
    unlink($path);
    echo  json_encode(["status" => "ok", "file_path" => $file_path]);
    return;
}

function deleteFile($path){
    $validator = Validator::getInstance();
    $path = $validator->Check('Path',$path,[]);
    if ($path === false){
        echo json_encode(["status" => "error", "message" => "Wrong path format"]);
        return;
    }
    if (!unlink($path)){
        echo json_encode(["status" => "error", "message" => "Delete file error"]);
        return;
    }
    echo json_encode(["status" => "ok"]);
    return;
}

function serverStatus(){
    echo json_encode(["status" => "ok","free_disk_space"=>disk_free_space(STORAGE)]);
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


