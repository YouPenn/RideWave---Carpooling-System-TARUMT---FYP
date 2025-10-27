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

// Fetch the userID associated with the driverID
$driverUserID = null;
$sql_driver_user = "SELECT userID FROM driver WHERE driverID = ?";
if ($stmt = $mysqli->prepare($sql_driver_user)) {
    $stmt->bind_param("i", $driverID);
    $stmt->execute();
    $stmt->bind_result($driverUserID);
    $stmt->fetch();
    $stmt->close();
}

// Redirect to error page if no userID is associated with the driverID
if (!$driverUserID) {
    header("Location: error.php");
    exit();
}

// Check the status of the user associated with the driver
$userStatus = null;
$sql_user_status = "SELECT status FROM user WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_user_status)) {
    $stmt->bind_param("i", $driverUserID);
    $stmt->execute();
    $stmt->bind_result($userStatus);
    $stmt->fetch();
    $stmt->close();
}

// If the user is not active, show an alert and redirect
if ($userStatus !== 'active') {
    $statusMessage = $userStatus === 'banned' ? 'This user is banned.' : 'This user has been deleted.';
    echo "<script>
        alert('$statusMessage');
    </script>";
}

// Check if the profile being viewed is the user's own profile
if ($driverUserID === $loggedInUserID) {
    header("Location: own_driver_profile.php"); // Redirect to user's own profile page
    exit();
}

// Initialize default values for variables
$userID = $studentID = $name = $email = $phone = $gender = $dateOfBirth = $userImg = $carImg = $licenseImg = $licenseNum = $carRegNo = "";

// Fetch driver and user details
$sql = "SELECT u.studentID, u.username AS name, u.email, u.phoneNumber, 
        u.gender, u.dateOfBirth, u.userImg, d.carImg, d.licenseImg, d.licenseNum, d.carRegNo
        FROM driver d
        JOIN user u ON d.userID = u.userID
        WHERE d.driverID = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $driverID);
    $stmt->execute();
    $stmt->bind_result($studentID, $name, $email, $phone, $gender, $dateOfBirth, $userImg, $carImg, $licenseImg, $licenseNum, $carRegNo);

    if (!$stmt->fetch()) {
        // Redirect to error page if no driver is found
        header("Location: error.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: error.php");
    exit();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Driver Profile</title>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4">View Driver Profile</h4>

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
                            <a href="blacklist_driver.php?driverID=<?php echo urlencode($driverID); ?>" class="btn btn-danger me-2 mb-2">
                                Blacklist
                            </a>
                            <a href="reporting.php?userID=<?php echo urlencode($driverUserID); ?>" class="btn btn-warning me-2 mb-2">
                                Report
                            </a>
                            <a href="view_driver_trip_history.php?driverID=<?php echo urlencode($driverID); ?>" class="btn btn-info me-2 mb-2">
                                View Trip History
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
                        
                        <div class="col-md-6 mb-3">
                            <label for="licenseNum" class="form-label">IC/License Number</label>
                            <input type="text" class="form-control" id="licenseNum" value="<?php echo htmlspecialchars($licenseNum); ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="carRegNo" class="form-label">Car Registration Number</label>
                            <input type="text" class="form-control" id="carRegNo" value="<?php echo htmlspecialchars($carRegNo); ?>" readonly>
                        </div>
                    </div> 
                </div>
            </div>

<!-- Car Image and License Image Section -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <h5 class="card-header">Car Image</h5>
            <div class="card-body text-center">
                <?php if ($carImg) : ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode(file_get_contents($carImg)); ?>" alt="car-image" class="rounded" height="100%" width="100%"/>
                <?php else : ?>
                    <p>No car image available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <h5 class="card-header">License Image</h5>
            <div class="card-body text-center">
                <?php if ($licenseImg) : ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode(file_get_contents($licenseImg)); ?>" alt="license-image" class="rounded" height="100%" width="100%"/>
                <?php else : ?>
                    <p>No license image available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
