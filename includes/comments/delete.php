<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
require '../dbconc.php'; // Include your database connection
include_once '../objects/comment.php'; // Include the Comment object

// Initialize objects
$comment = new Comment($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (empty($data->comment_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "Missing required fields: comment_id and user_id are required."));
    exit;
}

// Set comment properties
$comment->comment_id = $data->comment_id;
$comment->user_id = $data->user_id;

// Verify ownership before deletion
if (!$comment->verifyOwner()) {
    http_response_code(403); // Forbidden
    echo json_encode(array("message" => "You do not have permission to delete this comment."));
    exit;
}

// Delete the comment
if ($comment->delete()) {
    http_response_code(200);
    echo json_encode(array("message" => "Comment deleted successfully."));
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete comment."));
}
?>
