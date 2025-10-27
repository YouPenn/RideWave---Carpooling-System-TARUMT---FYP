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

// Get the driverID from the URL parameter
$driverID = isset($_GET['driverID']) ? intval($_GET['driverID']) : null;

// Redirect if driverID is not provided
if (!$driverID) {
    echo "<script>
        alert('Invalid driver ID.');
        window.location.href = 'error.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Fetch the userID associated with the driverID
$driverUserID = null;
$sql_driver_user = "SELECT userID FROM driver WHERE driverID = ?";
if ($stmt = $mysqli->prepare($sql_driver_user)) {
    $stmt->bind_param("i", $driverID);
    $stmt->execute();
    $stmt->bind_result($driverUserID);
    $stmt->fetch();
    $stmt->close();
}

// Redirect if no userID is found for the provided driverID
if (!$driverUserID) {
    echo "<script>
        alert('Driver not found.');
        window.location.href = 'error.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Check if the user is trying to blacklist themselves
if ($driverUserID === $loggedInUserID) {
    echo "<script>
        alert('You cannot blacklist yourself.');
        window.location.href = 'error.php'; // Replace with the appropriate redirect page
    </script>";
    exit();
}

// Check if the user has already blacklisted this user
$sql_check_blacklist = "SELECT role FROM blacklist WHERE userID = ? AND blacklistedID = ?";
$currentRole = null;

if ($stmt = $mysqli->prepare($sql_check_blacklist)) {
    $stmt->bind_param("ii", $loggedInUserID, $driverUserID);
    $stmt->execute();
    $stmt->bind_result($currentRole);
    $stmt->fetch();
    $stmt->close();
}

if ($currentRole !== null) {
    if ($currentRole === 'driver') {
        echo "<script>
            alert('This driver is already in your blacklist.');
            window.location.href = 'view_driver_profile.php?driverID={$driverID}';
        </script>";
        exit();
    } elseif ($currentRole === 'passenger') {
        // Update the role to driver
        $sql_update_role = "UPDATE blacklist SET role = 'driver' WHERE userID = ? AND blacklistedID = ?";
        if ($stmt = $mysqli->prepare($sql_update_role)) {
            $stmt->bind_param("ii", $loggedInUserID, $driverUserID);
            if ($stmt->execute()) {
                echo "<script>
                    alert('The role has been updated to driver in your blacklist.');
                    window.location.href = 'view_driver_profile.php?driverID={$driverID}';
                </script>";
            } else {
                echo "<script>
                    alert('Failed to update the role. Please try again later.');
                    window.location.href = 'view_driver_profile.php?driverID={$driverID}';
                </script>";
            }
            $stmt->close();
        }
        exit();
    }
}

// Define the role for the blacklisted user
$role = 'driver'; // Since this is specific to drivers

// Add the driver to the blacklist
$sql_blacklist = "INSERT INTO blacklist (userID, blacklistedID, role) VALUES (?, ?, ?)";
if ($stmt = $mysqli->prepare($sql_blacklist)) {
    $stmt->bind_param("iis", $loggedInUserID, $driverUserID, $role);
    if ($stmt->execute()) {
        echo "<script>
            alert('Driver successfully blacklisted.');
            window.location.href = 'view_driver_profile.php?driverID={$driverID}';
        </script>";
    } else {
        echo "<script>
            alert('Failed to blacklist the driver. Please try again later.');
            window.location.href = 'view_driver_profile.php?driverID={$driverID}';
        </script>";
    }
    $stmt->close();
} else {
    echo "<script>
        alert('An error occurred while processing your request.');
        window.location.href = 'view_driver_profile.php?driverID={$driverID}';
    </script>";
}

$mysqli->close();
?>
