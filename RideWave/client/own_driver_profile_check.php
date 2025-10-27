<?php
include_once('../config/config.php');
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION["userID"];


// Query to check if any required fields are NULL, empty, or invalid
$sql = "SELECT u.studentID, u.username, u.email, u.phoneNumber, u.gender, u.dateOfBirth, 
               d.licenseNum, d.carRegNo, d.licenseImg, d.carImg, d.licenseExpiry, d.licenseExpiryNotification 
        FROM user u
        LEFT JOIN driver d ON u.userID = d.userID
        WHERE u.userID = ?";

$redirectToProfile = false;
$expiredLicense = false;
$licenseExpiry = null;
$licenseExpiryNotification = null;
$email = null;
$username = null;

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($studentID, $username, $email, $phoneNumber, $gender, $dateOfBirth, $licenseNum, $carRegNo, $licenseImg, $carImg, $licenseExpiry, $licenseExpiryNotification);
    $stmt->fetch();
    $stmt->close();

    // Check required fields for NULL, empty values, or invalid dates
    if (is_null($studentID) || is_null($username) || is_null($email) || is_null($phoneNumber) || 
        is_null($gender) || $dateOfBirth == "0000-00-00" || is_null($dateOfBirth) || 
        is_null($licenseNum) || is_null($carRegNo) || is_null($licenseImg) || is_null($carImg) || 
        is_null($licenseExpiry) || $licenseExpiry == "0000-00-00" ||
        empty($studentID) || empty($username) || empty($email) || empty($phoneNumber) || 
        empty($gender) || empty($dateOfBirth) || empty($licenseNum) || 
        empty($carRegNo) || empty($licenseImg) || empty($carImg) || empty($licenseExpiry)) {
        $redirectToProfile = true;
    }

    // Check if the license has expired
    if (!is_null($licenseExpiry) && strtotime($licenseExpiry) < strtotime(date("Y-m-d"))) {
        $redirectToProfile = true;
        $expiredLicense = true;
    }

    // Check if a notification needs to be sent (license expiring within 7 days, notification not yet sent)
    $currentDate = date("Y-m-d");
    $sevenDaysAhead = date("Y-m-d", strtotime("+7 days"));

    if (!is_null($licenseExpiry) && 
        strtotime($licenseExpiry) <= strtotime($sevenDaysAhead) && 
        (is_null($licenseExpiryNotification) || strtotime($licenseExpiryNotification) < strtotime($currentDate))) {

        // Send email notification
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ourbus2003@gmail.com';  // SMTP username
            $mail->Password = 'nbcb anqx vzug lupd';  // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('ourbus2003@gmail.com', 'RideWave Admin');
            $mail->addAddress($email, $username);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'License Expiry Reminder';
            $mail->Body = "
                <p>Dear $username,</p>
                <p>This is a friendly reminder that your driving license is set to expire on <strong>$licenseExpiry</strong>.</p>
                <p>Please renew your license as soon as possible to avoid disruptions to your services.</p>
                <p>Thank you,</p>
                <p>RideWave Team</p>
            ";

            $mail->send();

            // Update the licenseExpiryNotification date in the database
            $update_sql = "UPDATE driver SET licenseExpiryNotification = CURDATE() WHERE userID = ?";
            if ($update_stmt = $mysqli->prepare($update_sql)) {
                $update_stmt->bind_param("i", $userID);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Email notification failed: " . $mail->ErrorInfo);
        }
    }
} else {
    // Handle query error
    echo "Error: Unable to execute query.";
}

// Display alert and redirect if required
if ($redirectToProfile) {
    if ($expiredLicense) {
        echo "<script>
                alert('Your license has expired. Please update the license expiry date in your profile.');
                window.location.href = 'own_driver_profile.php';
              </script>";
    } else {
        echo "<script>
                alert('Please complete all required information in your profile.');
                window.location.href = 'own_driver_profile.php';
              </script>";
    }
    exit();
}
?>