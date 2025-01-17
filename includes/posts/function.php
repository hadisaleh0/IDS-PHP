<?php
require_once __DIR__ . '/../config/dbcon.php';

function getPosts() {
    global $conn;

    // First, test the connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT 
                p.*,
                u.username as author_name,
                c.name as category_name,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                (SELECT COUNT(CASE WHEN vote_type = 1 THEN 1 END) FROM postvotes WHERE post_id = p.id) as upvotes,
                (SELECT COUNT(CASE WHEN vote_type = -1 THEN 1 END) FROM postvotes WHERE post_id = p.id) as downvotes
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.created_at DESC";

    try {
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }

        $posts = array();
        while($row = mysqli_fetch_assoc($result)) {
            // Calculate total votes
            $row['total_votes'] = ($row['upvotes'] ?? 0) - ($row['downvotes'] ?? 0);
            $posts[] = $row;
        }

        if (empty($posts)) {
            return json_encode([
                'status' => 404,
                'message' => 'No posts found in database'
            ]);
        }
        
        return json_encode($posts);

    } catch (Exception $e) {
        return json_encode([
            'status' => 500,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . " month" . ($months > 1 ? "s" : "") . " ago";
    } else {
        $years = floor($diff / 31536000);
        return $years . " year" . ($years > 1 ? "s" : "") . " ago";
    }
}