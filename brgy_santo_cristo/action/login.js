document.addEventListener("DOMContentLoaded", function () {
    const loginTab = document.getElementById("loginTab");
    const registerTab = document.getElementById("registerTab");
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");
    const verificationSection = document.getElementById("verificationSection");
    const verifyEmailDisplay = document.getElementById("verifyEmailDisplay");
    const resendCodeBtn = document.getElementById("resendCodeBtn");
    const cancelVerificationBtn = document.getElementById("cancelVerificationBtn");

    refreshCaptcha("loginCaptcha");
    refreshCaptcha("registerCaptcha");

    // Helper to enable/disable tabs
    function setTabsEnabled(enabled) {
        const tabs = [loginTab, registerTab];
        tabs.forEach(tab => {
            if (enabled) {
                tab.style.pointerEvents = "auto";
                tab.style.opacity = "1";
            } else {
                tab.style.pointerEvents = "none";
                tab.style.opacity = "0.5";
            }
        });
    }

    // --- Check session (pending verification email) from PHP ---
    const pendingEmail = document.body.getAttribute("data-pending-email");
    if (pendingEmail) {
        verificationSection.classList.remove("hidden");
        verifyEmailDisplay.textContent = pendingEmail;
        loginForm.classList.add("hidden");
        registerForm.classList.add("hidden");
        setTabsEnabled(false); // disable tabs
    }

    // Toggle Between Login and Register
    registerTab.addEventListener("click", function () {
        loginForm.classList.add("hidden");
        registerForm.classList.remove("hidden");
        registerTab.classList.add("active");
        loginTab.classList.remove("active");
        refreshCaptcha("registerCaptcha"); // Refresh captcha when switching
    });

    loginTab.addEventListener("click", function () {
        registerForm.classList.add("hidden");
        loginForm.classList.remove("hidden");
        loginTab.classList.add("active");
        registerTab.classList.remove("active");
        refreshCaptcha("loginCaptcha"); // Refresh captcha when switching
    });

    // Handle Registration
    document.getElementById("registerFormElement").addEventListener("submit", function (event) {
        event.preventDefault();

        const firstname = document.getElementById("register-name").value.trim();
        const middlename = document.getElementById("register-middlename").value.trim();
        const lastname = document.getElementById("register-lastname").value.trim();
        const email = document.getElementById("register-email").value.trim();
        const password = document.getElementById("register-password").value.trim();
        const confirmPassword = document.getElementById("confirm-password").value.trim();
        const namesuffix = document.getElementById("suffix").value;
        const captcha = document.getElementById("register-captcha").value.trim();

        if (password !== confirmPassword) {
            alert("Passwords do not match!");
            return;
        }

        if (password.length < 8) {
            alert("Password must be at least 8 characters long!");
            return;
        }

        const formData = new FormData();
        formData.append("firstname", firstname);
        formData.append("middlename", middlename);
        formData.append("lastname", lastname);
        formData.append("email", email);
        formData.append("password", password);
        formData.append("namesuffix", namesuffix);
        formData.append("captcha", captcha);

        // Show loading message
        showMessage("Creating account, please wait...", false);

        fetch("../register.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const isError = data.status !== "success";
                showMessage(data.message, isError);

                if (!isError) {
                    // Show verification section instead of login/register
                    registerForm.classList.add("hidden");
                    verificationSection.classList.remove("hidden");
                    verifyEmailDisplay.textContent = email;
                    setTabsEnabled(false); // Disable tabs during verification
                } else {
                    document.getElementById("register-captcha").value = "";
                    refreshCaptcha("registerCaptcha"); // Force refresh after error
                }
            })
            .catch(() => {
                document.getElementById("register-captcha").value = "";
                showMessage("An error occurred. Please try again later.", true);
                refreshCaptcha("registerCaptcha");
            });
    });

    // Handle Email Verification
    document.getElementById("verifyCodeForm").addEventListener("submit", function (event) {
        event.preventDefault();

        const code = document.getElementById("verification-code").value.trim();
        const email = verifyEmailDisplay.textContent;

        if (code.length !== 6) {
            showMessage("Invalid code. Please enter the 6-digit code sent to your email.", true);
            return;
        }

        const formData = new FormData();
        formData.append("mode", "verify");
        formData.append("email", email);
        formData.append("code", code);

        fetch("../register.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    showMessage("Email verified successfully! You can now log in.", false);

                    verificationSection.classList.add("hidden");
                    loginForm.classList.remove("hidden");
                    loginTab.classList.add("active");
                    registerTab.classList.remove("active");
                    setTabsEnabled(true); // Re-enable tabs

                    // Clear pending verification session
                    fetch("../register.php", {
                        method: "POST",
                        body: new URLSearchParams({
                            mode: "clear_session",
                            email: verifyEmailDisplay.textContent
                        })
                    });

                    refreshCaptcha("loginCaptcha"); // Refresh captcha when returning to login
                } else {
                    showMessage(data.message || "Invalid verification code.", true);
                }
            })
            .catch(() => {
                showMessage("An error occurred. Please try again later.", true);
            });
    });

    // Resend code (only allow once)
    resendCodeBtn.addEventListener("click", function () {
        resendCodeBtn.style.display = "none";    // Disable button immediately to prevent multiple clicks
    
        const email = verifyEmailDisplay.textContent;
    
        const formData = new FormData();
        formData.append("resend_email", email);
    
        fetch("../register.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.status !== "success");
                // You can also keep button hidden/disabled here if needed
            })
            .catch(() => {
                showMessage("Failed to resend code. Try again later.", true);
                // You can also keep button hidden/disabled here if needed
            });
    });
    

    // Cancel verification and go back to login
    if (cancelVerificationBtn) {
        cancelVerificationBtn.addEventListener("click", function () {
            verificationSection.classList.add("hidden");
            loginForm.classList.remove("hidden");
            loginTab.classList.add("active");
            registerTab.classList.remove("active");
            setTabsEnabled(true); // Re-enable tabs

            // Clear pending verification session
            fetch("../register.php", {
                method: "POST",
                body: new URLSearchParams({
                    mode: "cancel",
                    email: verifyEmailDisplay.textContent
                })
            });

            refreshCaptcha("loginCaptcha");
        });
    }

    // Handle Login
    document.getElementById("loginFormElement").addEventListener("submit", function (event) {
        event.preventDefault();

        const email = document.getElementById("login-email").value.trim();
        const password = document.getElementById("login-password").value.trim();
        const loginButton = document.querySelector("#loginFormElement button[type='submit']");
        const originalButtonText = loginButton.textContent;

        const captcha = document.getElementById("login-captcha").value.trim();

        const formData = new FormData();
        formData.append("email", email);
        formData.append("password", password);
        formData.append("captcha", captcha);

        fetch("../login.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                data = data.trim();
                let message = '';
                let redirectUrl = '';
                let isError = false;

                if (data === "success") {
                    message = "Login successful! Redirecting to home page...";
                    redirectUrl = "../structure/homepage.php";
                } else if (data === "admin") {
                    message = "Welcome Admin! Redirecting...";
                    redirectUrl = "../structure/adminpage.php";
                } else if (data === "verify_pending") {
                    loginForm.classList.add("hidden");
                    verificationSection.classList.remove("hidden");
                    verifyEmailDisplay.textContent = email;
                    setTabsEnabled(false);
                    showMessage("Please verify your email before logging in.", true);
                    return;
                } else if (data.startsWith("cooldown")) {
                    const seconds = parseInt(data.split("|")[1]);
                    message = `Too many failed attempts. Please try again in ${seconds} seconds.`;
                    isError = true;
                    showMessage(message, isError);

                    loginButton.disabled = true;

                    let remainingTime = seconds;
                    const countdownInterval = setInterval(() => {
                        loginButton.textContent = `Try again in ${remainingTime}s`;
                        remainingTime--;

                        if (remainingTime < 0) {
                            clearInterval(countdownInterval);
                            loginButton.disabled = false;
                            loginButton.textContent = originalButtonText;
                        }
                    }, 1000);

                } else if (data === "invalid") {
                    message = "Invalid credentials! Please try again.";
                    isError = true;
                    document.getElementById("login-captcha").value = "";
                    refreshCaptcha("loginCaptcha");
                } else {
                    message = "Login failed. Please try again.";
                    isError = true;
                    document.getElementById("login-captcha").value = "";
                    refreshCaptcha("loginCaptcha");
                }

                showMessage(message, isError);

                if (!isError) {
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1000);
                }
            })
            .catch(() => {
                showMessage("An error occurred. Please try again later.", true);
            });
    });

    // Function to show message
    function showMessage(message, isError) {
        const existingMessage = document.querySelector(".login-message");
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement("div");
        messageDiv.className = "login-message";
        messageDiv.textContent = message;
        messageDiv.style.position = "fixed";
        messageDiv.style.top = "20px";
        messageDiv.style.left = "50%";
        messageDiv.style.transform = "translateX(-50%)";
        messageDiv.style.padding = "10px 20px";
        messageDiv.style.backgroundColor = isError ? "#f44336" : "#4CAF50";
        messageDiv.style.color = "white";
        messageDiv.style.borderRadius = "5px";
        messageDiv.style.fontSize = "16px";
        messageDiv.style.zIndex = "9999";
        messageDiv.style.textAlign = "center";
        messageDiv.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.opacity = "0";
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }

    // Handle Suffix Selection
    const suffixSelect = document.getElementById("suffix");
    if (suffixSelect) {
        suffixSelect.style.color = "gray";
        suffixSelect.addEventListener("change", function () {
            this.style.color = this.value === "" ? "gray" : "black";
        });
    }
});

// Password Visibility Toggle
function togglePassword(inputId, button) {
    let input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        button.innerHTML = "👁️";
    } else {
        input.type = "password";
        button.innerHTML = "👁️‍🗨️";
    }
}

function validateNameInput(inputField) {
    inputField.value = inputField.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚüÜ\s\-']/g, '');
}

function refreshCaptcha(id) {
    const img = document.getElementById(id);
    if (img) {
        // Request new captcha and invalidate old session captcha
        img.src = "../captcha.php?refresh=1&rand=" + Date.now();

        // Optional: clear any input field linked to this captcha
        const inputField = img.closest("form").querySelector("input[type='text']");
        if (inputField) inputField.value = "";
    }
}

