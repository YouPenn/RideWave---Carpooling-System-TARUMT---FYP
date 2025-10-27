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

// Get the driverID from the URL parameter
$driverID = isset($_GET['driverID']) ? intval($_GET['driverID']) : null;

// Redirect to error page if driverID is not provided
if (!$driverID) {
    header("Location: error.php");
    exit();
}

// Fetch the driver name
$driverName = null;
$sql_driver_name = "SELECT u.username AS driverName 
                    FROM driver d
                    JOIN user u ON d.userID = u.userID
                    WHERE d.driverID = ?";
if ($stmt = $mysqli->prepare($sql_driver_name)) {
    $stmt->bind_param("i", $driverID);
    $stmt->execute();
    $stmt->bind_result($driverName);
    if (!$stmt->fetch()) {
        header("Location: error.php");
        exit();
    }
    $stmt->close();
}

// Fetch all completed trips for the driver
$completedTrips = [];
$sql_completed_trips = "
    SELECT t.tripID, t.startLocation, t.endLocation, t.startDateTime,
           COALESCE(AVG(r.star), 0) AS avgStar
    FROM trip t
    LEFT JOIN rating r ON t.tripID = r.tripID
    WHERE t.driverID = ? AND t.status = 'completed'
    GROUP BY t.tripID
    ORDER BY t.startDateTime DESC";

if ($stmt = $mysqli->prepare($sql_completed_trips)) {
    $stmt->bind_param("i", $driverID);
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
    <?php include_once('header.php'); ?>
    <title>Driver Trip History</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Trip Ratings /</span> Trip History</h4> 

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Completed Trips for Driver: <?php echo htmlspecialchars($driverName); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($completedTrips)): ?>
                        <p>No completed trips found for this driver.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Trip ID</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Date</th>
                                        <th>Average Star</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completedTrips as $trip): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trip['tripID']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['startLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['endLocation']); ?></td>
                                            <td><?php echo htmlspecialchars($trip['startDateTime']); ?></td>
<td>
    <?php if ($trip['avgStar'] > 0): ?>
        <?php 
            $fullStars = floor($trip['avgStar']); // Number of full stars
            $halfStar = ($trip['avgStar'] - $fullStars) >= 0.5 ? 1 : 0; // One half star if fractional part >= 0.5
            $emptyStars = 5 - $fullStars - $halfStar; // Remaining empty stars
        ?>
        <div style="font-size: 1.2rem; color: #ffc107; display: inline-flex;">
            <?php for ($i = 0; $i < $fullStars; $i++): ?>
                <span>&#9733;</span> <!-- Full star -->
            <?php endfor; ?>
            <?php if ($halfStar): ?>
                <span>&#9734;</span> <!-- Half star (use CSS for proper display if needed) -->
            <?php endif; ?>
            <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                <span style="color: #ccc;">&#9733;</span> <!-- Empty star -->
            <?php endfor; ?>
        </div>
        <span style="font-size: 0.9rem; color: #333;">(<?php echo htmlspecialchars(number_format($trip['avgStar'], 2)); ?>/5)</span>
    <?php else: ?>
        No Rating
    <?php endif; ?>
</td>
                                            <td>
                                                <a href="view_driver_trip_rating.php?tripID=<?php echo urlencode($trip['tripID']); ?>" 
                                                   class="btn btn-info btn-sm">View Ratings</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>  
        </div>    
    </div>

    <?php include_once('footer.php'); ?>        
</body>
</html>
