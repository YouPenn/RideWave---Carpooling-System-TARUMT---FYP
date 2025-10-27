<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include_once('../config/config.php');

// Define a secure encryption key
define('ENCRYPTION_KEY', 'your-secret-key'); // Replace with a strong key

function encryptOtp($otp) {
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($otp, $cipher, ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, $as_binary = true);
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

function decryptOtp($encryptedOtp) {
    $c = base64_decode($encryptedOtp);
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len = 32);
    $ciphertext_raw = substr($c, $ivlen + $sha2len);
    $calculated_hmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, $as_binary = true);
    if (hash_equals($hmac, $calculated_hmac)) {
        return openssl_decrypt($ciphertext_raw, $cipher, ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
    }
    return false;
}

$email = $otp = "";
$email_err = $otp_err = $otp_message = "";

// Check if the form is submitted to send OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_otp"])) {
    $email = trim($_POST["email"]);

    // Validate email
    if (empty($email)) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Check if the email exists in the database
        $sql_check_email = "SELECT email FROM user WHERE email = ?";
        if ($stmt_check_email = $mysqli->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();

            if ($stmt_check_email->num_rows == 0) {
                // Email not found
                echo "<script>
                        alert('Email not found. Please register or try a different email.');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
            $stmt_check_email->close();
        }
        
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Encrypt the OTP
        $encryptedOtp = encryptOtp($otp);

        // Save encrypted OTP in the database
        $sql = "UPDATE user SET otp = ? WHERE email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ss", $encryptedOtp, $email);
            $stmt->execute();
            $stmt->close();
        }

        // Send OTP via email using PHPMailer
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
            $mail->setFrom('your_email@gmail.com', 'RideWave');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset OTP';
            $mail->Body    = "Your OTP for resetting your password is: <b>" . $otp . "</b>";
            $mail->AltBody = "Your OTP for resetting your password is: " . $otp;

            $mail->send();
            $otp_message = "OTP has been sent to your email.";
        } catch (Exception $e) {
            $otp_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

// Verify OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_otp"])) {
    $email = trim($_POST["email"]);
    $otp_input = trim($_POST["otp"]);

    if (empty($email)) {
        $otp_err = "Invalid Email or OTP.";
    } elseif (empty($otp_input)) {
        $otp_err = "Invalid OTP.";
    } else {
        // Retrieve encrypted OTP from the database
        $sql = "SELECT otp FROM user WHERE email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($encryptedOtpFromDb);
                $stmt->fetch();

                // Decrypt the OTP from the database
                if ($encryptedOtpFromDb) {
                    $decryptedOtp = decryptOtp($encryptedOtpFromDb);

                    if ($decryptedOtp === $otp_input) {
                        // OTP is correct, proceed to reset password page
                        header("Location: reset_password.php?email=" . urlencode($email));
                        exit();
                    } else {
                        $otp_err = "Invalid OTP. Please try again.";
                    }
                } else {
                    $otp_err = "No OTP found. Please request a new one.";
                }
            } else {
                $email_err = "Email not found. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
   
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="assets/vendor/css/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />
    <script src="assets/vendor/js/helpers.js"></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Forgot Password Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <!-- Include your logo SVG here -->
                                </span>
                                <h2 class="app-brand-text text-body fw-bolder">Forgot Password</h2>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <!-- Send OTP Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-3">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <input
                                        type="email"
                                        name="email"
                                        id="email"
                                        class="form-control"
                                        placeholder="Enter your email"
                                        required
                                        value="<?php echo htmlspecialchars($email); ?>"
                                        aria-label="Email"
                                    />
                                    <button type="submit" name="send_otp" class="btn btn-outline-primary" id="button-addon2">Get OTP</button>
                                </div>
                                <span class="text-danger"><?php echo $email_err; ?></span>
                            </div>
                        </form>

                        <!-- OTP Message -->
                        <p class="text-success"><?php echo $otp_message; ?></p>

                        <!-- Verify OTP Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP</label>
                                <input type="text" name="otp" id="otp" class="form-control" placeholder="Enter the OTP sent to your email" required>
                                <span class="text-danger"><?php echo $otp_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <button type="submit" name="verify_otp" class="btn btn-primary d-grid w-100">Next</button>
                            </div>
                        </form>

                        <div class="text-center">
                            <a href="login.php" class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                                Back to login
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password Card -->
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

