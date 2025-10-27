<?php
include_once('../config/config.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) && isset($_COOKIE["remember_token"])) {
    $token = $_COOKIE["remember_token"];

    $sql = "SELECT userID, username FROM user WHERE remember_token = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $token);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($userID, $username);
                if ($stmt->fetch()) {

                    $_SESSION["loggedin"] = true;
                    $_SESSION["userID"] = $userID;
                    $_SESSION["username"] = $username;
                }
            }
        }
        $stmt->close();
    }
}

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $loggedInUserID = $_SESSION["userID"];

    // Check the logged-in user's status
    $sql_check_status = "SELECT status FROM user WHERE userID = ?";
    if ($stmt = $mysqli->prepare($sql_check_status)) {
        $stmt->bind_param("i", $loggedInUserID);
        $stmt->execute();
        $stmt->bind_result($userStatus);
        if ($stmt->fetch()) {
            if ($userStatus !== 'active') {
                // If user is banned or deleted, log them out
                header("Location: logout.php");
                exit();
            }
        }
        $stmt->close();
    }
}
?>
