<?php
session_start();
require_once 'includes/config/dbcon.php';

// Time ago function
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle username update
if (isset($_POST['update_username'])) {
    $new_username = mysqli_real_escape_string($conn, $_POST['new_username']);
    
    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $new_username, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $error = "Username already exists";
    } else {
        // Update username
        $update_query = "UPDATE users SET username = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_username, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $new_username;
            $success = "Username updated successfully";
        } else {
            $error = "Error updating username";
        }
    }
}

// Fetch current user data
$query = "SELECT username, email, role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch user's posts
$posts_query = "SELECT p.*, 
                COUNT(DISTINCT c.id) as comments_count,
                COALESCE(SUM(CASE WHEN v.vote_type = 1 THEN 1 ELSE 0 END), 0) as upvotes,
                COALESCE(SUM(CASE WHEN v.vote_type = -1 THEN 1 ELSE 0 END), 0) as downvotes,
                cat.name as category_name
                FROM posts p
                LEFT JOIN comments c ON p.id = c.post_id
                LEFT JOIN postvotes v ON p.id = v.post_id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE p.user_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC";
$stmt = mysqli_prepare($conn, $posts_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_posts = mysqli_stmt_get_result($stmt);

// Fetch user's voting history
$votes_query = "SELECT v.*, p.title as post_title, p.id as post_id
                FROM postvotes v
                JOIN posts p ON v.post_id = p.id
                WHERE v.user_id = ?
                ORDER BY v.created_at DESC";
$stmt = mysqli_prepare($conn, $votes_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_votes = mysqli_stmt_get_result($stmt);

// Fetch user's comments
$comments_query = "SELECT c.*, p.title as post_title, p.id as post_id
                  FROM comments c
                  JOIN posts p ON c.post_id = p.id
                  WHERE c.user_id = ?
                  ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $comments_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_comments = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - IDS OverFlow</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div id="particles-js"></div>

    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">
                    <img src="img/IDS.png" alt="IDSFlow Logo" class="logo-image" />
                    <span class="logo-text">
                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'IDS Overflow'; ?>
                    </span>
                </a>
            </div>

            <div class="nav-buttons">
                <span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </nav>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul>
                <li>
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                    <li>
                        <a href="users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="content">
            <div class="content-header">
                <h1>User Profile</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="profile-container">
                <div class="profile-section">
                    <h2>Update Username</h2>
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="new_username">New Username</label>
                            <input type="text" 
                                   id="new_username" 
                                   name="new_username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required>
                        </div>
                        <button type="submit" name="update_username" class="update-btn">
                            Update Username
                        </button>
                    </form>
                </div>

                <div class="profile-info">
                    <h2>Account Information</h2>
                    <div class="info-item">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Role:</span>
                        <span class="value">
                            <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </span>
                    </div>
                </div>

                <!-- User's Posts Section -->
                <div class="user-activity-section">
                    <h2>Your Posts</h2>
                    <div class="activity-list">
                        <?php if (mysqli_num_rows($user_posts) > 0): ?>
                            <?php while ($post = mysqli_fetch_assoc($user_posts)): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <h3>
                                            <a href="postReading.php?id=<?php echo $post['id']; ?>">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h3>
                                        <div class="post-meta">
                                            <div class="category-tag">
                                                <i class="fas fa-folder"></i>
                                                <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                                            </div>
                                            <div class="stats">
                                                <span><i class="fas fa-arrow-up"></i> <?php echo $post['upvotes']; ?></span>
                                                <span><i class="fas fa-arrow-down"></i> <?php echo $post['downvotes']; ?></span>
                                                <span><i class="fas fa-comment"></i> <?php echo $post['comments_count']; ?></span>
                                            </div>
                                            <span class="timestamp"><?php echo getTimeAgo($post['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-activity">You haven't created any posts yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User's Voting History -->
                <div class="user-activity-section">
                    <h2>Your Voting History</h2>
                    <div class="activity-list">
                        <?php if (mysqli_num_rows($user_votes) > 0): ?>
                            <?php while ($vote = mysqli_fetch_assoc($user_votes)): ?>
                                <div class="activity-item">
                                    <div class="vote-type <?php echo $vote['vote_type'] == 1 ? 'upvote' : 'downvote'; ?>">
                                        <i class="fas fa-arrow-<?php echo $vote['vote_type'] == 1 ? 'up' : 'down'; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <a href="postReading.php?id=<?php echo $vote['post_id']; ?>">
                                            <?php echo htmlspecialchars($vote['post_title']); ?>
                                        </a>
                                        <span class="timestamp"><?php echo getTimeAgo($vote['created_at']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-activity">You haven't voted on any posts yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User's Comments -->
                <div class="user-activity-section">
                    <h2>Your Comments</h2>
                    <div class="activity-list">
                        <?php if (mysqli_num_rows($user_comments) > 0): ?>
                            <?php while ($comment = mysqli_fetch_assoc($user_comments)): ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <div class="comment-text">
                                            <?php echo htmlspecialchars($comment['content']); ?>
                                        </div>
                                        <div class="comment-meta">
                                            <a href="postReading.php?id=<?php echo $comment['post_id']; ?>">
                                                on: <?php echo htmlspecialchars($comment['post_title']); ?>
                                            </a>
                                            <span class="timestamp"><?php echo getTimeAgo($comment['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-activity">You haven't commented on any posts yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS.load("particles-js", "particles-config.json");
    </script>

    <style>
        .profile-container {
            background: rgba(10, 10, 10, 0.8);
            border-radius: 12px;
            padding: 30px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.1);
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .profile-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .profile-form .form-group {
            margin-bottom: 20px;
        }

        .profile-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-size: 0.95em;
        }

        .profile-form input {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--dark-text);
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .profile-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(255, 0, 255, 0.2);
            outline: none;
        }

        .update-btn {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            background: var(--primary-color);
            color: var(--dark-bg);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }

        .profile-info {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 0, 255, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item .label {
            color: var(--secondary-color);
            font-size: 0.95em;
        }

        .info-item .value {
            color: var(--dark-text);
            font-size: 0.95em;
        }

        /* Activity Sections */
        .user-activity-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .user-activity-section h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(255, 0, 255, 0.2);
            border-color: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h3 {
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        .activity-content a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .activity-content a:hover {
            color: var(--primary-color);
            text-shadow: 0 0 10px var(--primary-color);
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.9em;
            color: var(--text-secondary);
        }

        .stats {
            display: flex;
            gap: 15px;
        }

        .stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .vote-type {
            padding: 8px;
            border-radius: 4px;
            width: 30px;
            text-align: center;
        }

        .vote-type.upvote {
            color: var(--success-color);
        }

        .vote-type.downvote {
            color: var(--danger-color);
        }

        .timestamp {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .comment-text {
            color: var(--dark-text);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .comment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
        }

        .no-activity {
            text-align: center;
            color: var(--text-secondary);
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            font-style: italic;
        }

        /* Error and Success Messages */
        .error-message,
        .success-message {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 0.95em;
            animation: fadeIn 0.3s ease-out;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .success-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html> 