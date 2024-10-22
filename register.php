<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php'; // Include your database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Handle file upload
    $photo = $_FILES['photo'];
    $photoDir = 'uploads/';
    $photoPath = $photoDir . basename($photo['name']);

    // Check if the upload directory exists, if not create it
    if (!is_dir($photoDir)) {
        mkdir($photoDir, 0777, true);
    }

    // Validate and move the uploaded file
    if (move_uploaded_file($photo['tmp_name'], $photoPath)) {
        
        // Prepare SQL query to insert the agent
        $query = $conn->prepare("INSERT INTO agents (name, username, password_hash, email, phone, photo) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Check if prepare failed
        if ($query === false) {
            die("Prepare failed: " . $conn->error); // Output the error from the connection
        }

        // Bind parameters and execute the query
        $query->bind_param("ssssss", $name, $username, $password, $email, $phone, $photoPath);
        
        if ($query->execute()) {
            header('Location: agents.php'); // Redirect to agents list
            exit(); // Stop script execution
        } else {
            echo "Registration failed: " . $query->error; // Output the error from the query
        }
    } else {
        echo "File upload failed.";
    }
}
?>
