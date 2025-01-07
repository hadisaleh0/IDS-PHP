<?php
$host = "localhost";
$username = "root";
$password = "hadi313saleh";
$database = "ids_internship";

$conn = mysqli_connect($host, $username, $password, $database);

if(!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?> 