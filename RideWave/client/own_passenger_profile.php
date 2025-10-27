<?php
include_once('../config/config.php');
include_once('remember_check.php');

// Start the session if not already started
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

// Check if user exists in the passenger table, insert if not
$sql_check = "SELECT passengerID FROM passenger WHERE userID = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("i", $userID);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows == 0) {
    // User not found in passenger table, insert new record
    $sql_insert = "INSERT INTO passenger (userID, status) VALUES (?, 'active')";
    $stmt_insert = $mysqli->prepare($sql_insert);
    $stmt_insert->bind_param("i", $userID);
    $stmt_insert->execute();
    $stmt_insert->close();
}
$stmt_check->close();

// Initialize variables for form fields
$studentID = $name = $email = $phone = $gender = $dateOfBirth = $userImg = "";

// Retrieve user data from the database
$sql = "SELECT u.studentID, u.username, u.email, u.phoneNumber, u.gender, u.dateOfBirth, u.userImg
        FROM user u
        LEFT JOIN passenger p ON u.userID = p.userID
        WHERE u.userID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($studentID, $name, $email, $phone, $gender, $dateOfBirth, $userImg);
$stmt->fetch();
$stmt->close();

// Handle form submission to update profile data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["save"])) {
        $email = $_POST["email"];
        $phone = $_POST["phone"];
        $gender = $_POST["gender"];
        $dateOfBirth = $_POST["dateOfBirth"];
        $name = trim($_POST["name"]);

if (isset($_FILES['userImg']) && $_FILES['userImg']['error'] == UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Allowed MIME types
    $fileType = mime_content_type($_FILES['userImg']['tmp_name']); // Get the MIME type

    if (!in_array($fileType, $allowedTypes)) {
        echo "<script>alert('Invalid file type. Please upload an image (JPEG, PNG, or GIF).');</script>";
    } else {
        $targetDir = "../uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = $userID . "_profile_" . basename($_FILES['userImg']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['userImg']['tmp_name'], $targetFilePath)) {
            // Update userImg field in the database
            $sql_update_img = "UPDATE user SET userImg = ? WHERE userID = ?";
            $stmt_update_img = $mysqli->prepare($sql_update_img);
            $stmt_update_img->bind_param("si", $targetFilePath, $userID);
            if (!$stmt_update_img->execute()) {
                echo "Error updating userImg: " . $stmt_update_img->error;
            }
            $stmt_update_img->close();

            // Update the $userImg variable to reflect the new image
            $userImg = $targetFilePath;
        } else {
            echo "<script>alert('Failed to upload the image.');</script>";
        }
    }
}

        // Validate the username
        if (empty($name)) {
            echo "<script>alert('Name cannot be empty.');</script>";
        } else {
            // Update user table for other fields, including username
            $sql_update = "UPDATE user SET username = ?, email = ?, phoneNumber = ?, gender = ?, dateOfBirth = ? WHERE userID = ?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("sssssi", $name, $email, $phone, $gender, $dateOfBirth, $userID);
            if (!$stmt_update->execute()) {
                echo "Error updating profile: " . $stmt_update->error;
            } else {
                echo "<script>alert('Profile updated successfully.');</script>";
            }
            $stmt_update->close();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="UTF-8">
    <?php include_once('header.php'); ?>
    <title>Passenger Profile</title>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Profile Settings /</span> Passenger Profile</h4>

            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Passenger Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="own_driver_profile.php"><i class="bx bx-user me-1"></i> Driver Profile</a>
                        </li>
                    </ul>
                    <div class="card mb-4">
                        <h5 class="card-header">Profile Details</h5>

                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                                <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                                    <img
                                        src="data:image/jpeg;base64,<?php echo base64_encode($userImg ? file_get_contents($userImg) : file_get_contents('../image/user_avatar.png')); ?>"
                                        alt="user-avatar"
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
                                
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                </div>

                                <!-- Student ID -->
                                <div class="mb-3">
                                    <label for="studentID" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="studentID" value="<?php echo htmlspecialchars($studentID); ?>" readonly>
                                </div>

<!-- Name -->
<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
</div>


    <!-- Phone Number -->
    <div class="mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input
            type="text"
            class="form-control"
            id="phone"
            name="phone"
            value="<?php echo htmlspecialchars($phone); ?>"
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

                                <!-- Edit/Save button -->
                                <div class="d-flex justify-content-between mt-2">
                                    <div>
                                        <button type="submit" name="save" class="btn btn-primary me-2" >Save changes</button>
                                        <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                                    </div>
                                </div>
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
            output.src = '<?php echo "data:image/jpeg;base64," . base64_encode($userImg ? file_get_contents($userImg) : file_get_contents("../image/user_avatar.png")); ?>';
            document.getElementById('userImg').value = ''; // Clear file input
        }
        
        function showSpinner() {
            const spinnerOverlay = document.getElementById('spinner-overlay');
            spinnerOverlay.style.display = 'flex'; // Show the spinner
        }
    </script>
</body>
</html>