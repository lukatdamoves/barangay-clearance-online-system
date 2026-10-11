<?php
session_start();
require '../db_connect.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";
$step = 1;


// Get IP address
function getUserIP() {
    return $_SERVER['REMOTE_ADDR'];
}
$ip = getUserIP();

// Check cooldown before any sensitive action
$cooldownActive = false;   
$remainingSeconds = 0;
$cooldownStmt = $conn->prepare("SELECT cooldown_expires_at FROM ip_cooldowns WHERE ip_address = ? AND type = 'forgot_password'");
$cooldownStmt->bind_param("s", $ip);
$cooldownStmt->execute();
$cooldownResult = $cooldownStmt->get_result();

if ($cooldownResult->num_rows > 0) {
    $cooldownData = $cooldownResult->fetch_assoc();
    $now = time();
    $cooldownTime = (int)$cooldownData['cooldown_expires_at'];


    if ($now < $cooldownTime) {
        $cooldownActive = true;
        $remainingSeconds = $cooldownTime - $now;
        $error = "Too many failed attempts. Please try again after " . ceil($remainingSeconds / 60) . " minutes.";
        $step = 2;
    } else {
        // Cooldown expired
        unset($_SESSION['failed_attempts']);
        $deleteCooldownStmt = $conn->prepare("DELETE FROM ip_cooldowns WHERE ip_address = ? AND type = 'forgot_password'");
        $deleteCooldownStmt->bind_param("s", $ip);
        $deleteCooldownStmt->execute();
    }
} elseif (isset($_SESSION['step'])) {
    // Only use session step if no cooldown is active
    $step = $_SESSION['step'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Clear previous step/session if user starts fresh and there's no cooldown
    if (!$cooldownActive) {
        unset($_SESSION['step'], $_SESSION['email'], $_SESSION['code'], $_SESSION['failed_attempts']);
    }
}

// STEP 1: Send Code
if (isset($_POST['send_code'])) {
    // Get email from POST data
    $email = $_POST['email'] ?? '';

    // Check if email is provided
    if (empty($email)) {
        $error = "Please provide your email.";
        $step = 1;
    } else {
        // Check if there's a cooldown active
        if ($cooldownActive) {
            $error = "You're in cooldown. Please wait before requesting another code.";
            $step = 2;
        } else {
            // If no cooldown, check if email is registered in the database
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // If email not found in the database
                $error = "Email is not registered.";
                $step = 1;
            } else {
                // If email is registered, generate verification code
                $code = rand(100000, 999999);
                $_SESSION['code'] = $code;
                $_SESSION['email'] = $email;
                $_SESSION['step'] = 2;
                $step = 2;

                // Send the verification code via email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your-email@gmail.com';
                    $mail->Password = 'your-app-password-here'; // App password only!
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('your-email@gmail.com', 'Barangay Santo Cristo Online Clearance');
                    $mail->addAddress($email);
                    $mail->Subject = 'Your Verification Code';
                    $mail->Body = "Your verification code is: $code";

                    $mail->send();
                    $success = "Verification code sent to your email.";
                } catch (Exception $e) {
                    // Handle error if email fails to send
                    $error = "Failed to send email: " . $mail->ErrorInfo;
                    $step = 1;
                }
            }
        }
    }
}


// Handle change email
if (isset($_POST['change_email'])) {
    if ($cooldownActive) {
        $error = "You cannot change email while in cooldown. Please wait.";
        $step = 2;
    } else {
        unset($_SESSION['code'], $_SESSION['email']);
        $_SESSION['step'] = 1;
        $step = 1;
    }
}

// STEP 2: Verify Code
if (isset($_POST['verify_code']) && !$cooldownActive) {
    $entered_code = $_POST['code'];

    if (!isset($_SESSION['code'])) {
        $error = "Session expired or code not set. Please start over.";
        $step = 1;
        unset($_SESSION['step'], $_SESSION['email'], $_SESSION['code'], $_SESSION['failed_attempts']);
    } elseif (strval($entered_code) === strval($_SESSION['code'])) {
        $_SESSION['step'] = 3;
        $step = 3;
        $success = "Code verified!";
        unset($_SESSION['failed_attempts']);
    } else {
        $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
        $error = "Invalid code.";
        $step = 2;

        if ($_SESSION['failed_attempts'] >= 3) {
            $cooldownDuration = new DateTime('+3 minutes');
            $formatted = $cooldownDuration->getTimestamp();

            $stmt = $conn->prepare("INSERT INTO ip_cooldowns (ip_address, cooldown_expires_at, type)
                        VALUES (?, ?, 'forgot_password')
                        ON DUPLICATE KEY UPDATE cooldown_expires_at = VALUES(cooldown_expires_at)");
            $stmt->bind_param("si", $ip, $formatted); // ← fixed here too
            $stmt->execute();

            $error = "Too many failed attempts. Please wait 3 minutes.";
        }
    }
}

// STEP 3: Change Password
if (isset($_POST['change_password'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $email = $_SESSION['email'] ?? null;

    if (!$email) {
        $error = "Session expired. Please restart the process.";
        $step = 1;
        session_destroy();
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
        $step = 3;
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
        $step = 3;
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $success = "Password successfully changed!";
            session_destroy();
            $step = 1;
        } else {
            $error = "Failed to change password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../design/forgotpass.css">
    <style>
        .center-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-size: 20px;
            text-align: center;
            flex-direction: column;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="box">
    <button onclick="window.location.href='../structure/homepage.php'" class="back-button">← Back</button>


        <?php if ($success === "Password successfully changed!"): ?>
            <div class="center-message">
                <div><?= $success ?></div>
                <div>Please wait, redirecting to login page...</div>
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = '../structure/loginpage.php';
                }, 3000);
            </script>
        <?php else: ?>

            <h2 class="header-title">Forgot Password</h2>

            <?php if ($success && $step !== 3): ?>
                <div style="color: green;"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="POST" class="form-box">
                    <input type="email" name="email" placeholder="Email" required><br>
                    <button type="submit" name="send_code" class="btn">Send Verification Code</button>
                </form>

            <?php elseif ($step == 2): ?>
                <form method="POST" class="form-box" id="verify-form">
                    <label class="label-name">Enter Verification Code:</label><br>
                    <input type="text" name="code" required <?= $cooldownActive ? 'disabled' : '' ?>><br>
                    <button type="submit" name="verify_code" class="btn" <?= $cooldownActive ? 'disabled' : '' ?>>Verify</button>
                </form>
                <form method="POST" style="margin-top: 10px;" id="change-email-form">
                    <button type="submit" name="change_email" class="btn" <?= $cooldownActive ? 'disabled' : '' ?>>Change Email</button>
                </form>
                <?php if ($cooldownActive): ?>
                    <p id="cooldown-timer" style="color: black; font-size: 16px;">Please wait...</p>
                <?php endif; ?>

            <?php elseif ($step == 3): ?>
                <form method="POST" class="form-box">
                    <input type="password" name="password" placeholder="New Password" required><br><br>
                    <input type="password" name="confirm" placeholder="Confirm Password" required><br>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>
            <?php endif; ?>

        <?php endif; ?> 
    </div>
</div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
        <?php if ($cooldownActive): ?>
        const verifyForm = document.getElementById("verify-form");
        const changeEmailForm = document.getElementById("change-email-form");

        if (verifyForm) {
            verifyForm.addEventListener("submit", function (e) {
                e.preventDefault();
                alert("Please wait until the cooldown ends before verifying your code.");
            });
        }

        if (changeEmailForm) {
            changeEmailForm.addEventListener("submit", function (e) {
                e.preventDefault();
                alert("You cannot change email during cooldown.");
            });
        }
        <?php endif; ?>
    });
    </script>

<?php if ($cooldownActive): ?>
    <script>
        let remainingSeconds = <?= $remainingSeconds ?>;

        function updateCountdown() {
            const display = document.getElementById("cooldown-timer");
            if (remainingSeconds > 0) {
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;
                display.textContent = `Please wait before trying again: ${minutes} minute(s) and ${seconds} second(s)`;
                remainingSeconds--;
            } else {
                display.textContent = "You can try again now.";
                document.getElementById("verify-form").querySelector("input").disabled = false;
                document.getElementById("verify-form").querySelector("button").disabled = false;
                document.getElementById("change-email-form").querySelector("button").disabled = false;
            }
        }

        setInterval(updateCountdown, 1000);
        window.onload = updateCountdown;
    </script>
<?php endif; ?>
</body>
</html>
                