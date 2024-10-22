<?php
session_start();
require 'db_connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the SQL query to get the user with the given username
    $query = $conn->prepare("SELECT * FROM agents WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $agent = $result->fetch_assoc();
        // Verify the password with the stored hash in 'password_hash' column
        if (password_verify($password, $agent['password_hash'])) {
            // Password is correct, start the session
            $_SESSION['username'] = $username;
            header('Location: profile.php'); // Redirect to profile page
            exit();
        } else {
            echo "Invalid login credentials.";
        }
    } else {
        echo "Invalid login credentials.";
    }
}
?>
