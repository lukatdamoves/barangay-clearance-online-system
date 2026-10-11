<?php
session_start();
header("Content-Type: application/json");

require 'vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db_connect.php';   

$mode = $_POST['mode'] ?? 'register'; // Default is registration

// ----------------------
// 1. VERIFY EMAIL CODE
// ----------------------
if ($mode === 'verify') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';

    if (!$email || !$code) {
        echo json_encode(["status" => "error", "message" => "Missing email or code."]);
        exit;
    }

    // Check if code exists and not expired (5 minutes)
    $stmt = $conn->prepare("SELECT * FROM email_verifications WHERE email = ? AND code = ? AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) <= 5");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Delete verification entry (mark as verified)
        $conn->query("DELETE FROM email_verifications WHERE email = '$email'");

        // Clear session pending email
        if (isset($_SESSION['pending_verification_email']) && $_SESSION['pending_verification_email'] === $email) {
            unset($_SESSION['pending_verification_email']);
        }

        echo json_encode(["status" => "success", "message" => "Email verified successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid or expired verification code."]);
    }
    exit;
}

// ----------------------
// 2. CANCEL VERIFICATION (delete user + verification code)
// ----------------------
if ($mode === 'cancel') {
    $email = $_POST['email'] ?? '';

    if (!$email) {
        echo json_encode(["status" => "error", "message" => "Missing email."]);
        exit;
    }

    // Delete verification entry
    $stmt = $conn->prepare("DELETE FROM email_verifications WHERE email = ?");
    $stmt->bind_param("s", $email);
    $verificationDeleted = $stmt->execute();
    $stmt->close();

    if ($verificationDeleted) {
        // Delete user from users table
        $stmt_user = $conn->prepare("DELETE FROM users WHERE email = ?");
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $stmt_user->close();

        // Clear session pending email
        if (isset($_SESSION['pending_verification_email']) && $_SESSION['pending_verification_email'] === $email) {
            unset($_SESSION['pending_verification_email']);
        }

        echo json_encode(["status" => "success", "message" => "Verification canceled and user removed."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to cancel verification."]);
    }
    exit;
}

// ----------------------
// 3. CLEAR SESSION ONLY (new mode)
// ----------------------
if ($mode === 'clear_session') {
    $email = $_POST['email'] ?? '';
    if (isset($_SESSION['pending_verification_email']) && $_SESSION['pending_verification_email'] === $email) {
        unset($_SESSION['pending_verification_email']);
    }
    echo json_encode(["status" => "success", "message" => "Session cleared."]);
    exit;
}

// ----------------------
// 4. RESEND VERIFICATION CODE
// ----------------------
if (isset($_POST['resend_email'])) {
    $email = $_POST['resend_email'];

    // Check if user exists
    $stmt_check = $conn->prepare("SELECT firstname FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Email not found."]);
        exit;
    }

    $user = $result->fetch_assoc();
    $firstname = $user['firstname'];

    // Generate new verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Update or insert into email_verifications table
    $stmt_upsert = $conn->prepare("REPLACE INTO email_verifications (email, code) VALUES (?, ?)");
    $stmt_upsert->bind_param("ss", $email, $verification_code);
    $stmt_upsert->execute();
    $stmt_upsert->close();

    // Send email
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
        $mail->addAddress($email);
        $mail->Subject = 'Resend Email Verification - Barangay Santo Cristo';
        $mail->Body = "Hello $firstname,\n\nYour new verification code is: $verification_code\n\nThis code will expire in 5 minutes.";

        $mail->send();

        echo json_encode(["status" => "success", "message" => "A new verification code has been sent to your email."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to resend verification email."]);
    }
    exit;
}

// ----------------------
// 5. NORMAL REGISTRATION
// ----------------------

// Only check CAPTCHA for registration mode
if ($mode === 'register') {
    $captcha_input = $_POST['captcha'] ?? '';

    if (!isset($_SESSION['captcha']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha'])) {
        echo json_encode(["status" => "error", "message" => "Incorrect CAPTCHA. Please try again."]);
        exit();
    }
}

// Get form data
$firstname = $_POST['firstname'];
$middlename = $_POST['middlename'] ?? null;
$lastname = $_POST['lastname'];
$namesuffix = isset($_POST['namesuffix']) && in_array($_POST['namesuffix'], ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V']) ? $_POST['namesuffix'] : null;
$email = $_POST['email'];
$password = $_POST['password'];

// Validate password length
if (strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long."]);
    exit();
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email already exists
$sql_check = "SELECT * FROM users WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "exists", "message" => "Email already exists. Please use another email."]);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// Insert user into database
$sql = "INSERT INTO users (firstname, middlename, lastname, namesuffix, email, password) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $firstname, $middlename, $lastname, $namesuffix, $email, $hashed_password);

if ($stmt->execute()) {
    // Generate 6-digit verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Insert code into email_verifications table
    $stmt_code = $conn->prepare("INSERT INTO email_verifications (email, code) VALUES (?, ?)");
    $stmt_code->bind_param("ss", $email, $verification_code);
    $stmt_code->execute();
    $stmt_code->close();

    // Send email using PHPMailer
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
        $mail->addAddress($email);
        $mail->Subject = 'Email Verification - Barangay Santo Cristo';
        $mail->Body = "Hello $firstname,\n\nYour verification code is: $verification_code\n\nThis code will expire in 5 minutes.";

        $mail->send();

        // Save pending verification email in session
        $_SESSION['pending_verification_email'] = $email;

        echo json_encode([
            "status" => "success",
            "message" => "Registration successful! A verification code has been sent to your email."
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Registered, but failed to send verification email."
        ]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Registration failed. Please try again."]);
}

// Close statements and connection
$stmt->close();
$conn->close();
?>
