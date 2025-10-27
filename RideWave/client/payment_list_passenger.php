<?php
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

$loggedInUserID = $_SESSION["userID"]; // Get the logged-in user's ID

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

// Fetch the passenger's ID associated with the logged-in user
$passengerID = null;
$sql_passenger = "SELECT passengerID FROM passenger WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_passenger)) {
    $stmt->bind_param("i", $loggedInUserID);
    $stmt->execute();
    $stmt->bind_result($passengerID);
    $stmt->fetch();
    $stmt->close();
}

// Redirect if no passenger ID is found
if (!$passengerID) {
    echo "<script>
        alert('Passenger profile not found.');
        window.location.href = 'dashboard.php';
    </script>";
    exit();
}

// Fetch all pending payment records for bookings made by this passenger
$sql_pending_payments = "SELECT p.paymentID, p.status, p.passengerConfirmDate, p.driverConfirmDate, b.bookingID, t.tripID, t.startLocation, t.endLocation, t.startDateTime 
                          FROM payment p
                          JOIN booking b ON p.bookingID = b.bookingID
                          JOIN trip t ON b.tripID = t.tripID
                          WHERE b.passengerID = ? AND p.status = 'pending' OR p.status = 'no received'";

$payments = [];
if ($stmt = $mysqli->prepare($sql_pending_payments)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}

// Fetch all completed payment records for bookings made by this passenger
$sql_completed_payments = "SELECT p.paymentID, p.status, p.passengerConfirmDate, p.driverConfirmDate, b.bookingID, t.tripID, t.startLocation, t.endLocation, t.startDateTime 
                             FROM payment p
                             JOIN booking b ON p.bookingID = b.bookingID
                             JOIN trip t ON b.tripID = t.tripID
                             WHERE b.passengerID = ? AND p.status != 'pending' AND p.status != 'no received'";

$completedPayments = [];
if ($stmt = $mysqli->prepare($sql_completed_payments)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedPayments[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Payment List</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Passenger /</span> Payment List
            </h4> 

            <!-- Pending Payments -->
            <div class="card mb-4">
                <h5 class="card-header">Pending Payments</h5>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking ID</th>
                                    <th>Trip ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date & Time</th>
                                    <th>Driver Confirm Date</th>
                                    <th>Passenger Confirm Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)) : ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No pending payments found.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($payments as $payment) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['paymentID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['bookingID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['tripID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['startLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['endLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['startDateTime']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['driverConfirmDate'] ?: 'Not confirmed'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['passengerConfirmDate'] ?: 'Not confirmed'); ?></td>
<td><?php echo strtoupper(htmlspecialchars($payment['status'])); ?></td>
                                            <td>
                                                <a href="payment_passenger_confirm.php?bookingID=<?php echo urlencode($payment['bookingID']); ?>" class="btn btn-primary btn-sm">Confirm</a> 
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Completed Payments -->
            <div class="card mb-4">
                <h5 class="card-header">Payments History</h5>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                          <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking ID</th>
                                    <th>Trip ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date & Time</th>
                                    <th>Driver Confirm Date</th>
                                    <th>Passenger Confirm Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($completedPayments)) : ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No completed payments found.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($completedPayments as $payment) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['paymentID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['bookingID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['tripID']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['startLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['endLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['startDateTime']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['driverConfirmDate'] ?: 'Not confirmed'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['passengerConfirmDate'] ?: 'Not confirmed'); ?></td>
<td><?php echo strtoupper(htmlspecialchars($payment['status'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>    
    </div>

    <?php include_once('footer.php'); ?>        
</body>
</html>
