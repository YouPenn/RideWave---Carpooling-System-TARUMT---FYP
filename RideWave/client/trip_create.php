<?php 
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_driver_profile_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a driver
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["userID"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"];

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

// Fetch the driver's ID from the `driver` table
$driverID = null;
$sql_driver = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($sql_driver)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($driverID);
    $stmt->fetch();
    $stmt->close();
}

// If no driverID is found, redirect to the dashboard with an error
if (is_null($driverID)) {
    echo "<script>
        alert('Error: Unable to find your driver profile.');
        window.location.href = 'driver_dashboard.php';
    </script>";
    exit();
}

// Initialize variables
$startLocation = $endLocation = $startDateTime = $amount = $availableSeat = $pickupLocation = "";

// Handle form submission to create a new trip
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $startLocation = trim($_POST["startLocation"]);
    $endLocation = trim($_POST["endLocation"]);
    $startDateTime = $_POST["startDateTime"];
    $amount = $_POST["amount"];
    $availableSeat = $_POST["availableSeat"];
    $pickupLocation = trim($_POST["pickupLocation"]);

    // Server-side validation
    if (empty($startLocation) || empty($endLocation) || empty($startDateTime) || 
        empty($amount) || empty($availableSeat) || empty($pickupLocation)) {
        echo "<script>
            alert('All fields are required.');
            window.history.back();
        </script>";
        exit();
    } elseif (!is_numeric($amount) || $amount <= 0) {
        echo "<script>
            alert('Amount must be a positive number.');
            window.history.back();
        </script>";
        exit();
    } elseif (!is_numeric($availableSeat) || $availableSeat < 1) {
        echo "<script>
            alert('Available seats must be at least 1.');
            window.history.back();
        </script>";
        exit();
    } elseif (strtotime($startDateTime) <= time()) {  // Ensure the date and time is in the future
        echo "<script>
            alert('Start date and time must be in the future.');
            window.history.back();
        </script>";
        exit();
    } else {
        // Insert the new trip into the database
        $sql = "INSERT INTO trip (driverID, startLocation, endLocation, startDateTime, status, amount, availableSeat, pickupLocation) 
                VALUES (?, ?, ?, ?, 'ongoing', ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("isssdss", $driverID, $startLocation, $endLocation, $startDateTime, $amount, $availableSeat, $pickupLocation);
            
            if ($stmt->execute()) {
                echo "<script>
                    alert('Trip created successfully!');
                    window.location.href = 'trip_create.php';
                </script>";
                exit();
            } else {
                echo "<script>
                    alert('Error: Unable to create trip. " . addslashes($stmt->error) . "');
                    window.history.back();
                </script>";
                exit();
            }
            $stmt->close();
        } else {
            echo "<script>
                alert('Error: Unable to prepare query.');
                window.history.back();
            </script>";
            exit();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Create New Trip</title>
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
    <?php include 'nav.php'; ?>

    <!-- Spinner Overlay -->
    <div id="spinner-overlay" class="overlay">
        <div class="spinner"></div>
    </div>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Driver /</span> Create Trip</h4> 
            
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return showSpinner()">
                        <div class="mb-3">
                            <label for="startLocation" class="form-label">Start Location</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="startLocation" 
                                name="startLocation" 
                                value="<?php echo htmlspecialchars($startLocation); ?>"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="endLocation" class="form-label">End Location</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="endLocation" 
                                name="endLocation" 
                                value="<?php echo htmlspecialchars($endLocation); ?>"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="startDateTime" class="form-label">Start Date and Time</label>
                            <input 
                                type="datetime-local" 
                                class="form-control" 
                                id="startDateTime" 
                                name="startDateTime" 
                                value="<?php echo htmlspecialchars($startDateTime); ?>"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (RM)</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="amount" 
                                name="amount" 
                                step="1" 
                                min="0" 
                                value="<?php echo htmlspecialchars($amount); ?>"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="availableSeat" class="form-label">Available Seats</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="availableSeat" 
                                name="availableSeat" 
                                min="1" 
                                value="<?php echo htmlspecialchars($availableSeat); ?>"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="pickupLocation" class="form-label">Pickup Location</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="pickupLocation" 
                                name="pickupLocation" 
                                value="<?php echo htmlspecialchars($pickupLocation); ?>"
                                required
                            />
                        </div>

                        <!-- Save and Cancel buttons -->
                        <div class="d-flex justify-content-between mt-2">
                            <div>
                                <button type="submit" name="save" class="btn btn-primary me-2">Create Trip</button>
                                <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>         
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Show the spinner and allow form submission
        function showSpinner() {
            document.getElementById("spinner-overlay").style.display = "flex";
            return true; // Allow form submission
        }

        // Set minimum date and time for the startDateTime field
        document.addEventListener("DOMContentLoaded", function() {
            const startDateTimeInput = document.getElementById("startDateTime");
            const now = new Date();
            const minDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16); // Convert to ISO format and adjust timezone
            startDateTimeInput.min = minDateTime;
        });
    </script>
</body>
</html>

