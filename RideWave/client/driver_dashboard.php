<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_driver_profile_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user's ID
$currentDateTime = date("Y-m-d H:i:s"); // Current date and time
$currentDate = date("Y-m-d"); // Current date

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
$sql_update_trips = "UPDATE trip SET status = 'completed' WHERE status = 'ongoing' AND startDateTime <= ?";
if ($stmt = $mysqli->prepare($sql_update_trips)) {
    $stmt->bind_param("s", $currentDateTime);
    $stmt->execute();
    $stmt->close();
}

// Fetch trips created by this driver
$ongoingTrips = [];
$pastTrips = [];

// Initialize selected date and search query
$selectedDate = isset($_GET['date']) ? $_GET['date'] : "";
$searchQuery = isset($_GET['search']) ? $_GET['search'] : "";

// SQL to fetch ongoing trips
$sql_ongoing = "SELECT trip.tripID, trip.startLocation, trip.endLocation, trip.startDateTime, trip.status
                FROM trip
                JOIN driver ON trip.driverID = driver.driverID
                WHERE driver.userID = ? AND trip.status = 'ongoing'";

// Fetch ongoing trips
if ($stmt = $mysqli->prepare($sql_ongoing)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($tripID, $startLocation, $endLocation, $startDateTime, $status);

    while ($stmt->fetch()) {
        $ongoingTrips[] = [
            'tripID' => $tripID,
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
            'status' => $status
        ];
    }

    $stmt->close();
}

// SQL to fetch past trips with optional date filter and search query
$sql_past = "SELECT trip.tripID, trip.startLocation, trip.endLocation, trip.startDateTime, trip.status
             FROM trip
             JOIN driver ON trip.driverID = driver.driverID
             WHERE driver.userID = ? AND trip.status = 'completed'";

if (!empty($selectedDate)) {
    $sql_past .= " AND DATE(trip.startDateTime) = '" . $mysqli->real_escape_string($selectedDate) . "'";
}
if (!empty($searchQuery)) {
    $sql_past .= " AND (trip.startLocation LIKE '%" . $mysqli->real_escape_string($searchQuery) . "%' 
                   OR trip.endLocation LIKE '%" . $mysqli->real_escape_string($searchQuery) . "%')";
}
$sql_past .= " ORDER BY trip.startDateTime ASC";

// Fetch past trips
if ($stmt = $mysqli->prepare($sql_past)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($tripID, $startLocation, $endLocation, $startDateTime, $status);

    while ($stmt->fetch()) {
        $pastTrips[] = [
            'tripID' => $tripID,
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
            'status' => $status
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
    <title>Driver Dashboard</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Driver /</span> Dashboard</h4> 

            <!-- Ongoing Trips Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ongoing Trips</h5>
                    <?php if (empty($ongoingTrips)) : ?>
                        <p>No ongoing trips found.</p>
                    <?php else : ?>
                        <ul class="list-group">
                            <?php foreach ($ongoingTrips as $trip) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Trip ID:</strong> <?php echo htmlspecialchars($trip['tripID']); ?><br>
                                        <strong>From:</strong> <?php echo htmlspecialchars($trip['startLocation']); ?> 
                                        <strong>To:</strong> <?php echo htmlspecialchars($trip['endLocation']); ?><br>
                                        <strong>Date:</strong> <?php echo htmlspecialchars($trip['startDateTime']); ?>
                                    </div>
                                    <a href="trip_driver_manage.php?tripID=<?php echo urlencode($trip['tripID']); ?>" class="btn btn-primary">Manage</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Create Trip Button -->
            <div class="d-flex mb-4">
                <a href="trip_create.php" class="btn btn-success">Create Trip</a>
            </div>

            <!-- Past Trips Section with Filter and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Past Trips</h5>

                    <!-- Filter and Search Form -->
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label for="date" class="form-label">Filter by Date</label>
                                <input 
                                    type="date" 
                                    id="date" 
                                    name="date" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($selectedDate); ?>" 
                                    max="<?php echo htmlspecialchars($currentDate); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Trips</label>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Search by location" 
                                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary mt-md-4">Apply Filter</button>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary mt-md-4">Clear Filter</a>
                            </div>
                        </div>
                    </form>

                    <!-- Past Trips List -->
                    <?php if (empty($pastTrips)) : ?>
                        <p>No past trips found.</p>
                    <?php else : ?>
                        <ul class="list-group">
                            <?php foreach ($pastTrips as $trip) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Trip ID:</strong> <?php echo htmlspecialchars($trip['tripID']); ?><br>
                                        <strong>From:</strong> <?php echo htmlspecialchars($trip['startLocation']); ?> 
                                        <strong>To:</strong> <?php echo htmlspecialchars($trip['endLocation']); ?><br>
                                        <strong>Date:</strong> <?php echo htmlspecialchars($trip['startDateTime']); ?>
                                    </div>
<div class="dropdown">
    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="trip_driver_view.php?tripID=<?php echo urlencode($trip['tripID']); ?>">
            <i class="bx bx-show me-1"></i> View
        </a>
        <a class="dropdown-item" href="view_driver_trip_rating.php?tripID=<?php echo urlencode($trip['tripID']); ?>">
            <i class="bx bx-star me-1"></i> View Ratings
        </a>
    </div>
</div>
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
