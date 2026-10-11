<?php
session_start(); 
include 'db_connect.php';
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("User not logged in.");
}

// Fetch user's email and name from the database
$stmt = $conn->prepare("SELECT email, firstname, middlename, lastname, namesuffix FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("User not found.");
}
$user_data = $result->fetch_assoc();
$user_email = $user_data['email'];
$db_first_name = $user_data['firstname'];
$db_middle_name = $user_data['middlename'] ?? null;
$db_last_name = $user_data['lastname'];
$db_name_suffix = $user_data['namesuffix'] ?? null;

// Retrieve form data
$application_type = $_POST['applicationType'];
$date_submitted = $_POST['dateSubmitted'];
$business_name = $_POST['businessName'];
$business_address = $_POST['businessAddress'];
$business_telephone = $_POST['businessTele'];
$owner_name = $_POST['ownerName'];
$owner_contact = $_POST['ownerContact'];
$owner_address = $_POST['ownerAddress'];
$kind_of_business = $_POST['kindOfBusiness'];
$dti_sec_reg_no = $_POST['dtiSecRegNo'];
$ctc_no = $_POST['ctcNo'];
$issued_on = $_POST['issuedOn'];
$issued_at = $_POST['issuedAt'];
$tin_no = $_POST['tinNo'];
$additional_info = $_POST['additionalInfo'];

// Handle file uploads
$uploadDir = 'uploads/';
function uploadFile($file, $uploadDir) {
    $fileName = basename($file["name"]);
    $targetFilePath = $uploadDir . time() . '_' . uniqid() . '_' . $fileName;
    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        return $targetFilePath;
    }
    return null;
}

$dti_sec_registration = uploadFile($_FILES["dtiSecRegistration"], $uploadDir);
$lease_of_contract = uploadFile($_FILES["leaseOfContract"], $uploadDir);
$land_title = !empty($_FILES["landTitle"]["name"]) ? uploadFile($_FILES["landTitle"], $uploadDir) : null;
$old_barangay_clearance = !empty($_FILES["oldBarangayClearance"]["name"]) ? uploadFile($_FILES["oldBarangayClearance"], $uploadDir) : null;
$old_mayors_permit = !empty($_FILES["oldMayorsPermit"]["name"]) ? uploadFile($_FILES["oldMayorsPermit"], $uploadDir) : null;

// Insert data into database
$stmt = $conn->prepare("INSERT INTO business_request
(user_id, application_type, date_submitted, business_name, business_address, business_telephone, 
owner_name, owner_contact, owner_address, kind_of_business, dti_sec_reg_no, ctc_no, 
issued_on, issued_at, tin_no, additional_info, dti_sec_registration, 
lease_of_contract, land_title, old_barangay_clearance, old_mayors_permit) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("issssssssssssssssssss", 
    $user_id, $application_type, $date_submitted, $business_name, $business_address, $business_telephone, 
    $owner_name, $owner_contact, $owner_address, $kind_of_business, $dti_sec_reg_no, $ctc_no, 
    $issued_on, $issued_at, $tin_no, $additional_info, $dti_sec_registration, 
    $lease_of_contract, $land_title, $old_barangay_clearance, $old_mayors_permit
);

if ($stmt->execute()) {
    // Send confirmation email
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
        $mail->Subject = 'Business Clearance Request Submission Confirmation';

        $mail->Body = "Dear $db_first_name" .
                     ($db_middle_name ? " $db_middle_name" : "") .
                     " $db_last_name" .
                     ($db_name_suffix ? " $db_name_suffix" : "") .
                     ",\n\nYour Business Clearance Request has been successfully submitted.\n\nPlease wait for the Approval Email Shortly.";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email failed to send: " . $mail->ErrorInfo);
    }

    $form_id = $stmt->insert_id; // get last inserted business form ID
    $_SESSION['form_submitted'] = true;
    header("Location: /brgy_santo_cristo/structure/previewbusiness.php?form_id=$form_id&type=business");
    exit();
    
} else {
    die("Error executing statement: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
