<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "barangay_clearance";

$conn = new mysqli($servername, $username, $password, $dbname);

// Set UTF-8 encoding for special characters
$conn->set_charset("utf8mb4");

// Check the connection
if ($conn->connect_error) {
    exit(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
?>
