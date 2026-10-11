<?php
// Always include this file at the beginning of any PHP file to avoid duplicate session starts            
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Barangay Clearance</title>   
    <link rel="stylesheet" href="../design/previewpage.css">
    <script src="../action/homefunction.js" defer></script>
    <style>
        <?php if ($isEmbed): ?>
        body.embed-mode {
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .background-overlay,
        .background-image,
        .header,
        #mobile-menu-wrapper,
        .btn {
            display: none !important;
        }
        .preview-container {
            box-shadow: none;
            border: none;
            padding: 10px;
        }
        <?php endif; ?>
    </style>
</head>
<body class="<?php echo $isEmbed ? 'embed-mode' : ''; ?>">

<?php if (!$isEmbed): ?>
<div class="background-overlay"></div>
<div class="background-image"></div>
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
                <a href="../structure/profilepage.php">
                <div class="image-container">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="text-container">
                    <?php echo $_SESSION['firstname']; ?> <?php echo $_SESSION['lastname']; ?>
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
</header>
<?php endif; ?>

<?php
    include '../db_connect.php'; // Ensure database connection is included

    if (!isset($_SESSION['user_id'])) {
        die("User not logged in.");
    }
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid request ID.");
    }
    
    $requested_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    if ($_SESSION['user_id'] == 1) {
        // Admin can access any request
        $sql = "SELECT * FROM clearance_request WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requested_id);
    } else {
        // Normal user: must match their own user_id
        $sql = "SELECT * FROM clearance_request WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $requested_id, $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    
    if (!$application) {
        echo "<p>No application found.</p>";
    } else {
        // Display application
?>

<div class="preview-container">
    <?php if (!$isEmbed): ?>
    <a class="btn" href="profilepage.php">Go Back</a>
    <?php endif; ?>
    <h2 class="title-clearance">Clearance Preview</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($application['first_name']) ?> <?= htmlspecialchars($application['middle_name']) ?> <?= htmlspecialchars($application['last_name']) ?> <?= htmlspecialchars($application['name_suffix']) ?></p>
    <p><strong>Contact:</strong> <?= htmlspecialchars($application['contact']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($application['address']) ?></p>
    <p><strong>Age:</strong> <?= htmlspecialchars($application['age']) ?></p>
    <p><strong>Sex:</strong> <?= htmlspecialchars($application['sex']) ?></p>
    <p><strong>Civil Status:</strong> <?= htmlspecialchars($application['civil_status']) ?></p>
    <p><strong>Birth Date:</strong> <?= htmlspecialchars($application['birth_date']) ?></p>
    <p><strong>Birth Place:</strong> <?= htmlspecialchars($application['birth_place']) ?></p>
    <p><strong>Nationality:</strong> <?= htmlspecialchars($application['nationality']) ?></p>
    <p><strong>Resident Since:</strong> <?= htmlspecialchars($application['since_when']) ?></p>
    <p><strong>Purpose:</strong> <?= htmlspecialchars($application['purpose']) ?></p>
    <p><strong>Additional Info:</strong> <?= htmlspecialchars($application['additional_info']) ?></p>
    <p><strong>Submitted Date:</strong> <?= htmlspecialchars($application['date_submitted']) ?></p>

    <div class="row">
        <h3>Uploaded Documents</h3>
        <p><strong>Applicant Photo:</strong></p>
        <img src="../uploads/image.php?file=<?= htmlspecialchars($application['applicant_photo']) ?>" alt="Applicant Photo" class="zoom-trigger" style="width: 150px; cursor: pointer;">
        <p><strong>Valid ID:</strong></p>
        <img src="../uploads/image.php?file=<?= htmlspecialchars($application['valid_id_photo']) ?>" alt="Valid ID" class="zoom-trigger" style="width: 150px; cursor: pointer;">
    </div>
    <br>

    <!-- Modal for zoom -->
    <div id="imageModal" class="modal" style="display: none;">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            const closeBtn = document.querySelector(".close");

            document.querySelectorAll(".zoom-trigger").forEach(img => {
                img.addEventListener("click", function () {
                    modal.style.display = "block";
                    modalImg.src = img.src;
                    modalImg.classList.remove("zoomed");
                });
            });

            closeBtn.onclick = function () {
                modal.style.display = "none";
            };

            modalImg.addEventListener("click", function (event) {
                const rect = modalImg.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                modalImg.style.transformOrigin = `${x}px ${y}px`;
                if (modalImg.classList.contains("zoomed")) {
                    modalImg.classList.remove("zoomed");
                    modalImg.style.transition = "none";
                } else {
                    modalImg.classList.add("zoomed");
                    modalImg.style.transition = "transform 0.3s ease";
                }
            });

            modal.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });
        });
    </script>
<?php } ?>
</div>
</body>
</html>
