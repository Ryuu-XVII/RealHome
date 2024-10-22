<?php
session_start(); // Start session to check login status

// If the session is active, log out the user and destroy the session
if (isset($_SESSION['user_id'])) {
    // Unset all session variables
    session_unset();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to the login page after logging out
    header("Location: login.php");
    exit();
}
?>
