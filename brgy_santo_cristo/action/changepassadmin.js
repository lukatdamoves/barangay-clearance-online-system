document.getElementById("changePasswordForm").addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent default form submission
    console.log("Change Password button clicked!");

    let formData = new FormData(this);
    formData.append("change_password", "1"); // Ensure PHP detects it

    fetch("../change_password.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json(); // Directly parse JSON response
    })
    .then(data => {
        console.log("Server Response:", data);

        // Show an alert instead of updating messageBox
        alert(data.message);

        if (data.status === "success") {
            window.location.href = "../structure/profileadmin.php"; // Redirect after success
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert("An error occurred. Please try again.");
    });
});



function openModal() {
    let modal = document.getElementById('changePasswordModal');
    console.log("openModal() called!");

    modal.style.display = "flex"; // Ensure it shows
    modal.classList.add('active');

    console.log("After opening, modal classes: ", modal.classList);
}


function closeModal() {
    let modal = document.getElementById('changePasswordModal');
    console.log("closeModal() called!");
    
    modal.classList.remove('active');
    modal.style.display = "none"; // Force hiding

    console.log("After closing, modal classes: ", modal.classList);
}

// Password Visibility Toggle
function togglePassword(inputId, button) {
    let input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        button.innerHTML = "👁️"; // Eye with slash (password visible)
    } else {
        input.type = "password";
        button.innerHTML = "👁️‍🗨️"; // Regular eye (password hidden)
    }
}
