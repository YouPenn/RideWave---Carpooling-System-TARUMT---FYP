<?php
include_once('../config/config.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"];

//// Check if user exists in the passenger table, insert if not
//$sql_check = "SELECT passengerID FROM passenger WHERE userID = ?";
//$stmt_check = $mysqli->prepare($sql_check);
//$stmt_check->bind_param("i", $userID);
//$stmt_check->execute();
//$stmt_check->store_result();
//
//if ($stmt_check->num_rows == 0) {
//    // User not found in passenger table, insert new record
//    $sql_insert = "INSERT INTO passenger (userID, status) VALUES (?, 'active')";
//    $stmt_insert = $mysqli->prepare($sql_insert);
//    $stmt_insert->bind_param("i", $userID);
//    $stmt_insert->execute();
//    $stmt_insert->close();
//}
//$stmt_check->close();

// Query to check if any required fields for the passenger profile are NULL, empty, or invalid
$sql = "SELECT studentID, username, email, phoneNumber, gender, dateOfBirth 
        FROM user
        WHERE userID = ?";

$redirectToProfile = false;

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($studentID, $username, $email, $phoneNumber, $gender, $dateOfBirth);
    $stmt->fetch();
    $stmt->close();

    // Check each field for NULL values, empty values, or invalid date (0000-00-00)
    if (is_null($studentID) || is_null($username) || is_null($email) || is_null($phoneNumber) || 
        is_null($gender) || $dateOfBirth == "0000-00-00" || is_null($dateOfBirth) ||
        empty($studentID) || empty($username) || empty($email) || empty($phoneNumber) || 
        empty($gender) || empty($dateOfBirth)) {
        $redirectToProfile = true;
    }
} else {
    // Handle query error
    echo "Error: Unable to execute query.";
}

// Close the database connection
//$mysqli->close();

// Display alert and redirect if any required fields are NULL, empty, or invalid
if ($redirectToProfile) {
    echo "<script>
            alert('Please complete all required information in your profile.');
            window.location.href = 'own_passenger_profile.php';
          </script>";
    exit();
}
?>
