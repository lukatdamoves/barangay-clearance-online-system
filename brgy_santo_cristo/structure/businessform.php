<?php
    session_start(); // Start the session to access session variables
    include '../db_connect.php'; // Include your database connection file

    // Assuming you have a user ID stored in session after login
    $user_id = $_SESSION['user_id'] ?? null; 

    $firstname = "";
    $lastname = "";

    if ($user_id) {
        // Query to fetch first name and last name
        $stmt = $conn->prepare("SELECT firstname, middlename, lastname, namesuffix FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($firstname, $middlename, $lastname, $namesuffix);
        $stmt->fetch();
        $stmt->close();
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Form</title>
        <link rel="stylesheet" href="../design/businessform.css">
    </head>
    <body>

        <div id="fill-up-form" class="form-container">
            <button onclick="window.history.back()" class="back-button">
            &#8592; Back
            </button>
            <h2 class="form-title">Application Form</h2>
            <form id="clearanceForm" action="../business_submit_form.php" method="POST" enctype="multipart/form-data">
                <div class="row-radio">
                    <div><label for="new"><input type="radio" id="new" name="applicationType" value="NEW" required> New</label></div>    
                    <div><label for="renewal"><input type="radio" id="renewal" name="applicationType" value="RENEWAL" required> Renewal</label></div>                
                </div>
            
            
                <div class="row">
                <div class="input-container">
                    <label for="dateSubmitted">Date Today</label>
                    <input type="date" id="date" name="dateSubmitted" readonly required>
                    </div>

                    <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        // Get current date in Philippines timezone using Intl API
                        const options = { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' };
                        const formatter = new Intl.DateTimeFormat('en-CA', options); // 'en-CA' gives YYYY-MM-DD format
                        const todayPH = formatter.format(new Date());

                        const dateInput = document.getElementById('date');
                        dateInput.value = todayPH;
                        dateInput.max = todayPH;      // Prevent future dates
                        dateInput.readOnly = true;    // Disable manual typing
                    });
                    </script>

                    <!-- Full Name -->
                    <div class="input-container">
                        <label for="businessName">Business Name</label>
                        <input type="text" id="businessName" name="businessName" required>
                    </div>  
                    <div class="input-container">
                        <label for="businessAddress">Business Address</label>
                        <input type="text" id="businessAddress" name="businessAddress" required>
                    </div>
                    <div class="input-container">
                        <label for="businessTele">Business Telephone</label>
                        <input type="text" id="businessTele" name="businessTele" 
                            required 
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-container">
                        <label for="ownersName">Owner's Full Name</label>
                        <input type="text" id="ownersName" name="ownerName" required>
                    </div>
                    
                    <!-- Contact No -->
                    <div class="input-container">
                        <label for="contact">Owner's Contact No.</label>
                        <input type="tel" id="contact" name="ownerContact" 
                            pattern="[0-9]{11}" 
                            maxlength="11" 
                            minlength="11" 
                            required 
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            title="Please enter exactly 11 digits">
                    </div>

                    <div class="input-container">
                        <label for="ownersAddress">Owner's Address</label>
                        <input type="text" id="ownersAddress" name="ownerAddress" required>
                    </div>

                    <div class="input-container">
                        <label for="kindofBusiness">Kind of Business</label>
                        <input type="text" id="kindofBusiness" name="kindOfBusiness" required>
                    </div>
                </div>

                <div class="row">
                    <div class="input-container">
                        <label for="dtisecRegno">DTI / SEC Reg no.</label>
                        <input type="text" id="dtisecRegno" name="dtiSecRegNo" required>
                    </div>
                    <div class="input-container">
                        <label for="ctcno">CTC No. (Cedula)</label>
                        <input type="text" id="ctcno" name="ctcNo" required>
                    </div>

                    <div class="input-container">
                        <label for="issuedOn">Issued On</label>
                        <input type="date" id="issuedOn" name="issuedOn" required>
                    </div>

                    <script>
                        const today = new Date().toISOString().split('T')[0];
                        document.getElementById('issuedOn').setAttribute('max', today);
                    </script>


                    <div class="input-container">
                        <label for="issuedAt">Issued At</label>
                        <input type="text" id="issuedAt" name="issuedAt" required>
                    </div>
                    <div class="input-container">
                        <label for="tinNo">TIN No.</label>
                        <input type="text" id="tinNo" name="tinNo" required>
                    </div>
                </div>
            
                <div class="input-container">
                    <label for="additionalInfo">Additional Info</label>
                    <input type="text" id="additionalInfo" name="additionalInfo">
                </div>

                <div class="attach-requirement">
                    <div class="attach-photo">
                        <label >DTI / SEC Registration: <br></label>
                        <label for="dtisecRegistration" class="custom-file-upload">Choose File</label>
                        <span id="dtisecRegistration-file-name">No file chosen</span>
                        <input type="file" id="dtisecRegistration" name="dtiSecRegistration" accept="image/*">
                        <img id="dtisecRegistrationPreview" class="image" src="" alt="Preview">
                        <br>
                    </div>

                    <div class="attach-photo">
                        <label >Lease of Contract or Award Notice (SM Concessionaries): <br></label>
                        <label for="leaseOfContract" class="custom-file-upload">Choose File</label>
                        <span id="leaseOfContract-file-name">No file chosen</span>
                        <input type="file" id="leaseOfContract" name="leaseOfContract" accept="image/*">
                        <img id="leaseOfContractPreview" class="image" src="" alt="Preview">
                        <br>
                    </div>

                    <div class="attach-photo">
                        <label>If Owner: Land Title: <br></label>
                        <label for="landTitle" class="custom-file-upload">Choose File</label>
                        <span id="landTitle-file-name">No file chosen</span>
                        <input type="file" id="landTitle" name="landTitle" accept="image/*">
                        <img id="landTitlePreview" class="image" src="" alt="Preview">
                        <br>
                    </div>

                    <div id="renewalFields" style="display: none;">
                        <div class="attach-photo">
                            <label>Old Barangay Clearance: <br></label>
                            <label for="oldBarangayClearance" class="custom-file-upload">Choose File</label>
                            <span id="oldBarangayClearance-file-name">No file chosen</span>
                            <input type="file" id="oldBarangayClearance" name="oldBarangayClearance" accept="image/*" >
                            <br>
                            <img id="oldBarangayClearancePreview" class="image" src="" alt="Preview">
                        </div>

                        <div class="attach-photo">
                            <label>Old Mayor's Permit: <br></label>
                            <label for="oldMayorsPermit" class="custom-file-upload">Choose File</label>
                            <span id="oldMayorsPermit-file-name">No file chosen</span>
                            <input type="file" id="oldMayorsPermit" name="oldMayorsPermit" accept="image/*">
                            <br>
                            <img id="oldMayorsPermitPreview" class="image" src="" alt="Preview">
                            <br>
                        </div>
                    </div>
                </div>
                
                        
                <button type="submit" class="submit-btn">Submit</button>
            </form>


            <script>

                document.getElementById('dtisecRegistration').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('dtisecRegistrationPreview').src = e.target.result;
                            document.getElementById('dtisecRegistrationPreview').style.display = "block";
                            document.getElementById('dtisecRegistration-file-name').textContent = file.name; // Update file name
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.getElementById('oldBarangayClearance').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('oldBarangayClearancePreview').src = e.target.result;
                            document.getElementById('oldBarangayClearancePreview').style.display = "block";
                            document.getElementById('oldBarangayClearance-file-name').textContent = file.name; // Update file name
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.getElementById('oldMayorsPermit').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('oldMayorsPermitPreview').src = e.target.result;
                            document.getElementById('oldMayorsPermitPreview').style.display = "block";
                            document.getElementById('oldMayorsPermit-file-name').textContent = file.name; // Update file name
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.getElementById('leaseOfContract').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('leaseOfContractPreview').src = e.target.result;
                            document.getElementById('leaseOfContractPreview').style.display = "block";
                            document.getElementById('leaseOfContract-file-name').textContent = file.name; // Update file name
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.getElementById('landTitle').addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('landTitlePreview').src = e.target.result;
                            document.getElementById('landTitlePreview').style.display = "block";
                            document.getElementById('landTitle-file-name').textContent = file.name; // Update file name
                        };
                        reader.readAsDataURL(file);
                    }
                });

                document.addEventListener("DOMContentLoaded", function () {
                    const renewalFields = document.getElementById("renewalFields");
                    const newRadio = document.getElementById("new");
                    const renewalRadio = document.getElementById("renewal");

                    function toggleRenewalFields() {
                        if (renewalRadio.checked) {
                            renewalFields.style.display = "block";
                        } else {
                            renewalFields.style.display = "none";
                        }
                    }

                    newRadio.addEventListener("change", toggleRenewalFields);
                    renewalRadio.addEventListener("change", toggleRenewalFields);
                });

                

                document.querySelector("form").addEventListener("submit", function (event) {
                    event.preventDefault();

                    const submitBtn = document.querySelector(".submit-btn");
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Submitting...";

                    // Check required fields first
                    const requiredFields = [
                        "businessName", "businessAddress", "businessTele",
                        "ownerName", "ownerContact", "ownerAddress",
                        "kindOfBusiness", "dtiSecRegNo", "ctcNo",
                        "issuedOn", "issuedAt", "tinNo"
                    ];

                    
                    for (let field of requiredFields) {
                        const input = document.querySelector(`[name="${field}"]`);
                        if (!input || !input.value.trim()) {
                            submitBtn.disabled = false;  // ADDED THIS
                            submitBtn.textContent = "Submit";  // ADDED THIS
                            alert(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()} field`);
                            return;
                        }
                    }

                    // Check required files
                    const requiredFiles = [
                        {id: "dtisecRegistration", name: "DTI / SEC Registration"},
                        {id: "leaseOfContract", name: "Lease of Contract"},
                        {id: "landTitle", name: "Land Title"}
                    ];

                    for (let file of requiredFiles) {
                        const input = document.getElementById(file.id);
                        if (!input || !input.files || input.files.length === 0) {
                            submitBtn.disabled = false;  // ADDED THIS
                            submitBtn.textContent = "Submit";  // ADDED THIS
                            alert(`Please upload a file for ${file.name}`);
                            return;
                        }
                    }

                    // If renewal, check additional files
                    const applicationType = document.querySelector('input[name="applicationType"]:checked');
                    if (!applicationType) {
                        alert("Please select an Application Type: New or Renewal.");
                        return;
                    }

                    if (applicationType.value.toLowerCase() === "renewal") {
                        const renewalFiles = [
                            {id: "oldBarangayClearance", name: "Old Barangay Clearance"},
                            {id: "oldMayorsPermit", name: "Old Mayor's Permit"}
                        ];

                        for (let file of renewalFiles) {
                            const input = document.getElementById(file.id);
                            if (!input || !input.files || input.files.length === 0) {
                                submitBtn.disabled = false;  // ADDED THIS
                                submitBtn.textContent = "Submit";  // ADDED THIS
                                alert(`Please upload a file for ${file.name}`);
                                return;
                            }
                        }
                    }

                    // -- Submission animation and fetch logic --
                    const form = event.target;
                    const formData = new FormData(form);

                    const style = document.createElement('style');
                    style.innerHTML = `
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                        .spinner {
                            display: inline-block;
                            width: 16px;
                            height: 16px;
                            border: 2px solid white;
                            border-top-color: transparent;
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                            margin-left: 10px;
                            vertical-align: middle;
                        }
                    `;
                    document.head.appendChild(style);

                    const messageDiv = document.createElement("div");
                    messageDiv.innerHTML = `<div class="spinner"></div> Successfully submitted the form!`;
                    messageDiv.style.position = "fixed";
                    messageDiv.style.top = "20px";
                    messageDiv.style.left = "50%";
                    messageDiv.style.transform = "translateX(-50%)";
                    messageDiv.style.padding = "12px 20px";
                    messageDiv.style.backgroundColor = "#4CAF50";
                    messageDiv.style.color = "white";
                    messageDiv.style.borderRadius = "8px";
                    messageDiv.style.fontSize = "16px";
                    messageDiv.style.zIndex = "9999";
                    messageDiv.style.boxShadow = "0 2px 8px rgba(0,0,0,0.2)";
                    messageDiv.style.opacity = "0";
                    messageDiv.style.transition = "opacity 0.5s ease";
                    document.body.appendChild(messageDiv);

                    setTimeout(() => {
                        messageDiv.style.opacity = "1";
                    }, 50);

                    fetch(form.action, {
                        method: form.method,
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            if (response.redirected) {
                                window.location.href = response.url;
                            } else {
                                setTimeout(() => {
                                    messageDiv.style.opacity = "0";
                                    setTimeout(() => {
                                        messageDiv.remove();
                                    }, 500);
                                }, 10000);
                            }
                        } else {
                            throw new Error("Network response was not ok");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("There was an error submitting the form. Please try again.");
                    });

                });


            window.addEventListener("pageshow", function(event) {
                if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                       // User came from back/forward button — redirect to homepage
                       window.location.href = "../structure/homepage.php"; // Change this path to your actual homepage if different
                    }
                });

            </script>
        </div>
    </body>
    </html>
