document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuWrapper = document.getElementById('mobile-menu-wrapper');

    // Ensure it starts hidden
    menuWrapper.style.display = "none";  

    // Open sidebar
    menuToggle.addEventListener('click', function(event) {
        event.stopPropagation();
        menuWrapper.style.display = "block";
        requestAnimationFrame(() => {
            mobileMenu.style.transform = "translateX(0)";
        });
    });

    // Close sidebar when clicking outside
    menuWrapper.addEventListener('click', function(event) {
        if (event.target === menuWrapper) {
            mobileMenu.style.transform = "translateX(100%)";
            setTimeout(() => {
                menuWrapper.style.display = "none";
            }, 300);
        }
    });

    // Prevent clicks inside sidebar from closing it
    mobileMenu.addEventListener('click', function(event) {
        event.stopPropagation();
    });

    // Close menu when window is resized to desktop mode
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            menuWrapper.style.display = "none";
            mobileMenu.style.transform = "translateX(100%)";
        }
    });

});


    document.getElementById("applyclearance-BUTTON").addEventListener("click", function() {
        fetch("../check_session.php")
            .then(response => response.json()) // Handle as plain text
            .then(data => {
        
                let message = '';
                let redirectUrl = '';
        
                // Check if the user is logged in
                if (data.loggedIn) {
                    message = "Redirecting to Services Page...";
                    redirectUrl = "../structure/servicespage.php";
                } else {
                    // If not logged in, show message and redirect to login page
                    message = "You must log in to apply for clearance...";
                    redirectUrl = "../structure/loginpage.php";
                }
        
                // Create a div element to display the message
                const messageDiv = document.createElement("div");
                messageDiv.textContent = message;
                messageDiv.style.position = "fixed";
                messageDiv.style.top = "20px";
                messageDiv.style.left = "50%";
                messageDiv.style.transform = "translateX(-50%)";
                messageDiv.style.padding = "10px";
                messageDiv.style.backgroundColor = "#4CAF50"; 
                messageDiv.style.color = "white";
                messageDiv.style.borderRadius = "5px";
                messageDiv.style.fontSize = "16px";
                messageDiv.style.zIndex = "9999";
                document.body.appendChild(messageDiv);
        
                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000); // Redirect after 2 seconds
            })
            .catch(error => {
                console.error("Error during session check:", error);
            });
    });
