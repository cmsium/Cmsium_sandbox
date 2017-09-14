<?php


function create(){
    $owner_user_id = checkAuth();
    //$owner_user_id = 'eeec1e618690fba21fd416df610da961';
    echo json_encode(["status" => "error", "message" => "name: {$_FILES['userfile']['name']}"]);
    exit;
    if (!empty($_FILES)) {
        $validator = Validator::getInstance();
        $file_data = $validator->ValidateAllByMask($_FILES['userfile'], 'fileUploadMask');
        if ($file_data === false) {
            echo json_encode(["status" => "error", "message" => "Wrong file format"]);
            return;
        }
        if (!checkMime($_FILES['userfile']['tmp_name'])) {
            echo json_encode(["status" => "error", "message" => "Wrong file type"]);
            return;
        }
        $size = filesize($_FILES['userfile']['tmp_name']);
        if (($file_data["size"] > MAX_FILE_UPLOAD_SIZE) or ($size > MAX_FILE_UPLOAD_SIZE)) {
            echo json_encode(["status" => "error", "message" => "File is too large"]);
            return;
        }
        $path = ROOTDIR."/".STORAGE;
        $fullpath = "$path/".urldecode($_FILES['userfile']['name']);
        if (upload($_FILES['userfile']['tmp_name'],$fullpath)) {
            $id = md5_file($fullpath);
            $controller = Config::get('controller_url');
            $response = sendRequest("$controller/create?id=$id&file=$fullpath&user=$owner_user_id",'GET',null,null);
            switch ($response['status']){
                case 'error': echo $response['message'];break;
                case 'ok': echo $response['id'];break;
                default:var_dump($response);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Could not create file"]);
        }
    }
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
    }
    unlink($path);
    echo  json_encode(["status" => "ok", "file_path" => $file_path]);
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
        <form action='http://$server/create' method='post' enctype=\"multipart/form-data\">
            <input type='file' name='userfile'>
            <input type='submit'>
        </form>
    </body>
</html>
";
    return;
}


