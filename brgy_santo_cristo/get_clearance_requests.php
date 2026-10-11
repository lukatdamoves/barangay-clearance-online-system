<?php
session_start(); // Make sure session is started
include 'db_connect.php';

header('Content-Type: application/json');

// Check if logged in and is admin (user_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    http_response_code(403); // Forbidden
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Optional filter by user
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Pagination
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    $query = "
        SELECT 
            clearance_request.*, 
            users.firstname, 
            users.middlename, 
            users.lastname,
            users.email,
            clearance_request.date_submitted,
            clearance_request.purpose,
            outputed_docx.file_path
        FROM clearance_request
        JOIN users ON clearance_request.user_id = users.user_id
        LEFT JOIN outputed_docx 
            ON clearance_request.id = outputed_docx.clearance_id 
            AND outputed_docx.type = 'clearance'
    ";

    // Build conditions dynamically
    $params = [];
    $types = "";

    if ($user_id !== null) {
        $query .= " WHERE clearance_request.user_id = ?";
        $types .= "i";
        $params[] = $user_id;
    }

    $query .= " ORDER BY clearance_request.date_submitted DESC LIMIT ? OFFSET ?";
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $fullName = $row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname'];
        $row['name'] = $fullName;
        $requests[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $requests]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
