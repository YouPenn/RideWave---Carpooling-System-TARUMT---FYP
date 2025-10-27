<?php
// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Adjust the path based on your project structure

include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_driver_profile_check.php');

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

//Update ongoing trips to "completed" if startDateTime has passed
$currentDateTime = date("Y-m-d H:i:s"); // Current date and time
$sql_update_trips = "UPDATE trip SET status = 'completed' WHERE status = 'ongoing' AND startDateTime <= ?";
if ($stmt = $mysqli->prepare($sql_update_trips)) {
    $stmt->bind_param("s", $currentDateTime);
    $stmt->execute();
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

if (!$driverID) {
    echo "<script>
        alert('Driver profile not found.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Get the trip ID from the URL parameter
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Redirect to dashboard if trip ID is not provided
if (!$tripID) {
    echo "<script>
        alert('Invalid trip ID.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Verify if the trip belongs to the logged-in driver
$sql_check_owner = "SELECT tripID, startLocation, endLocation, pickupLocation, startDateTime, amount, status, availableSeat
                    FROM trip WHERE tripID = ? AND driverID = ?";
$trip = [];


if ($stmt = $mysqli->prepare($sql_check_owner)) {
    $stmt->bind_param("ii", $tripID, $driverID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<script>
            alert('You are not authorized to manage this trip.');
            window.location.href = 'driver_dashboard.php';
        </script>";
        exit();
    }
    $trip = $result->fetch_assoc();
    $stmt->close();
}

$discountedAmount = $trip['amount'] * 2 / 3;


// Fetch requested passengers
$requestedPassengers = [];
$sql_requested = "SELECT booking.bookingID, booking.status, passenger.passengerID, user.username, user.email 
                  FROM booking
                  JOIN passenger ON booking.passengerID = passenger.passengerID
                  JOIN user ON passenger.userID = user.userID
                  WHERE booking.tripID = ? AND booking.status = 'pending'";

if ($stmt = $mysqli->prepare($sql_requested)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $requestedPassengers[] = $row;
    }
    $stmt->close();
}

// Fetch approved passengers
$approvedPassengers = [];
$sql_approved = "SELECT booking.bookingID, passenger.passengerID, user.username, user.email 
                 FROM booking
                 JOIN passenger ON booking.passengerID = passenger.passengerID
                 JOIN user ON passenger.userID = user.userID
                 WHERE booking.tripID = ? AND booking.status = 'approved'";

if ($stmt = $mysqli->prepare($sql_approved)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $approvedPassengers[] = $row;
    }
    $stmt->close();
}

// Check if approved passengers exceed available seats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['approve']) && isset($_GET['bookingID'])) {
    $bookingID = intval($_GET['bookingID']);
    $approvedCount = count($approvedPassengers);

    if ($approvedCount >= $trip['availableSeat']) {
        echo "<script>
            alert('Cannot approve passenger. Maximum number of passengers has been reached.');
            window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
        </script>";
        exit();
    }

    // Approve the passenger
    $sql_approve = "UPDATE booking SET status = 'approved' WHERE bookingID = ?";
    if ($stmt = $mysqli->prepare($sql_approve)) {
        $stmt->bind_param("i", $bookingID);
        if ($stmt->execute()) {
            echo "<script>
                alert('Passenger approved successfully.');
                window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
            </script>";
        } else {
            echo "<script>
                alert('Error approving passenger: " . addslashes($stmt->error) . "');
                window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
            </script>";
        }
        $stmt->close();
    }
}

// Cancel Trip Logic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_trip"])) {
    // Check the trip's current status
    if ($trip['status'] === 'completed' || $trip['status'] === 'canceled') {
        echo "<script>
            alert('Cannot cancel a trip that is already completed or canceled.');
            window.location.href = 'trip_driver_manage.php?tripID={$tripID}';
        </script>";
        exit();
    }

    $sql_update_trip = "UPDATE trip SET status = 'canceled' WHERE tripID = ? AND driverID = ?";
    if ($stmt = $mysqli->prepare($sql_update_trip)) {
        $stmt->bind_param("ii", $tripID, $driverID);
        if ($stmt->execute()) {
            // Fetch driver email
            $driverEmail = null;
            $sql_driver_email = "SELECT email FROM user WHERE userID = ?";
            if ($stmt_email = $mysqli->prepare($sql_driver_email)) {
                $stmt_email->bind_param("i", $userID);
                $stmt_email->execute();
                $stmt_email->bind_result($driverEmail);
                $stmt_email->fetch();
                $stmt_email->close();
            }

            // Fetch emails of all passengers (approved and requested)
            $passengerEmails = [];
            $sql_passenger_emails = "SELECT DISTINCT user.email 
                                     FROM booking
                                     JOIN passenger ON booking.passengerID = passenger.passengerID
                                     JOIN user ON passenger.userID = user.userID
                                     WHERE booking.tripID = ?";
            if ($stmt_passenger_emails = $mysqli->prepare($sql_passenger_emails)) {
                $stmt_passenger_emails->bind_param("i", $tripID);
                $stmt_passenger_emails->execute();
                $result = $stmt_passenger_emails->get_result();
                while ($row = $result->fetch_assoc()) {
                    $passengerEmails[] = $row['email'];
                }
                $stmt_passenger_emails->close();
            }

            // Send email notifications using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';
                $mail->Password = 'nbcb anqx vzug lupd';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Email to Driver
                if ($driverEmail) {
                    $mail->setFrom('your_email@gmail.com', 'RideWave');
                    $mail->addAddress($driverEmail);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Trip has been Canceled';
                    $mail->Body = "
                        <p>Dear Driver,</p>
                        <p>Your trip with ID: {$tripID} has been successfully canceled.</p>
                        <p><strong>Trip Details:</strong></p>
                        <ul>
                            <li><strong>From:</strong> " . htmlspecialchars($trip['startLocation']) . "</li>
                            <li><strong>To:</strong> " . htmlspecialchars($trip['endLocation']) . "</li>
                            <li><strong>Date & Time:</strong> " . htmlspecialchars($trip['startDateTime']) . "</li>
                        </ul>
                        <p>Regards,<br>RideWave Team</p>
                    ";
                    $mail->send();
                    $mail->clearAddresses();
                }

                // Email to Passengers
                foreach ($passengerEmails as $email) {
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Trip Canceled Notification';
                    $mail->Body = "
                        <p>Dear Passenger,</p>
                        <p>We regret to inform you that the trip you were part of has been canceled by the driver.</p>
                        <p><strong>Trip Details:</strong></p>
                        <ul>
                            <li><strong>Trip ID:</strong> {$tripID}</li>
                            <li><strong>From:</strong> " . htmlspecialchars($trip['startLocation']) . "</li>
                            <li><strong>To:</strong> " . htmlspecialchars($trip['endLocation']) . "</li>
                            <li><strong>Date & Time:</strong> " . htmlspecialchars($trip['startDateTime']) . "</li>
                        </ul>
                        <p>We apologize for the inconvenience. Contact support if needed.</p>
                        <p>Regards,<br>RideWave Team</p>
                    ";
                    $mail->send();
                    $mail->clearAddresses();
                }

echo "<script>
    alert('Trip successfully canceled. Notifications sent.');
    window.location.href = 'driver_dashboard.php';
</script>";
exit();

            } catch (Exception $e) {
                echo "Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error canceling trip: " . $stmt->error;
        }
        $stmt->close();
    }
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Driver Manage Trip</title>
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
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Driver /</span> Manage Trip</h4> 

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Trip Details</h5>
                    <p><strong>Trip ID:</strong> <?php echo htmlspecialchars($trip['tripID']); ?></p>
                    <p><strong>Origin:</strong> <?php echo htmlspecialchars($trip['startLocation']); ?></p>
                    <p><strong>Destination:</strong> <?php echo htmlspecialchars($trip['endLocation']); ?></p>
                    <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($trip['pickupLocation']); ?></p>
                    <p><strong>Start Date Time:</strong> <?php echo htmlspecialchars($trip['startDateTime']); ?></p>
                    <p><strong>Standard Amount (1 Person):</strong> RM <?php echo htmlspecialchars($trip['amount']); ?></p>
                    <p><strong>Discounted Amount (for 2 or more people):</strong> RM <?php echo number_format($discountedAmount, 2); ?></p>
                    <p><strong>Available Seats:</strong> <?php echo htmlspecialchars($trip['availableSeat']); ?></p>
<p><strong>Status:</strong> <?php echo strtoupper(htmlspecialchars($trip['status'])); ?></p>
                </div>           
            </div>                      

            <form method="POST" action="" onsubmit="return confirmCancel()">
                <button type="submit" name="cancel_trip" class="btn btn-danger mb-4">
                    <i class="bx bx-trash me-1"></i> Cancel Trip
                </button>
            </form>

            <!-- Requested Passengers Section -->
            <div class="card">
                <h5 class="card-header">Requested Passengers</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Passenger Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if (empty($requestedPassengers)) : ?>
                                <tr>
                                    <td colspan="4" class="text-center">No requested passengers found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($requestedPassengers as $passenger) : ?>
                                    <tr>
                                        <td><i class="fab fa-user-circle fa-lg text-primary me-3"></i><strong><?php echo htmlspecialchars($passenger['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($passenger['email']); ?></td>
                                        <td><span class="badge bg-label-primary me-1">Pending</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="view_passenger_profile.php?passengerID=<?php echo urlencode($passenger['passengerID']); ?>" target="_blank">
                                                        <i class="bx bx-show me-1"></i> View
                                                    </a>
                                                    <a class="dropdown-item" href="booking_approve.php?bookingID=<?php echo urlencode($passenger['bookingID']); ?>&tripID=<?php echo urlencode($tripID); ?>" onclick="return confirmAction('Are you sure you want to approve this passenger?')">
                                                        <i class="bx bx-check-circle me-1"></i> Approve
                                                    </a>
                                                    <a class="dropdown-item" href="booking_reject.php?bookingID=<?php echo urlencode($passenger['bookingID']); ?>&tripID=<?php echo urlencode($tripID); ?>" onclick="return confirmAction('Are you sure you want to reject this passenger?')">
                                                        <i class="bx bx-x-circle me-1"></i> Reject
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approved Passengers Section -->
            <div class="card mt-4">
                <h5 class="card-header">Approved Passengers</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Passenger Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if (empty($approvedPassengers)) : ?>
                                <tr>
                                    <td colspan="4" class="text-center">No approved passengers found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($approvedPassengers as $passenger) : ?>
                                    <tr>
                                        <td><i class="fab fa-user-circle fa-lg text-success me-3"></i><strong><?php echo htmlspecialchars($passenger['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($passenger['email']); ?></td>
                                        <td><span class="badge bg-label-success me-1">Approved</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="view_passenger_profile.php?passengerID=<?php echo urlencode($passenger['passengerID']); ?>" target="_blank">
                                                        <i class="bx bx-show me-1"></i> View
                                                    </a>
                                                    <a class="dropdown-item" href="payment_driver_confirm.php?tripID=<?php echo urlencode($trip['tripID']); ?>&bookingID=<?php echo urlencode($passenger['bookingID']); ?>" target="_blank"> 
                                                        <i class="bx bx-money me-1"></i> Confirm Payment
                                                    </a>
                                                    <a class="dropdown-item" href="booking_delete.php?bookingID=<?php echo urlencode($passenger['bookingID']); ?>&tripID=<?php echo urlencode($tripID); ?>" onclick="return confirmAction('Are you sure you want to delete this passenger from the trip?')">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>      
    </div>

    <?php include_once('footer.php'); ?>     

    <div id="spinner-overlay" class="overlay">
        <div class="spinner"></div>
    </div>

    <script>
function confirmAction(message) {
    // Display confirmation dialog
    const confirmed = confirm(message);

    if (confirmed) {
        showSpinner(); // Show the spinner if confirmed
    }
    return confirmed; // Return true if confirmed, false otherwise
}
        
        
        // Show the spinner and overlay
        function showSpinner() {
            document.getElementById("spinner-overlay").style.display = "flex";
        }


    function confirmCancel() {
        showSpinner();

        var result = confirm("Are you sure you want to cancel this trip? This action cannot be undone.");

        if (result) {
            return true;  
        } else {
            document.getElementById("spinner-overlay").style.display = "none"; 
            return false; 
        }
    }
    </script>
</body>
</html>

