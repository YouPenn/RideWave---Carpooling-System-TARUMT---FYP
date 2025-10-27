<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear the remember_token cookie if it exists
if (isset($_COOKIE["remember_token"])) {
    setcookie("remember_token", "", time() - 3600, "/"); // Expire the cookie immediately
}

// Include the database configuration
include_once('../config/config.php');

// Remove the remember_token from the database if the user was logged in
if (isset($_SESSION["userID"])) {
    $userID = $_SESSION["userID"];
    $sql = "UPDATE user SET remember_token = NULL WHERE userID = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->close();
    }
    $mysqli->close();
}

// Redirect to the login page
header("Location: login.php");
exit();
?>
