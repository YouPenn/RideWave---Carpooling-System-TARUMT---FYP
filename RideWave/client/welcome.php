<?php
include_once('remember_check.php');

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once('../config/config.php'); // Database connection

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

//Update ongoing trips to "completed" if startDateTime has passed
$currentDateTime = date("Y-m-d H:i:s"); // Current date and time
$sql_update_trips = "UPDATE trip SET status = 'completed' WHERE status = 'ongoing' AND startDateTime <= ?";
if ($stmt = $mysqli->prepare($sql_update_trips)) {
    $stmt->bind_param("s", $currentDateTime);
    $stmt->execute();
    $stmt->close();
}

//// Check if user exists in the passenger table, insert if not
//$sql_check = "SELECT passengerID FROM passenger WHERE userID = ?";
//$stmt_check = $mysqli->prepare($sql_check);
//$stmt_check->bind_param("i", $userID);
//$stmt_check->execute();
//$stmt_check->store_result();
//
//if ($stmt_check->num_rows == 0) {
//    // User not found in passenger table, insert new record
//    $sql_insert = "INSERT INTO passenger (userID, status) VALUES (?, 'active')";
//    $stmt_insert = $mysqli->prepare($sql_insert);
//    $stmt_insert->bind_param("i", $userID);
//    $stmt_insert->execute();
//    $stmt_insert->close();
//}
//$stmt_check->close();
//
//// Check if the user already exists in the driver table, and insert if not
//$sql_check1 = "SELECT driverID FROM driver WHERE userID = ?";
//$stmt_check1 = $mysqli->prepare($sql_check1);
//$stmt_check1->bind_param("i", $userID);
//$stmt_check1->execute();
//$stmt_check1->store_result();
//
//if ($stmt_check1->num_rows == 0) {
//    // If the user is not already a driver, insert a new record in the driver table
//    $sql_insert1 = "INSERT INTO driver (userID) VALUES (?)";
//    if ($stmt_insert1 = $mysqli->prepare($sql_insert1)) {
//        $stmt_insert1->bind_param("i", $userID);
//        $stmt_insert1->execute();
//        $stmt_insert1->close();
//    }
//}
//$stmt_check1->close();


// Handle role selection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['role'])) {
        if ($_POST['role'] == 'passenger') {
            header("Location: passenger_dashboard.php");
            exit();
        } elseif ($_POST['role'] == 'driver') {
            header("Location: driver_dashboard.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Welcome</title>
    <!-- Bootstrap CSS -->
    <style>
        /* Custom Styles */
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        .card-custom:hover {
            transform: translateY(-5px);
        }
        .btn-custom {
            border-radius: 25px;
            font-size: 1.1rem;
            padding: 10px 20px;
            margin: 10px 0;
            width: 100%;
        }
        .header-icon {
            width: 200px; /* Adjust the width as needed */
            height: auto;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-custom text-center p-4">
                    <div class="card-body">
                        <div class="mb-4">
                            <img src="../image/RideWave_logo.png" alt="RideWave Logo" class="header-icon">
                            <h3 class="mt-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h3>
                            <p class="text-muted">Please select your role to proceed:</p>
                        </div>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <button type="submit" name="role" value="passenger" class="btn btn-primary btn-custom">Passenger</button>
                            <button type="submit" name="role" value="driver" class="btn btn-secondary btn-custom">Driver</button>
                        </form>
                        <a href="logout.php" class="btn btn-outline-danger btn-custom mt-4">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="buy-now">
      <a href="sos.php" class="btn btn-danger btn-buy-now">SOS</a>
    </div>
    
    
<!-- Spinner Overlay -->
<div id="spinner-overlay" class="overlay" style="display: none;">
    <div class="spinner"></div>
</div>

<style>
/* Spinner styles */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
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
    
    <!-- Optional JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    
        <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="assets/js/dashboards-analytics.js"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    
    
    <!-- Custom SOS Script -->
<script>
// Show the spinner when the SOS button is clicked
document.querySelector('.btn-buy-now').addEventListener('click', function(event) {
    event.preventDefault(); // Prevents page reload
    const spinnerOverlay = document.getElementById('spinner-overlay');
    spinnerOverlay.style.display = 'flex'; // Show the spinner

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(saveLocation, showError);
    } else {
        alert("Geolocation is not supported by this browser.");
        spinnerOverlay.style.display = 'none'; // Hide the spinner
    }
});

function saveLocation(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const location = `Latitude: ${latitude}, Longitude: ${longitude}`;

    // Debugging: Log location data
    console.log("Location:", location);

    // Send the location data to sos.php using AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "sos.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            const spinnerOverlay = document.getElementById('spinner-overlay');
            spinnerOverlay.style.display = 'none'; // Hide the spinner

            console.log("Server Response:", xhr.responseText); // Log server response for debugging
            if (xhr.status === 200) {
                alert("SOS request sent successfully!");
            } else {
                alert("Error sending SOS request. Status: " + xhr.status);
            }
        }
    };
    xhr.send("location=" + encodeURIComponent(location));
}

function showError(error) {
    const spinnerOverlay = document.getElementById('spinner-overlay');
    spinnerOverlay.style.display = 'none'; // Hide the spinner

    switch (error.code) {
        case error.PERMISSION_DENIED:
            alert("User denied the request for Geolocation.");
            break;
        case error.POSITION_UNAVAILABLE:
            alert("Location information is unavailable.");
            break;
        case error.TIMEOUT:
            alert("The request to get user location timed out.");
            break;
        case error.UNKNOWN_ERROR:
            alert("An unknown error occurred.");
            break;
    }
}
</script>

    
    
</body>
</html>
