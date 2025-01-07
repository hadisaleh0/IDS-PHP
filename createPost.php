<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/config/dbcon.php';
require_once 'includes/objects/post.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $post = new Post($conn);
        
        // Set post properties from form
        $post->user_id = $_SESSION['user_id'];
        $post->title = $_POST['title'] ?? '';
        $post->description = $_POST['description'] ?? '';
        $post->category_id = $_POST['category_id'] ?? 1; // Default category if not specified
        
        // Validate input
        if (empty($post->title) || empty($post->description)) {
            throw new Exception("Title and description are required");
        }
        
        // Create the post
        if ($post->create()) {
            header('Location: index.php?success=Post created successfully');
            exit();
        } else {
            throw new Exception("Failed to create post");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch categories for dropdown
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Question - IDS OverFlow</title>
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
                    <span class="logo-text">IDSFlow</span>
                </a>
            </div>
        </nav>
    </header>

    <div class="container">
        <main class="content">
            <div class="ask-question-container">
                <h1>Ask a Question</h1>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="question-form">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            placeholder="What's your programming question? Be specific."
                            required
                            maxlength="255"
                        />
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Provide details about your problem..."
                            required
                            rows="10"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category_id" required>
                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-question">Post Your Question</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">Cancel</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS.load("particles-js", "particles-config.json");
    </script>
</body>
</html> 