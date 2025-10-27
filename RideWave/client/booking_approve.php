<?php
// Import PHPMailer and FPDF classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Adjust the path based on your project structure

include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a driver
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["userID"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user ID

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

// Get booking and trip IDs from the URL parameters
$bookingID = isset($_GET['bookingID']) ? intval($_GET['bookingID']) : null;
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Ensure booking ID and trip ID are provided
if (!$bookingID || !$tripID) {
    echo "<script>
        alert('Invalid booking or trip ID.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Verify the trip's status before allowing approval
$sql_check_status = "SELECT status FROM trip WHERE tripID = ? AND driverID = ?";
$tripStatus = null;

if ($stmt = $mysqli->prepare($sql_check_status)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $stmt->bind_result($tripStatus);
    $stmt->fetch();
    $stmt->close();
}

// Ensure the trip is ongoing before allowing approval
if ($tripStatus != 'ongoing') {
    echo "<script>
        alert('Cannot approve booking. The trip is not ongoing.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Verify if the trip belongs to the logged-in driver
$sql_verify_trip = "SELECT tripID, availableSeat FROM trip WHERE tripID = ? AND driverID = ?";
$tripDetails = [];

if ($stmt = $mysqli->prepare($sql_verify_trip)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Unauthorized access or invalid trip.');
            window.location.href = 'driver_dashboard.php';
        </script>";
        exit();
    }
    $tripDetails = $result->fetch_assoc();
    $stmt->close();
} else {
    echo "<script>
        alert('Error verifying trip.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Fetch the userID of the passenger for this booking
$sql_fetch_user = "SELECT p.userID FROM booking b
                   JOIN passenger p ON b.passengerID = p.passengerID
                   WHERE b.bookingID = ?";
$passengerUserID = null;

if ($stmt = $mysqli->prepare($sql_fetch_user)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $stmt->bind_result($passengerUserID);
    $stmt->fetch();
    $stmt->close();
}

// Check if the passenger is blacklisted by the driver
$sql_check_blacklist = "SELECT COUNT(*) FROM blacklist WHERE userID = ? AND blacklistedID = ?";
$isBlacklisted = 0;

if ($stmt = $mysqli->prepare($sql_check_blacklist)) {
    $stmt->bind_param("ii", $userID, $passengerUserID);
    $stmt->execute();
    $stmt->bind_result($isBlacklisted);
    $stmt->fetch();
    $stmt->close();
}

if ($isBlacklisted > 0) {
    echo "<script>
        alert('Cannot approve booking. The passenger is in your blacklist.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Check the number of approved passengers
$sql_count_approved = "SELECT COUNT(*) AS approvedCount FROM booking WHERE tripID = ? AND status = 'approved'";
$approvedCount = 0;

if ($stmt = $mysqli->prepare($sql_count_approved)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $stmt->bind_result($approvedCount);
    $stmt->fetch();
    $stmt->close();
}

// Check if there are enough available seats
if ($approvedCount >= $tripDetails['availableSeat']) {
    echo "<script>
        alert('Cannot approve booking. Not enough available seats.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Fetch trip and passenger details
$sql = "SELECT b.bookingID, t.startLocation, t.endLocation, t.startDateTime, 
               u.username AS passengerName, u.email AS passengerEmail 
        FROM booking b
        JOIN trip t ON b.tripID = t.tripID
        JOIN passenger p ON b.passengerID = p.passengerID
        JOIN user u ON p.userID = u.userID
        WHERE b.bookingID = ? AND t.driverID = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ii", $bookingID, $driverID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Invalid booking or unauthorized access.');
            window.location.href = 'driver_dashboard.php';
        </script>";
        exit();
    }
    $tripDetails = $result->fetch_assoc();
    $stmt->close();
}

// Update the booking status to "approved"
$sql_update = "UPDATE booking SET status = 'approved' WHERE bookingID = ?";
if ($stmt = $mysqli->prepare($sql_update)) {
    $stmt->bind_param("i", $bookingID);
    if (!$stmt->execute()) {
        echo "<script>
            alert('Error approving booking: " . addslashes($stmt->error) . "');
            window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
        </script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>
        alert('Error preparing statement.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Insert bookingID into Payment table
$sql_insert_payment = "INSERT INTO Payment (bookingID) VALUES (?)";
if ($stmt = $mysqli->prepare($sql_insert_payment)) {
    $stmt->bind_param("i", $bookingID);
    if (!$stmt->execute()) {
        echo "<script>
            alert('Error inserting payment record: " . addslashes($stmt->error) . "');
            window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
        </script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>
        alert('Error preparing payment insert statement.');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Generate the ticket using FPDF
$ticketFilePath = "../tickets/ticket_" . $bookingID . ".pdf";
if (!file_exists("../tickets")) {
    mkdir("../tickets", 0777, true); // Create the directory if it doesn't exist
}

require('../vendor/setasign/fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();

// Header Design
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 10, 'RideWave Ticket', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Thank you for riding with us!', 0, 1, 'C');
$pdf->Ln(10);

// Trip Information Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 10, 'Trip Details', 0, 1, 'L', true);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Trip ID:', 0, 0);
$pdf->Cell(100, 10, $tripID, 0, 1);
$pdf->Cell(50, 10, 'From:', 0, 0);

// Use MultiCell for startLocation
$pdf->SetX(60); // Adjust X position to align properly
$pdf->MultiCell(100, 10, $tripDetails['startLocation'], 0, 'L');

$pdf->Cell(50, 10, 'To:', 0, 0);
// Use MultiCell for endLocation
$pdf->SetX(60); // Adjust X position to align properly
$pdf->MultiCell(100, 10, $tripDetails['endLocation'], 0, 'L');

$pdf->Cell(50, 10, 'Date & Time:', 0, 0);
$pdf->Cell(100, 10, $tripDetails['startDateTime'], 0, 1);
$pdf->Ln(5);

// Passenger Information Section
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(0, 10, 'Passenger Details', 0, 1, 'L', true);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Passenger Name:', 0, 0);
$pdf->Cell(100, 10, $tripDetails['passengerName'], 0, 1);
$pdf->Cell(50, 10, 'Email:', 0, 0);
$pdf->Cell(100, 10, $tripDetails['passengerEmail'], 0, 1);
$pdf->Cell(50, 10, 'Booking ID:', 0, 0);
$pdf->Cell(100, 10, $bookingID, 0, 1);
$pdf->Ln(10);

// Footer
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'RideWave - Reliable & Comfortable Rides', 0, 1, 'C');
$pdf->Cell(0, 10, 'Customer Support: teeyp-jm21@student.tarc.edu.my | +601110721617', 0, 1, 'C');
$pdf->Output('F', $ticketFilePath);

// Send email with PHPMailer
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
    $mail->addAddress($tripDetails['passengerEmail'], $tripDetails['passengerName']);
    $mail->addAttachment($ticketFilePath);

    $mail->isHTML(true);
    $mail->Subject = 'Your RideWave Ticket';
    $mail->Body = "
        <p>Dear {$tripDetails['passengerName']},</p>
        <p>Your booking for the trip has been approved. Please find your ticket attached.</p>
        <p><strong>Trip Details:</strong></p>
        <ul>
            <li>Trip ID: {$tripID}</li>
            <li>From: {$tripDetails['startLocation']}</li>
            <li>To: {$tripDetails['endLocation']}</li>
            <li>Date and Time: {$tripDetails['startDateTime']}</li>
        </ul>
        <p>Thank you for choosing RideWave!</p>
        <p>Regards,<br>RideWave Team</p>
    ";

    $mail->send();
} catch (Exception $e) {
    echo "<script>
        alert('Booking approved, but email could not be sent. Error: {$mail->ErrorInfo}');
        window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
    </script>";
    exit();
}

// Redirect back to the trip management page
echo "<script>
    alert('Booking approved and ticket emailed to passenger successfully!');
    window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
</script>";
exit();
?>
