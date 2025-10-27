  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

<?php
$currentPage = basename($_SERVER['PHP_SELF']); // Get the current file name
?>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="welcome.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="../image/RideWave_logo.png" alt="RideWave Logo" width="200" />
            </span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <!-- Home -->
        <li class="menu-item <?= ($currentPage == 'welcome.php') ? 'active' : ''; ?>">
            <a href="welcome.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Home</div>
            </a>
        </li>
        <!-- User Section -->
        <li class="menu-header small text-uppercase"><span class="menu-header-text">User</span></li>

        <!-- Passenger -->
        <li class="menu-item <?= in_array($currentPage, ['passenger_dashboard.php', 'trip_history.php', 'payment_list_passenger.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Layouts">Passenger</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?= ($currentPage == 'passenger_dashboard.php') ? 'active' : ''; ?>">
                    <a href="passenger_dashboard.php" class="menu-link">
                        <div data-i18n="Without menu">Passenger Dashboard</div>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage == 'trip_history.php') ? 'active' : ''; ?>">
                    <a href="trip_history.php" class="menu-link">
                        <div data-i18n="Without navbar">Trip History</div>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage == 'payment_list_passenger.php') ? 'active' : ''; ?>">
                    <a href="payment_list_passenger.php" class="menu-link">
                        <div data-i18n="Container">Payment List</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Driver -->
        <li class="menu-item <?= in_array($currentPage, ['driver_dashboard.php', 'trip_create.php', 'payment_list_driver.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-car"></i>
                <div data-i18n="Layouts">Driver</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?= ($currentPage == 'driver_dashboard.php') ? 'active' : ''; ?>">
                    <a href="driver_dashboard.php" class="menu-link">
                        <div data-i18n="Without menu">Driver Dashboard</div>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage == 'trip_create.php') ? 'active' : ''; ?>">
                    <a href="trip_create.php" class="menu-link">
                        <div data-i18n="Without navbar">Create Trip</div>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage == 'payment_list_driver.php') ? 'active' : ''; ?>">
                    <a href="payment_list_driver.php" class="menu-link">
                        <div data-i18n="Container">Payment List</div>
                    </a>
                </li>
                <li class="menu-item <?= ($currentPage == 'view_driver_trip_history.php') ? 'active' : ''; ?>"> 
                    <a href="view_driver_trip_history.php?driverID=<?= $navDriverId ?>" class="menu-link">
                        <div data-i18n="Container">Trip Rating History</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Other -->
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Other</span></li>
        <li class="menu-item <?= ($currentPage == 'blacklist.php') ? 'active' : ''; ?>">
            <a href="blacklist.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div data-i18n="Basic">Blacklist</div>
            </a>
        </li>
        <li class="menu-item <?= ($currentPage == 'feedback.php') ? 'active' : ''; ?>">
            <a href="feedback.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-copy"></i>
                <div data-i18n="Basic">Feedback</div>
            </a>
        </li>
        <li class="menu-item <?= ($currentPage == 'announcement.php') ? 'active' : ''; ?>">
            <a href="announcement.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div data-i18n="Basic">Announcement</div>
            </a>
        </li>
        <li class="menu-item <?= ($currentPage == 'guideline.php') ? 'active' : ''; ?>">
            <a href="guideline.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div data-i18n="Basic">Guideline</div>
            </a>
        </li>
    </ul>
</aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

<nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar"
>
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>
    

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- User Profile Section -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            
<li class="nav-item lh-1 me-3">
  <a href="user_chat.php" class="nav-link">
    <i class="bx bx-envelope" style="font-size: 1.5rem;"></i>
  </a>
</li>
            <!-- User Dropdown -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a
                    class="nav-link dropdown-toggle hide-arrow"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown"
                >
                    <div class="avatar avatar-online">
                        <img
                            src="<?php echo htmlspecialchars($navUserImgPath); ?>"
                            alt="User Avatar"
                            class="w-px-40 h-px-40 rounded-circle"
                        />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img
                                            src="<?php echo htmlspecialchars($navUserImgPath); ?>"
                                            alt="User Avatar"
                                            class="w-px-40 h-px-40 rounded-circle"
                                        />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($navUsername); ?></span>
                                    <small class="text-muted">Client</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a
                            class="dropdown-item <?= ($currentPage == 'own_passenger_profile.php') ? 'active' : ''; ?>"
                            href="own_passenger_profile.php"
                        >
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">My Passenger Profile</span>
                        </a>
                    </li>
                    <li>
                        <a
                            class="dropdown-item <?= ($currentPage == 'own_driver_profile.php') ? 'active' : ''; ?>"
                            href="own_driver_profile.php"
                        >
                            <i class="bx bx-car me-2"></i>
                            <span class="align-middle">My Driver Profile</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="logout.php">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- /User Dropdown -->
        </ul>
    </div>
</nav>


          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
          <!-- Content -->