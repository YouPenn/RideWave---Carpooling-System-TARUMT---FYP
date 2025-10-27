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

    <title>About Us</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="client/assets/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="client/assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="client/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="client/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="client/assets/css/demo.css" />
    <link rel="stylesheet" href="client/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <script src="client/assets/vendor/js/helpers.js"></script>
    <script src="client/assets/js/config.js"></script>
    
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>
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
      <span class="text-muted fw-light"></span> About Us
    </h4>

    <div class="row g-4 align-items-stretch">
      <!-- Map Card -->
      <div class="col-md-6 d-flex">
        <div class="card h-100 w-100 d-flex flex-column" style="min-height: 400px;">
          <div class="card-header">
            <h5>Map</h5>
          </div>
          <div class="card-body flex-grow-1">
            <div id="map" style="height: 100%; min-height: 300px;"></div>
          </div>
        </div>
      </div>

      <!-- About Us Content -->
      <div class="col-md-6 d-flex">
        <div class="card h-100 w-100 d-flex flex-column" style="min-height: 400px;">
          <div class="card-header"><h5>About Us</h5></div>
          <div class="card-body flex-grow-1">
            <p>
              Welcome to <strong>RideWave</strong>, the dedicated carpooling platform for TAR UMT Johor Branch Campus. Our goal is to connect students who are looking to share rides, save on travel costs, and contribute to a cleaner environment.
            </p>
            <p>
              At RideWave, we prioritize <strong>sustainability</strong>, <strong>convenience</strong>, and <strong>safety</strong>. Drivers can offer available seats in their vehicles, while passengers can find trusted carpool partners quickly and easily. Our platform makes sharing rides simple and efficient, encouraging collaboration among our campus community.
            </p>
            <p>
              By choosing RideWave, you’re not only reducing transportation costs but also helping to lower traffic congestion and minimize carbon emissions. Together, we can build a <strong>greener future</strong> while fostering a community that values trust, responsibility, and shared journeys.
            </p>
            <p>
              <strong>Join us today, and let’s make every ride count!</strong>
            </p>
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
    
    <script>
        // Initialize Leaflet map
        var map = L.map('map').setView([2.464179,102.901458], 15); // Latitude, Longitude for TAR UMT Johor

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Add a marker to the map
        var marker = L.marker([2.464179,102.901458]).addTo(map);
        marker.bindPopup("<b>TAR UMT Johor Branch</b><br>85000, Segamat, Johor").openPopup();
    </script>

  </body>
</html>
