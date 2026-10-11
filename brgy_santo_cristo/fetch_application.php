<?php
include 'db_connect.php'; // adjust if your DB path is different

header('Content-Type: application/json');

// Check if user_id, form_id, and request_type are set in the URL
if (!isset($_GET['user_id']) || !isset($_GET['form_id']) || !isset($_GET['request_type'])) {
    echo json_encode(['error' => 'Missing user_id, form_id, or request_type']);
    exit();
}

$user_id = intval($_GET['user_id']);
$form_id = intval($_GET['form_id']);
$request_type = $_GET['request_type']; // 'clearance' or 'business'

// Determine which table to query based on the request_type
$table = '';
$identifier = '';  // This will hold the column name for identifying the form

if ($request_type === 'clearance') {
    $table = 'clearance_request';
    $identifier = 'id';  // Use `id` for clearance_request
} elseif ($request_type === 'business') {
    $table = 'business_request';
    $identifier = 'form_id';  // Use `form_id` for business_request
} else {
    echo json_encode(['error' => 'Invalid request_type']);
    exit();
}

// Prepare SQL query to get the application based on user_id and form_id (or id)
$stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ? AND $identifier = ?");
$stmt->bind_param("ii", $user_id, $form_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if an application was found
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Application not found']);
    exit();
}

// Fetch the application data
$data = $result->fetch_assoc();

// Return the data as JSON
echo json_encode(['status' => 'success', 'data' => $data]);
?>
