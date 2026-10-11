<?php

session_start();
if (!isset($_SESSION['user_id'])) {
header("Location: loginpage.php");
exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="../design/admin.css">
    <script src="../action/homefunction.js" defer></script>
    <script src="../action/changepassadmin.js" defer></script>
</head>
<body>
        <!-- Header -->
        <header class="header">
        <div class="logo">
            <img src="../image/logo.jpg" alt="Logo">
            <a href="../structure/homepage.php" class="company-name-link">
                <span class="company-name">Barangay Santo Cristo</span>
            </a>
        </div>
           <!-- Navigation -->
           <nav class="nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="profile-view">
                            <a href="homepage.php">
                                <div class="image-container">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-container">
                                     <?= isset($_SESSION['firstname'], $_SESSION['lastname']) 
                                    ? htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) 
                                    : 'Guest' ?>       
                                </div>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="login-link">
                            <a href="../structure/loginpage.php">Login</a>
                        </div>
                    <?php endif; ?>
            </nav>
        </header>


        <!-- Profile Section -->
        <main>
            
            <div class="container-profile">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                  <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                </svg>
                <h2>
                    <?php 
                    if (isset($_SESSION['firstname'], $_SESSION['lastname'])) {
                        echo htmlspecialchars($_SESSION['firstname'])." ".
                            (!empty($_SESSION['middlename']) ? htmlspecialchars($_SESSION['middlename']): '')." ".
                            htmlspecialchars($_SESSION['lastname'])." ".
                            (!empty($_SESSION['namesuffix']) ? htmlspecialchars($_SESSION['namesuffix']): '');
                    } else {
                        echo "No name available.";
                    }
                    ?>
                </h2>
                <p><?= isset($_SESSION['email']) 
                  ? htmlspecialchars($_SESSION['email']) 
                  : 'Guest' ?>
                </p>
                <a onclick="openModal()" class="change-password">Change password</a>
                <form action="../logout.php" method="POST">
                  <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>

            <!-- Change Password Modal -->
            <div id="changePasswordModal" class="modal-overlay">
                <div class="modal-content">
                  <h2 class="modal-title">Change Password</h2>

                    <form id="changePasswordForm">
                        <div class="input-group">
                        <label class="form-label">Current Password:</label>
                        <input type="password" name="current_password" required class="form-input" id="current_password">
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">👁️‍🗨️</button>
                        </div>

                        <div class="input-group">
                            <label class="form-label">New Password:</label>
                            <input type="password" name="new_password" required class="form-input" id="new_password">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">👁️‍🗨️</button>
                        </div>

                        <div class="input-group">   
                        <label class="form-label">Confirm New Password:</label>
                        <input type="password" name="confirm_password" required class="form-input" id="confirm_password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️‍🗨️</button>
                        </div>
                        

                        <div class="modal-buttons">
                          <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                          <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
</body>
</html>
