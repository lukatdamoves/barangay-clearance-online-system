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
    <title>About - Online Barangay Clearance</title>
    <link rel="stylesheet" href="../design/aboutpage.css">
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
        <h2 >About Barangay Clearance Online</h2>
        <p>The Barangay Clearance Online System is a modern, web-based solution that allows residents to easily apply for their barangay clearances. By eliminating long queues and manual application processes, the system streamlines the request process and makes it more accessible while the actual clearance is still received in person.</p>
        
        <div class="section">
            <h3>Purpose of the System</h3>
            <br>
            <p>The goal of this system is to create an efficient, paperless, and hassle-free clearance application process for Barangay Santo Cristo. Residents can conveniently submit their applications online, track the status of their requests, and receive notifications. Once approved, they simply need to visit the barangay hall to claim their clearance.
            Please note: This system does not support online payment. All payments, if any, must be settled in person at the barangay hall.</p>
        </div>

        <div class="section">
            <h3>Key Features</h3>
            <br>
            <ul>
                <li>Online Application for Barangay Clearance - Residents can apply for clearance anytime, anywhere.</li>
                <li>Automated Approval & Processing System - Streamlines the review process for faster handling.</li>
                <li>Real-Time Status Tracking - Applicants can monitor the progress of their requests.</li>
                <li>Email Notifications - Important updates are sent directly to the applicant's email.</li>
                <li>Fast & Convenient Transactions - Saves time by minimizing manual steps and long queues.</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; 2025 Barangay Santo Cristo || All Rights Reserved.
    </footer>
    <script src="../action/homefunction.js"></script>
</body>
</html>