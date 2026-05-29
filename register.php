<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php'; // Include your database connection
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'client';
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($username) || empty($_POST['password']) || empty($email) || empty($phone)) {
        echo "<script>alert('Please fill in all standard fields.'); window.history.back();</script>";
        exit();
    }

    if ($role === 'agent') {
        $name = trim($_POST['name']);
        $photo = $_FILES['photo'];

        if (empty($name) || empty($photo['name'])) {
            echo "<script>alert('Agents must supply a full name and profile photo.'); window.history.back();</script>";
            exit();
        }

        $photoDir = 'uploads/';
        $photoPath = $photoDir . uniqid() . '-' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($photo['name']));

        // Check if the upload directory exists, if not create it
        if (!is_dir($photoDir)) {
            mkdir($photoDir, 0777, true);
        }

        // Validate and move the uploaded file
        if (move_uploaded_file($photo['tmp_name'], $photoPath)) {
            // Prepare SQL query to insert the agent
            $query = $conn->prepare("INSERT INTO agents (name, username, password_hash, email, phone, photo) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($query === false) {
                die("Prepare failed: " . $conn->error);
            }

            $query->bind_param("ssssss", $name, $username, $password, $email, $phone, $photoPath);
            
            if ($query->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'agent';
                header('Location: profile.php'); // Redirect to agent profile dashboard
                exit();
            } else {
                echo "<script>alert('Agent registration failed: Username or Email may already exist.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('File upload failed.'); window.history.back();</script>";
            exit();
        }
    } else {
        // Client registration logic
        $query = $conn->prepare("INSERT INTO users (username, password_hash, email, phone) VALUES (?, ?, ?, ?)");
        
        if ($query === false) {
            die("Prepare failed: " . $conn->error);
        }

        $query->bind_param("ssss", $username, $password, $email, $phone);
        
        if ($query->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'client';
            
            // Auto-login and redirect to index homepage
            header('Location: index.php');
            exit();
        } else {
            echo "<script>alert('Client registration failed: Username or Email may already exist.'); window.history.back();</script>";
            exit();
        }
    }
}
?>
