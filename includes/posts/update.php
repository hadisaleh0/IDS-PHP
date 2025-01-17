<?php
// Include the database connection
require'../dbconc.php';

if (!isset($_GET['id'])) {
    echo "Error: User ID is not provided!";
    exit;
}

$post_id = intval($_GET['id']); // Get the user ID from the URL
echo "Post ID: " . $post_id; // Debugging: Show the user ID to confirm it's being passed

// Check if 'id' is passed in the URL (e.g., update.php?id=1)
if (isset($_GET['id'])) {
    $post_id = intval($_GET['id']); // Get the user ID from the URL

    // Prepare the SQL query to fetch user data
    $sql = "SELECT * FROM Posts WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);  // Prepare the SQL statement to prevent SQL injection
    if ($stmt === false) {
        echo "Error preparing SQL statement: " . mysqli_error($conn);
        exit;
    }

    // Bind the user_id parameter to the query
    mysqli_stmt_bind_param($stmt, "i", $post_id); // Bind the user_id parameter to the query

    // Execute the query
    if (!mysqli_stmt_execute($stmt)) {
        echo "Error executing SQL query: " . mysqli_error($conn);
        exit;
    }

    // Get the result of the query
    $result = mysqli_stmt_get_result($stmt);

    // Check if the user exists
    if ($user = mysqli_fetch_assoc($result)) {
        // If user data is found, we can use it in the form
    } else {
        // If no user is found with the provided ID
        echo "User not found!";
        exit;
    }
     // Check if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve the form data
        $title = $_POST['title'];
        $description = $_POST['description'];
        $tags = $_POST['tags'];

        // Prepare the SQL query to update the user data
        $sql = "UPDATE Posts SET title = ?, description = ?, tags = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        // Bind the parameters to the SQL query
        mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $tags, $post_id);

        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            echo "User updated successfully!";
            
        } else {
            echo "Error updating user: " . mysqli_error($conn);
        }
    }
} else {
    // If 'id' is not provided in the URL
    echo "Error: User ID is required!";
    exit;
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Post</title>
</head>
<body>
    <h2>Update Post</h2>
    <form method="POST" action="update.php?id=<?php echo $post_id; ?>">
        <label>Title: <input type="text" name="title" required></label><br>
        <label>Description: <textarea name="description" required></textarea></label><br>
        <label>Tags: <input type="text" name="tags"></label><br>
        <button type="submit">Update Post</button>
    </form>
</body>
</html>
