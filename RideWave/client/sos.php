<?php
include_once('../config/config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || !$_SESSION["loggedin"]) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$userID = $_SESSION["userID"];

// Check if location data is received via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['location'])) {
    $location = $_POST['location'];
    $status = "active";

    // Prepare and execute SQL query to insert SOS data
    $sql = "INSERT INTO sos (userID, dateTime, location, status) VALUES (?, NOW(), ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("iss", $userID, $location, $status);
        if ($stmt->execute()) {
            echo "SOS request inserted successfully.";

            // Send email notification
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ourbus2003@gmail.com';
                $mail->Password = 'nbcb anqx vzug lupd';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('ourbus2003@gmail.com', 'SOS');
                $mail->addAddress('teeyp-jm21@student.tarc.edu.my');

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'SOS Alert!';
                $mail->Body = "An SOS alert has been triggered by user ID: $userID. <br> Location: $location <br> Date and Time: " . date('Y-m-d H:i:s');

                // Send the email
                if ($mail->send()) {
                    echo "<script>
                        alert('SOS request inserted and email sent successfully.');
                        window.location.href = 'welcome.php'; // Redirect to dashboard or another page
                    </script>";
                } else {
                    echo "<script>
                        alert('SOS request inserted but email could not be sent.');
                        window.location.href = 'welcome.php'; // Redirect to dashboard or another page
                    </script>";
                }

            } catch (Exception $e) {
                echo "<script>
                    alert('SOS request inserted but email could not be sent. Mailer Error: {$mail->ErrorInfo}');
                    window.location.href = 'welcome.php'; // Redirect to dashboard or another page
                </script>";
            }
        } else {
            echo "<script>
                alert('Error: Could not send SOS request. " . addslashes($stmt->error) . "');
                window.location.href = 'welcome.php'; // Redirect to dashboard or another page
            </script>";
        }
        $stmt->close();
    } else {
        echo "<script>
            alert('Error in preparing the statement: " . addslashes($mysqli->error) . "');
            window.location.href = 'welcome.php'; // Redirect to dashboard or another page
        </script>";
    }
} else {
    echo "<script>
        alert('No location data received.');
        window.location.href = 'welcome.php'; // Redirect to dashboard or another page
    </script>";
}
$mysqli->close();
?>
