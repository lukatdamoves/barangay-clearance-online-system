<?php
session_start();
include 'db_connect.php';
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['user_id'])) {
    // user_id exists in the session
    echo 'User is logged in. User ID: ' . $_SESSION['user_id'];
} else {
    // user_id does not exist in the session
    echo 'User is not logged in.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        // Redirect to homepage if no session exists
        header("Location: ../index.php");
        exit();
    }

    // Fetch the user's details (including name) from the users table based on user_id
    $stmt = $conn->prepare("SELECT email, firstname, middlename, lastname, namesuffix FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user exists in the database
    if ($result->num_rows === 0) {
        die("User not found.");
    }

    // Get all user details from the database
    $user_data = $result->fetch_assoc();
    $user_email = $user_data['email'];
    $db_first_name = $user_data['firstname'];
    $db_middle_name = $user_data['middlename'] ?? null;
    $db_last_name = $user_data['lastname'];
    $db_name_suffix = $user_data['namesuffix'] ?? null;

    // Retrieve form data (these will be stored in the database)
    $first_name = $_POST['firstName'];
    $middle_name = $_POST['middleName'] ?? null;
    $last_name = $_POST['lastName'];
    $name_suffix = $_POST['namesuffix'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $date_submitted = $_POST['date'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $civil_status = $_POST['civil-status'];
    $birth_date = $_POST['birthDate'];
    $birth_place = $_POST['birthPlace'];
    $nationality = $_POST['nationality'];
    $since_when = $_POST['sinceWhen'];
    $purpose = $_POST['purpose'];
    $additional_info = $_POST['additional-info'];

    // Handle file uploads
    $applicant_photo = "uploads/" . basename($_FILES["applicantPhoto"]["name"]);
    move_uploaded_file($_FILES["applicantPhoto"]["tmp_name"], $applicant_photo);

    $valid_id_photo = "uploads/" . basename($_FILES["validIDPhoto"]["name"]);
    move_uploaded_file($_FILES["validIDPhoto"]["tmp_name"], $valid_id_photo);

    // Insert into database (using form-submitted names)
    $stmt = $conn->prepare("INSERT INTO clearance_request 
        (user_id, first_name, middle_name, last_name, name_suffix, contact, address, date_submitted, 
         age, sex, civil_status, birth_date, birth_place, nationality, since_when, purpose, 
         additional_info, applicant_photo, valid_id_photo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issssssssssssssssss", 
        $user_id, $first_name, $middle_name, $last_name, $name_suffix, $contact, $address, 
        $date_submitted, $age, $sex, $civil_status, $birth_date, $birth_place, $nationality, 
        $since_when, $purpose, $additional_info, $applicant_photo, $valid_id_photo);

    if ($stmt->execute()) {
        // Send email after successful insertion (using database-stored names)
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
            $mail->Subject = 'Clearance Request Submission Confirmation';
            
            // Use the name from the users table (not the form) for the email
            $mail->Body = "Dear $db_first_name" . 
                         ($db_middle_name ? " $db_middle_name" : "") . 
                         " $db_last_name" . 
                         ($db_name_suffix ? " $db_name_suffix" : "") . 
                         ",\n\nYour Clearance Request has been successfully submitted.\n\nPlease wait for the Approval Email Shortly.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email failed to send: " . $mail->ErrorInfo);
        }

        $inserted_id = $stmt->insert_id; // Get last inserted clearance ID
        header("Location: /brgy_santo_cristo/structure/previewpage.php?id=$inserted_id&type=clearance");
        exit();
    } else {
        die("Error executing statement: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    

} else {
    // Redirect to homepage if accessed directly without POST request
    header("Location: /brgy_santo_cristo/structure/homepage.php");
    exit();
}
?>