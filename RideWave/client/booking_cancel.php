<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user's ID

// Get the trip ID from the URL parameter
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Redirect to history page if trip ID is not provided
if (!$tripID) {
    echo "<script>
        alert('Invalid trip ID.');
        window.location.href = 'trip_history.php';
    </script>";
    exit();
}

// Fetch passenger ID for the logged-in user
$passengerID = null;
$sql_passenger = "SELECT passengerID FROM passenger WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_passenger)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($passengerID);
    $stmt->fetch();
    $stmt->close();
}

if (!$passengerID) {
    echo "<script>
        alert('Passenger profile not found.');
        window.location.href = 'trip_history.php';
    </script>";
    exit();
}

// Fetch booking details, including status, driver details, and trip details
$bookingID = null;
$bookingStatus = null;
$driverEmail = null;
$driverName = null;
$startLocation = null;
$endLocation = null;
$startDateTime = null;

$sql_booking = "
    SELECT b.bookingID, b.status, u.email, u.username AS driverName, t.startLocation, t.endLocation, t.startDateTime
    FROM booking b
    JOIN trip t ON b.tripID = t.tripID
    JOIN driver d ON t.driverID = d.driverID
    JOIN user u ON d.userID = u.userID
    WHERE b.tripID = ? AND b.passengerID = ?";
if ($stmt = $mysqli->prepare($sql_booking)) {
    $stmt->bind_param("ii", $tripID, $passengerID);
    $stmt->execute();
    $stmt->bind_result($bookingID, $bookingStatus, $driverEmail, $driverName, $startLocation, $endLocation, $startDateTime);
    $stmt->fetch();
    $stmt->close();
}

if (!$bookingID) {
    echo "<script>
        alert('No booking found for this trip.');
        window.location.href = 'trip_history.php';
    </script>";
    exit();
}

// Fetch user ID and name of the passenger
$passengerUserID = null;
$passengerName = null;

$sql_user = "SELECT u.userID, u.username FROM passenger p JOIN user u ON p.userID = u.userID WHERE p.passengerID = ?";
if ($stmt = $mysqli->prepare($sql_user)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $stmt->bind_result($passengerUserID, $passengerName);
    $stmt->fetch();
    $stmt->close();
}

if (!$passengerUserID || !$passengerName) {
    echo "<script>
        alert('Failed to retrieve passenger details.');
        window.location.href = 'trip_history.php';
    </script>";
    exit();
}

// Check if there's an existing payment record for this booking ID
$sql_check_payment = "SELECT paymentID FROM payment WHERE bookingID = ?";
$paymentID = null;
if ($stmt = $mysqli->prepare($sql_check_payment)) {
    $stmt->bind_param("i", $bookingID);  // Use bookingID to check for payments
    $stmt->execute();
    $stmt->bind_result($paymentID);
    $stmt->fetch();
    $stmt->close();
}

if ($paymentID) {
    // Delete the related payment record if it exists
    $sql_delete_payment = "DELETE FROM payment WHERE bookingID = ?";
    if ($stmt = $mysqli->prepare($sql_delete_payment)) {
        $stmt->bind_param("i", $bookingID); // Delete payment related to the booking
        if (!$stmt->execute()) {
            echo "<script>
                alert('Failed to delete payment record. Please try again later.');
                window.location.href = 'trip_history.php';
            </script>";
            exit();
        }
        $stmt->close();
    } else {
        echo "<script>
            alert('An error occurred while deleting payment record.');
            window.location.href = 'trip_history.php';
        </script>";
        exit();
    }
}

// Delete the booking
$sql_delete_booking = "DELETE FROM booking WHERE bookingID = ? AND passengerID = ?";
if ($stmt = $mysqli->prepare($sql_delete_booking)) {
    $stmt->bind_param("ii", $bookingID, $passengerID); // Use bookingID for deleting booking
    if ($stmt->execute()) {
        if ($bookingStatus === 'approved') {
            // Send email to the driver
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';
                $mail->Password = 'nbcb anqx vzug lupd';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                $mail->addAddress($driverEmail, $driverName);

                $mail->isHTML(true);
                $mail->Subject = 'Passenger Unjoined Your Trip';
                $mail->Body = "
                    <p>Dear {$driverName},</p>
                    <p>The passenger (<strong>Passenger ID:</strong> {$passengerID}, <strong>Name:</strong> {$passengerName}) has changed their mind and unjoined the following trip:</p>
                    <ul>
                        <li><strong>Trip ID:</strong> {$tripID}</li>
                        <li><strong>From:</strong> {$startLocation}</li>
                        <li><strong>To:</strong> {$endLocation}</li>
                        <li><strong>Date & Time:</strong> {$startDateTime}</li>
                    </ul>
                    <p>Please update your records accordingly.</p>
                    <p>Regards,<br>RideWave Team</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                echo "<script>
                    alert('Request canceled, but email notification to the driver failed.');
                    window.location.href = 'trip_history.php';
                </script>";
                exit();
            }
        }

        echo "<script>
            alert('You have successfully cancel or unjoin the trip');
            window.location.href = 'trip_history.php';
        </script>";
    } else {
        echo "<script>
            alert('Failed to unjoin the trip. Please try again later.');
            window.location.href = 'trip_history.php';
        </script>";
    }
    $stmt->close();
} else {
    echo "<script>
        alert('An error occurred while processing your request.');
        window.location.href = 'trip_history.php';
    </script>";
}

$mysqli->close();
?>
