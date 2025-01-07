<?php
session_start();
require_once 'includes/config/dbcon.php';
require_once 'includes/objects/post.php';
require_once 'includes/posts/function.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$post_id) {
    header('Location: index.php');
    exit();
}

try {
    // Get full post details
    $post = Post::readOne($post_id);
    
    if (!$post) {
        throw new Exception("Post not found");
    }

    // Check if current user is post owner using verifyOwner method
    $isOwner = isset($_SESSION['user_id']) && Post::verifyOwner($post_id, $_SESSION['user_id']);

    // Handle comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $query = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iis", $post_id, $_SESSION['user_id'], $comment);
            mysqli_stmt_execute($stmt);
            header("Location: postReading.php?id=" . $post_id);
            exit();
        }
    }

    // Handle post deletion
    if (isset($_POST['delete_post']) && $isOwner) {
        $delete_query = "DELETE FROM posts WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: index.php?success=Post deleted');
            exit();
        }
    }

    // Fetch comments
    $comments_query = "SELECT c.*, u.username 
                      FROM comments c 
                      LEFT JOIN users u ON c.user_id = u.id 
                      WHERE c.post_id = ? 
                      ORDER BY c.created_at DESC";
    $stmt = mysqli_prepare($conn, $comments_query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $comments_result = mysqli_stmt_get_result($stmt);

} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - IDS OverFlow</title>
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
                        <?php
                        if(isset($_SESSION['username'])) {
                            $username = $_SESSION['username'];
                            $query = mysqli_query($conn, "SELECT users.* FROM users WHERE users.username = '$username'");
                            while($row = mysqli_fetch_array($query)) {
                                echo $row['username'];
                            }
                        }
                        else {
                            echo "IDS Overflow";
                        }
                        ?>
                    </span>
                </a>
            </div>

            

            <div class="nav-buttons">
                <?php if(isset($_SESSION['username'])): ?>
                    <span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <button class="signup-btn" onclick="window.location.href='signup.php'">Sign up</button>
                    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
                <?php else: ?>
                    <button class="login-btn" onclick="window.location.href='login.php'">Log in</button>
                    <button class="signup-btn" onclick="window.location.href='signup.php'">Sign up</button>
                <?php endif; ?>
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
                <li class="active">
                    <a href="#"><i class="fas fa-globe"></i> Questions</a>
                </li>
                <li>
                    <a href="#"><i class="fas fa-tag"></i> Tags</a>
                </li>
                <li>
                    <a href="#"><i class="fas fa-user"></i> Users</a>
                </li>
                <li>
                    <a href="#"><i class="fas fa-trophy"></i> Badges</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="content">
            <div class="post-container">
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="post-meta">
                    <div class="post-info">
                        Asked <?php echo getTimeAgo($post['created_at']); ?>
                        by <a href="#"><?php echo htmlspecialchars($post['username'] ?? 'Anonymous'); ?></a>
                    </div>
                </div>

                <div class="post-body">
                    <div class="vote-controls">
                        <button class="vote-btn up"><i class="fas fa-caret-up"></i></button>
                        <span class="vote-count"><?php echo ($post['upvotes'] ?? 0) - ($post['downvotes'] ?? 0); ?></span>
                        <button class="vote-btn down"><i class="fas fa-caret-down"></i></button>
                    </div>
                </div>

                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                </div>

                <div class="post-footer">
                    <div class="post-tags">
                        <?php 
                        if (!empty($post['tags'])) {
                            $tags = explode(',', $post['tags']);
                            foreach ($tags as $tag) {
                                echo '<a href="#" class="tag">' . htmlspecialchars(trim($tag)) . '</a>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="post-actions">
                        <?php if ($isOwner): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <button type="submit" name="delete_post" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        <?php endif; ?>
                        <button class="share-btn">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="comments-section">
                <h2>Comments</h2>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" class="comment-form">
                        <textarea 
                            name="comment" 
                            placeholder="Write your comment here..." 
                            required
                        ></textarea>
                        <button type="submit">Post Comment</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt">Please <a href="login.php">log in</a> to post comments.</p>
                <?php endif; ?>

                <div class="comments-list">
                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                        <div class="comment">
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            <div class="comment-meta">
                                <span class="comment-author">
                                    <?php echo htmlspecialchars($comment['username'] ?? 'Anonymous'); ?>
                                </span>
                                <span class="comment-date">
                                    <?php echo getTimeAgo($comment['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS.load("particles-js", "particles-config.json");
    </script>
</body>
</html>
