<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_passenger_profile_check.php');

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

$currentDateTime = date("Y-m-d H:i:s"); // Current date and time
$currentDate = date("Y-m-d"); // Current date

// Update ongoing trips that have passed their start date to "completed"
$sql_update_trips = "UPDATE trip SET status = 'completed' WHERE status = 'ongoing' AND startDateTime <= ?";
if ($stmt = $mysqli->prepare($sql_update_trips)) {
    $stmt->bind_param("s", $currentDateTime);
    $stmt->execute();
    $stmt->close();
}

// Fetch the driver's driverID for the logged-in user (if the user is also a driver)
$driverID = null;
$sql_driver = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_driver)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($driverID);
    $stmt->fetch();
    $stmt->close();
}

// Initialize selectedDate and searchQuery
$selectedDate = isset($_GET['date']) ? $_GET['date'] : "";
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch trips based on selected date, search query, or all upcoming trips
$upcomingTrips = [];
$sql = "SELECT trip.tripID, trip.startLocation, trip.endLocation, trip.startDateTime, trip.availableSeat, user.username AS driverName
        FROM trip
        JOIN driver ON trip.driverID = driver.driverID
        JOIN user ON driver.userID = user.userID
        LEFT JOIN blacklist b1 ON b1.blacklistedID = driver.userID AND b1.userID = ?
        LEFT JOIN blacklist b2 ON b2.userID = driver.userID AND b2.blacklistedID = ?
        WHERE trip.startDateTime > ? 
          AND trip.status = 'ongoing' 
          AND trip.driverID != ? 
          AND user.status = 'active'  -- Ensure the driver's user status is active
          AND b1.blacklistedID IS NULL 
          AND b2.blacklistedID IS NULL";

if (!empty($selectedDate)) {
    $sql .= " AND DATE(trip.startDateTime) = '" . $mysqli->real_escape_string($selectedDate) . "'";
}

if (!empty($searchQuery)) {
    $sql .= " AND (trip.startLocation LIKE '%" . $mysqli->real_escape_string($searchQuery) . "%' OR 
                    trip.endLocation LIKE '%" . $mysqli->real_escape_string($searchQuery) . "%' OR 
                    user.username LIKE '%" . $mysqli->real_escape_string($searchQuery) . "%')";
}

$sql .= " ORDER BY trip.startDateTime ASC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("issi", $userID, $userID, $currentDateTime, $driverID);
    $stmt->execute();
    $stmt->bind_result($tripID, $startLocation, $endLocation, $startDateTime, $availableSeat, $driverName);

    while ($stmt->fetch()) {
        $upcomingTrips[] = [
            'tripID' => $tripID,
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
            'availableSeat' => $availableSeat,
            'driverName' => $driverName
        ];
    }

    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Passenger Dashboard</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Passenger /</span> Dashboard</h4> 
            
            <!-- Filter and Search Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">Filter by Date</label>
                                <input 
                                    type="date" 
                                    id="date" 
                                    name="date" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($selectedDate); ?>" 
                                    min="<?php echo htmlspecialchars($currentDate); ?>" 
                                >
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="search" class="form-label">Search</label>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Search by location or driver name" 
                                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply</button>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Upcoming Trips Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Trips</h5>
                    
                    <?php if (empty($upcomingTrips)) : ?>
                        <p>No upcoming trips found.</p>
                    <?php else : ?>
                        <ul class="list-group">
                            <?php foreach ($upcomingTrips as $trip) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Trip ID:</strong> <?php echo htmlspecialchars($trip['tripID']); ?><br>
                                        <strong>Driver Name:</strong> <?php echo htmlspecialchars($trip['driverName']); ?><br>
                                        <strong>From:</strong> <?php echo htmlspecialchars($trip['startLocation']); ?> 
                                        <strong>To:</strong> <?php echo htmlspecialchars($trip['endLocation']); ?><br>
                                        <strong>Date:</strong> <?php echo htmlspecialchars($trip['startDateTime']); ?><br>
                                        <strong>Available Seats:</strong> <?php echo htmlspecialchars($trip['availableSeat']); ?>
                                    </div>
                                    <a href="trip_passenger_view.php?tripID=<?php echo urlencode($trip['tripID']); ?>" class="btn btn-primary">View</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>   
            </div>   
        </div>    
    </div>

    <?php include_once('footer.php'); ?>        
</body>
</html>
