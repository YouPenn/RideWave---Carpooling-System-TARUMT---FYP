<?php
// Load PHPMailer classes
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$successMessage = $errorMessage = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    try {
        $mail = new PHPMailer(true);
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'ourbus2003@gmail.com'; // Your Gmail email
        $mail->Password = 'nbcb anqx vzug lupd'; // Your Gmail app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and Recipient
        $mail->setFrom($email, $name); // From user input
        $mail->addAddress('teeyp-jm21@student.tarc.edu.my'); // Admin email

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <h3>New Contact Form Submission</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <p>$message</p>
        ";

        $mail->send();
        $successMessage = "Message sent successfully!";
    } catch (Exception $e) {
        $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>


<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="client/assets/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />
    <meta name="description" content="Contact Us - RideWave" />

    <title>Contact Us</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="client/assets/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="client/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="client/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="client/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="client/assets/css/demo.css" />
    <link rel="stylesheet" href="client/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <script src="client/assets/vendor/js/helpers.js"></script>
    <script src="client/assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar layout-without-menu">
      <div class="layout-container">
        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                
        <a href="index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="image/RideWave_logo.png" alt="RideWave Logo" width="100" />
            </span>
        </a>


              <ul class="navbar-nav flex-row align-items-center ms-auto">

                
                

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="image/user_avatar.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="image/user_avatar.png" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">Guest</span>
                            <small class="text-muted">User</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="client/login.php">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">Login as Client</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="Site_Admin/admin/auth-login.php">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">Login as Admin</span>
                      </a>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

<!-- Content wrapper -->
<div class="content-wrapper">
  <!-- Content -->
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light"></span> Contact Us
    </h4>

    <div class="row">
        
    <!-- Contact Details -->
    <div class="col-md-3 mb-4">
        <div class="card">
          <div class="card-header">
            <h5>Contact Details</h5>
          </div>
          <div class="card-body">
              <p><strong>TAR UMT Johor Branch Campus</strong><br><br>
              <strong>Address:</strong><br />
              Jalan Segamat / Labis,<br />
              85000, Segamat<br />
              Johor, Malaysia
            </p>
            <p>
              <strong>Admin Phone No:</strong> <br />
              +6011-10721617
            </p>
            <p>
              <strong>Admin Email:</strong> <br />
              <a href="mailto:teeyp-jm21@student.tarc.edu.my"
                >teeyp-jm21@student.tarc.edu.my</a
              >
            </p>
            <p>
              <strong>Working Hours:</strong><br />
              Monday - Friday: 9:00 AM to 5:00 PM
            </p>
          </div>
        </div>
      </div>
            <!-- Contact Form -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header"><h5>Contact Form</h5></div>
                    <div class="card-body">
<?php if ($successMessage): ?>
    <script>
        alert("<?php echo $successMessage; ?>");
    </script>
<?php elseif ($errorMessage): ?>
    <script>
        alert("<?php echo $errorMessage; ?>");
    </script>
<?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
      
      


    </div>
  </div>
  <!-- / Content -->
</div>
<!-- Content wrapper -->
            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  Carpooling System | By
                  <a href="index.php" class="footer-link fw-bolder">RideWave</a>
                </div>
                <div>
                  <a href="contactUs.php" class="footer-link me-4">Contact Us</a>
                  <a href="aboutUs.php" class="footer-link me-4">About Us</a>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- / Content wrapper -->
        </div>
      </div>
    <!-- / Layout wrapper -->

    <!-- Scripts -->
    <script src="client/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="client/assets/vendor/libs/popper/popper.js"></script>
    <script src="client/assets/vendor/js/bootstrap.js"></script>
    <script src="client/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="client/assets/vendor/js/menu.js"></script>
    <script src="client/assets/js/main.js"></script>
  </body>
</html>
