

<form method="GET">
    <label>User ID to delete: <input type="number" name="id" required></label><br>
    <button type="submit">Delete User</button>
</form>


<?php
require'../dbconc.php';


if (!isset($_GET['id'])) {
    echo "Error: User ID is not provided!";
    exit;
}

$user_id = intval($_GET['id']); // Get the user ID from the URL
echo "User ID: " . $user_id;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM Users WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        echo "User deleted successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
