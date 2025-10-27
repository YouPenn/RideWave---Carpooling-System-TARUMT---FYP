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

$loggedInUserID = $_SESSION["userID"]; // Logged-in user's ID

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

// Fetch blacklisted users
$blacklistedUsers = [];
$sql_blacklist = "SELECT b.blacklistID, b.blacklistedID, b.role, u.username, u.email
                  FROM blacklist b
                  JOIN user u ON b.blacklistedID = u.userID
                  WHERE b.userID = ?";
if ($stmt = $mysqli->prepare($sql_blacklist)) {
    $stmt->bind_param("i", $loggedInUserID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $blacklistedUsers[] = $row;
    }
    $stmt->close();
}

// Handle unblock functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock'])) {
    $blacklistID = intval($_POST['blacklistID']);

    $sql_unblock = "DELETE FROM blacklist WHERE blacklistID = ? AND userID = ?";
    if ($stmt = $mysqli->prepare($sql_unblock)) {
        $stmt->bind_param("ii", $blacklistID, $loggedInUserID);
        if ($stmt->execute()) {
            echo "<script>
                alert('User successfully unblocked.');
                window.location.href = 'blacklist.php';
            </script>";
        } else {
            echo "<script>
                alert('Failed to unblock the user. Please try again.');
                window.location.href = 'blacklist.php';
            </script>";
        }
        $stmt->close();
    }
}

// Handle view functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view'])) {
    $blacklistedID = intval($_POST['blacklistedID']);
    $role = $_POST['role'];

    if ($role === 'driver') {
        // Fetch driverID using userID
        $driverID = null;
        $sql_driver = "SELECT driverID FROM driver WHERE userID = ?";
        if ($stmt = $mysqli->prepare($sql_driver)) {
            $stmt->bind_param("i", $blacklistedID);
            $stmt->execute();
            $stmt->bind_result($driverID);
            $stmt->fetch();
            $stmt->close();
        }
        if ($driverID) {
            header("Location: view_driver_profile.php?driverID=" . urlencode($driverID));
            exit();
        }
    } elseif ($role === 'passenger') {
        // Fetch passengerID using userID
        $passengerID = null;
        $sql_passenger = "SELECT passengerID FROM passenger WHERE userID = ?";
        if ($stmt = $mysqli->prepare($sql_passenger)) {
            $stmt->bind_param("i", $blacklistedID);
            $stmt->execute();
            $stmt->bind_result($passengerID);
            $stmt->fetch();
            $stmt->close();
        }
        if ($passengerID) {
            header("Location: view_passenger_profile.php?passengerID=" . urlencode($passengerID));
            exit();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>Blacklist</title>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">   
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">User /</span> Blacklist</h4> 
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Blacklisted Users</h5>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($blacklistedUsers)) : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No blacklisted users found.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($blacklistedUsers as $index => $user) : ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                            <td>
                                                <form method="POST" action="" style="display: inline-block;" target="_blank">
                                                    <input type="hidden" name="blacklistedID" value="<?php echo htmlspecialchars($user['blacklistedID']); ?>">
                                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                                                    <button type="submit" name="view" class="btn btn-info btn-sm" target="_blank">
                                                        View
                                                    </button>
                                                </form>
                                                <form method="POST" action="" style="display: inline-block;">
                                                    <input type="hidden" name="blacklistID" value="<?php echo htmlspecialchars($user['blacklistID']); ?>">
                                                    <button type="submit" name="unblock" class="btn btn-danger btn-sm">
                                                        Unblock
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>  
        </div>    
    </div>

    <?php include_once('footer.php'); ?>        
</body>
</html>
