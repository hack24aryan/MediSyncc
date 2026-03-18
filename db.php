<?php
// db.php
$host = "localhost";
$username = "Username"; // Default XAMPP username
$password = "Password";     // Default XAMPP password is empty
$dbname = "medisyncc_db";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}
?>