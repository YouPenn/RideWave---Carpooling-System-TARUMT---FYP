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

$carImgPath = "../image/car.png"; // Default car image path
$licenseImgPath = "../image/license.png"; // Default license image path
$userImgPath = "../image/user_avatar.png"; // Default user image path

// Check if the user already exists in the driver table, and insert if not
$sql_check = "SELECT driverID FROM driver WHERE userID = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("i", $userID);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows == 0) {
    // If the user is not already a driver, insert a new record in the driver table
    $sql_insert = "INSERT INTO driver (userID) VALUES (?)";
    if ($stmt_insert = $mysqli->prepare($sql_insert)) {
        $stmt_insert->bind_param("i", $userID);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
}
$stmt_check->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["save"])) {
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];
    $gender = $_POST["gender"];
    $dateOfBirth = $_POST["dateOfBirth"];
    $licenseNum = $_POST["licenseNum"];
    $carRegNo = $_POST["carRegNo"];
    $licenseExpiry = $_POST["licenseExpiry"];
    $username = trim($_POST["username"]);

        // Validate username
        if (empty($username)) {
            echo "<script>alert('Username cannot be empty.');</script>";
        } else {
            // Update user table
            $sql_user = "UPDATE user SET username = ?, email = ?, phoneNumber = ?, gender = ?, dateOfBirth = ? WHERE userID = ?";
            if ($stmt_user = $mysqli->prepare($sql_user)) {
                $stmt_user->bind_param("sssssi", $username, $email, $phoneNumber, $gender, $dateOfBirth, $userID);
                if ($stmt_user->execute()) {
                    // Display success alert if the query executes successfully
                    echo "<script>alert('Profile updated successfully.');</script>";
                } else {
                    // Handle error if the query fails
                    echo "<script>alert('Failed to update profile. Please try again.');</script>";
                }
                $stmt_user->close();
            }
        }

        // Update driver table with licenseExpiryNotification reset
        $sql_driver = "UPDATE driver SET licenseNum = ?, carRegNo = ?, licenseExpiry = ?, licenseExpiryNotification = NULL WHERE userID = ?";
        if ($stmt_driver = $mysqli->prepare($sql_driver)) {
            $stmt_driver->bind_param("sssi", $licenseNum, $carRegNo, $licenseExpiry, $userID);
            $stmt_driver->execute();
            $stmt_driver->close();
        }

        // Validate and upload user profile image
        if (isset($_FILES['userImg']) && $_FILES['userImg']['error'] == UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($_FILES['userImg']['tmp_name']); // Get MIME type

            if (!in_array($fileType, $allowedTypes)) {
                echo "<script>alert('Invalid file type. Please upload a valid image (JPG, PNG, GIF).');</script>";
            } else {
                $targetDir = "../uploads/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = $userID . "_profile_" . basename($_FILES['userImg']['name']);
                $targetFilePath = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['userImg']['tmp_name'], $targetFilePath)) {
                    // Update userImg field in the user table
                    $sql_update_img = "UPDATE user SET userImg = ? WHERE userID = ?";
                    if ($stmt_update_img = $mysqli->prepare($sql_update_img)) {
                        $stmt_update_img->bind_param("si", $targetFilePath, $userID);
                        $stmt_update_img->execute();
                        $stmt_update_img->close();
                    }
                    $userImgPath = $targetFilePath;
                } else {
                    echo "<script>alert('Failed to upload the image. Please try again.');</script>";
                }
            }
        }
    }

// Allowed file MIME types for image validation
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

// Handle car image upload if "Update Car Image" button was clicked
if (isset($_POST["update_car_image"])) {
    if (isset($_FILES['carImg']) && $_FILES['carImg']['error'] == UPLOAD_ERR_OK) {
        $fileMimeType = mime_content_type($_FILES['carImg']['tmp_name']);
        if (in_array($fileMimeType, $allowedMimeTypes)) {
            $targetDir = "../uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = $userID . "_car_" . basename($_FILES['carImg']['name']);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['carImg']['tmp_name'], $targetFilePath)) {
                // Update carImg field in the driver table
                $sql_update_car_img = "UPDATE driver SET carImg = ? WHERE userID = ?";
                if ($stmt_update_car_img = $mysqli->prepare($sql_update_car_img)) {
                    $stmt_update_car_img->bind_param("si", $targetFilePath, $userID);
                    $stmt_update_car_img->execute();
                    $stmt_update_car_img->close();
                }
                $carImgPath = $targetFilePath;
            } else {
                echo "<script>alert('Failed to move car image file. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type for car image. Only JPG, PNG, and GIF are allowed.');</script>";
        }
    }
}

// Handle license image upload if "Update License Image" button was clicked
if (isset($_POST["update_license_image"])) {
    if (isset($_FILES['licenseImg']) && $_FILES['licenseImg']['error'] == UPLOAD_ERR_OK) {
        $fileMimeType = mime_content_type($_FILES['licenseImg']['tmp_name']);
        if (in_array($fileMimeType, $allowedMimeTypes)) {
            $targetDir = "../uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = $userID . "_license_" . basename($_FILES['licenseImg']['name']);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['licenseImg']['tmp_name'], $targetFilePath)) {
                // Update licenseImg field in the driver table
                $sql_update_license_img = "UPDATE driver SET licenseImg = ? WHERE userID = ?";
                if ($stmt_update_license_img = $mysqli->prepare($sql_update_license_img)) {
                    $stmt_update_license_img->bind_param("si", $targetFilePath, $userID);
                    $stmt_update_license_img->execute();
                    $stmt_update_license_img->close();
                }
                $licenseImgPath = $targetFilePath;
            } else {
                echo "<script>alert('Failed to move license image file. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type for license image. Only JPG, PNG, and GIF are allowed.');</script>";
        }
    }
}
}

// Retrieve data from the database
$sql = "SELECT u.studentID, u.username, u.email, u.phoneNumber, u.gender, u.dateOfBirth, u.userImg, d.licenseNum, d.carRegNo, d.carImg, d.licenseImg, d.licenseExpiry 
        FROM user u
        LEFT JOIN driver d ON u.userID = d.userID
        WHERE u.userID = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($studentID, $username, $email, $phoneNumber, $gender, $dateOfBirth, $userImg, $licenseNum, $carRegNo, $carImg, $licenseImg, $licenseExpiry);
    $stmt->fetch();
    if ($userImg) {
        $userImgPath = $userImg; // Use the image from the database if it exists
    }
    if ($carImg) {
        $carImgPath = $carImg;
    }
    if ($licenseImg) {
        $licenseImgPath = $licenseImg;
    }
    $stmt->close();
}



$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">

<head>
    <?php include_once('header.php'); ?>
    <title>Driver Profile</title>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Profile Settings /</span> Driver Profile</h4> 

            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item">
                            <a class="nav-link" href="own_passenger_profile.php"><i class="bx bx-user me-1"></i> Passenger Profile</a> 
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Driver Profile</a> 
                        </li>

                    </ul>
                    <div class="card mb-4">
                        <h5 class="card-header">Profile Details</h5>

                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                                <!-- User Image -->
                                <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                                    <img
                                        src="<?php echo "data:image/jpeg;base64," . base64_encode(file_get_contents($userImgPath)); ?>"
                                        alt="User Avatar"
                                        class="d-block rounded"
                                        height="100"
                                        width="100"
                                        id="uploadedAvatar"
                                    />
                                    <div class="button-wrapper">
                                        <label for="userImg" class="btn btn-primary me-2 mb-4" tabindex="0">
                                            <span class="d-none d-sm-block">Upload new photo</span>
                                            <i class="bx bx-upload d-block d-sm-none"></i>
                                            <input
                                                type="file"
                                                id="userImg"
                                                name="userImg"
                                                class="account-file-input"
                                                hidden
                                                accept="image/png, image/jpeg, image/gif"
                                                onchange="previewImage(event)"
                                            />
                                        </label>
                                        <button type="button" class="btn btn-outline-secondary account-image-reset mb-4" onclick="resetImage()">
                                            <i class="bx bx-reset d-block d-sm-none"></i>
                                            <span class="d-none d-sm-block">Reset</span>
                                        </button>
                                        <p class="text-muted mb-0">Allowed JPG, PNG, GIF.</p>
                                    </div>
                                </div>

                                <!-- Other Form Fields -->
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="text" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="studentID" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="studentID" value="<?php echo htmlspecialchars($studentID); ?>" readonly>
                                </div>

<div class="mb-3">
    <label for="username" class="form-label">Name</label>
    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
</div>



    <!-- Phone Number -->
    <div class="mb-3">
        <label for="phoneNumber" class="form-label">Phone</label>
        <input
            type="text"
            class="form-control"
            id="phoneNumber"
            name="phoneNumber"
            value="<?php echo htmlspecialchars($phoneNumber); ?>"
            pattern="\d{2,3}-\d{7,8}$"
            title="Enter a valid phone number (e.g., 011-10721617, 019-7711682, or 07-5236270)"
            required
        />
    </div>


    
    <!-- Gender -->
<div class="mb-3">
    <label for="gender" class="form-label">Gender</label>
    <select
        class="form-select"
        id="gender"
        name="gender"
        required
    >
        <option value="" disabled>Select your gender</option>
        <option value="Male" <?php echo $gender === "Male" ? "selected" : ""; ?>>Male</option>
        <option value="Female" <?php echo $gender === "Female" ? "selected" : ""; ?>>Female</option>
        <option value="Other" <?php echo $gender === "Other" ? "selected" : ""; ?>>Other</option>
    </select>
</div>

    <!-- Date of Birth -->
    <div class="mb-3">
        <label for="dateOfBirth" class="form-label">Date of Birth</label>
        <input
            type="date"
            class="form-control"
            id="dateOfBirth"
            name="dateOfBirth"
            value="<?php echo htmlspecialchars($dateOfBirth); ?>"

            required
        />
    </div>

<!-- IC/License Number -->
<div class="mb-3">
    <label for="licenseNum" class="form-label">IC/License Number</label>
    <input
        type="text"
        class="form-control"
        id="licenseNum"
        name="licenseNum"
        value="<?php echo htmlspecialchars($licenseNum); ?>"
        pattern="^\d{6}-\d{2}-\d{4}$"
        title="Enter a valid IC/License number (e.g., 030320-01-0935)"
        required
    />
</div>

<!-- License Expiry Date -->
<div class="mb-3">
    <label for="licenseExpiry" class="form-label">License Expiry Date</label>
    <input
        type="date"
        class="form-control"
        id="licenseExpiry"
        name="licenseExpiry"
        value="<?php echo htmlspecialchars($licenseExpiry); ?>"
        min="<?php echo date('Y-m-d'); ?>"
        required
    />
</div>


    <!-- Car Registration Number -->
    <div class="mb-3">
        <label for="carRegNo" class="form-label">Car Registration Number</label>
        <input
            type="text"
            class="form-control"
            id="carRegNo"
            name="carRegNo"
            value="<?php echo htmlspecialchars($carRegNo); ?>"
            required
        />
    </div>

    <!-- Save and Cancel buttons -->
    <div class="d-flex justify-content-between mt-2">
        <div>
            <button type="submit" name="save" class="btn btn-primary me-2">Save changes</button>
            <button type="reset" class="btn btn-outline-secondary">Cancel</button>
        </div>
    </div>

<hr class="my-4">

<!-- Car Image Section -->
<div class="mb-3">
    <label for="carImg" class="form-label">Car Image:</label>
    <div class="text-center">
        <img src="<?php echo htmlspecialchars($carImgPath); ?>" alt="Car Image" class="img-thumbnail mb-3" width="200" id="carImgPreview">
    </div>
    <div class="input-group">
        <input type="file" class="form-control" id="carImg" name="carImg" onchange="previewCarImage(event)">
        <button type="button" onclick="validateImageWithSpinner('carImg', 'update_car_image')" class="btn btn-outline-primary">Update Image</button>
    </div>
</div>

<!-- License Image Section -->
<div class="mb-3">
    <label for="licenseImg" class="form-label">License Image:</label>
    <div class="text-center">
        <img src="<?php echo htmlspecialchars($licenseImgPath); ?>" alt="License Image" class="img-thumbnail mb-3" width="200" id="licenseImgPreview">
    </div>
    <div class="input-group">
        <input type="file" class="form-control" id="licenseImg" name="licenseImg" onchange="previewLicenseImage(event)">
        <button type="button" onclick="validateImageWithSpinner('licenseImg', 'update_license_image')" class="btn btn-outline-primary">Update Image</button>
    </div>
</div>


                                <!-- Hidden field to trigger save action in PHP -->
                                <input type="hidden" id="saveAction" name="save" value="">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Spinner Overlay -->
<div id="spinner-overlay" class="overlay" style="display: none;">
    <div class="spinner"></div>
</div>

<style>
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
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
    
    <?php include 'footer.php'; ?>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById('uploadedAvatar');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function resetImage() {
        const output = document.getElementById('uploadedAvatar');
        output.src = '<?php echo "data:image/jpeg;base64," . base64_encode(file_get_contents($userImgPath)); ?>';
        document.getElementById('userImg').value = ''; // Clear file input
    }

    // Preview selected Car Image
    function previewCarImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('carImgPreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Preview selected License Image
    function previewLicenseImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('licenseImgPreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
    
    // Ensure the file input is not empty before submitting
    function validateImage(inputId, buttonName) {
        const fileInput = document.getElementById(inputId);
        if (fileInput.files.length === 0) {
            alert("Please select an image file to upload.");
        } else {
            // Create a hidden input field to specify which button was clicked
            const hiddenButton = document.createElement("input");
            hiddenButton.type = "hidden";
            hiddenButton.name = buttonName;
            document.querySelector("form").appendChild(hiddenButton);
            document.querySelector("form").submit();
        }
    }
    
        function showSpinner() {
            const spinnerOverlay = document.getElementById('spinner-overlay');
            spinnerOverlay.style.display = 'flex'; // Show the spinner
        }
        
            // Validate image and show spinner while processing
    function validateImageWithSpinner(inputId, buttonName) {
        const fileInput = document.getElementById(inputId);
        if (fileInput.files.length === 0) {
            alert("Please select an image file to upload.");
        } else {
            // Show spinner
            document.getElementById('spinner-overlay').style.display = 'flex';
            
            // Create a hidden input field to specify which button was clicked
            const hiddenButton = document.createElement("input");
            hiddenButton.type = "hidden";
            hiddenButton.name = buttonName;
            document.querySelector("form").appendChild(hiddenButton);
            
            // Submit the form
            document.querySelector("form").submit();
        }
    }
</script>
</body>
</html>