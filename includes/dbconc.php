<?php

$host = 'localhost';
$username = "root";
$password = "hadi313saleh";
$dbname = "ids_internship";

$conn = mysqli_connect($host, $username, $password, $dbname);


if(!$conn){

    die("Connection Failed: ". mysqli_connect_error());

}

