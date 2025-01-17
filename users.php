<?php
session_start();
require_once 'includes/config/dbcon.php';

// Check if user is admin, if not redirect to home
if(isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // Don't allow admin to delete themselves
    if ($user_id != $_SESSION['user_id']) {
        // Delete user's posts
        $delete_posts = "DELETE FROM posts WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_posts);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Delete user's comments
        $delete_comments = "DELETE FROM comments WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_comments);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Delete user's votes
        $delete_votes = "DELETE FROM postvotes WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_votes);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Finally delete the user
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_user);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        header('Location: users.php?success=User deleted successfully');
        exit();
    }
}

// Fetch all users
$query = "SELECT id, username, email, role FROM users ORDER BY username ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - IDS OverFlow</title>
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
                <?php if(isset($_SESSION['username'])): ?>
                    <span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
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
                <li>
                    <a href="#"><i class="fas fa-globe"></i> Questions</a>
                </li>
                <li>
                    <a href="#"><i class="fas fa-tag"></i> Tags</a>
                </li>
                <li class="active">
                    <a href="users.php"><i class="fas fa-user"></i> Users</a>
                </li>
                <li>
                    <a href="#"><i class="fas fa-trophy"></i> Badges</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="content">
            <div class="content-header">
                <h1>Users Management</h1>
            </div>
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="users-list">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their posts, comments, and votes.');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="delete-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS.load("particles-js", "particles-config.json");

        function editUser(userId) {
            // Implement edit functionality
            console.log('Edit user:', userId);
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                // Implement delete functionality
                console.log('Delete user:', userId);
            }
        }
    </script>

    <style>
        /* Add these styles to your existing CSS file */
        .users-list {
            background: rgba(10, 10, 10, 0.8);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.1);
        }

        .users-table {
            width: 100%;
            background: transparent;
            border-radius: 8px;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        .users-table th {
            background: rgba(0, 0, 0, 0.4);
            color: var(--primary-color);
            font-weight: 600;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 0, 255, 0.1);
            color: var(--dark-text);
            font-size: 0.95em;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge.admin {
            background: rgba(255, 0, 255, 0.15);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            box-shadow: 0 0 10px rgba(255, 0, 255, 0.2);
        }

        .role-badge.user {
            background: rgba(0, 255, 255, 0.15);
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .delete-btn {
            background: transparent;
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9em;
        }

        .delete-btn:hover {
            background: var(--danger-color);
            color: var(--dark-bg);
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .delete-btn i {
            font-size: 1.1em;
        }

        /* Success message styling */
        .success-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 0.95em;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
            animation: fadeIn 0.3s ease-out;
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