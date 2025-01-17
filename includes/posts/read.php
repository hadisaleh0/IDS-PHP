<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin:*');
header('Content-Type: application/json');
header('Access-Control-Allow-Method: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');

require_once __DIR__ . '/function.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];

if($requestMethod == "GET"){
    try {
        $postList = getPosts();
        if (!$postList) {
            throw new Exception("Failed to get posts");
        }
        echo $postList;
    } catch (Exception $e) {
        echo json_encode([
            'status' => 500,
            'message' => $e->getMessage()
        ]);
    }
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod. ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

// Add this to your post query
$query = "SELECT p.*, 
          (SELECT vote_type FROM postvotes WHERE post_id = p.id AND user_id = ?) as user_vote
          FROM posts p 
          WHERE p.id = ?";

// Bind the user_id parameter if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;