<?php
// connectdb.php

$servername = "localhost"; // replace with your server name
$username = "root"; // replace with your database username
$password = ""; // replace with your database password
$database = "carpooldb"; // replace with your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the database connection
if ($conn->connect_error) {
    // Set an error flag for the notification
    $db_connected = false;
} else {
    // Set a success flag for the notification
    $db_connected = true;
}

// Optional: Set character set to utf8mb4
$conn->set_charset("utf8mb4");

// You can add additional configuration here if needed
?>
