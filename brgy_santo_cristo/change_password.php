<?php
session_start();
require 'db_connect.php';

// Set JSON response header
header("Content-Type: application/json");

// Clear any unwanted output before sending JSON
ob_start();
ob_clean();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in to change your password."]);
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(["status" => "error", "message" => "New passwords do not match."]);
        exit;
    }

    // Fetch the current password from the database
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result(); 
    $stmt->bind_result($hashed_password);

    if ($stmt->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User not found."]);
        $stmt->close();
        exit;
    }

    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        error_log("Stored Hash: " . $hashed_password);
        error_log("User Input: " . $current_password);

        echo json_encode(["status" => "error", "message" => "Current password is incorrect."]);
        exit;
    }

    // Hash the new password
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("si", $hashed_new_password, $user_id);

    if ($stmt->execute()) {
        error_log("Password updated successfully for User ID: " . $user_id);
        echo json_encode(["status" => "success", "message" => "Password changed successfully."]);
    } else {
        error_log("Password update failed: " . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Failed to update password."]);
    }
    

    $stmt->close();
    exit;
}

// Ensure no extra output is sent
ob_end_flush();
?>
