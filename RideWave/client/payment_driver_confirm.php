<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_driver_profile_check.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["userID"])) {
    echo "<script>
        alert('You must be logged in to access this page.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user's ID

$navUserID = $_SESSION["userID"];

// Fetch user data from the database (For NAV)
$navsql = "SELECT username, userImg FROM user WHERE userID = ?";
if ($stmt = $mysqli->prepare($navsql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navUsername, $navUserImg);
    $stmt->fetch();
    $stmt->close();
} else {
    $navUsername = "Guest";
    $navUserImg = null;
}

// Use default avatar if user image is not set or empty
$navUserImgPath = $navUserImg ? $navUserImg : "../image/user_avatar.png";

// Fetch driver ID from the driver table using $navUserID
$navDriverId = null; // Default value if the user is not a driver
$driverSql = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($driverSql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navDriverId);
    $stmt->fetch();
    $stmt->close();
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

// Check if the driver exists
if (!$driverID) {
    echo "<script>
        alert('Driver profile not found.');
        window.location.href = 'payment_list_driver.php';
    </script>";
    exit();
}

// Get the booking ID from the URL parameters
$bookingID = isset($_GET['bookingID']) ? intval($_GET['bookingID']) : null;

// Validate if the logged-in driver is authorized to access this page
$sql_validate_driver = "
    SELECT t.driverID 
    FROM booking b 
    JOIN trip t ON b.tripID = t.tripID 
    WHERE b.bookingID = ?";
$authorizedDriverID = null;

if ($stmt = $mysqli->prepare($sql_validate_driver)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Invalid booking ID.');
            window.location.href = 'payment_list_driver.php';
        </script>";
        exit();
    }
    $authorizedDriverID = $result->fetch_assoc()['driverID'];
    $stmt->close();
}

// Check if the logged-in driver ID matches the driver ID associated with the trip
if ($authorizedDriverID !== $driverID) {
    echo "<script>
        alert('You are not authorized to access this page.');
        window.location.href = 'payment_list_driver.php';
    </script>";
    exit();
}

// Fetch the payment details for this booking
$sql_payment = "SELECT paymentID, driverConfirmDate, status, passengerConfirmDate FROM payment WHERE bookingID = ?";
$payment = [];
if ($stmt = $mysqli->prepare($sql_payment)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Payment not found.');
            window.location.href = 'payment_list_driver.php';
        </script>";
        exit();
    }
    $payment = $result->fetch_assoc();
    $stmt->close();
}

// Fetch the trip details
$sql_trip = "SELECT tripID, startLocation, endLocation FROM trip WHERE tripID = (SELECT tripID FROM booking WHERE bookingID = ?)";
$trip = [];
if ($stmt = $mysqli->prepare($sql_trip)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Trip not found.');
            window.location.href = 'payment_list_driver.php';
        </script>";
        exit();
    }
    $trip = $result->fetch_assoc();
    $stmt->close();
}

// Fetch the passenger's details
$sql_passenger = "SELECT user.username, user.email, user.phoneNumber 
                  FROM passenger 
                  JOIN user ON passenger.userID = user.userID 
                  WHERE passenger.passengerID = (SELECT passengerID FROM booking WHERE bookingID = ?)";
$passenger = [];
if ($stmt = $mysqli->prepare($sql_passenger)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Passenger not found.');
            window.location.href = 'payment_list_driver.php';
        </script>";
        exit();
    }
    $passenger = $result->fetch_assoc();
    $stmt->close();
}

// Fetch the driver's details
$sql_driver_user = "SELECT user.username, user.email, user.phoneNumber 
                    FROM driver 
                    JOIN user ON driver.userID = user.userID 
                    WHERE driver.driverID = ?";
$driver = [];
if ($stmt = $mysqli->prepare($sql_driver_user)) {
    $stmt->bind_param("i", $driverID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Driver information not found.');
            window.location.href = 'payment_list_driver.php';
        </script>";
        exit();
    }
    $driver = $result->fetch_assoc();
    $stmt->close();
}

// Handle the confirm payment action
if (isset($_POST['confirm_payment'])) {
    // Check if driverConfirmDate is already set
    if ($payment['driverConfirmDate'] !== null) {
        echo "<script>
            alert('You already confirmed the payment.');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    // Check if the status is already 'absent(refunded)'
    if ($payment['status'] === 'absent(refunded)') {
        echo "<script>
            alert('The passenger is already marked as absent(refunded).');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }

    // If not already confirmed, update the driverConfirmDate
    $currentDate = date("Y-m-d H:i:s");
    $status = ($payment['passengerConfirmDate'] === null) ? 'pending' : 'completed';

    // Update the payment table with driver confirmation and status
    $sql_confirm_payment = "UPDATE payment SET driverConfirmDate = ?, status = ? WHERE paymentID = ?";
    if ($stmt = $mysqli->prepare($sql_confirm_payment)) {
        $stmt->bind_param("ssi", $currentDate, $status, $payment['paymentID']);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>
            alert('Payment confirmed.');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
}

// Handle the "No Received" action
if (isset($_POST['no_received'])) {
    
    // Check if the status is already 'no received'
    if ($payment['status'] === 'no received') {
        echo "<script>
            alert('The payment status is already marked as No Received.');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    // Check if the status is already 'absent(refunded)'
    if ($payment['status'] === 'absent(refunded)') {
        echo "<script>
            alert('The passenger is already marked as absent(refunded).');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    // Set the status to 'no received' and nullify driverConfirmDate
    $sql_no_received = "UPDATE payment SET status = 'no received', driverConfirmDate = NULL WHERE paymentID = ?";
    if ($stmt = $mysqli->prepare($sql_no_received)) {
        $stmt->bind_param("i", $payment['paymentID']);
        $stmt->execute();
        $stmt->close();
        
        // Fetch the passenger's email from the database
        $sql_passenger_email = "SELECT user.email FROM passenger JOIN user ON passenger.userID = user.userID WHERE passenger.passengerID = (SELECT passengerID FROM booking WHERE bookingID = ?)";
        $passengerEmail = '';
        if ($stmt = $mysqli->prepare($sql_passenger_email)) {
            $stmt->bind_param("i", $bookingID);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $passenger = $result->fetch_assoc();
                $passengerEmail = $passenger['email'];
            }
            $stmt->close();
        }

        // Fetch the driver's email and phone number
        $driverEmail = '';
        $driverPhone = '';
        $sql_driver_contact = "SELECT user.email, user.phoneNumber FROM driver JOIN user ON driver.userID = user.userID WHERE driver.driverID = ?";
        if ($stmt = $mysqli->prepare($sql_driver_contact)) {
            $stmt->bind_param("i", $driverID);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $driver = $result->fetch_assoc();
                $driverEmail = $driver['email'];
                $driverPhone = $driver['phoneNumber'];
            }
            $stmt->close();
        }

        // Send email to passenger (informing about the "No Received" status)
        if (!empty($passengerEmail)) {
            // Create PHPMailer instance
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';  // Set the SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';  // SMTP username
                $mail->Password = 'nbcb anqx vzug lupd';  // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                $mail->addAddress($passengerEmail); // Add passenger email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Driver Did Not Receive Your Payment';
                $mail->Body = 'Dear Passenger, <br><br> Your payment has been marked as "No Received". Please check your payment status and contact the driver for further details. <br><br><strong>Driver Contact:</strong><br>Email: ' . $driverEmail . '<br>Phone: ' . $driverPhone;

                // Send email to passenger
                $mail->send();
            } catch (Exception $e) {
                echo "Mailer Error: {$mail->ErrorInfo}";
            }
        }

        echo "<script>
            alert('Passenger marked as No Received.');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
    }
}



// Handle the absent action
if (isset($_POST['mark_absent'])) {
    
    // Check if the status is already 'absent' or 'absent(refunded)'
    if ($payment['status'] === 'absent' || $payment['status'] === 'absent(refunded)') {
        echo "<script>
            alert('The passenger is already marked as absent or absent(refunded).');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    // Set the status to 'absent' and nullify driverConfirmDate
    $sql_mark_absent = "UPDATE payment SET status = 'absent', driverConfirmDate = NULL WHERE paymentID = ?";
    if ($stmt = $mysqli->prepare($sql_mark_absent)) {
        $stmt->bind_param("i", $payment['paymentID']);
        $stmt->execute();
        $stmt->close();
        
        // Check if passenger has already confirmed payment
        if ($payment['passengerConfirmDate'] !== null) {
            // Send email to driver
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';
                $mail->Password = 'nbcb anqx vzug lupd';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Email to Driver
                $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                $mail->addAddress($driver['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Absent Notification';
                $mail->Body = "Dear Driver, <br><br>The passenger has been marked as absent but payment has been confirmed. Initiate a refund if necessary.<br><br>Passenger Contact:<br>Email: {$passenger['email']}<br>Phone: {$passenger['phoneNumber']}";

                $mail->send();

                // Email to Passenger
                $mail->clearAddresses();
                $mail->addAddress($passenger['email']);
                $mail->Subject = 'Absent Notification';
                $mail->Body = "Dear Passenger, <br><br>Your payment has been marked as absent but you have confirmed the payment. Please contact the driver for a refund if necessary.<br><br>Driver Contact:<br>Email: {$driver['email']}<br>Phone: {$driver['phoneNumber']}";

                $mail->send();
            } catch (Exception $e) {
                echo "Mailer Error: {$mail->ErrorInfo}";
            }
        }

        echo "<script>
            alert('Passenger marked as absent.');
            window.location.href = 'payment_driver_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
}



?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Payment Confirmation</title>
    <style>
        /* Spinner and overlay styles */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Make sure it's above other elements */
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Driver /</span> Confirm Payment
            </h4> 

            <!-- Trip Info -->
            <div class="card mb-4">
                <h5 class="card-header">Trip Details</h5>
                <div class="card-body">
                    <p><strong>Trip ID:</strong> <?php echo htmlspecialchars($trip['tripID']); ?></p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($trip['startLocation']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($trip['endLocation']); ?></p>
                    <p><strong>Driver Name:</strong> <?php echo htmlspecialchars($driver['username']); ?></p>
                    <p><strong>Driver Email:</strong> <?php echo htmlspecialchars($driver['email']); ?></p>
                    <p><strong>Driver Confirm Date:</strong> <?php echo isset($payment['driverConfirmDate']) ? htmlspecialchars($payment['driverConfirmDate']) : 'Not confirmed yet'; ?></p>
                </div>
            </div>

            <!-- Passenger Info -->
            <div class="card mb-4">
                <h5 class="card-header">Passenger Details</h5>
                <div class="card-body">
                    <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($bookingID); ?></p>
                    <p><strong>Passenger Name:</strong> <?php echo htmlspecialchars($passenger['username']); ?></p>
                    <p><strong>Passenger Email:</strong> <?php echo htmlspecialchars($passenger['email']); ?></p>
                    <p><strong>Passenger Confirm Date:</strong> <?php echo isset($payment['passengerConfirmDate']) ? htmlspecialchars($payment['passengerConfirmDate']) : 'Not confirmed yet'; ?></p>
                </div>
            </div>

            <!-- Payment Status -->
            <form method="POST">
                <div class="card mb-4">
                <h5 class="card-header">Payment Status</h5>       
                    <div class="card-body">
<p><strong>Status:</strong> <?php echo isset($payment['status']) ? strtoupper(htmlspecialchars($payment['status'])) : 'NOT SET'; ?></p>
                    </div>
                </div>
            </form>
            
<!-- Action Buttons -->
<form method="POST" onsubmit="return showSpinner()">
    <div class="d-flex justify-content-between">
        <button type="submit" name="confirm_payment" class="btn btn-success w-100 me-2">Confirm Payment</button>
        <button type="submit" name="no_received" class="btn btn-warning w-100 me-2">No Received</button>
        <button type="submit" name="mark_absent" class="btn btn-danger w-100">Mark as Absent</button>
    </div>
</form>

        </div>    
    </div>

    <?php include_once('footer.php'); ?>     

    <!-- Spinner Overlay -->
    <div id="spinner-overlay" class="overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Show spinner overlay when form is submitted
        function showSpinner() {
            document.getElementById("spinner-overlay").style.display = "flex";
            return true; // Allow form submission
        }
    </script>
</body>
</html>
