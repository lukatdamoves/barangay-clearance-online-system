<?php
// ✅ Check for internal token first (used by Dompdf)
if (isset($_GET['token']) && $_GET['token'] === 'change-me-in-production') {
    $filename = basename($_GET['file'] ?? '');
    $filepath = __DIR__ . '/' . $filename;

    if (file_exists($filepath)) {
        serveFile($filepath);
    } else {
        http_response_code(404);
        exit('File not found');
    }
}

// 🟡 If no valid token, fall back to session-protected access
session_start();

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}


require_once '../db_connect.php'; // adjust path if needed

$filename = basename($_GET['file'] ?? '');
if (!$filename) {
    http_response_code(400);
    exit('No file specified.');
}

$filepath = __DIR__ . '/' . $filename;
if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found');
}

$current_user_id = $_SESSION['user_id'];

// If admin, allow access
if ($current_user_id == 1) {
    serveFile($filepath);
}

// Check if the user owns the file (in clearance_request)
$query = "SELECT 1 FROM clearance_request WHERE user_id = ? AND (applicant_photo LIKE ? OR valid_id_photo LIKE ?)";
$stmt = $conn->prepare($query);
$likeFile = "%$filename";
$stmt->bind_param("iss", $current_user_id, $likeFile, $likeFile);
$stmt->execute();
$result = $stmt->get_result();

// After the clearance_request check, add this business_request check:
$businessQuery = "SELECT 1 FROM business_request WHERE user_id = ? AND (
    dti_sec_registration LIKE ? OR 
    lease_of_contract LIKE ? OR 
    land_title LIKE ? OR 
    old_barangay_clearance LIKE ? OR 
    old_mayors_permit LIKE ?
)";
$businessStmt = $conn->prepare($businessQuery);
$businessStmt->bind_param("isssss", $current_user_id, $likeFile, $likeFile, $likeFile, $likeFile, $likeFile);
$businessStmt->execute();
$businessResult = $businessStmt->get_result();

if ($businessResult->num_rows > 0) {
    serveFile($filepath);
}
if ($result->num_rows > 0) {
    serveFile($filepath);
} else {
    http_response_code(403);
    exit('Access denied.');
}

// Serve the file securely
function serveFile($filepath) {
    $mime = mime_content_type($filepath);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($filepath));
    readfile($filepath);
    exit;
}
