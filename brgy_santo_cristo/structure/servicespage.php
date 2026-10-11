<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user_id is 1, redirect to admin page
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1) {
        header("Location: adminpage.php"); // Redirect to admin page
        exit();
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Online Barangay Clearance</title>
    <link rel="stylesheet" href="../design/homepage.css">
    <link rel="stylesheet" href="../design/servicespage.css">
    <script src="../action/homefunction.js" defer></script>
</head>
<body>

    <!-- Navigation -->
    <header class="header">
        <div class="logo">
            <a href="../structure/homepage.php">
                <img src="../image/logo.jpg" alt="Logo">
            </a>
            <a href="../structure/homepage.php" class="company-name-link">
                <span class="company-name">Barangay Santo Cristo</span>
            </a>
        </div>


            <!-- Desktop Navigation -->
            <nav class="nav">
                <a href="../structure/homepage.php">Home</a>
                <a href="../structure/aboutpage.php">About</a>
                <a href="../structure/servicespage.php">Services</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Show User Profile If Logged In -->
                <div class="profile-view">
                    <a href="profilepage.php">
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
                <!-- Show Login Button If Not Logged In -->
                <div class="login-link">
                   <a href="../structure/loginpage.php">Login</a>
                </div>
                <?php endif; ?>
            </nav>

            <!-- Mobile Menu Button -->
            <button id="menu-toggle" class="menu-button" aria-label="Toggle mobile menu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="menu-icon">
                    <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75ZM3 12a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </header>

    <!-- Mobile Sidebar Wrapper -->
    <div id="mobile-menu-wrapper" class="mobile-menu-wrapper">
        <div id="mobile-menu" class="mobile-menu">
            <h2 class="mobile-title">Barangay Santo Cristo</h2>
            <nav class="mobile-nav">
                <a href="../structure/homepage.php">Home</a>
                <a href="../structure/aboutpage.php">About</a>
                <a href="../structure/servicespage.php">Services</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                 <!-- Show User Profile If Logged In -->
                    <div class="profile-view">
                        <a href="profilepage.php">
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
                <!-- Show Login Button If Not Logged In -->
                    <div class="login-link">
                      <a href="../structure/loginpage.php">Login</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="content-container">
        <h2>Barangay Clearance Services</h2>
        <p>Apply for barangay-related documents online with ease.</p>

        <!-- Services Grid -->
        <div class="services-grid">

            <!-- Barangay Clearance -->
            <div class="service-box">
                <h3>Barangay Clearance</h3>
                <p>Apply for your Barangay Clearance online without hassle.</p>
                <button onclick="redirectToService('brgyCLRform.php')">Proceed</button>
            </div>

            <!-- Business Clearance -->
            <div class="service-box">
                <h3>Business Clearance</h3>
                <p>Obtain your business clearance for official registration.</p>
                <button onclick="redirectToService('businessform.php')">Proceed</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; 2025 Barangay Santo Cristo | All Rights Reserved.
    </footer>
 
    <script>
        function redirectToService(page) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = '../structure/loginpage.php';
            <?php else: ?>
                window.location.href = page;
            <?php endif; ?>
        }
    </script>
</body>
</html>
