        <?php
        session_start();
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
            <link rel="stylesheet" href="../design/brgyCLRform.css">
        </head>
        <body>

            <div id="fill-up-form" class="form-container">
                <button onclick="window.history.back()" class="back-button">
                &#8592; Back
                </button>
                <h2 class="form-title">Application Form</h2>
                <form id="clearanceForm" action="../submit_form.php" method="POST" enctype="multipart/form-data">

                    <div class="row">
                            <!-- Full Name -->
                            <div class="input-container">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" required 
                                    value="<?php echo htmlspecialchars($firstname); ?>" readonly>
                            </div>  
                            <div class="input-container">
                                <label for="middleName">Middle Name</label>
                                <input type="text" id="middleName" name="middleName" required 
                                    value="<?php echo htmlspecialchars($middlename); ?>" readonly>
                            </div>
                            <div class="input-container">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" required 
                                    value="<?php echo htmlspecialchars($lastname); ?>" readonly>
                            </div>
                        <div id="input-container">
                            <label for="namesuffix">Name Suffix</label>
                            <select id="namesuffix" name="namesuffix">
                                <option value="" <?php echo ($namesuffix == "") ? "selected" : ""; ?>>N/A</option>
                                <option value="Jr." <?php echo ($namesuffix == "Jr.") ? "selected" : ""; ?>>Jr.</option>
                                <option value="Sr." <?php echo ($namesuffix == "Sr.") ? "selected" : ""; ?>>Sr.</option>
                                <option value="I" <?php echo ($namesuffix == "I") ? "selected" : ""; ?>>I</option>
                                <option value="II" <?php echo ($namesuffix == "II") ? "selected" : ""; ?>>II</option>
                                <option value="III" <?php echo ($namesuffix == "III") ? "selected" : ""; ?>>III</option>
                                <option value="IV" <?php echo ($namesuffix == "IV") ? "selected" : ""; ?>>IV</option>
                                <option value="V" <?php echo ($namesuffix == "V") ? "selected" : ""; ?>>V</option>  
                            </select>
                        </div>

                    </div>

                    <div class="row">
                        <!-- Contact No -->
                        <div class="input-container">
                            <label for="contact">Contact</label>
                            <input type="tel" id="contact" name="contact" 
                                pattern="[0-9]{11}" 
                                title="Please enter exactly 11 digits (numbers only)"
                                maxlength="11"
                                oninput="validateContactInput(this)"
                                required>
                        </div>

                        <!-- Address -->
                        <div class="input-container">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>

                        <!-- Date Input -->
                        <div class="input-container">
                            <label for="date">Date Today</label>
                            <input type="date" id="date" name="date" required readonly>
                        </div>
                        <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            // Get current time in PH timezone (UTC+8)
                            const now = new Date(new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));

                            let year = now.getFullYear();
                            let month = String(now.getMonth() + 1).padStart(2, '0');
                            let day = String(now.getDate()).padStart(2, '0');
                            let formatted = `${year}-${month}-${day}`;

                            const dateInput = document.getElementById('date');
                            dateInput.value = formatted;
                            dateInput.max = formatted; // Prevent future dates
                        });
                        </script>

                        <div class="input-container">
                            <label for="birth-date">Birth Date</label>
                            <input type="date" id="birth-date" name="birthDate" required>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const birthInput = document.getElementById("birth-date");
                                

                                // Get today's date based on PH timezone
                                const todayPH = new Date(new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));
                                const year = todayPH.getFullYear();
                                const month = String(todayPH.getMonth() + 1).padStart(2, '0');
                                const day = String(todayPH.getDate()).padStart(2, '0');
                                const todayStr = `${year}-${month}-${day}`;

                                // Set max attribute to today's date
                                birthInput.max = todayStr;

                                birthInput.addEventListener("change", function () {
                                    let birthDate = new Date(this.value);
                                    let today = new Date(new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));

                                    let age = today.getFullYear() - birthDate.getFullYear();
                                    let monthDiff = today.getMonth() - birthDate.getMonth();
                                    let dayDiff = today.getDate() - birthDate.getDate();

                                    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                                        age--;
                                    }
                                    document.getElementById("age").value = age;
                                    if (age < 18) {
                                        alert("You must be at least 18 years old to apply.");
                                        document.getElementById("age").value = ""; // Clear invalid age
                                    }
                                });
                            });
                        </script>
                    </div>

                    <div class="row">
                        <div class="input-container">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" required min="18" max="99" readonly>
                        </div>

                        <div id="input-container">
                        <label for="sex">Gender</label>    
                        <select id="sex" name="sex" required>
                            <option value="" disabled selected hidden></option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        </div>

                        <div id="input-container">  
                            <label for="civil-status">Civil Status</label>
                            <select id="civil-status" name="civil-status" required>
                                <option value="" disabled selected hidden></option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="divorced">Divorced</option>
                                <option value="separated">Separated</option>
                                <option value="annulled">Annulled</option>
                            </select>
                        </div>

                        <!-- More fields -->
                        <div class="input-container">
                            <label for="birth-place">Birth Place</label>
                            <input type="text" id="birth-place" name="birthPlace" required>
                        </div>
                    </div>

                        <div class="row-1">
                        <div class="input-container">
                            <label for="nationality">Nationality</label>
                            <select id="nationality" name="nationality" required>
                                <option value="Filipino" selected>Filipino</option> <!-- Default "Filipino" option -->
                                <option value="">Loading...</option> <!-- Loading message will be replaced -->
                            </select>
                        </div>

                        <div class="input-container">
                            <label for="since-when" class="since">Living Here Since (Month/Year)</label>
                            <input type="month" id="since-when" name="sinceWhen" required>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const sinceInput = document.getElementById("since-when");

                                // Get current PH time
                                const todayPH = new Date(new Date().toLocaleString("en-US", { timeZone: "Asia/Manila" }));
                                const year = todayPH.getFullYear();
                                const month = String(todayPH.getMonth() + 1).padStart(2, '0');
                                const maxMonth = `${year}-${month}`;

                                // Set max attribute
                                sinceInput.max = maxMonth;
                            });
                        </script>

                        <div id="input-container">
                            <label for="purpose">Purpose</label>
                            <select id="purpose" name="purpose" required>
                                <option value="" disabled selected hidden></option>
                                <option value="Residency">Residency</option>
                                <option value="Employment">Employment</option>
                                <option value="Postal">Postal</option>
                                <option value="School Requirement">School Requirement</option>
                                <option value="Indigency">Indigency</option>
                                <option value="Senior Citizen / Solo Parent / PWD">Senior Citizen / Solo Parent / PWD</option>
                                <option value="Good Moral / No Pending Case">Good Moral / No Pending Case</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-container">
                        <label for="additional-info">Additional Info</label>
                        <input type="text" id="additional-info" name="additional-info" class="large-input">
                    </div>

                    <div class="row-photo">
                        <div class="attach-applicant">
                            <label>Attach Applicant Photo: <br></label>
                            <label for="applicantPhoto" class="custom-file-upload">Choose File</label>
                            <span id="applicant-file-name">No file chosen</span>
                            <input type="file" id="applicantPhoto" name="applicantPhoto" accept="image/*">
                            <img id="applicantPhotoPreview" class="image" src="" alt="Preview">
                            <br>
                        </div>

                        <div class="attach-valid">
                            <label >Attach Valid ID Photo: <br></label>
                            <label for="validIDPhoto" class="custom-file-upload">Choose File</label>
                            <span id="validID-file-name">No file chosen</span>
                            <input type="file" id="validIDPhoto" name="validIDPhoto" accept="image/*">
                            <img id="validIDPhotoPreview" class="image" src="" alt="Preview">
                            <br>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn" id="submit">Submit</button>
                </form>


                <script>
                document.querySelector("form").addEventListener("submit", function (event) {
                    event.preventDefault(); // Prevent default form submission
                    
                    const applicantPhoto = document.getElementById("applicantPhoto");
                    const validIDPhoto = document.getElementById("validIDPhoto");
                    const ageInput = document.getElementById("age");

                    const submitBtn = document.getElementById("submit");
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Submitting...";
                    
                    if (parseInt(ageInput.value) < 18) {
                        alert("You must be at least 18 years old to submit this form.");
                        return;
                    }

                    // Check if both photos are uploaded
                    if (!applicantPhoto.files.length || !validIDPhoto.files.length) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Submit";
                        alert("Please upload both Applicant Photo and Valid ID.");
                        return;
                    }

                    const form = document.getElementById("clearanceForm");
                    const formData = new FormData(form); // Prepare the form data


                    // Show the animation and confirmation message
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
                    messageDiv.innerHTML = `
                        <div class="spinner"></div> Submitting Form...
                    `;
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
                            // Check if the response contains a redirect URL
                            if (response.redirected) {
                                window.location.href = response.url; // Redirect immediately after successful submission
                            } else {
                                // If no redirect, just show success message
                                setTimeout(() => {
                                    messageDiv.style.opacity = "0";
                                    setTimeout(() => {
                                        messageDiv.remove();
                                    }, 500); // Removes the message after it fades out
                                }, 10000); // Keep it visible for 10 seconds
                            }
                        } else {
                            throw new Error('Network response was not ok');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('There was an error submitting the form. Please try again.');
                    });
                });
            

                    document.getElementById('applicantPhoto').addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                document.getElementById('applicantPhotoPreview').src = e.target.result;
                                document.getElementById('applicantPhotoPreview').style.display = "block";
                                document.getElementById('applicant-file-name').textContent = file.name; // Update file name
                            };
                            reader.readAsDataURL(file);
                        }
                    });

                    document.getElementById('validIDPhoto').addEventListener('change', function(event) {
                        const file = event.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                document.getElementById('validIDPhotoPreview').src = e.target.result;
                                document.getElementById('validIDPhotoPreview').style.display = "block";
                                document.getElementById('validID-file-name').textContent = file.name; // Update file name
                            };
                            reader.readAsDataURL(file);
                        }
                    });

                    
                    
                    function validateContactInput(input) {
                        // Remove any non-digit characters
                        input.value = input.value.replace(/\D/g, '');
                        
                        // Trim to 11 characters if needed
                        if (input.value.length > 11) {
                            input.value = input.value.slice(0, 11);
                        }
                    }


                const select = document.getElementById("nationality");

                fetch("https://raw.githubusercontent.com/Dinuks/country-nationality-list/master/countries.json")
                    .then(response => response.json())
                    .then(data => {
                        // Clear the loading option and populate the dropdown
                        select.innerHTML = ""; // Remove all options

                        // Get unique and sorted list of nationalities
                        const nationalities = [...new Set(data.map(item => item.nationality))].sort();

                        // Add the default "Filipino" option back to the list
                        const defaultOption = document.createElement("option");
                        defaultOption.value = "Filipino";
                        defaultOption.textContent = "Filipino";
                        defaultOption.selected = true;
                        select.appendChild(defaultOption);

                        // Add the fetched nationality options
                        nationalities.forEach(nationality => {
                            const option = document.createElement("option");
                            option.value = nationality;
                            option.textContent = nationality;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching nationality data:", error);
                        // Fallback in case of an error, just keep the Filipino option
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
