<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user object files
require '../config/dbcon.php';
include_once '../objects/user.php';

// Initialize user object
$user = new User($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure user_id is not empty
if(!empty($data->user_id)) {
    // Set user id to delete
    $user->user_id = $data->user_id;

    // Delete the user
    if($user->delete()) {
        http_response_code(200);
        echo json_encode(array("message" => "User was deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to delete user."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete user. No user ID provided."));
}
?>
