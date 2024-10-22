<?php
$servername = "localhost:3306";
$username = "ryuuxvii_Adnaan_Home";
$password = "Arise@10m";
$dbname = "ryuuxvii_Real_Home";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
