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

$userID = $_SESSION["userID"]; // Get the logged-in user's ID

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

// Get the passengerID from the URL parameter
$passengerID = isset($_GET['passengerID']) ? intval($_GET['passengerID']) : null;

// Redirect to error page if passengerID is not provided
if (!$passengerID) {
    echo "<script>
        alert('Invalid passenger ID.');
        window.location.href = 'error.php';
    </script>";
    exit();
}

// Fetch passenger and user details
$sql = "SELECT p.passengerID, p.status AS passengerStatus, u.userID, u.studentID, u.username AS name, u.email, u.phoneNumber, 
        u.gender, u.dateOfBirth, u.userImg, u.status AS userStatus
        FROM passenger p
        JOIN user u ON p.userID = u.userID
        WHERE p.passengerID = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $passengerID);
    $stmt->execute();
    $stmt->bind_result($passengerID, $passengerStatus, $profileUserID, $studentID, $name, $email, $phone, $gender, $dateOfBirth, $userImg, $userStatus);

    if (!$stmt->fetch()) {
        echo "<script>
            alert('Passenger not found.');
            window.location.href = 'error.php';
        </script>";
        exit();
    }
    $stmt->close();
} else {
    echo "<script>
        alert('Error fetching passenger details.');
        window.location.href = 'error.php';
    </script>";
    exit();
}

// Check user status
if ($userStatus !== 'active') {
    $message = $userStatus === 'banned' ? 'This user has been banned.' : 'This user has been deleted.';
    echo "<script>
        alert('$message');
    </script>";
}

// Check if the profile being viewed is the user's own profile
if ($profileUserID === $userID) {
    header("Location: own_passenger_profile.php"); // Redirect to user's own profile page
    exit();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Passenger Profile</title>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4">View Passenger Profile</h4>

            <div class="card mb-4">
                <h5 class="card-header">Profile Details</h5>
                <div class="card-body">
                    <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                        <img
                            src="data:image/jpeg;base64,<?php echo base64_encode($userImg ? file_get_contents($userImg) : file_get_contents('../image/user_avatar.png')); ?>"
                            alt="user-avatar"
                            class="d-block rounded"
                            height="100"
                            width="100"
                        />
<div class="button-wrapper">
    <a href="blacklist_passenger.php?passengerID=<?php echo urlencode($passengerID); ?>" class="btn btn-danger me-2 mb-4">
        Blacklist
    </a>
    <a href="reporting.php?userID=<?php echo urlencode($profileUserID); ?>" class="btn btn-warning mb-4">
        Report
    </a>
</div>

                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="studentID" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="studentID" value="<?php echo htmlspecialchars($studentID); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($name); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($phone); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <input type="text" class="form-control" id="gender" value="<?php echo htmlspecialchars($gender); ?>" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="dateOfBirth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dateOfBirth" value="<?php echo htmlspecialchars($dateOfBirth); ?>" readonly>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
