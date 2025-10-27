          </div>
          <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  Carpooling System | By
                  <a href="../index.php" class="footer-link fw-bolder">RideWave</a>
                </div>
                
<!--                  <div>

                  <a href="" target="_blank" class="footer-link me-4">Contact Us</a>
                  <a href="" target="_blank" class="footer-link me-4">About Us</a>
                
                  </div>-->
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <div class="buy-now">
      <a href="sos.php" class="btn btn-danger btn-buy-now">SOS</a>
    </div>
    
    
<!-- Spinner Overlay -->
<div id="spinner-overlay1" class="overlay1" style="display: none;">
    <div class="spinner1"></div>
</div>

<style>
/* Spinner styles */
.overlay1 {
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

.spinner1 {
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

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="assets/js/dashboards-analytics.js"></script>

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    
    
    <!-- Custom SOS Script -->
<script>
// Show the spinner when the SOS button is clicked
document.querySelector('.btn-buy-now').addEventListener('click', function(event) {
    event.preventDefault(); // Prevents page reload
    const spinnerOverlay = document.getElementById('spinner-overlay1');
    spinnerOverlay.style.display = 'flex'; // Show the spinner

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(saveLocation, showError);
    } else {
        alert("Geolocation is not supported by this browser.");
        spinnerOverlay.style.display = 'none'; // Hide the spinner
    }
});

function saveLocation(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const location = `Latitude: ${latitude}, Longitude: ${longitude}`;

    // Debugging: Log location data
    console.log("Location:", location);

    // Send the location data to sos.php using AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "sos.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            const spinnerOverlay = document.getElementById('spinner-overlay1');
            spinnerOverlay.style.display = 'none'; // Hide the spinner

            console.log("Server Response:", xhr.responseText); // Log server response for debugging
            if (xhr.status === 200) {
                alert("SOS request sent successfully!");
            } else {
                alert("Error sending SOS request. Status: " + xhr.status);
            }
        }
    };
    xhr.send("location=" + encodeURIComponent(location));
}

function showError(error) {
    const spinnerOverlay = document.getElementById('spinner-overlay1');
    spinnerOverlay.style.display = 'none'; // Hide the spinner

    switch (error.code) {
        case error.PERMISSION_DENIED:
            alert("User denied the request for Geolocation.");
            break;
        case error.POSITION_UNAVAILABLE:
            alert("Location information is unavailable.");
            break;
        case error.TIMEOUT:
            alert("The request to get user location timed out.");
            break;
        case error.UNKNOWN_ERROR:
            alert("An unknown error occurred.");
            break;
    }
}
</script>


  </body>
</html>
