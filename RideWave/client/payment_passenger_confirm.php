<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_passenger_profile_check.php');

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

// Get the booking ID from the URL parameters
$bookingID = isset($_GET['bookingID']) ? intval($_GET['bookingID']) : null;


// Validate the logged-in user's access to this page
$sql_booking_passenger = "SELECT passengerID FROM booking WHERE bookingID = ?";
$bookingPassengerID = null;
if ($stmt = $mysqli->prepare($sql_booking_passenger)) {
    $stmt->bind_param("i", $bookingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Invalid booking ID.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $bookingPassengerID = $result->fetch_assoc()['passengerID'];
    $stmt->close();
}

// Get the passenger ID for the logged-in user
$sql_logged_passenger = "SELECT passengerID FROM passenger WHERE userID = ?";
$loggedPassengerID = null;
if ($stmt = $mysqli->prepare($sql_logged_passenger)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Passenger profile not found.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $loggedPassengerID = $result->fetch_assoc()['passengerID'];
    $stmt->close();
}

// Verify that the logged-in user's passenger ID matches the booking's passenger ID
if ($loggedPassengerID !== $bookingPassengerID) {
    echo "<script>
        alert('You are not authorized to access this page.');
        window.location.href = 'payment_list_passenger.php';
    </script>";
    exit();
}

// Redirect to dashboard if booking ID is not provided
if (!$bookingID) {
    echo "<script>
        alert('Invalid booking ID.');
        window.location.href = 'payment_list_passenger.php';
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
            window.location.href = 'payment_list_passenger.php';
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
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $trip = $result->fetch_assoc();
    $stmt->close();
}

// Fetch the passenger details using the logged-in user's ID
$sql_passenger = "SELECT username, email FROM user WHERE userID = ?";
$passenger = [];
if ($stmt = $mysqli->prepare($sql_passenger)) {
    $stmt->bind_param("i", $userID); // Bind logged-in userID
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Passenger not found.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $passenger = $result->fetch_assoc(); // Fetch passenger data
    $stmt->close();
}

// Fetch the driver's details using the tripID
$sql_driverID = "SELECT driverID FROM trip WHERE tripID = ?";
$driverID = null;
if ($stmt = $mysqli->prepare($sql_driverID)) {
    $stmt->bind_param("i", $trip['tripID']); // Bind tripID to fetch the driverID
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Driver not found.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $driverID = $result->fetch_assoc()['driverID']; // Get driverID
    $stmt->close();
}

// Fetch the driver's details using the driverID
$sql_driver_userID = "SELECT userID FROM driver WHERE driverID = ?";
$driverUserID = null;
if ($stmt = $mysqli->prepare($sql_driver_userID)) {
    $stmt->bind_param("i", $driverID); // Bind driverID to fetch the userID
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Driver not found in driver table.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $driverUserID = $result->fetch_assoc()['userID']; // Get userID
    $stmt->close();
}

// Fetch the driver's name and email using the driverUserID
$sql_driver = "SELECT username, email FROM user WHERE userID = ?";
$driver = [];
if ($stmt = $mysqli->prepare($sql_driver)) {
    $stmt->bind_param("i", $driverUserID); // Bind driverUserID to fetch name and email
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('Driver details not found.');
            window.location.href = 'payment_list_passenger.php';
        </script>";
        exit();
    }
    $driver = $result->fetch_assoc(); // Fetch driver data
    $stmt->close();
}

if (isset($_POST['confirm_payment'])) {
    
    if ($payment['status'] === 'completed') {
        echo "<script>
            alert('Payment is already marked as completed.');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    if ($payment['status'] === 'absent') {
        echo "<script>
            alert('The driver has marked you as absent. Please contact the driver for further clarification.');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
        </script>";
        exit();
    }
    
    $currentDate = date("Y-m-d H:i:s");
    
    if ($payment['status'] === 'no received') {
        $newStatus = 'pending';

        $sql_update_payment = "UPDATE payment SET passengerConfirmDate = ?, status = ? WHERE paymentID = ?";
        if ($stmt = $mysqli->prepare($sql_update_payment)) {
            $stmt->bind_param("ssi", $currentDate, $newStatus, $payment['paymentID']);
            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ourbus2003@gmail.com'; // SMTP username
                    $mail->Password = 'nbcb anqx vzug lupd';   // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                    $mail->addAddress($driver['email'], $driver['username']); // Driver email and name

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Payment Confirmation Updated';
                    $mail->Body = "Dear {$driver['username']},<br><br>The payment you marked as not received has been confirmed by the passenger for the trip. Please check and confirm, and if there is still a problem, contact the passenger.<br><br>
                    <strong>Trip Details:</strong><br>
                    Trip ID: {$trip['tripID']}<br>
                    From: {$trip['startLocation']}<br>
                    To: {$trip['endLocation']}<br><br>
                    <strong>Passenger:</strong> {$passenger['username']}<br>
                    Email: {$passenger['email']}<br><br>
                    Please log in to confirm the payment.";

                    // Send email
                    $mail->send();

                    echo "<script>
                        alert('Payment confirmation successful. Notification email sent to the driver.');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
                    </script>";
                } catch (Exception $e) {
                    echo "<script>
                        alert('Payment confirmation updated, but email failed to send: {$mail->ErrorInfo}');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
                    </script>";
                }
            } else {
                echo "<script>
                    alert('Failed to update payment status. Please try again.');
                </script>";
            }
            $stmt->close();
        }
    } else if ($payment['passengerConfirmDate'] === null) {

    $newStatus = ($payment['driverConfirmDate'] !== null) ? 'completed' : 'pending';

    $sql_update_payment = "UPDATE payment SET passengerConfirmDate = ?, status = ? WHERE paymentID = ?";
    if ($stmt = $mysqli->prepare($sql_update_payment)) {
        $stmt->bind_param("ssi", $currentDate, $newStatus, $payment['paymentID']);
        if ($stmt->execute()) {
            echo "<script>
                alert('Payment confirmation successful.');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
            </script>";
        } else {
            echo "<script>
                alert('Failed to confirm payment. Please try again.');
            </script>";
        }
        $stmt->close();
    }
} else {
    echo "<script>
        alert('Payment has already been confirmed.');
            window.location.href = 'payment_passenger_confirm.php?&bookingID=" . $bookingID . "';
    </script>";
}
}

if (isset($_POST['mark_refund'])) {
    // Check if the current status is 'absent' and passengerConfirmDate exists
    if ($payment['status'] === 'absent' && $payment['passengerConfirmDate'] !== null) {
        // Update the status to 'absent(refunded)'
        $newStatus = 'absent(refunded)';
        $sql_update_refund = "UPDATE payment SET status = ? WHERE paymentID = ?";
        
        if ($stmt = $mysqli->prepare($sql_update_refund)) {
            $stmt->bind_param("si", $newStatus, $payment['paymentID']);
            if ($stmt->execute()) {
                echo "<script>
                    alert('Payment status updated to absent(refunded).');
                    window.location.href = 'payment_passenger_confirm.php?bookingID=" . $bookingID . "';
                </script>";
            } else {
                echo "<script>
                    alert('Failed to update payment status. Please try again.');
                </script>";
            }
            $stmt->close();
        }
    } else {
        // If the conditions are not met, show an appropriate alert
        if ($payment['status'] !== 'absent') {
            echo "<script>
                alert('Payment status must be absent to mark it as refunded.');
                window.location.href = 'payment_passenger_confirm.php?bookingID=" . $bookingID . "';
            </script>";
        } elseif ($payment['passengerConfirmDate'] === null) {
            echo "<script>
                alert('Passenger confirmation date is missing. Cannot mark as refunded.');
                window.location.href = 'payment_passenger_confirm.php?bookingID=" . $bookingID . "';
            </script>";
        }
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
            z-index: 9999; /* Ensure it's above other elements */
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
    
    <!-- Spinner Overlay -->
    <div id="spinner-overlay" class="overlay">
        <div class="spinner"></div>
    </div>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Passenger /</span> Confirm Payment
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

            <!-- Payment Info -->
            <div class="card mb-4">
                <h5 class="card-header">Payment Status</h5>       
                <div class="card-body">
<p><strong>Status:</strong> <?php echo isset($payment['status']) ? strtoupper(htmlspecialchars($payment['status'])) : 'NOT SET'; ?></p>
                </div>
            </div>
            
<form method="POST" onsubmit="return showSpinner()" class="d-grid gap-2">
    <button type="submit" name="confirm_payment" class="btn btn-success w-100">Confirm Payment</button>
</form>
        </div>    
    </div>

    <?php include_once('footer.php'); ?>   

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show spinner and overlay
    function showSpinner() {
        document.getElementById("spinner-overlay").style.display = "flex";
        return true; // Ensure form submission continues
    }

    // Show warning message on page load
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: 'Be honest! Scammers will face the law.',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Understood'
        });
    });
</script>
</body>
</html>
