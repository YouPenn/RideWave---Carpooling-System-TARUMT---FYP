<?php
include_once('../config/config.php');

// Initialize variables to store form data and error messages
$username = $email = $password = $confirm_password = $studentID = "";
$username_err = $email_err = $password_err = $confirm_password_err = $studentID_err = $agree_err = "";

// reCAPTCHA secret key
$secretKey = "6LfdUi8qAAAAACmsWc4CLsUcXbjhn4rA0Heqr5wH";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    // Validate reCAPTCHA
    if (empty($_POST['g-recaptcha-response'])) {
        $agree_err = "Please complete the reCAPTCHA.";
    } else {
        $recaptchaResponse = $_POST['g-recaptcha-response'];
        $recaptchaURL = 'https://www.google.com/recaptcha/api/siteverify';
        $postData = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        // Send the request to Google's API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $recaptchaURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $apiResponse = curl_exec($ch);
        curl_close($ch);

        // Decode the JSON response
        $responseKeys = json_decode($apiResponse, true);

        // Check reCAPTCHA success
        if (!$responseKeys["success"]) {
            $agree_err = "reCAPTCHA verification failed. Please try again.";
        }
    }
    
    
    
    
    
// Validate student ID
if (empty(trim($_POST["studentID"]))) {
    $studentID_err = "Please enter a student ID.";
} else {
    $studentID_input = trim($_POST["studentID"]);
    
    // Check if the student ID matches the required format (2 numbers, 3 letters, 5 numbers)
    if (!preg_match('/^\d{2}[A-Za-z]{3}\d{5}$/', $studentID_input)) {
        $studentID_err = "Student ID must follow the format (e.g. 23JMR08033)";
    } else {
        // Check if the student ID already exists in the database
        $sql = "SELECT userID FROM user WHERE studentID = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $studentID_input);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $studentID_err = "This student ID is already registered.";
            } else {
                $studentID = $studentID_input;
            }
            $stmt->close();
        }
    }
}


    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username_input = trim($_POST["username"]);
        // Check if username already exists
        $sql = "SELECT userID FROM user WHERE username = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $username_input);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $username_err = "This username is already taken.";
            } else {
                $username = $username_input;
            }
            $stmt->close();
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email_input = trim($_POST["email"]);
        // Check if email already exists
        $sql = "SELECT userID FROM user WHERE email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $email_input);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $email_err = "This email is already registered.";
            } else {
                $email = $email_input;
            }
            $stmt->close();
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\W).{8,}$/', trim($_POST["password"]))) {
        $password_err = "Password must have at least 8 characters, 1 uppercase letter, 1 lowercase letter, and 1 symbol.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($password != $confirm_password) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate the "I agree" checkbox
    if (!isset($_POST["agree"])) {
        $agree_err = "You must agree to the privacy policy & terms.";
    }

    // Check for errors before inserting into the database
    if (empty($studentID_err) && empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($agree_err)) {
        // Prepare an insert statement for user
        $sql = "INSERT INTO user (studentID, username, email, password) VALUES (?, ?, ?, ?)";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssss", $studentID, $username, $email, $hashed_password);

            // Execute the statement
            if ($stmt->execute()) {
                // Get the user ID of the newly inserted user
                $userID = $stmt->insert_id;

                // Insert the user ID into the passenger table
                $sql_passenger = "INSERT INTO passenger (userID, status) VALUES (?, 'active')";
                if ($stmt_passenger = $mysqli->prepare($sql_passenger)) {
                    $stmt_passenger->bind_param("i", $userID);
                    $stmt_passenger->execute();
                    $stmt_passenger->close();
                }

                // Insert the user ID into the driver table
                $sql_driver = "INSERT INTO driver (userID) VALUES (?)";
                if ($stmt_driver = $mysqli->prepare($sql_driver)) {
                    $stmt_driver->bind_param("i", $userID);
                    $stmt_driver->execute();
                    $stmt_driver->close();
                }

                // Redirect to login.php after successful registration
                header("Location: login.php");
                exit();
            } else {
                echo "Something went wrong. Please try again.";
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
    <title>Register</title>
    
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="assets/vendor/css/core.css" />
    <link rel="stylesheet" href="assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="assets/vendor/css/pages/page-auth.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Register Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <!-- Include Sneat logo SVG here -->
                                </span>
                                <h2 class="app-brand-text text-body fw-bolder">Register</h2>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-3">
                            <div class="mb-3">
                                <label for="studentID" class="form-label">Student ID</label>
                                <input type="text" name="studentID" class="form-control" value="<?php echo $studentID; ?>">
                                <span class="text-danger"><?php echo $studentID_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                                <span class="text-danger"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                                <span class="text-danger"><?php echo $email_err; ?></span>
                            </div>

                            <!-- Password Field with Toggle -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password" id="password" class="form-control">
                                    <span class="input-group-text cursor-pointer" onclick="togglePassword('password', this)">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <span class="text-danger"><?php echo $password_err; ?></span>
                            </div>

                            <!-- Confirm Password Field with Toggle -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                                    <span class="input-group-text cursor-pointer" onclick="togglePassword('confirm_password', this)">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <span class="text-danger"><?php echo $confirm_password_err; ?></span>
                            </div>

                            <!-- Privacy Policy Checkbox -->
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="agree" name="agree">
                                <label class="form-check-label" for="agree">
                                    I agree to <a href="../privacy_policy.php" target="_blank">privacy policy & terms</a>
                                </label>
                                <br>
                                <span class="text-danger"><?php echo $agree_err; ?></span>
                            </div>
                            
<div class="d-flex justify-content-center align-items-center" style="margin-bottom: 5px;">
    <div class="g-recaptcha" data-sitekey="6LfdUi8qAAAAABtV6XTudHz9AVDEYRvH_2tNfIcA"></div>
</div>

                            <div class="mb-3">
                                
                                <button type="submit" class="btn btn-primary d-grid w-100">Register</button>

                            </div>
                        </form>

                        <p class="text-center">
                            <span>Already have an account?</span>
                            <a href="login.php"><span>Login here</span></a>
                        </p>
                    </div>
                </div>
                <!-- /Register Card -->
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
    

    <!-- JavaScript for Password Toggle -->
    <script>
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === "password") {
                field.type = "text";
                icon.querySelector("i").classList.replace("bx-hide", "bx-show");
            } else {
                field.type = "password";
                icon.querySelector("i").classList.replace("bx-show", "bx-hide");
            }
        }
    </script>
</body>
</html>


