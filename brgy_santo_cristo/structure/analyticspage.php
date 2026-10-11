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
    <link rel="stylesheet" href="../design/analytics.css">
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
                    <a href="adminpage.php">
                        <div class="text-container">
                            <div><?php echo htmlspecialchars("Dashboard"); ?></div>
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

    <main class="container">
        <div class="header-container">
                <a href="adminpage.php" class="back-button">←</a>
                <h2 class="analytics-label">Analytics Overview</h2>
        </div>
        <br>
        
        <div class="second-group">
            <!-- Filters Section -->
            <div class="left-column">
                <div class="filters">
                    <div class="month-picker-wrapper">
                        <input type="month" class="styled-select" id="monthPicker">
                    </div>
                    <script>
                        const monthPicker = document.getElementById('monthPicker');
                        monthPicker.addEventListener('click', () => {
                            if (typeof monthPicker.showPicker === 'function') {
                                monthPicker.showPicker();
                            }
                        });
                    </script>
                

                    <!-- Clearance Groups -->
                    <div class="custom-multiselect" id="clearanceMultiselect">
                        <button type="button" class="styled-select" id="groupDropdownToggle">Select Clearance Groups</button>
                        <div class="dropdown-menu" id="clearanceDropdown">
                            <button type="button" class="styled-select select-toggle-btn" id="toggleAllClearanceCheckboxes">Deselect All</button>
                            <label><input type="checkbox" value="Residency"> Residency</label>
                            <label><input type="checkbox" value="Employment"> Employment</label>
                            <label><input type="checkbox" value="Postal"> Postal</label>
                            <label><input type="checkbox" value="Indigency"> Indigency</label>
                            <label><input type="checkbox" value="Senior Citizen / Solo Parent / PWD"> Senior / Solo Parent / PWD</label>
                            <label><input type="checkbox" value="School Requirement"> School Requirement</label>
                            <label><input type="checkbox" value="Good Moral / No Pending Case"> Good Moral / No Pending Case</label>
                        </div>
                    </div>

                    <!-- Business Groups -->
                    <div class="custom-multiselect" id="businessMultiselect">
                        <button type="button" class="styled-select" id="BusinessgroupDropdownToggle">Select Business Groups</button>
                        <div class="dropdown-menu" id="businessDropdown">
                            <button type="button" class="styled-select select-toggle-btn" id="toggleAllBusinessCheckboxes">Deselect All</button>
                            <label><input type="checkbox" value="New"> New (Business)</label>
                            <label><input type="checkbox" value="Renewal"> Renewal (Business)</label>
                        </div>
                    </div>
                </div>

                <div class="search-bar">
                <form id="searchForm">
                    <input type="text" id="searchInput" name="search" placeholder="Search by Name">
                    <button type="submit">Search</button>
                    <button type="button" id="clearSearchBtn">Clear</button>
                </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                function setupHoverBehavior(multiselectElement, dropdownElement) {
                    // Show dropdown when mouse enters the whole container
                    multiselectElement.addEventListener('mouseenter', function () {
                        dropdownElement.style.display = 'block';
                    });

                    // Hide dropdown when mouse leaves the whole container
                    multiselectElement.addEventListener('mouseleave', function () {
                        dropdownElement.style.display = 'none';
                    });
                }

                setupHoverBehavior(
                    document.getElementById('clearanceMultiselect'),
                    document.querySelector('#clearanceMultiselect .dropdown-menu')
                );

                setupHoverBehavior(
                    document.getElementById('businessMultiselect'),
                    document.querySelector('#businessMultiselect .dropdown-menu')
                );
            });
            </script>

            <!-- Stats Summary Section -->
            <div class="container2">
                <div class="registration-section">
                    <h3>Total Registration <br><span class="time-period-text">(All Time)</span></h3>
                    <p class="registration-count" id="totalRegistrations">Loading...</p>
                </div>
                <div class="clearance-section">
                    <h3>Total Clearance <br><span class="time-period-text">(All Time)</span></h3>
                    <p class="clearance-count" id="totalClearances">Loading...</p>
                </div>
                <div class="business-clearance-section">
                    <h3>Total Business Clearance <br><span class="time-period-text">(All Time)</span></h3>
                    <p class="business-count" id="totalBusinessClearances">Loading...</p>
                </div>
            </div>

        </div>
       

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="loading-indicator" style="display: none;">
            <p>Loading data...</p>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message" style="display: none;">
            <p>Error loading data. Please try again.</p>
        </div>

        <!-- Analytics Table -->
        <div class="table-container">
            <table class="analytics-table" id="analyticsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Surname</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="analyticsTableBody">
                    <!-- Data will be loaded here via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
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
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Export Options -->
            <div class="export-options">
                <button type="button" class="styled-select" id="exportPDFBtn">Export to PDF</button>
            </div>

    </main>
    <script src="../action/analytics.js"></script>
</body>
</html>