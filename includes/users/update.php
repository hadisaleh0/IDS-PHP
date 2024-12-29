



<?php
// Include the database connection
require'../dbconc.php';

if (!isset($_GET['id'])) {
    echo "Error: User ID is not provided!";
    exit;
}

$user_id = intval($_GET['id']); // Get the user ID from the URL
echo "User ID: " . $user_id; // Debugging: Show the user ID to confirm it's being passed

// Check if 'id' is passed in the URL (e.g., update.php?id=1)
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Get the user ID from the URL

    // Prepare the SQL query to fetch user data
    $sql = "SELECT * FROM Users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);  // Prepare the SQL statement to prevent SQL injection
    if ($stmt === false) {
        echo "Error preparing SQL statement: " . mysqli_error($conn);
        exit;
    }

    // Bind the user_id parameter to the query
    mysqli_stmt_bind_param($stmt, "i", $user_id); // Bind the user_id parameter to the query

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
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Prepare the SQL query to update the user data
        $sql = "UPDATE Users SET username = ?, email = ?, role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        // Bind the parameters to the SQL query
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $role, $user_id);

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




<!-- HTML Form to Update User -->
<form method="POST" action="update.php?id=<?php echo $user_id; ?>">
    <label>Username: <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></label><br>
    <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></label><br>
    <label>Role: 
        <select name="role" required>
            <option value="User" <?php if ($user['role'] === 'User') echo 'selected'; ?>>User</option>
            <option value="Admin" <?php if ($user['role'] === 'Admin') echo 'selected'; ?>>Admin</option>
        </select>
    </label><br>
    <button type="submit">Update User</button>
</form>
