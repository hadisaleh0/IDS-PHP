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
    // Prepare the query with vote information
    $query = "SELECT p.*, u.username, c.name as category_name,
              (SELECT COUNT(*) FROM postvotes WHERE post_id = p.id AND vote_type = 1) as upvotes,
              (SELECT COUNT(*) FROM postvotes WHERE post_id = p.id AND vote_type = -1) as downvotes,
              (SELECT vote_type FROM postvotes WHERE post_id = p.id AND user_id = ?) as user_vote
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $post = mysqli_fetch_assoc($result);
    
    if (!$post) {
        throw new Exception("Post not found");
    }

    // Check if current user is post owner
    $isOwner = isset($_SESSION['user_id']) && $post['user_id'] == $_SESSION['user_id'];

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
    if (isset($_POST['delete_post']) && isset($_SESSION['user_id'])) {
        if ($isOwner || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
            $delete_query = "DELETE FROM posts WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "i", $post_id);
            if (mysqli_stmt_execute($stmt)) {
                header('Location: index.php?success=Post deleted');
                exit();
            }
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
    <style>
        .post-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .category-tag {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 0, 255, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            color: var(--primary-color);
            font-size: 0.9em;
        }

        .category-tag i {
            margin-right: 5px;
            font-size: 0.9em;
        }
    </style>
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
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
              <li>
                  <a href="users.php"><i class="fas fa-user"></i> Users</a>
              </li>
              <?php endif; ?>
              <?php if(isset($_SESSION['user_id'])): ?>
              <li>
                  <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
              </li>
          <?php endif; ?>
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
                    <div class="category-tag">
                        <i class="fas fa-folder"></i>
                        <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                </div>

                <div class="post-body">
                    <div class="voting-section">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="vote-buttons">
                                <button class="vote-btn upvote <?php echo (int)$post['user_vote'] === 1 ? 'active' : ''; ?>" 
                                        data-post-id="<?php echo htmlspecialchars($post['id']); ?>" 
                                        data-vote-type="1">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <span class="votes-count <?php echo ((int)$post['upvotes'] - (int)$post['downvotes']) >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo ((int)$post['upvotes'] - (int)$post['downvotes']); ?>
                                </span>
                                <button class="vote-btn downvote <?php echo (int)$post['user_vote'] === -1 ? 'active' : ''; ?>" 
                                        data-post-id="<?php echo htmlspecialchars($post['id']); ?>" 
                                        data-vote-type="-1">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="vote-buttons disabled">
                                <button class="vote-btn" onclick="window.location.href='login.php'" title="Login to vote">
                                    <i class="fas fa-thumbs-up"></i>
                                </button>
                                <span class="votes-count <?php echo ((int)$post['upvotes'] - (int)$post['downvotes']) >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo ((int)$post['upvotes'] - (int)$post['downvotes']); ?>
                                </span>
                                <button class="vote-btn" onclick="window.location.href='login.php'" title="Login to vote">
                                    <i class="fas fa-thumbs-down"></i>
                                </button>
                            </div>
                        <?php endif; ?>
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
                        <?php if(isset($_SESSION['user_id']) && ($isOwner || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']))): ?>
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
                                <?php if(isset($_SESSION['user_id']) && ($comment['user_id'] == $_SESSION['user_id'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']))): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" name="delete_comment" class="delete-btn">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const voteButtons = document.querySelectorAll('.vote-btn:not(.disabled)');
        
        voteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const postId = this.dataset.postId;
                const voteType = parseInt(this.dataset.voteType);
                const isActive = this.classList.contains('active');
                
                // If already voted the same way, remove vote
                const finalVoteType = isActive ? 0 : voteType;
                
                fetch('includes/posts/vote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        vote_type: finalVoteType
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        // Update vote count
                        const votesCount = this.parentElement.querySelector('.votes-count');
                        votesCount.textContent = data.votes.total;
                        
                        // Update button states
                        const upvoteBtn = this.parentElement.querySelector('.upvote');
                        const downvoteBtn = this.parentElement.querySelector('.downvote');
                        
                        upvoteBtn.classList.remove('active');
                        downvoteBtn.classList.remove('active');
                        
                        if (finalVoteType === 1) {
                            upvoteBtn.classList.add('active');
                        } else if (finalVoteType === -1) {
                            downvoteBtn.classList.add('active');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    });
    </script>

    <?php
    if (isset($_POST['delete_comment']) && isset($_SESSION['user_id'])) {
        $comment_id = $_POST['comment_id'];
        
        // First check if user is admin or comment owner
        $check_query = "SELECT user_id FROM comments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $comment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comment = mysqli_fetch_assoc($result);
        
        if ($comment && ($comment['user_id'] == $_SESSION['user_id'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']))) {
            $delete_query = "DELETE FROM comments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "i", $comment_id);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: postReading.php?id=" . $post_id . "&success=Comment deleted");
                exit();
            }
        }
    }
    ?>
</body>
</html>
