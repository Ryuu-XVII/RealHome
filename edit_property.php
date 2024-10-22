<?php
session_start();
require 'db_connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

// Initialize variables for property details
$property = null;

// Get property ID from URL
if (!isset($_GET['property_id'])) {
    echo "Error: Property ID not provided.";
    exit();
}

$property_id = $_GET['property_id'];

// Fetch property details
$query = $conn->prepare("SELECT * FROM properties WHERE id = ?");
$query->bind_param("i", $property_id);
$query->execute();
$property = $query->get_result()->fetch_assoc();

if (!$property) {
    echo "Error: Property not found.";
    exit();
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get property details from the form
    $property_type = !empty($_POST['propertyType']) ? $_POST['propertyType'] : $property['property_type'];
    $listing_type = !empty($_POST['listingType']) ? $_POST['listingType'] : $property['listing_type'];
    $address = !empty($_POST['address']) ? $_POST['address'] : $property['address'];
    $price = !empty($_POST['price']) ? $_POST['price'] : $property['price'];
    $bedrooms = !empty($_POST['bedrooms']) ? $_POST['bedrooms'] : $property['bedrooms'];
    $bathrooms = !empty($_POST['bathrooms']) ? $_POST['bathrooms'] : $property['bathrooms'];
    $garage_capacity = !empty($_POST['garageCapacity']) ? $_POST['garageCapacity'] : $property['garage'];
    $floor_size = !empty($_POST['floorSize']) ? $_POST['floorSize'] : $property['floor_size'];

    // Handle file uploads (optional)
    $uploaded_images = [];
    $image_dir = 'uploads/'; // Directory to save images

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $image) {
            $image_name = time() . '_' . $_FILES['images']['name'][$key];
            $image_tmp_name = $_FILES['images']['tmp_name'][$key];
            $image_destination = $image_dir . $image_name;

            if (move_uploaded_file($image_tmp_name, $image_destination)) {
                $uploaded_images[] = $image_name;
            } else {
                echo "Error uploading image: " . $_FILES['images']['name'][$key];
            }
        }
    }

    // Retain existing images if no new images are uploaded
    $existing_images = json_decode($property['images'], true);

    // Check if existing_images is not an array
    if (!is_array($existing_images)) {
        $existing_images = []; // Initialize as an empty array if it's not valid
    }

    if (!empty($uploaded_images)) {
        $existing_images = array_merge($existing_images, $uploaded_images);
    }

    // Convert the image array to JSON to store in DB
    $images_json = json_encode($existing_images);

    // Update the property in the database
    $updateQuery = $conn->prepare("UPDATE properties 
                                   SET property_type = ?, listing_type = ?, address = ?, price = ?, bedrooms = ?, bathrooms = ?, garage = ?, floor_size = ?, images = ? 
                                   WHERE id = ?");
    $updateQuery->bind_param("sssiiiiiis", $property_type, $listing_type, $address, $price, $bedrooms, $bathrooms, $garage_capacity, $floor_size, $images_json, $property_id);

    if ($updateQuery->execute()) {
        // Redirect back to the profile page after successful update
        header('Location: profile.php');
        exit();
    } else {
        echo "Error updating property: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property</title>
    <link rel="stylesheet" href="edit_property.css">
    <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style type="text/css">
        /* Body Background */
        body {
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }

        /* Heading Styles */
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Form Styles */
        form {
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 20px auto;
            color: white;
        }

        /* Label Styles */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        /* Input Styles */
        input[type="text"],
        input[type="number"],
        select,
        input[type="file"],
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        /* Input Focus Styles */
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            border-color: #2980b9;
            outline: none;
        }

        /* Button Styles */
        button {
            background-color: #2980b9;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Button Hover Styles */
        button:hover {
            background-color: #1a6395;
        }

        /* Image Display Styles */
        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .image-gallery img {
            max-width: 100px; /* Set a max-width for images */
            margin-right: 10px;
            margin-bottom: 10px;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            form {
                padding: 15px;
                margin: 10px;
            }

            h2 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <h2>Edit Property</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="propertyType">Property Type:</label>
        <select id="propertyType" name="propertyType" required>
            <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
            <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
            <option value="townhouse" <?php echo $property['property_type'] === 'townhouse' ? 'selected' : ''; ?>>Townhouse</option>
            <option value="commercial" <?php echo $property['property_type'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
        </select>

        <label for="listingType">Listing Type:</label>
        <select id="listingType" name="listingType" required>
            <option value="sale" <?php echo $property['listing_type'] === 'sale' ? 'selected' : ''; ?>>Sale</option>
            <option value="rent" <?php echo $property['listing_type'] === 'rent' ? 'selected' : ''; ?>>Rent</option>
        </select>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?php echo $property['address']; ?>" required>

        <label for="price">Price:</label>
        <input type="number" id="price" name="price" value="<?php echo $property['price']; ?>" required>

        <label for="bedrooms">Number of Bedrooms:</label>
        <input type="number" id="bedrooms" name="bedrooms" value="<?php echo $property['bedrooms']; ?>" required>

        <label for="bathrooms">Number of Bathrooms:</label>
        <input type="number" id="bathrooms" name="bathrooms" value="<?php echo $property['bathrooms']; ?>" required>

        <label for="garageCapacity">Garage Capacity:</label>
        <input type="number" id="garageCapacity" name="garageCapacity" value="<?php echo $property['garage']; ?>" required>

        <label for="floorSize">Floor Size (m²):</label>
        <input type="number" id="floorSize" name="floorSize" value="<?php echo $property['floor_size']; ?>" required>

        <label for="images">Upload Images:</label>
        <input type="file" id="images" name="images[]" multiple>

        <h3>Current Images:</h3>
        <div class="image-gallery">
            <?php
            $existing_images = json_decode($property['images'], true);
            if (is_array($existing_images) && !empty($existing_images)) {
                foreach ($existing_images as $image) {
                    $image_path = 'uploads/' . $image;
                    echo "<img src='$image_path' alt='Property Image'>";
                    echo "<p>Image Path: $image_path</p>"; // Debug output
                }
            } else {
                echo "No images uploaded.";
            }
            ?>
        </div>

        <button type="submit">Update Property</button>
    </form>
</body>
</html>
