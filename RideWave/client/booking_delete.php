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
$driverContact = null;
$sql_driver = "SELECT driverID, user.email AS driverEmail, user.phoneNumber AS driverPhone
               FROM driver 
               JOIN user ON driver.userID = user.userID 
               WHERE user.userID = ?";
if ($stmt = $mysqli->prepare($sql_driver)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($driverID, $driverEmail, $driverPhone);
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

// Verify if the trip belongs to the driver
$sql_check_owner = "SELECT startLocation, endLocation, startDateTime, status 
                    FROM trip 
                    WHERE tripID = ? AND driverID = ?";
$tripDetails = [];
if ($stmt = $mysqli->prepare($sql_check_owner)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $stmt->bind_result($startLocation, $endLocation, $startDateTime, $tripStatus);
    if ($stmt->fetch()) {
        $tripDetails = [
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
            'status' => $tripStatus, // Added tripStatus
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

// Check if the trip status is "ongoing"
if ($tripDetails['status'] !== 'ongoing') {
    echo "<script>
        alert('You can only delete bookings for ongoing trips.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
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

// Update the booking record status to "deleted"
$sql_update = "UPDATE booking SET status = 'deleted' WHERE bookingID = ? AND tripID = ?";
if ($stmt = $mysqli->prepare($sql_update)) {
    $stmt->bind_param("ii", $bookingID, $tripID);
    if ($stmt->execute()) {
        
        // Now, check if there is any payment record related to this bookingID and delete it
        $sql_check_payment = "SELECT paymentID FROM payment WHERE bookingID = ?";
        if ($stmt_payment = $mysqli->prepare($sql_check_payment)) {
            $stmt_payment->bind_param("i", $bookingID); // Use bookingID to check payments
            $stmt_payment->execute();
            $stmt_payment->store_result();
            
            // If there is a payment record, delete it
            if ($stmt_payment->num_rows > 0) {
                $sql_delete_payment = "DELETE FROM payment WHERE bookingID = ?";
                if ($stmt_delete_payment = $mysqli->prepare($sql_delete_payment)) {
                    $stmt_delete_payment->bind_param("i", $bookingID);
                    $stmt_delete_payment->execute();
                    $stmt_delete_payment->close();
                }
            }
            $stmt_payment->close();
        }
        
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
                $mail->Subject = 'Notice of Removal from Trip';
                $mail->Body = "
                    <p>Dear {$passengerName},</p>
                    <p>We regret to inform you that you have been removed from the trip.</p>
                    <p><strong>Trip Details:</strong></p>
                    <ul>
                        <li>Trip ID: {$tripID}</li>
                        <li>From: {$tripDetails['startLocation']}</li>
                        <li>To: {$tripDetails['endLocation']}</li>
                        <li>Date and Time: {$tripDetails['startDateTime']}</li>
                    </ul>
                    <p>We sincerely apologize for the inconvenience caused. If you have any questions, please contact the driver directly:</p>
                    <p><strong>Email:</strong> {$driverEmail}<br>
                       <strong>Phone:</strong> {$driverPhone}</p>
                    <p>Thank you for understanding.</p>
                    <p>Regards,<br>RideWave Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>
                    alert('Passenger removed successfully, but the email notification could not be sent. Error: {$mail->ErrorInfo}');
                    window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "';
                </script>";
                exit();
            }
        }

        echo "<script>
            alert('Passenger successfully marked as removed from the trip.');
            window.location.href = 'trip_driver_manage.php?tripID=" . urlencode($tripID) . "'; 
        </script>";
    } else {
        echo "<script>
            alert('Error updating booking status: " . addslashes($stmt->error) . "');
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
?>
