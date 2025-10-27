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

// Get the tripID from the URL parameter
$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null;

// Redirect to error page if tripID is not provided
if (!$tripID) {
    header("Location: error.php");
    exit();
}

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

// Fetch the trip details and ratings
$tripDetails = null;
$ratings = [];

$sql_trip_details = "
    SELECT t.startLocation, t.endLocation, t.startDateTime, u.username AS driverName
    FROM trip t
    JOIN driver d ON t.driverID = d.driverID
    JOIN user u ON d.userID = u.userID
    WHERE t.tripID = ?";
if ($stmt = $mysqli->prepare($sql_trip_details)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $stmt->bind_result($startLocation, $endLocation, $startDateTime, $driverName);
    if ($stmt->fetch()) {
        $tripDetails = [
            'startLocation' => $startLocation,
            'endLocation' => $endLocation,
            'startDateTime' => $startDateTime,
            'driverName' => $driverName
        ];
    } else {
        header("Location: error.php");
        exit();
    }
    $stmt->close();
}

// Fetch ratings for the trip and calculate average star rating
$averageStar = 0;
$sql_ratings = "
    SELECT u.username AS passengerName, r.star, r.comment
    FROM rating r
    JOIN passenger p ON r.passengerID = p.passengerID
    JOIN user u ON p.userID = u.userID
    WHERE r.tripID = ?";

$sql_average_star = "
    SELECT AVG(r.star) AS averageStar
    FROM rating r
    WHERE r.tripID = ?";

// Fetch ratings
if ($stmt = $mysqli->prepare($sql_ratings)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    $stmt->close();
}

// Fetch average star rating
if ($stmt = $mysqli->prepare($sql_average_star)) {
    $stmt->bind_param("i", $tripID);
    $stmt->execute();
    $stmt->bind_result($averageStar);
    $stmt->fetch();
    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Trip Ratings</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Trip Ratings /</span> Trip ID: <?php echo htmlspecialchars($tripID); ?>
            </h4> 

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Trip Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Driver:</strong> <?php echo htmlspecialchars($tripDetails['driverName']); ?></p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($tripDetails['startLocation']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($tripDetails['endLocation']); ?></p>
                    <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($tripDetails['startDateTime']); ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Passenger Ratings</h5>
                </div>
                <div class="card-body">

<p><strong>Average Star Rating:</strong>
    <?php if ($averageStar > 0): ?>
        <?php 
            $fullStars = floor($averageStar); 
            $halfStar = ($averageStar - $fullStars) >= 0.5 ? 1 : 0; 
            $emptyStars = 5 - $fullStars - $halfStar; 
        ?>
        <div style="font-size: 1.5rem; color: #ffc107; display: inline-flex;">
            <?php for ($i = 0; $i < $fullStars; $i++): ?>
                <span>&#9733;</span>
            <?php endfor; ?>
            <?php if ($halfStar): ?>
                <span>&#9734;</span>
            <?php endif; ?>
            <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                <span style="color: #ccc;">&#9733;</span>
            <?php endfor; ?>
        </div>
        <span style="font-size: 0.9rem; color: #333;">(<?php echo htmlspecialchars(round($averageStar, 2)); ?>/5)</span>
    <?php else: ?>
        No Ratings Yet
    <?php endif; ?>
</p>                    
                    
                    <?php if (empty($ratings)): ?>
                        <p>No ratings found for this trip.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Passenger Name</th>
                                        <th>Star Rating</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ratings as $rating): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rating['passengerName']); ?></td>
<td>
    <?php 
        $starRating = $rating['star'];
        $fullStars = floor($starRating); 
        $halfStar = ($starRating - $fullStars) >= 0.5 ? 1 : 0; 
        $emptyStars = 5 - $fullStars - $halfStar; 
    ?>
    <div style="font-size: 1.5rem; color: #ffc107; display: inline-flex;">
        <?php for ($i = 0; $i < $fullStars; $i++): ?>
            <span>&#9733;</span>
        <?php endfor; ?>
        <?php if ($halfStar): ?>
            <span>&#9734;</span>
        <?php endif; ?>
        <?php for ($i = 0; $i < $emptyStars; $i++): ?>
            <span style="color: #ccc;">&#9733;</span>
        <?php endfor; ?>
    </div>
    <span style="font-size: 0.9rem; color: #333;"><?php echo htmlspecialchars($starRating); ?>/5</span>
</td>
                                            <td><?php echo htmlspecialchars($rating['comment'] ?: 'No Comment'); ?></td>
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
