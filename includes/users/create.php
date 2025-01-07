<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user object files
require '../config/dbcon.php';
include_once '../objects/user.php';

// Initialize database and user object
$user = new User($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(
    !empty($data->username) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    // Set user property values
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = password_hash($data->password, PASSWORD_DEFAULT);

    if($user->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "User was created."));
    }
    else {
        http_response_code(503);
        echo json_encode(array(
            "message" => "Unable to create user.",
            "error" => error_get_last()
        ));
    }
}
else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create user. Data is incomplete."));
}
?>


