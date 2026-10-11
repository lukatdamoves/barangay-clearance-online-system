<?php
session_start();
require 'db_connect.php'; // adjust path if needed
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$status = $_POST['status'] ?? '';
$form_id = $_POST['form_id'] ?? '';
$type = $_POST['type'] ?? '';
$user_id = $_POST['user_id'] ?? '';

if ($status !== 'Approved' || !$form_id || !$type || !$user_id) {
    echo "Invalid input.";
    exit;
}

// Determine table and ID field based on type
$table = $type === 'business' ? 'business_request' : 'clearance_request';
$id_field = $type === 'business' ? 'form_id' : 'id';

// Get user info
$query = "
    SELECT u.firstname, u.middlename, u.lastname, u.namesuffix, u.email
    FROM $table r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.$id_field = ? AND r.user_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $form_id, $user_id);

if (!$stmt->execute()) {
    echo "Error retrieving user data.";
    exit;
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Request not found.";
    exit;
}

$db_first_name = $user['firstname'];
$db_middle_name = $user['middlename'];
$db_last_name = $user['lastname'];
$db_name_suffix = $user['namesuffix'];
$user_email = $user['email'];

$stmt->close();

// Update status to Approved
$update = $conn->prepare("UPDATE $table SET status = 'Approved' WHERE $id_field = ?");
$update->bind_param("i", $form_id);

if ($update->execute()) {
    // Send approval email
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password-here'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Barangay Santo Cristo Online Clearance');
        $mail->addAddress($user_email);
        $mail->Subject = 'Barangay ' . ucfirst($type) . ' Request Approved';

        $docType = $type === 'business' ? 'Business Clearance' : 'Barangay Clearance';

        $mail->Body = "Dear $db_first_name" .
                      ($db_middle_name ? " $db_middle_name" : "") .
                      " $db_last_name" .
                      ($db_name_suffix ? " $db_name_suffix" : "") .
                      ",\n\nYour $docType request has been APPROVED.\n\nYou may now proceed to the Barangay Hall to claim your document.\n\nThank you for using our online service.";

        $mail->send();
        echo "Email sent and status updated.";
    } catch (Exception $e) {
        error_log("Approval email failed to send: " . $mail->ErrorInfo);
        echo "Status updated, but email failed to send.";
    }
} else {
    echo "Failed to update status: " . $update->error;
}

$update->close();
$conn->close();
?>
