<?php
require 'db_connect.php';  // Ensure the database connection is included

// Get the request method
$request_method = $_SERVER['REQUEST_METHOD'];

// Check if it's a POST request
if ($request_method == 'POST') {
    // Parse the POST request body to get parameters
    $data = $_POST;

    // Check if the necessary parameters are present
    if (isset($data['form_id']) && isset($data['type'])) {
        $form_id = $data['form_id'];
        $type = $data['type'];  // Either "clearance" or "business"

        // Prepare response array
        $response = [
            'status' => 'error',
            'message' => ''
        ];

        // Determine which table to delete from based on the 'type' parameter
        if ($type == 'clearance') {
            // Prepare the SQL query to delete from the clearance_request table
            $sql = "DELETE FROM clearance_request WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                // Bind the form_id to the query
                $stmt->bind_param("i", $form_id);
                // Execute the query
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Clearance request deleted successfully';
                } else {
                    $response['message'] = 'Failed to delete clearance request';
                }
                // Close the prepared statement
                $stmt->close();
            } else {
                $response['message'] = 'Error preparing query for clearance';
            }
        } elseif ($type == 'business') {
            // Prepare the SQL query to delete from the business_request table
            $sql = "DELETE FROM business_request WHERE form_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                // Bind the form_id to the query
                $stmt->bind_param("i", $form_id);
                // Execute the query
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Business request deleted successfully';
                } else {
                    $response['message'] = 'Failed to delete business request';
                }
                // Close the prepared statement
                $stmt->close();
            } else {
                $response['message'] = 'Error preparing query for business';
            }
        } else {
            // Invalid type, respond with error
            $response['message'] = 'Invalid request type';
        }

        // Return JSON response
        echo json_encode($response);
    } else {
        // If form_id or type is missing
        echo json_encode(['status' => 'error', 'message' => 'Missing form_id or type']);
    }
} else {
    // If the request method is not POST, respond with error
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
