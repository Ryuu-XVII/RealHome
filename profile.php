<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

// Get the logged-in agent's username from the session
$username = $_SESSION['username'];

// Fetch the logged-in agent's details using the username
$query = $conn->prepare("SELECT * FROM agents WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$agent = $query->get_result()->fetch_assoc();

if (!$agent) {
    echo "Error: Agent details not found.";
    exit();
}

// Fetch properties listed by the agent
$propertiesQuery = $conn->prepare("SELECT * FROM properties WHERE agent_username = ?");
$propertiesQuery->bind_param("s", $username);
$propertiesQuery->execute();
$propertiesResult = $propertiesQuery->get_result();

// Handle property listing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $propertyType = $_POST['property_type'];
    $listingType = $_POST['listing_type'];
    $address = $_POST['address'];
    $price = $_POST['price'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $garage = $_POST['garage'];
    $floorSize = $_POST['floor_size'];
    $images = $_FILES['images'];
    
    // Process images
    $imagePaths = [];
    $uploadsDir = 'uploads/';
    foreach ($images['tmp_name'] as $key => $tmpName) {
        $imageName = basename($images['name'][$key]);
        $uploadFile = $uploadsDir . uniqid() . '-' . $imageName;
        if (move_uploaded_file($tmpName, $uploadFile)) {
            $imagePaths[] = $uploadFile;
        }
    }

    // Serialize image paths for storage
    $imagesSerialized = serialize($imagePaths);

    // Insert property into database
    $insertQuery = $conn->prepare("INSERT INTO properties (agent_username, property_type, listing_type, address, price, bedrooms, bathrooms, garage, floor_size, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertQuery->bind_param("ssssdiiiss", $username, $propertyType, $listingType, $address, $price, $bedrooms, $bathrooms, $garage, $floorSize, $imagesSerialized);

    if ($insertQuery->execute()) {
        echo "<p>Property listed successfully!</p>";
    } else {
        echo "<p>Error: Could not list property. " . $conn->error . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile</title>
    <link rel="stylesheet" href="profile_style.css">
    <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style type="text/css">
        /* Body Background */
        body {
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: white;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Property Image */
        .property-image {
            max-width: 100%;
            max-height: 300px;
            width: auto;
            height: auto;
            margin: 5px;
        }

        /* Agent Photo */
        .agent-photo {
            max-width: 150px;
            height: auto;
        }

        /* Properties Container */
        .properties-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }

        /* Property Box */
        .property-box {
            background-color: rgba(0, 0, 0, 0.6);
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 10px;
            width: calc(100% - 20px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
            position: relative;
        }

        /* Edit Button */
        .edit-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            text-align: center;
            display: inline-block;
            position: absolute;
            bottom: 10px;
            right: 10px;
        }

        .edit-button:hover {
            background-color: #0056b3;
        }

        /* Container Styles */
        .container {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            width: 90%;
            margin: 20px auto;
        }

        /* Profile Page Styles */
        .profile-container {
            text-align: center;
        }

        .profile-container h2 {
            margin-top: 10px;
            color: white;
            font-size: 36px;
        }

        /* Form Styles */
        form {
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        form input, form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #0056b3;
        }

        /* Footer */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                width: 90%;
            }

            .properties-container {
                flex-direction: column;
                gap: 10px;
            }

            .property-box {
                width: 100%;
            }

            .profile-container h2 {
                font-size: 28px;
            }

            .agent-photo {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>RealHome</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="listing.html">Listings</a></li>
                <li><a href="agents.php">Agents</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="profile-container">
        <h2>Agent Profile</h2>
        <div id="profileDetails">
            <div class="agent-profile">
                <img src="<?php echo $agent['photo']; ?>" alt="Profile Picture" class="agent-photo">
                <p>Name: <?php echo $agent['name']; ?></p>
                <p>Username: <?php echo $agent['username']; ?></p>
                <p>Email: <?php echo $agent['email']; ?></p>
                <p>Phone: <?php echo $agent['phone']; ?></p>
            </div>
        </div>
        
         <h2>List a New Property</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="property_type">Property Type:</label>
            <select name="property_type" id="property_type" required>
                <option value="House">House</option>
                <option value="Apartment">Apartment</option>
                <option value="Townhouse">Townhouse</option>
                <option value="Commercial">Commercial</option>
            </select>

            <label for="listing_type">Listing Type:</label>
            <select name="listing_type" id="listing_type" required>
                <option value="For Sale">For Sale</option>
                <option value="For Rent">For Rent</option>
            </select>

            <label for="address">Area:</label>
            <input style="width: 578px" type="text" name="address" id="address" required>

            <label for="price">Price:</label>
            <input style="width: 578px" type="number" name="price" id="price" required step="0.01">

            <label for="bedrooms">Bedrooms:</label>
            <input style="width: 578px" type="number" name="bedrooms" id="bedrooms" required>

            <label for="bathrooms">Bathrooms:</label>
            <input style="width: 578px" type="number" name="bathrooms" id="bathrooms" required>

            <label for="garage">Garage Capacity:</label>
            <input style="width: 578px" type="number" name="garage" id="garage" required>

            <label for="floor_size">Floor Size (m²):</label>
            <input style="width: 578px" type="number" name="floor_size" id="floor_size" required>

            <label for="images">Upload Images:</label>
            <input style="width: 578px" type="file" name="images[]" id="images" multiple required>

            <button type="submit">List Property</button>
        </form>
    </div>

        <h2>Your Listed Properties</h2>
        <div class="properties-container">
            <?php if ($propertiesResult->num_rows > 0): ?>
                <?php while ($property = $propertiesResult->fetch_assoc()): ?>
                    <div class="property-box">
                        <?php $images = unserialize($property['images']); ?>
                        <img src="<?php echo $images[0]; ?>" alt="Property Image" class="property-image">
                        <p><strong>Type:</strong> <?php echo $property['property_type']; ?></p>
                        <p><strong>Address:</strong> <?php echo $property['address']; ?></p>
                        <p><strong>Price:</strong> R<?php echo number_format($property['price'], 2); ?></p>
                        <p><strong>Bedrooms:</strong> <?php echo $property['bedrooms']; ?></p>
                        <p><strong>Bathrooms:</strong> <?php echo $property['bathrooms']; ?></p>
                        <p><strong>Garage:</strong> <?php echo $property['garage']; ?></p>
                        <p><strong>Floor Size:</strong> <?php echo $property['floor_size']; ?> m²</p>
                        <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="edit-button">Edit</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No properties listed yet.</p>
            <?php endif; ?>
        </div>

       

    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>
</body>
</html>
