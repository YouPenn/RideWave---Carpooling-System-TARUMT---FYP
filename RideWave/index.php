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
    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="client/assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="client/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="client/assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="client/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="client/assets/css/demo.css" />
    <link rel="stylesheet" href="client/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="client/assets/vendor/libs/apex-charts/apex-charts.css" />
    <script src="client/assets/vendor/js/helpers.js"></script>
    <script src="client/assets/js/config.js"></script>
    
    
<style>
  .carousel-item img {
    height: 70vh; 
    object-fit: cover;
  }
</style>
    
    <title>RideWave</title>

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
                        <a class="dropdown-item" href="client/welcome.php">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">Login as Client</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="../RideWave_Admin/Site_Admin/admin/auth-login.php">
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
            
            <!-- Part A -->
<div class="row">
  <!-- Bootstrap carousel -->
  
  <div class="col-md">
    <h5 class="my-1"></h5>

    <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
      <ol class="carousel-indicators">
        <li data-bs-target="#carouselExample" data-bs-slide-to="0" class="active"></li>
        <li data-bs-target="#carouselExample" data-bs-slide-to="1"></li>
        <li data-bs-target="#carouselExample" data-bs-slide-to="2"></li>
      </ol>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <div class="carousel-image-wrapper">
            <img class="w-100" src="image/pic1.png" alt="First slide" />
          </div>
          <div class="carousel-caption d-none d-md-block ">
            <h3>Find your carpool partners</h3>
            <p>at the best carpooling platform in TAR UMT Johor Branch</p>
          </div>
        </div>
        <div class="carousel-item">
          <div class="carousel-image-wrapper">
            <img class="w-100" src="image/pic2.png" alt="Second slide" />
          </div>
          <div class="carousel-caption d-none d-md-block">
            <h3>Save on Transportation Costs</h3>
            <p>Reduce your daily commuting expenses by sharing rides with fellow students.</p>
          </div>
        </div>
        <div class="carousel-item">
          <div class="carousel-image-wrapper">
            <img class="w-100" src="image/pic3.png" alt="Third slide" />
          </div>
          <div class="carousel-caption d-none d-md-block">
            <h3>Promote Sustainability</h3>
            <p>Join the movement towards greener transportation and reduce your carbon footprint.</p>
          </div>
        </div>
      </div>
      <a class="carousel-control-prev" href="#carouselExample" role="button" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </a>
      <a class="carousel-control-next" href="#carouselExample" role="button" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </a>
    </div>
  </div>
</div>

<style>
/* Ensure the image container keeps a 2:1 aspect ratio */
.carousel-image-wrapper {
  position: relative;
  width: 100%;
  padding-top: 40%; /* 2:1 aspect ratio */
  overflow: hidden;
}

.carousel-image-wrapper img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover; /* Ensure the image fills the container proportionally */
}

.carousel-caption {
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
    background: rgba(0, 0, 0, 0.5); /* 半透明背景 */
    padding: 10px;
}
</style>
            
            <div class="mt-3"></div>
                
              <!-- Part B -->
 <!-- Part B -->
<div class="row mb-5">
  <!-- First Card -->
  <div class="col-md-6">
    <div class="card shadow-sm mb-4">
      <div class="row g-0 align-items-center">
<!--        <div class="col-md-4">
          <img class="card-img card-img-left rounded-start" src="../assets/img/elements/12.jpg" alt="Card image" />
        </div>-->
        <div class="col-md-8">
          <div class="card-body">
            <h5 class="card-title">Ride with Us</h5>
            <p class="card-text text-muted">
              Discover safe and reliable carpooling solutions tailored for students and staff.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Second Card -->
  <div class="col-md-6">
    <div class="card shadow-sm mb-4">
      <div class="row g-0 align-items-center">
        <div class="col-md-8">
          <div class="card-body">
            <h5 class="card-title">Join the Community</h5>
            <p class="card-text text-muted">
              Be part of a growing community committed to sustainable and cost-effective travel solutions.
            </p>
          </div>
        </div>
<!--        <div class="col-md-4">
          <img class="card-img card-img-right rounded-end" src="../assets/img/elements/17.jpg" alt="Card image" />
        </div>-->
      </div>
    </div>
  </div>
</div>
            </div>
            
            

            
            <!-- / Content -->

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
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->

    <script src="client/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="client/assets/vendor/libs/popper/popper.js"></script>
    <script src="client/assets/vendor/js/bootstrap.js"></script>
    <script src="client/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="client/assets/vendor/js/menu.js"></script>

    <script src="client/assets/js/main.js"></script>


    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
  </body>
</html>
