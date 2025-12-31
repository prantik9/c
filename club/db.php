<?php
// Basic mysqli connection - update with your DB credentials
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'club';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('DB connection error: ' . $conn->connect_error);
}
else {
    mysqli_select_db($conn, $dbname);
    
}
