<?php
session_start();
$pendingEmail = $_SESSION['pending_verification_email'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Clearance System</title>
    <link rel="stylesheet" href="../design/loginstyle.css">
</head>
<body data-pending-email="<?php echo htmlspecialchars($_SESSION['pending_verification_email'] ?? '', ENT_QUOTES); ?>">
    <div class="container">
        <div id="login-section" class="box">
            <button onclick="window.history.back()" class="back-button">
                &#8592; Back
            </button>
            
            <div class="tabs">
                <button id="loginTab" class="tab active">Log in</button>
                <button id="registerTab" class="tab">Register</button>
            </div>
            
            <!-- Login form -->
            <div id="loginForm" class="form-box">
                <form id="loginFormElement">
                    <div class="input-group">
                        <input type="email" id="login-email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <input type="password" id="login-password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('login-password', this)">👁️‍🗨️</button>
                    </div>

                    <div class="input-group">
                        <div class="captcha-container">
                            <img src="" alt="CAPTCHA" id="loginCaptcha">
                            <button type="button" onclick="refreshCaptcha('loginCaptcha')">Refresh</button>
                        </div>
                        <input type="text" name="captcha" id="login-captcha" placeholder="Enter CAPTCHA" required>
                    </div>
                    <button type="submit" class="btn">LOG IN</button>
                    <a href="../structure/forgot_password.php" id="forgotPassword" class="link">Forgot password?</a>
                </form>
            </div>
            
            <!-- Register form -->
                <!-- Register form -->
            <div id="registerForm" class="form-box hidden">
                <form id="registerFormElement">
                    <div class="input-group">
                        <input type="text" id="register-name" name="name" placeholder="First Name" 
                            oninput="validateNameInput(this)" required>
                    </div>
                    <div class="input-group">
                        <input type="text" id="register-middlename" name="middlename" placeholder="Middle Name" 
                            oninput="validateNameInput(this)" required>
                    </div>
                    <div class="input-group">
                        <input type="text" id="register-lastname" name="lastname" placeholder="Last Name" 
                            oninput="validateNameInput(this)" required>
                    </div>
                        
                    <div class="input-group">
                        <select id="suffix" name="namesuffix">
                            <option value="" selected>Suffix (Optional)</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="I">I</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                            <option value="V">V</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <input type="email" id="register-email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <input type="password" id="register-password" name="password" placeholder="Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('register-password', this)">👁️‍🗨️</button>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm-password', this)">👁️‍🗨️</button>
                    </div>
                    <div class="input-group">
                        <div class="captcha-container">
                            <img src="" alt="CAPTCHA" id="registerCaptcha">
                            <button type="button" onclick="refreshCaptcha('registerCaptcha')">Refresh</button>
                        </div>
                        <input type="text" name="captcha_input" id="register-captcha" placeholder="Enter CAPTCHA" required>
                    </div>

                    <button type="submit" class="btn">CREATE ACCOUNT</button>
                </form>
            </div>

            <!-- Email Verification Section (hidden initially) -->
            <div id="verificationSection" class="form-box hidden">
                <h3>Email Verification</h3>
                <p>We sent a 6-digit code to <span id="verifyEmailDisplay"></span>. Enter it below to verify your account.</p>
                <form id="verifyCodeForm">
                    <div class="input-group">
                        <input type="text" id="verification-code" name="verification-code" placeholder="Enter 6-digit code" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn">VERIFY CODE</button>
                    <button type="button" id="cancelVerificationBtn" class="cancel-btn">Cancel</button>
                    <button type="button" class="link" id="resendCodeBtn">Resend Code</button>
                </form>
            </div>
            </div>
        </div>

    <script src="../action/login.js"></script>
</body>
</html>
    