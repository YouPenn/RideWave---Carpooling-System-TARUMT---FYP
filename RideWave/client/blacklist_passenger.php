<?php
include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    echo "<script>
        alert('You must be logged in to access this page.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

$loggedInUserID = $_SESSION["userID"]; // Logged-in user's ID

// Get the passengerID from the URL parameter
$passengerID = isset($_GET['passengerID']) ? intval($_GET['passengerID']) : null;

// Redirect if passengerID is not provided
if (!$passengerID) {
    echo "<script>
        alert('Invalid passenger ID.');
        window.location.href = 'view_passenger_profile.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Fetch the userID associated with the passengerID
$passengerUserID = null;
$sql_passenger_user = "SELECT userID FROM passenger WHERE passengerID = ?";
if ($stmt = $mysqli->prepare($sql_passenger_user)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $stmt->bind_result($passengerUserID);
    $stmt->fetch();
    $stmt->close();
}

// Redirect if no userID is found for the provided passengerID
if (!$passengerUserID) {
    echo "<script>
        alert('Passenger not found.');
        window.location.href = 'view_passenger_profile.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Check if the user is trying to blacklist themselves
if ($passengerUserID === $loggedInUserID) {
    echo "<script>
        alert('You cannot blacklist yourself.');
        window.location.href = 'view_passenger_profile.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Check if the user has already blacklisted this user
$sql_check_blacklist = "SELECT role FROM blacklist WHERE userID = ? AND blacklistedID = ?";
$currentRole = null;

if ($stmt = $mysqli->prepare($sql_check_blacklist)) {
    $stmt->bind_param("ii", $loggedInUserID, $passengerUserID);
    $stmt->execute();
    $stmt->bind_result($currentRole);
    $stmt->fetch();
    $stmt->close();
}

if ($currentRole !== null) {
    if ($currentRole === 'passenger') {
        echo "<script>
            alert('This passenger is already in your blacklist.');
            window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
        </script>";
        exit();
    } elseif ($currentRole === 'driver') {
        // Update the role to passenger
        $sql_update_role = "UPDATE blacklist SET role = 'passenger' WHERE userID = ? AND blacklistedID = ?";
        if ($stmt = $mysqli->prepare($sql_update_role)) {
            $stmt->bind_param("ii", $loggedInUserID, $passengerUserID);
            if ($stmt->execute()) {
                echo "<script>
                    alert('The role has been updated to passenger in your blacklist.');
                    window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
                </script>";
            } else {
                echo "<script>
                    alert('Failed to update the role. Please try again later.');
                    window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
                </script>";
            }
            $stmt->close();
        }
        exit();
    }
}

// Define the role for the blacklisted user
$role = 'passenger'; // Since this is specific to passengers

// Add the passenger to the blacklist
$sql_blacklist = "INSERT INTO blacklist (userID, blacklistedID, role) VALUES (?, ?, ?)";
if ($stmt = $mysqli->prepare($sql_blacklist)) {
    $stmt->bind_param("iis", $loggedInUserID, $passengerUserID, $role);
    if ($stmt->execute()) {
        echo "<script>
            alert('Passenger successfully blacklisted.');
            window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
        </script>";
    } else {
        echo "<script>
            alert('Failed to blacklist the passenger. Please try again later.');
            window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
        </script>";
    }
    $stmt->close();
} else {
    echo "<script>
        alert('An error occurred while processing your request.');
        window.location.href = 'view_passenger_profile.php?passengerID={$passengerID}';
    </script>";
}

$mysqli->close();
?>
