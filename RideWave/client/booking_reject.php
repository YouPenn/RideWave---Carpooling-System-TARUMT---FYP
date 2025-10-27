<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Ensure you have the correct path to PHPMailer

include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["userID"])) {
    echo "<script>
        alert('You must be logged in to perform this action.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user ID
$bookingID = isset($_GET['bookingID']) ? intval($_GET['bookingID']) : null;
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Ensure booking ID and trip ID are provided
if (!$bookingID || !$tripID) {
    echo "<script>
        alert('Invalid request.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Fetch the driver's ID associated with the logged-in user
$driverID = null;
$sql_driver = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_driver)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($driverID);
    $stmt->fetch();
    $stmt->close();
}

if (!$driverID) {
    echo "<script>
        alert('Driver profile not found.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Check the trip's status before proceeding with rejection
$tripStatus = null;
$sql_check_status = "SELECT status FROM trip WHERE tripID = ? AND driverID = ?";
if ($stmt = $mysqli->prepare($sql_check_status)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $stmt->bind_result($tripStatus);
    $stmt->fetch();
    $stmt->close();
}

// If the trip is not ongoing, prevent rejection
if ($tripStatus !== 'ongoing') {
    echo "<script>
        alert('You cannot reject a booking for this trip. The trip is not ongoing.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Verify if the trip belongs to the driver
$tripDetails = [];
$sql_check_owner = "SELECT startLocation, endLocation, startDateTime 
                    FROM trip 
                    WHERE tripID = ? AND driverID = ?";
if ($stmt = $mysqli->prepare($sql_check_owner)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $stmt->bind_result($startLocation, $endLocation, $startDateTime);
    if ($stmt->fetch()) {
        $tripDetails = [
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
        ];
    } else {
        echo "<script>
            alert('Unauthorized action.');
            window.location.href = 'driver_dashboard.php';
        </script>";
        exit();
    }
    $stmt->close();
}

// Fetch passenger details
$passengerEmail = $passengerName = null;
$sql_passenger = "SELECT u.email, u.username 
                  FROM booking b
                  JOIN passenger p ON b.passengerID = p.passengerID
                  JOIN user u ON p.userID = u.userID
                  WHERE b.bookingID = ? AND b.tripID = ?";
if ($stmt = $mysqli->prepare($sql_passenger)) {
    $stmt->bind_param("ii", $bookingID, $tripID);
    $stmt->execute();
    $stmt->bind_result($passengerEmail, $passengerName);
    $stmt->fetch();
    $stmt->close();
}

// Fetch driver's contact details
$driverContact = [];
$sql_driver_contact = "SELECT email, phoneNumber FROM user WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_driver_contact)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($driverEmail, $driverPhone);
    if ($stmt->fetch()) {
        $driverContact = [
            'email' => $driverEmail,
            'phone' => $driverPhone,
        ];
    }
    $stmt->close();
}

// Update the booking record status to "rejected"
$sql_update = "UPDATE booking SET status = 'rejected' WHERE bookingID = ? AND tripID = ?";
if ($stmt = $mysqli->prepare($sql_update)) {
    $stmt->bind_param("ii", $bookingID, $tripID);
    if ($stmt->execute()) {
        // Send email notification to the passenger
        if ($passengerEmail) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com'; // Replace with your email
                $mail->Password = 'nbcb anqx vzug lupd'; // Replace with your email password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                $mail->addAddress($passengerEmail, $passengerName);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Booking Request Rejected';
                $mail->Body = "
                    <p>Dear {$passengerName},</p>
                    <p>We regret to inform you that your request to join the trip has been rejected by the driver. (Possibly due to insufficient available seats.)</p>
                    <p><strong>Trip Details:</strong></p>
                    <ul>
                        <li>Trip ID: {$tripID}</li>
                        <li>From: {$tripDetails['startLocation']}</li>
                        <li>To: {$tripDetails['endLocation']}</li>
                        <li>Date and Time: {$tripDetails['startDateTime']}</li>
                    </ul>
                    <p>We sincerely apologize for the inconvenience caused. If you have any questions, please contact the driver:</p>
                    <ul>
                        <li>Email: {$driverContact['email']}</li>
                        <li>Phone: {$driverContact['phone']}</li>
                    </ul>
                    <p>Thank you for your understanding.</p>
                    <p>Regards,<br>RideWave Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>
                    alert('Booking rejected, but the email notification could not be sent. Error: {$mail->ErrorInfo}');
                    window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "';
                </script>";
                exit();
            }
        }

        echo "<script>
            alert('Booking rejected successfully and the passenger has been notified.');
            window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "';
        </script>";
    } else {
        echo "<script>
            alert('Error rejecting booking: " . addslashes($stmt->error) . "');
            window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "';
        </script>";
    }
    $stmt->close();
} else {
    echo "<script>
        alert('Error preparing the update query.');
        window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "';
    </script>";
}

$mysqli->close();
exit();
