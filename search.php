<?php
session_start();
require_once 'includes/config/dbcon.php';

if (isset($_GET['query'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['query']);
    
    $sql = "SELECT 
                p.*,
                u.username as author_name,
                c.name as category_name,
                COUNT(DISTINCT cm.id) as comments_count,
                COALESCE(SUM(CASE WHEN v.vote_type = 'upvote' THEN 1 ELSE 0 END), 0) as upvotes,
                COALESCE(SUM(CASE WHEN v.vote_type = 'downvote' THEN 1 ELSE 0 END), 0) as downvotes
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN comments cm ON p.id = cm.post_id
            LEFT JOIN postvotes v ON p.id = v.post_id
            WHERE 
                p.title LIKE ? OR 
                p.description LIKE ? OR 
                p.tags LIKE ? OR
                c.name LIKE ?
            GROUP BY p.id
            ORDER BY p.created_at DESC";
    
    $search_term = "%$search_query%";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $posts = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($posts);
    exit();
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(['error' => 'No search query provided']); 