<?php
session_start();
include '../db_connect.php';

// Restrict access to only the Head Admin (user_id == 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: ../structure/loginpage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Barangay Santo Cristo</title>
    <link rel="stylesheet" href="../design/admin.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <a href="../structure/homepage.php">
                <img src="../image/logo.jpg" alt="Logo">
            </a>
            <a href="../structure/homepage.php" class="company-name-link">
                <span class="company-name">Barangay Santo Cristo</span>
            </a>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="right-section">
                <div class="analytics">
                    <a href="analyticspage.php">
                        <div class="text-container">
                            <div><?php echo htmlspecialchars("Analytics"); ?></div>
                        </div>
                    </a>
                </div>
                <div class="profile-view">
                    <a href="profileadmin.php">
                        <div class="image-container">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-container">
                            <?php echo htmlspecialchars($_SESSION['firstname']); ?>
                            <?php echo htmlspecialchars($_SESSION['lastname']); ?>
                        </div>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <div class="tabs">
        <button id="clearance-tab" class="tab active" data-tab="clearance" aria-label="Clearance Requests">CLEARANCE REQUEST</button>
        <button id="business-tab" class="tab" data-tab="business" aria-label="Business Requests">BUSINESS REQUEST</button>
        <button id="approved-tab" class="tab" data-tab="approved" aria-label="Approved Clearances">APPROVED CLEARANCES</button>
        <button id="rejected-tab" class="tab" data-tab="rejected" aria-label="Rejected Requests">REJECTED REQUESTS</button>
    </div>

    <div class="search-container">
        <input type="text" id="search-input" placeholder="Search..." autocomplete="off">
    </div>
    
    
    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th id="sort-name" style="cursor:pointer;">
                    Name <span id="name-arrow">↑</span>
                </th>
                <th>Email</th>
                <th>Type</th>
                <th id="sort-date" style="cursor:pointer;">
                    Date <span id="date-arrow">↓</span>
                </th>
                <th>Action</th>
                <th id="sort-status" style="cursor:pointer; text-align: center;">
                    Status <span id="status-arrow">↓</span>
                </th>
            </tr>
        </thead>
        <tbody id="request-list">
            <!-- Requests will be loaded dynamically from admin.js -->
        </tbody>
    </table>

    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span id="closePreview" class="close">&times;</span>
            <div id="previewContent">
                <!-- The application details will appear here -->
            </div>
        </div>
    </div>

    <div id="imagePreviewModal" class="image-modal">
        <span class="close" id="closeImagePreview">&times;</span>
        <img class="modal-content" id="imagePreview" alt="Image Preview">
    </div>
    <div id="docx-toast" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        display: none;
        z-index: 9999;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    "></div>
    <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                <span id="paginationInfo">Showing 0 - 0 of 0 records</span>
            </div>
            <div class="pagination-controls">
                <button id="prevPageBtn" class="pagination-btn" disabled>Previous</button>
                <div id="pageNumbers" class="page-numbers"></div>
                <button id="nextPageBtn" class="pagination-btn" disabled>Next</button>
            </div>
            <div class="records-per-page">
                <label for="recordsPerPage">Records per page:</label>
                <select id="recordsPerPage" class="styled-select">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>


    <script src="../action/admin.js"></script>
</body>
</html>
