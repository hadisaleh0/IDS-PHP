<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
require '../config/dbcon.php'; // Include your database connection
include_once '../objects/post.php'; // Include the Comment object

// Initialize objects
$post = new Post($conn);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (empty($data->post_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(array("message" => "Missing required fields: post_id and user_id are required."));
    exit;
}

// Set comment properties
$post->post_id = $data->post_id;
$post->user_id = $data->user_id;

// Verify ownership before deletion
if (!$post->verifyOwner($post->post_id, $post->user_id)) {
    http_response_code(403); // Forbidden
    echo json_encode(array("message" => "You do not have permission to delete this post."));
    exit;
}

// Delete the comment
if ($post->delete()) {
    http_response_code(200);
    echo json_encode(array("message" => "Post deleted successfully."));
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete post."));
}
?>
