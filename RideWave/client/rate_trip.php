<?php
include_once('../config/config.php');
include_once('remember_check.php');
include_once('own_passenger_profile_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"]; // Logged-in user's userID

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

$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : null; // Get trip ID from the URL

// Fetch passenger ID for the logged-in user
$passengerID = getPassengerID($userID);

// Fetch the trip details and check if it's in an acceptable status
$tripDetails = getTripDetails($tripID);

// Check if the passenger is approved for the trip
$isPassengerApproved = checkPassengerBookingStatus($passengerID, $tripID);

// Ensure that the trip is either ongoing or completed and the passenger is approved
if ($tripDetails && $isPassengerApproved) {
    // Check if the trip is "ongoing" or "completed"
    if ($tripDetails['status'] !== 'ongoing' && $tripDetails['status'] !== 'completed') {
        echo "<script>alert('You can only rate trips that are ongoing or completed.'); window.location.href = 'trip_history.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('You are not eligible to rate this trip.'); window.location.href = 'trip_history.php';</script>";
    exit();
}

// Check if the user has already rated this trip
$ratingExists = false;
$rating = null;
$comment = null;
$sql_rating = "SELECT star, comment FROM rating WHERE passengerID = ? AND tripID = ?";
if ($stmt = $mysqli->prepare($sql_rating)) {
    $stmt->bind_param("ii", $passengerID, $tripID);
    $stmt->execute();
    $stmt->bind_result($rating, $comment);
    if ($stmt->fetch()) {
        $ratingExists = true;
    }
    $stmt->close();
}

// Process rating submission or deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_rating'])) {
        // Delete the rating
        deleteRating($passengerID, $tripID);
        echo "<script>
            alert('Your rating has been deleted.');
            window.location.href = 'rate_trip.php?tripID=" . $tripID . "';
        </script>";
        exit();
    } else {
        // Handle rating submission
        $star = isset($_POST['star']) ? intval($_POST['star']) : null;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;
        $error = null;

        // Validate the rating value
        if ($star < 1 || $star > 5) {
            $error = "Please select a valid star rating (1-5).";
        }

        if (!$error) {
            if ($ratingExists) {
                // Update existing rating
                updateRating($star, $comment, $passengerID, $tripID);
            } else {
                // Insert new rating
                insertRating($star, $comment, $passengerID, $tripID);
            }

            // Redirect after successful submission
            echo "<script>
                alert('Thank you for your rating!');
                window.location.href = 'rate_trip.php?tripID=" . $tripID . "';
            </script>";
            exit();
        }
    }
}

function getPassengerID($userID) {
    global $mysqli;
    $sql_passenger = "SELECT passengerID FROM passenger WHERE userID = ?";
    if ($stmt = $mysqli->prepare($sql_passenger)) {
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->bind_result($passengerID);
        $stmt->fetch();
        $stmt->close();
    }
    return $passengerID;
}

function getTripDetails($tripID) {
    global $mysqli;
    $tripDetails = null;
    $sql_trip = "SELECT t.startLocation, t.endLocation, t.startDateTime, u.username AS driverName, t.status
                 FROM trip t
                 JOIN driver d ON t.driverID = d.driverID
                 JOIN user u ON d.userID = u.userID
                 WHERE t.tripID = ?";
    if ($stmt = $mysqli->prepare($sql_trip)) {
        $stmt->bind_param("i", $tripID);
        $stmt->execute();
        $stmt->bind_result($startLocation, $endLocation, $startDateTime, $driverName, $status);
        if ($stmt->fetch()) {
            // Trip data found, continue to render the page
            $tripDetails = array(
                'startLocation' => $startLocation,
                'endLocation' => $endLocation,
                'startDateTime' => $startDateTime,
                'driverName' => $driverName, // Include driver name in the result
                'status' => $status // Include trip status
            );
        } else {
            // Trip not found
            echo "<script>alert('Trip not found.'); window.location.href = 'trip_history.php';</script>";
            exit();
        }
        $stmt->close();
    }
    return $tripDetails;
}

function checkPassengerBookingStatus($passengerID, $tripID) {
    global $mysqli;
    $sql_booking = "SELECT status FROM booking WHERE passengerID = ? AND tripID = ?";
    if ($stmt = $mysqli->prepare($sql_booking)) {
        $stmt->bind_param("ii", $passengerID, $tripID);
        $stmt->execute();
        $stmt->bind_result($status);
        if ($stmt->fetch() && $status === 'approved') {
            return true;
        }
        $stmt->close();
    }
    return false;
}

function updateRating($star, $comment, $passengerID, $tripID) {
    global $mysqli;
    $sql_update_rating = "UPDATE rating SET star = ?, comment = ?, dateSubmitted = CURDATE() WHERE passengerID = ? AND tripID = ?";
    if ($stmt = $mysqli->prepare($sql_update_rating)) {
        $stmt->bind_param("isii", $star, $comment, $passengerID, $tripID);
        $stmt->execute();
        $stmt->close();
    }
}

function insertRating($star, $comment, $passengerID, $tripID) {
    global $mysqli;
    $sql_insert_rating = "INSERT INTO rating (passengerID, tripID, star, comment, dateSubmitted) VALUES (?, ?, ?, ?, CURDATE())";
    if ($stmt = $mysqli->prepare($sql_insert_rating)) {
        $stmt->bind_param("iiis", $passengerID, $tripID, $star, $comment);
        $stmt->execute();
        $stmt->close();
    }
}

function deleteRating($passengerID, $tripID) {
    global $mysqli;
    $sql_delete_rating = "DELETE FROM rating WHERE passengerID = ? AND tripID = ?";
    if ($stmt = $mysqli->prepare($sql_delete_rating)) {
        $stmt->bind_param("ii", $passengerID, $tripID);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Rate Trip</title>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Passenger /</span> Rate Trip</h4>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Trip Details</h5>
                    <p><strong>Driver:</strong> <?php echo htmlspecialchars($tripDetails['driverName']); ?></p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($tripDetails['startLocation']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($tripDetails['endLocation']); ?></p>
                    <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($tripDetails['startDateTime']); ?></p>
                    
                    <hr>

                    <h5 class="card-title">Submit Your Rating</h5>

<?php if ($ratingExists): ?>
    <p>You have already rated this trip:</p>
    <p><strong>Star Rating:</strong> <br>
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <span style="font-size: 2rem; color: <?php echo ($i <= $rating) ? '#ffc107' : '#ccc'; ?>;">&#9733;</span>
        <?php endfor; ?>
    </p>
    <p><strong>Comment:</strong> <?php echo htmlspecialchars($comment); ?></p>
    <form method="POST">
        <button type="submit" name="delete_rating" class="btn btn-danger">Delete Rating</button>
    </form>
<?php else: ?>
    <form method="POST">
        <div class="mb-3">
            <label for="star" class="form-label">Star Rating</label>
            <div class="star-rating">
                <input type="radio" name="star" value="1" id="star1" required><label for="star1">&#9733;</label>
                <input type="radio" name="star" value="2" id="star2"><label for="star2">&#9733;</label>
                <input type="radio" name="star" value="3" id="star3"><label for="star3">&#9733;</label>
                <input type="radio" name="star" value="4" id="star4"><label for="star4">&#9733;</label>
                <input type="radio" name="star" value="5" id="star5"><label for="star5">&#9733;</label>
            </div>
        </div>

        <div class="mb-3">
            <label for="comment" class="form-label">Comment</label>
            <textarea name="comment" id="comment" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Rating</button>
    </form>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
<?php endif; ?>


                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <style>
        .star-rating {
            display: flex;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
        }

        .star-rating input[type="radio"]:checked,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }

        /* Hover effect */
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }

        /* Reset color for unchecked stars */
        .star-rating input[type="radio"]:not(:checked) ~ label {
            color: #ccc;
        }
    </style>
    
    <script>
        const stars = document.querySelectorAll('.star-rating input[type="radio"]');
        const labels = document.querySelectorAll('.star-rating label');

        stars.forEach((star, index) => {
            star.addEventListener('change', () => {
                labels.forEach(label => label.style.color = '#ccc');

                for (let i = 0; i <= index; i++) {
                    labels[i].style.color = '#ffc107';
                }
            });
        });
    </script>
        
</body>
</html>
