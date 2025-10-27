<?php
include_once('../config/config.php');

// Initialize variables
$email = isset($_GET['email']) ? $_GET['email'] : '';
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = $reset_err = "";

// Check if email is provided
if (empty($email)) {
    echo "<script>
            alert('Invalid request. No email provided.');
            window.location.href = 'login.php';
          </script>";
    exit();
}

// Process form data when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter a new password.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', trim($_POST["new_password"]))) {
        $new_password_err = "Password must be at least 8 characters, with at least one uppercase letter, one lowercase letter, one digit, and one special character.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($new_password != $confirm_password) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check for errors before updating the password
    if (empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare an update statement
        $sql = "UPDATE user SET password = ?, otp = NULL WHERE email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            // Hash the new password
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $new_password_hashed, $email);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                echo "Password has been reset successfully.";
                // Redirect to login page after successful password reset
                header("Location: login.php");
                exit();
            } else {
                $reset_err = "Something went wrong. Please try again.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    
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
                <!-- Reset Password Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <!-- Include your logo SVG here -->
                                </span>
                                <h2 class="app-brand-text text-body fw-bolder">Reset Password</h2>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <!-- Error Message -->
                        <?php if (!empty($reset_err)) echo '<div class="alert alert-danger">' . $reset_err . '</div>'; ?>

                        <!-- Reset Password Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?email=' . urlencode($email); ?>" method="post" class="mb-3">
<div class="mb-3">
    <label for="new_password" class="form-label">New Password</label>
    <div class="input-group input-group-merge">
        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
        <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('new_password', this)">
            <i class="bx bx-hide"></i>
        </span>
    </div>
    <span class="text-danger"><?php echo $new_password_err; ?></span>
</div>
<div class="mb-3">
    <label for="confirm_password" class="form-label">Confirm Password</label>
    <div class="input-group input-group-merge">
        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
        <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('confirm_password', this)">
            <i class="bx bx-hide"></i>
        </span>
    </div>
    <span class="text-danger"><?php echo $confirm_password_err; ?></span>
</div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary d-grid w-100">Save</button>
                            </div>
                        </form>

                        <!-- Back to Login Link -->
                        <div class="text-center">
                            <a href="login.php" class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                                Back to login
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Reset Password Card -->
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
    
    <script>
    function togglePasswordVisibility(fieldId, icon) {
        const field = document.getElementById(fieldId);
        const iconElement = icon.querySelector("i");

        if (field.type === "password") {
            field.type = "text";
            iconElement.classList.replace("bx-hide", "bx-show");
        } else {
            field.type = "password";
            iconElement.classList.replace("bx-show", "bx-hide");
        }
    }
    </script>
</body>
</html>

