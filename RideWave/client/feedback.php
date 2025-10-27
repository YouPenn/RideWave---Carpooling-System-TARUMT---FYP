<?php
include_once('../config/config.php');
include_once('remember_check.php');

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

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);

    if (empty($content)) {
        echo "<script>alert('Feedback content cannot be empty.');</script>";
    } else {
        $sql_feedback = "INSERT INTO feedback (userID, content, dateSubmitted) VALUES (?, ?, CURDATE())";
        if ($stmt = $mysqli->prepare($sql_feedback)) {
            $stmt->bind_param("is", $userID, $content);
            if ($stmt->execute()) {
                echo "<script>
                    alert('Feedback submitted successfully.');
                    window.location.href = 'feedback.php';
                </script>";
            } else {
                echo "<script>alert('Failed to submit feedback. Please try again.');</script>";
            }
            $stmt->close();
        }
    }
}

// Fetch feedback history for the logged-in user
$feedbackHistory = [];
$sql_fetch_feedback = "SELECT content, dateSubmitted FROM feedback WHERE userID = ? ORDER BY dateSubmitted DESC";
if ($stmt = $mysqli->prepare($sql_fetch_feedback)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $feedbackHistory[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Feedback</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">User /</span> Feedback
            </h4> 

            <!-- Feedback Form -->
            <div class="card mb-4">
                <h5 class="card-header">Submit Feedback</h5>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="content" class="form-label">Your Feedback</label>
                            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>

            <!-- Feedback History -->
            <div class="card mt-4">
                <h5 class="card-header">Your Feedback History</h5>
                <div class="card-body">
                    <?php if (empty($feedbackHistory)): ?>
                        <p>No feedback submitted yet.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($feedbackHistory as $feedback): ?>
                                <li class="list-group-item">
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($feedback['dateSubmitted']); ?></p>
                                    <p><?php echo htmlspecialchars($feedback['content']); ?></p>
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
