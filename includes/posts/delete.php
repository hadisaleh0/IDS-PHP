<form method="GET">
    <label>Post ID to delete: <input type="number" name="id" required></label><br>
    <button type="submit">Delete Post</button>
</form>


<?php
require'../dbconc.php';


if (!isset($_GET['id'])) {
    echo "Error: Post ID is not provided!";
    exit;
}

$post_id = intval($_GET['id']); // Get the user ID from the URL
echo "Post ID: " . $post_id;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM Posts WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        echo "Post deleted successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>