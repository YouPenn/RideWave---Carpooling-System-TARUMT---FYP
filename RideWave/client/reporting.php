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

// Get the userID of the user being reported
$reportedUserID = isset($_GET['userID']) ? intval($_GET['userID']) : null;

// Redirect if no userID is provided or if the user is trying to report themselves
if (!$reportedUserID || $reportedUserID === $loggedInUserID) {
    echo "<script>
        alert('Invalid user to report or you cannot report yourself.');
        window.location.href = 'error.php';
    </script>";
    exit();
}

// Check if the reported user exists and fetch their details
$sql_check_user = "SELECT userID, studentID, username FROM user WHERE userID = ?";
$userDetails = [];
if ($stmt = $mysqli->prepare($sql_check_user)) {
    $stmt->bind_param("i", $reportedUserID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userDetails = $result->fetch_assoc();
    } else {
        echo "<script>
            alert('The user you are trying to report does not exist.');
            window.location.href = 'error.php';
        </script>";
        exit();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);

    if (empty($reason)) {
        echo "<script>
            alert('Please provide a reason for the report.');
        </script>";
    } else {
        // Insert report into the anonymous_report table
        $sql_insert_report = "INSERT INTO anonymous_report (userID, reason, reportDate, status) VALUES (?, ?, CURDATE(), 'pending')";
        if ($stmt = $mysqli->prepare($sql_insert_report)) {
            $stmt->bind_param("is", $reportedUserID, $reason);
            if ($stmt->execute()) {
                echo "<script>
                    alert('Your report has been submitted successfully.');
                    window.location.href = 'welcome.php';
                </script>";
            } else {
                echo "<script>
                    alert('Failed to submit the report. Please try again later.');
                </script>";
            }
            $stmt->close();
        } else {
            echo "<script>
                alert('Database error. Please try again later.');
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Submit a Report</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Reporting /</span> Submit a Report
            </h4> 

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">User Details</h5>
                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($userDetails['userID']); ?></p>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($userDetails['studentID']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($userDetails['username']); ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Reporting</label>
                            <textarea class="form-control" id="reason" name="reason" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Report</button>
                    </form>
                </div>
            </div>
        </div>    
    </div>

    <?php include_once('footer.php'); ?>        
</body>
</html>
