<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "subsync_db";
$port = 3306; // change to 3307 if your XAMPP MySQL uses that port
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
