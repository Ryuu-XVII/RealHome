<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

// Check if property ID is set
if (isset($_POST['property_id'])) {
    $property_id = $_POST['property_id'];

    // Prepare delete statement
    $deleteQuery = $conn->prepare("DELETE FROM properties WHERE id = ? AND agent_username = ?");
    $deleteQuery->bind_param("is", $property_id, $_SESSION['username']);
    
    if ($deleteQuery->execute()) {
        // Redirect back to profile page with success message
        header('Location: profile.php?message=Property removed successfully.');
    } else {
        // Redirect back to profile page with error message
        header('Location: profile.php?error=Failed to remove property.');
    }
} else {
    header('Location: profile.php');
}
