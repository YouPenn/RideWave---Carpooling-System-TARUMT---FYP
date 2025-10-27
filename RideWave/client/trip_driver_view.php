<?php
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

// Check if the driver exists
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
            alert('You are not authorized to view this trip.');
            window.location.href = 'driver_dashboard.php';
        </script>";
        exit();
    }
    $trip = $result->fetch_assoc();
    $stmt->close();
}

$discountedAmount = $trip['amount'] * 2 / 3;


// Fetch approved passengers for the trip
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
?>



<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Driver View Trip</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Driver /</span> View Trip</h4> 

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
                                            <i class="bx bx-show me-1"></i> View Profile
                                        </a>
                                        <a class="dropdown-item" href="payment_driver_confirm.php?tripID=<?php echo urlencode($trip['tripID']); ?>&bookingID=<?php echo urlencode($passenger['bookingID']); ?>" target="_blank"> 
                                            <i class="bx bx-money me-1"></i> Confirm Payment
                                        </a>
                                        <a class="dropdown-item" href="view_driver_trip_rating.php?tripID=<?php echo urlencode($trip['tripID']); ?>" target="_blank">
                                            <i class="bx bx-star me-1"></i> View Rating
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
</body>
</html>
