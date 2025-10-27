<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_passenger_profile_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user's userID

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

// Update ongoing trips that have passed their start date to "completed"
$currentDateTime = date("Y-m-d H:i:s"); // Current date and time
$sql_update_trips = "UPDATE trip SET status = 'completed' WHERE status = 'ongoing' AND startDateTime <= ?";
if ($stmt = $mysqli->prepare($sql_update_trips)) {
    $stmt->bind_param("s", $currentDateTime);
    $stmt->execute();
    $stmt->close();
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
        window.location.href = 'passenger_dashboard.php';
    </script>";
    exit();
}

// Fetch requested (and ongoing) trips ordered by date (new to old)
$sql_requested = "SELECT t.tripID, t.startLocation, t.endLocation, t.startDateTime
                  FROM booking b
                  JOIN trip t ON b.tripID = t.tripID
                  WHERE b.passengerID = ? AND b.status = 'pending' AND t.status = 'ongoing'
                  ORDER BY t.startDateTime DESC";
if ($stmt = $mysqli->prepare($sql_requested)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $requestedTrips[] = $row;
    }
    $stmt->close();
}

// Fetch approved trips ordered by date (new to old)
$sql_approved = "SELECT t.tripID, t.startLocation, t.endLocation, t.startDateTime, b.bookingID
                 FROM booking b
                 JOIN trip t ON b.tripID = t.tripID
                 WHERE b.passengerID = ? AND b.status = 'approved' AND t.status = 'ongoing'
                 ORDER BY t.startDateTime DESC";
if ($stmt = $mysqli->prepare($sql_approved)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $approvedTrips[] = $row;
    }
    $stmt->close();
}

// Fetch completed trips ordered by date (new to old)
$sql_completed = "SELECT t.tripID, t.startLocation, t.endLocation, t.startDateTime, b.bookingID
                  FROM booking b
                  JOIN trip t ON b.tripID = t.tripID
                  WHERE b.passengerID = ? AND b.status = 'approved' AND t.status = 'completed'
                  ORDER BY t.startDateTime DESC";
if ($stmt = $mysqli->prepare($sql_completed)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedTrips[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Trip History</title>
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
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Passenger /</span> Trip History</h4> 

            <!-- Spinner -->
            <div id="spinner-overlay" class="overlay">
                <div class="spinner"></div>
            </div>

            <!-- Requested Trip Section -->
            <div class="card">
                <h5 class="card-header">Requested Trips</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Trip ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requestedTrips)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center">No requested trips found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($requestedTrips as $trip) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trip['tripID']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['endLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startDateTime']); ?></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="trip_passenger_view.php?tripID=<?php echo urlencode($trip['tripID']); ?>" target="_blank">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="booking_cancel.php?tripID=<?php echo urlencode($trip['tripID']); ?>" 
                               onclick="return showSpinner('Are you sure you want to cancel the request?');">
                                <i class="bx bx-x-circle me-1"></i> Cancel
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

            <!-- Approved Trip Section -->
            <div class="card mt-4">
                <h5 class="card-header">Approved Trips</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Trip ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approvedTrips)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center">No approved trips found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($approvedTrips as $trip) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trip['tripID']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['endLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startDateTime']); ?></td>
                    <td>
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="trip_passenger_view.php?tripID=<?php echo urlencode($trip['tripID']); ?>" target="_blank">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="booking_cancel.php?tripID=<?php echo urlencode($trip['tripID']); ?>" 
                               onclick="return showSpinner('Are you sure you want to unjoin this trip?');">
                                <i class="bx bx-x-circle me-1"></i> Unjoin
                            </a>
                            <a class="dropdown-item" href="payment_passenger_confirm.php?bookingID=<?php echo urlencode($trip['bookingID']); ?>">
                                <i class="bx bx-check-circle me-1"></i> Confirm Payment
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

            <!-- Completed Trip Section -->
            <div class="card mt-4">
                <h5 class="card-header">Completed Trips</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Trip ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($completedTrips)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center">No completed trips found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($completedTrips as $trip) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($trip['tripID']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['endLocation']); ?></td>
                                        <td><?php echo htmlspecialchars($trip['startDateTime']); ?></td>
                <td>
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="trip_passenger_view.php?tripID=<?php echo urlencode($trip['tripID']); ?>" target="_blank">
                                <i class="bx bx-show me-1"></i> View
                            </a>
                            <a class="dropdown-item" href="rate_trip.php?tripID=<?php echo urlencode($trip['tripID']); ?>">
                                <i class="bx bx-star me-1"></i> Rate Trip
                            </a>
                            <a class="dropdown-item" href="payment_passenger_confirm.php?bookingID=<?php echo urlencode($trip['bookingID']); ?>">
                                <i class="bx bx-check-circle me-1"></i> Confirm Payment
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

    <script>
        // Function to display spinner and show confirmation dialog
        function showSpinner(confirmMessage) {
            if (confirm(confirmMessage)) {
                document.getElementById("spinner-overlay").style.display = "flex";
                return true; // Allow the link to execute
            }
            return false; // Prevent link execution if not confirmed
        }
    </script>  
    
    
</body>
</html>

