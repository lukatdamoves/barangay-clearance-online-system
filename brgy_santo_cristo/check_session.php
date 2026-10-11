<?php
session_start();

//check the response if the login is false
$response = ["loggedIn" => false];

//check the session if set the user id to login to true
if (isset($_SESSION['user_id'])) {
    $response["loggedIn"] = true;
}

//return the response
echo json_encode($response);
?>
