<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');

require '../config/dbcon.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Please login to vote.'
    ]);
    exit();
}

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id) && isset($data->vote_type)) {
    $post_id = $data->post_id;
    $user_id = $_SESSION['user_id'];
    $vote_type = $data->vote_type;

    // Validate vote type
    if (!in_array($vote_type, [1, -1, 0])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid vote type'
        ]);
        exit();
    }

    try {
        if ($vote_type === 0) {
            // Remove vote
            $query = "DELETE FROM postvotes WHERE post_id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
            mysqli_stmt_execute($stmt);
        } else {
            // Add or update vote
            $query = "INSERT INTO postvotes (post_id, user_id, vote_type) 
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE vote_type = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiii", $post_id, $user_id, $vote_type, $vote_type);
            mysqli_stmt_execute($stmt);
        }

        // Get updated vote counts
        $query = "SELECT 
                    (SELECT COUNT(*) FROM postvotes WHERE post_id = ? AND vote_type = 1) as upvotes,
                    (SELECT COUNT(*) FROM postvotes WHERE post_id = ? AND vote_type = -1) as downvotes";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $post_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $votes = mysqli_fetch_assoc($result);

        $total_votes = ($votes['upvotes'] ?? 0) - ($votes['downvotes'] ?? 0);

        echo json_encode([
            'status' => 'success',
            'message' => 'Vote recorded successfully',
            'votes' => [
                'upvotes' => (int)$votes['upvotes'],
                'downvotes' => (int)$votes['downvotes'],
                'total' => $total_votes
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required data'
    ]);
}
?> 