<?php
session_start();
require 'db_connection.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and validate
    $propertyType = trim($_POST['propertyType']);
    $listingType = trim($_POST['propertyStatus']);
    $address = trim($_POST['address']);
    $price = trim($_POST['price']);
    $bedrooms = trim($_POST['bedrooms']);
    $bathrooms = trim($_POST['bathrooms']);
    $garage = trim($_POST['garage']);
    $floorSize = trim($_POST['floorSize']);

    // Validate required fields
    $errors = [];
    if (empty($propertyType) || empty($listingType) || empty($address) || empty($price) || empty($bedrooms) || empty($bathrooms) || empty($garage) || empty($floorSize)) {
        $errors[] = 'All fields are required.';
    }

    // Process uploaded images
    $imageUrls = [];
    if (!empty($_FILES['images']['name'][0])) {
        $targetDir = "uploads/";
        foreach ($_FILES['images']['name'] as $key => $imageName) {
            $targetFile = $targetDir . basename($imageName);
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES['images']['tmp_name'][$key]);

            // Validate image file type and size
            if ($check === false) {
                $errors[] = "File {$imageName} is not an image.";
            } elseif ($_FILES['images']['size'][$key] > 5000000) { // Limit file size to 5MB
                $errors[] = "File {$imageName} exceeds the maximum size of 5MB.";
            } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $errors[] = "File {$imageName} is not a valid image format (only JPG, JPEG, PNG, GIF allowed).";
            } elseif (!move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
                $errors[] = "Error uploading file {$imageName}.";
            } else {
                $imageUrls[] = $targetFile;
            }
        }
    }

    // Serialize image URLs for storage
    $imageUrlsSerialized = serialize($imageUrls);

    // Get the logged-in agent's username
    $username = $_SESSION['username'];

    // Insert the property into the database if there are no errors
    if (empty($errors)) {
        $query = $conn->prepare("INSERT INTO properties (agent_username, property_type, listing_type, address, price, bedrooms, bathrooms, garage, floor_size, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $query->bind_param("ssssssssss", $username, $propertyType, $listingType, $address, $price, $bedrooms, $bathrooms, $garage, $floorSize, $imageUrlsSerialized);
        
        if ($query->execute()) {
            // Redirect to listings page upon successful insertion
            header('Location: listing.php');
            exit();
        } else {
            // Handle database insertion error
            echo "Database Error: " . $query->error;
        }

        $query->close();
    } else {
        // Display errors
        foreach ($errors as $error) {
            echo "<p>Error: $error</p>";
        }
    }
}

$conn->close();
?>
