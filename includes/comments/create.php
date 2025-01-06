<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include the database connection

include_once '../objects/comment.php';
require '../dbconc.php'; // Ensure this file sets up $conn

// Get posted data
$data = json_decode(file_get_contents("php://input"));

try {
    // Validate input data
    if (empty($data->post_id) || empty($data->user_id) || empty($data->content)) {
        throw new Exception("Missing required fields: post_id, user_id, or content.");
    }

    // Sanitize input
    $post_id = mysqli_real_escape_string($conn, $data->post_id);
    $user_id = mysqli_real_escape_string($conn, $data->user_id);
    $content = mysqli_real_escape_string($conn, $data->content);

    // Insert comment into the database
    $query = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES ('$post_id', '$user_id', '$content', NOW())";
    if (mysqli_query($conn, $query)) {
        // Respond with success
        http_response_code(201);
        echo json_encode(array("message" => "Comment was created successfully."));
    } else {
        // Handle query failure
        throw new Exception("Failed to create comment: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(array("message" => $e->getMessage()));
}
?>



