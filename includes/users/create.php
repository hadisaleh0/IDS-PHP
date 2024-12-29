<form method="POST">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <label>Role: 
        <select name="role" required>
            <option value="User">User</option>
            <option value="Admin">Admin</option>
        </select>
    </label><br>
    <button type="submit">Create User</button>
</form>


<?php
require '../dbconc.php'; // Database connection

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // SQL query to insert the user into the database
    $sql = "INSERT INTO Users (username, email, password, role) VALUES ('$username', '$email', '$password_hash', '$role')";

    // Execute the query and check for errors
    if (mysqli_query($conn, $sql)) {
        echo "User created successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>


