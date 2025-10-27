<?php
include_once('../config/config.php');
session_start();

$username = $password = "";
$username_err = $password_err = $login_err = "";


// Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (empty(trim($_POST["identifier"]))) {
            $username_err = "Please enter your email or student ID.";
        } else {
            $identifier = trim($_POST["identifier"]);
        }

// Determine if input is email or student ID
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $column = "email"; // If input is a valid email
    } else {
        $column = "studentID"; // Otherwise, treat it as a student ID
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If there are no errors
    if (empty($username_err) && empty($password_err)) {
        // Prepare a statement to fetch user details
$sql = "SELECT userID, username, password, login_attempts, unlock_time, status FROM user WHERE $column = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("s", $identifier);
    if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userID, $username, $hashed_password, $login_attempts, $unlock_time, $status);
            if ($stmt->fetch()) {
                        // Check if user is banned
                        if ($status === 'banned') {
                            $login_err = "Your account has been banned. Please contact support.";
                        } else {
                            // Check if the account is locked
                            if ($unlock_time && strtotime($unlock_time) > time()) {
                                $login_err = "Account locked. Try again later.";
                            } else {
                                // Verify password
                                if (password_verify($password, $hashed_password)) {
                                    // Reset login attempts and unlock time upon successful login
                                    $reset_sql = "UPDATE user SET login_attempts = 0, unlock_time = NULL WHERE userID = ?";
                                    $reset_stmt = $mysqli->prepare($reset_sql);
                                    $reset_stmt->bind_param("i", $userID);
                                    $reset_stmt->execute();
                                    $reset_stmt->close();

                                    // Set session variables
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["userID"] = $userID;
                                    $_SESSION["username"] = $username;

                                    // Handle "Remember Me" functionality
                                    if (isset($_POST['remember'])) {
                                        $token = bin2hex(random_bytes(16));
                                        $expiry_time = time() + (86400 * 30);
                                        setcookie("remember_token", $token, $expiry_time, "/", "", false, true);

                                        $update_token_sql = "UPDATE user SET remember_token = ? WHERE userID = ?";
                                        $update_token_stmt = $mysqli->prepare($update_token_sql);
                                        $update_token_stmt->bind_param("si", $token, $userID);
                                        $update_token_stmt->execute();
                                        $update_token_stmt->close();
                                    } 
                                    
                                    // Redirect to welcome page
                                    header("Location: welcome.php");
                                    exit();
                                } else {
                                    // Increment login attempts if password is incorrect
                                    $login_attempts++;
                                    $update_attempts_sql = "UPDATE user SET login_attempts = ? WHERE userID = ?";
                                    $update_attempts_stmt = $mysqli->prepare($update_attempts_sql);
                                    $update_attempts_stmt->bind_param("ii", $login_attempts, $userID);
                                    $update_attempts_stmt->execute();
                                    $update_attempts_stmt->close();

                                    // Lock account if login attempts reach 3
                                    if ($login_attempts >= 3) {
                                        $unlock_time = date("Y-m-d H:i:s", strtotime("+30 seconds"));
                                        $lock_sql = "UPDATE user SET unlock_time = ? WHERE userID = ?";
                                        $lock_stmt = $mysqli->prepare($lock_sql);
                                        $lock_stmt->bind_param("si", $unlock_time, $userID);
                                        $lock_stmt->execute();
                                        $lock_stmt->close();

                                        $login_err = "Account locked due to multiple failed login attempts. Try again after 30 seconds.";
                                    } else {
                                        $login_err = "Invalid email, student ID, or password.";
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $login_err = "Invalid email or student ID.";
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                <!-- Login Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <!-- Include Sneat logo SVG here -->
                                </span>
                                <h2 class="app-brand-text text-body fw-bolder">Login</h2>
                            </a>
                        </div>
                        <!-- /Logo -->
                       

                        <!-- Error Message -->
                        <?php if (!empty($login_err)) echo '<div class="alert alert-danger">' . $login_err . '</div>'; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-3">
                            <div class="mb-3">
                                <label for="identifier" class="form-label">Email or Student ID</label>
                                <input type="text" name="identifier" id="identifier" class="form-control" value="<?php echo $username; ?>" placeholder="Enter your email or student ID">
                                <span class="text-danger"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="forgot_password.php"><small>Forgot Password?</small></a>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                                <span class="text-danger"><?php echo $password_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember-me">
                                    <label class="form-check-label" for="remember-me">Remember Me</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary d-grid w-100">Login</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>Don't have an account?</span>
                            <a href="register.php"><span>Register here</span></a>
                        </p>
                        
                        <div class="text-center">
                            <a href="../index.php" class="d-flex align-items-center justify-content-center">
                                <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
                                Back
                            </a>
                        </div>
                        
                    </div>
                    
                    
                </div>
                <!-- /Login Card -->
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

