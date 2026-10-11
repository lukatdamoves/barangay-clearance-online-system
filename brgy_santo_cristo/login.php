<?php
session_start();
include 'db_connect.php';

$captcha_input = $_POST['captcha'];

if (!isset($_SESSION['captcha']) || $captcha_input !== $_SESSION['captcha']) {
    echo json_encode(["status" => "error", "message" => "Incorrect CAPTCHA. Please try again."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'login'; // Determine the type (login or forgot_password)

    if (empty($email) || empty($password)) {
        echo "invalid";
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'];

    // Check if there's a cooldown for this IP with the specified type (login or forgot_password)
    $sql = "SELECT cooldown_expires_at FROM ip_cooldowns WHERE ip_address = ? AND type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ip, $type);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($cooldown_expires_at);
        $stmt->fetch();

        if ($cooldown_expires_at > time()) {
            $remaining = $cooldown_expires_at - time();
            echo "cooldown|$remaining";
            exit;
        }
    }

    // Query to fetch user details
    $sql = "SELECT user_id, firstname, middlename, lastname, namesuffix, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "server_error";
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        echo "server_error";
        exit;
    }

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ---- EMAIL VERIFICATION CHECK ----
        $verify_stmt = $conn->prepare("SELECT 1 FROM email_verifications WHERE email = ?");
        $verify_stmt->bind_param("s", $email);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows > 0) {
            // Save to session for persistence on refresh
            $_SESSION['pending_verification_email'] = $email;

            echo "verify_pending"; // Frontend will handle modal
            exit;
        }
        $verify_stmt->close();
        // ---- END CHECK ----

        if (password_verify($password, $user['password'])) {
            // Successful login - reset cooldown
            $sql = "DELETE FROM ip_cooldowns WHERE ip_address = ? AND type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $ip, $type);
            $stmt->execute();

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['middlename'] = $user['middlename'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['namesuffix'] = $user['namesuffix'];
            $_SESSION['email'] = $user['email'];

            if ($user['user_id'] == 1) {
                echo "admin";
            } else {
                echo "success";
            }
        } else {
            // Failed attempt - increment counter and set cooldown
            $attempt_key = "login_attempts_{$ip}_{$email}";
            $_SESSION[$attempt_key] = isset($_SESSION[$attempt_key]) ? $_SESSION[$attempt_key] + 1 : 1;

            // If 3 or more failed attempts, set cooldown
            if ($_SESSION[$attempt_key] >= 3) {
                $cooldown_time = 60; // 1 minute cooldown
                $cooldown_expires_at = time() + $cooldown_time;

                $sql = "REPLACE INTO ip_cooldowns (ip_address, cooldown_expires_at, type) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sis", $ip, $cooldown_expires_at, $type);
                $stmt->execute();

                echo "cooldown|$cooldown_time";
            } else {
                echo "invalid";
            }
        }
    } else {
        // Email not found - also increment counter
        $attempt_key = "login_attempts_{$ip}_{$email}";
        $_SESSION[$attempt_key] = isset($_SESSION[$attempt_key]) ? $_SESSION[$attempt_key] + 1 : 1;

        if ($_SESSION[$attempt_key] >= 3) {
            $cooldown_time = 60; // 1 minute cooldown
            $cooldown_expires_at = time() + $cooldown_time;

            $sql = "REPLACE INTO ip_cooldowns (ip_address, cooldown_expires_at, type) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $ip, $cooldown_expires_at, $type);
            $stmt->execute();

            echo "cooldown|$cooldown_time";
        } else {
            echo "invalid";
        }
    }
    $stmt->close();
    $conn->close();
}
?>
