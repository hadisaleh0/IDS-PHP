<?php
include_once 'includes/config/dbcon.php';
include_once 'includes/objects/user.php';


$user = new User($conn);

if(isset($_POST['signUp'])) {

   $user->username = $_POST['username'];
   $user->email = $_POST['email'];
   $user->password = $_POST['password'];
   $user->role = "User";
   $user->reputation_points = 0;

   $checkEmail = "SELECT * FROM users WHERE email = ?";
   $stmt = $conn->prepare($checkEmail);
   $stmt->bind_param("s", $user->email);
   $stmt->execute();
   $result = $stmt->get_result();
   if($result->num_rows > 0) {
    header("Location: signup.php?error=Email already exists");
   }
   else {
      $insertQuery = "INSERT INTO users (username, email, password,role,reputation_points)
                        VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($insertQuery);
      $stmt->bind_param("ssssi", $user->username, $user->email, $user->password, $user->role, $user->reputation_points);
      $stmt->execute();
      if($stmt->affected_rows > 0) {
        header("Location: login.php");
      }
      else {
        echo "Error: " . $conn->error;
      }

   }
}


if(isset($_POST['login'])) {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    $checkEmail = "SELECT * FROM users WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['reputation_points'] = $row['reputation_points'];
        header("Location: index.php");
    }
    else {
        echo "Invalid email or password";
    }

}



?>