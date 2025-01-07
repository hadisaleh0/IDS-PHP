

<!-- Form to Create a Post -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
</head>
<body>
    <form method="POST">
        <label>User ID: <input type="number" name="user_id" required></label><br>
        <label>Category ID: <input type="number" name="category_id" required></label><br>
        <label>Title: <input type="text" name="title" required></label><br>
        <label>Description: <textarea name="description" required></textarea></label><br>
        <label>Tags (optional): <input type="text" name="tags"></label><br>
        <button type="submit">Create Post</button>
    </form>
</body>
</html>




<?php
require'../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $user_id = intval($_POST['user_id']); // The user creating the post
    $category_id = intval($_POST['category_id']); // The category of the post
    $title = $_POST['title']; // Title of the post
    $description = $_POST['description']; // Description of the post
    $tags = $_POST['tags'] ?? null; // Optional tags
    $created_at = date('Y-m-d H:i:s'); // Current timestamp
    $updated_at = $created_at; // Initially same as created_at
    $upvotes = 0; // Default value
    $downvotes = 0; // Default value

    // Insert query for Posts table
    $sql = "
        INSERT INTO Posts (user_id, category_id, title, description, tags, created_at, updated_at, upvotes, downvotes)
        VALUES ('$user_id', '$category_id', '$title', '$description', '$tags', '$created_at', '$updated_at', '$upvotes', '$downvotes')
    ";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["message" => "Post created successfully!"]);
    } else {
        echo json_encode(["error" => mysqli_error($conn)]);
    }
}
?>

