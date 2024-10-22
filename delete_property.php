<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in and property ID is provided
if (isset($_SESSION['username']) && isset($_GET['id'])) {
    $property_id = $_GET['id'];

    // Delete the property from the database
    $query = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $query->bind_param("i", $property_id);
    $query->execute();

    // Redirect back to the profile page after deletion
    header('Location: profile.php');
    exit();
} else {
    // If the user is not logged in or ID is not provided, redirect to login
    header('Location: login.html');
    exit();
}
?>
