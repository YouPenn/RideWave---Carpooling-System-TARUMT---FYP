<!--config.php-->

<?php
$dbuser = "root";
$dbpass = "";
$host = "localhost";
$db = "carpooldb";
$mysqli = new mysqli($host, $dbuser, $dbpass, $db);

date_default_timezone_set('Asia/Kuala_Lumpur'); 

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>