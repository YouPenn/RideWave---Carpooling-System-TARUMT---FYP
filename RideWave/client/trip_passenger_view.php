<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_passenger_profile_check.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; 

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    echo "<script>
        alert('You must be logged in to view this page.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

$userID = $_SESSION["userID"]; // Get logged-in user ID

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

// Get the trip ID from the URL parameter
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Redirect to dashboard if trip ID is not provided
if (!$tripID) {
    echo "<script>
        alert('Invalid trip ID.');
        window.location.href = 'passenger_dashboard.php';
    </script>";
    exit();
}

// Check if the driver associated with the trip is active
$sql_check_driver_status = "SELECT driver.driverID, driver.userID AS driverUserID, user.status AS userStatus 
                            FROM trip 
                            JOIN driver ON trip.driverID = driver.driverID 
                            JOIN user ON driver.userID = user.userID 
                            WHERE trip.tripID = ?";

$driverUserID = null;
$userStatus = null;
if ($stmt = $mysqli->prepare($sql_check_driver_status)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $stmt->bind_result($driverID, $driverUserID, $userStatus);
    if ($stmt->fetch()) {
        // Check if the driver's user status is not active
        if ($userStatus !== 'active') {
            echo "<script>
                alert('The driver associated with this trip is either deleted or banned.');
                window.location.href = 'passenger_dashboard.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('Trip not found.');
            window.location.href = 'passenger_dashboard.php';
        </script>";
        exit();
    }
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

// Fetch trip details
$trip = [];
$driver = [];
$sql_trip = "SELECT trip.tripID, trip.startLocation, trip.endLocation, trip.pickupLocation, trip.startDateTime, 
                    trip.amount, trip.status, trip.availableSeat, trip.driverID, driver.userID AS driverUserID, 
                    user.username AS driverName, user.email, driver.carRegNo
             FROM trip 
             JOIN driver ON trip.driverID = driver.driverID
             JOIN user ON driver.userID = user.userID
             WHERE trip.tripID = ?";


if ($stmt = $mysqli->prepare($sql_trip)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $stmt->bind_result($tripID, $startLocation, $endLocation, $pickupLocation, $startDateTime, $amount, $status, $availableSeat, $driverID, $driverUserID, $driverName, $driverEmail, $carRegNo);

    
    if ($stmt->fetch()) {
        $trip = [
            'tripID' => $tripID,
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'pickupLocation' => $pickupLocation,
            'startDateTime' => $startDateTime,
            'amount' => $amount,
            'status' => $status,
            'availableSeat' => $availableSeat
        ];
        $driver = [
            'driverID' => $driverID,
            'driverUserID' => $driverUserID,
            'driverName' => $driverName,
            'email' => $driverEmail,
            'carRegNo' => $carRegNo
        ];
    } else {
        echo "<script>
            alert('Trip not found.');
            window.location.href = 'passenger_dashboard.php';
        </script>";
        exit();
    }
    $stmt->close();
}

$discountedAmount = $trip['amount'] * 2 / 3;

// Generate QR Code for this page
$writer = new PngWriter();
// Dynamically detect the current server and build the URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST']; // Server host (IP address or domain)
$path = "/RideWave/client/trip_passenger_view.php"; // Adjust the path as needed
$ngrok = "https://ibex-golden-kindly.ngrok-free.app/";

$fullURL = $ngrok . $path . "?tripID={$tripID}";

// Generate QR Code
$qrCode = new QrCode($fullURL);

$result = $writer->write($qrCode);
$qrCodeBase64 = base64_encode($result->getString());


// Fetch other joined passengers for the trip
$joinedPassengers = [];
$sql_passengers = "SELECT passenger.passengerID, user.username, user.email 
                   FROM booking
                   JOIN passenger ON booking.passengerID = passenger.passengerID
                   JOIN user ON passenger.userID = user.userID
                   WHERE booking.tripID = ? AND booking.status = 'approved'";

if ($stmt = $mysqli->prepare($sql_passengers)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $joinedPassengers[] = $row;
    }
    $stmt->close();
}

// Handle join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_request'])) {
    // Check if the trip status allows joining
    if ($trip['status'] !== 'ongoing') {
        echo "<script>
            alert('You cannot join a canceled or completed trip.');
            window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
        </script>";
        exit();
    }

    // Check if the driver of the trip is blacklisted by the user
    $sql_check_blacklist = "SELECT COUNT(*) FROM blacklist WHERE userID = ? AND blacklistedID = ?";
    $isBlacklisted = 0;

    if ($stmt = $mysqli->prepare($sql_check_blacklist)) {
        $stmt->bind_param("ii", $userID, $driver['driverUserID']);
        $stmt->execute();
        $stmt->bind_result($isBlacklisted);
        $stmt->fetch();
        $stmt->close();
    }

    if ($isBlacklisted > 0) {
        echo "<script>
            alert('You cannot join a trip hosted by a blacklisted driver.');
            window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
        </script>";
        exit();
    }

    // Check if a booking already exists
    $sql_check_booking = "SELECT COUNT(*) FROM booking 
                          WHERE passengerID = (SELECT passengerID FROM passenger WHERE userID = ?) 
                          AND tripID = ?";
    $bookingExists = false;

    if ($stmt = $mysqli->prepare($sql_check_booking)) {
        $stmt->bind_param("ii", $userID, $tripID);
        $stmt->execute();
        $stmt->bind_result($bookingExists);
        $stmt->fetch();
        $stmt->close();
    }

    if ($bookingExists) {
        echo "<script>
            alert('You have already requested to join this trip.');
            window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
        </script>";
        exit();
    }

    // Proceed with join request
    $sql_request = "INSERT INTO booking (passengerID, tripID, bookingDate, status) 
                    VALUES ((SELECT passengerID FROM passenger WHERE userID = ?), ?, CURDATE(), 'pending')";

    if ($stmt = $mysqli->prepare($sql_request)) {
        $stmt->bind_param("ii", $userID, $tripID);
        if ($stmt->execute()) {
            // Send email to the driver
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';
                $mail->Password = 'nbcb anqx vzug lupd';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('ourbus2003@gmail.com', 'RideWave');
                $mail->addAddress($driver['email']);

                $mail->isHTML(true);
                $mail->Subject = 'New Join Request for Your Trip';
                $mail->Body = "
                    <p>Dear {$driver['driverName']},</p>
                    <p>A passenger has requested to join your trip:</p>
                    <ul>
                        <li><strong>Trip ID:</strong> {$trip['tripID']}</li>
                        <li><strong>From:</strong> {$trip['startLocation']}</li>
                        <li><strong>To:</strong> {$trip['endLocation']}</li>
                        <li><strong>Date & Time:</strong> {$trip['startDateTime']}</li>
                    </ul>
                    <p>Please review the request in your dashboard.</p>
                    <p>Regards,<br>RideWave Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>
                    alert('Join request sent, but notification email could not be sent: {$mail->ErrorInfo}');
                    window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
                </script>";
                exit();
            }

            echo "<script>
                alert('Join request sent successfully!');
                window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
            </script>";
        } else {
            echo "<script>
                alert('Error sending join request: " . addslashes($stmt->error) . "');
                window.location.href = 'trip_passenger_view.php?tripID={$tripID}';
            </script>";
        }
        $stmt->close();
    }
    exit();
}

$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Passenger View Trip</title>
    <style>
        /* Spinner styles */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
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
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Passenger /</span> View Trip</h4> 

            <!-- Spinner -->
            <div id="spinner-overlay" class="overlay">
                <div class="spinner"></div>
            </div>

            <!-- Display Message -->
            <?php if (isset($_SESSION['message'])) : ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Trip Details Section -->
                <div class="card col-md-6 mb-3">                
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

                <!-- QR Code Section -->
                <div class="card col-md-6 mb-3">
                    <div class="card-body text-center">
                        <p>Share this trip by scanning the QR code below:</p>
                        <img src="data:image/png;base64,<?php echo $qrCodeBase64; ?>" alt="Trip QR Code" style="max-width: 55%;">
                    </div>
                </div>
            </div>
            
            <!-- Driver Details Section -->
            <div class="card mb-4">
                <h5 class="card-header">Driver Details</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Driver Name</th>
                                <th>Email</th>
                                <th>Car Registration Number</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <tr>
                                <td><i class="fab fa-user-circle fa-lg text-primary me-3"></i> <strong><?php echo htmlspecialchars($driver['driverName']); ?></strong></td>
                                <td><?php echo htmlspecialchars($driver['email']); ?></td>
                                <td><?php echo htmlspecialchars($driver['carRegNo']); ?></td>
                                <td>
                                    <a href="view_driver_profile.php?driverID=<?php echo urlencode($driver['driverID']); ?>" class="btn btn-outline-info btn-sm" target="_blank">
                                        <i class="bx bx-show me-1"></i> View Profile
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Joined Passenger Details Section -->
            <div class="card mb-4">
                <h5 class="card-header">Passenger Details</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Passenger Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if (empty($joinedPassengers)) : ?>
                                <tr>
                                    <td colspan="3" class="text-center">No passengers have joined this trip yet.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($joinedPassengers as $passenger) : ?>
                                    <tr>
                                        <td><i class="fab fa-user-circle fa-lg text-success me-3"></i> <strong><?php echo htmlspecialchars($passenger['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($passenger['email']); ?></td>
                                        <td>
                                            <a href="view_passenger_profile.php?passengerID=<?php echo urlencode($passenger['passengerID']); ?>" class="btn btn-outline-info btn-sm" target="_blank">
                                                <i class="bx bx-show me-1"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Join Request Button -->
            <form method="POST" action="" onsubmit="return showSpinner()">
                <input type="hidden" name="tripID" value="<?php echo htmlspecialchars($trip['tripID']); ?>">
                <button type="submit" name="join_request" class="btn btn-success">Request to Join Trip</button>
            </form>      
        </div>      
    </div>

    <?php include_once('footer.php'); ?>        

    <script>
        // Function to show spinner
        function showSpinner() {
            document.getElementById("spinner-overlay").style.display = "flex";
            return true; // Allow form submission
        }
    </script>
</body>
</html>

