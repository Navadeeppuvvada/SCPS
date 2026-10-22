<?php
// Database connection file for SCPS
$host = "localhost";
$user = "root";        // change if your DB username is different
$pass = "";            // change if your DB password is set
$dbname = "SCPS";      // your database name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>