<?php
session_start();
require 'db_connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'client';

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }

    if ($role === 'agent') {
        // Query agents table
        $query = $conn->prepare("SELECT * FROM agents WHERE username = ?");
        $query->bind_param("s", $username);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $agent = $result->fetch_assoc();
            // Verify password using legacy hash column
            if (password_verify($password, $agent['password_hash'])) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'agent';
                
                header('Location: profile.php'); // Redirect to agent profile dashboard
                exit();
            }
        }
        echo "<script>alert('Invalid Agent credentials.'); window.history.back();</script>";
        exit();
    } else {
        // Query users (clients) table
        $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $query->bind_param("s", $username);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'client';
                
                // Successful login -> redirected to home page
                header('Location: index.php'); 
                exit();
            }
        }
        echo "<script>alert('Invalid Client credentials. Please register if you do not have an account.'); window.history.back();</script>";
        exit();
    }
}
?>
