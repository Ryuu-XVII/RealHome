<?php
require 'db_connection.php';

// Get the property ID from the URL
$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($propertyId <= 0) {
    die("Invalid property ID.");
}

// Prepare the SQL query
$sql = "SELECT properties.*, agents.name AS agent_name, agents.photo AS agent_photo 
        FROM properties 
        JOIN agents ON properties.agent_username = agents.username 
        WHERE properties.id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Display error if preparation fails
}

$stmt->bind_param('i', $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $property = $result->fetch_assoc();
} else {
    echo '<p>Property not found.</p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['address']); ?> - Property Details</title>
    <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style>
        /* Global Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            background-color: #f4f4f4;
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }

        /* Header Styles */
        header {
            background-color: #333;
            color: white;
            padding: 15px 20px;
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 22px;
        }

        /* Navigation Styles */
        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 10px 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        nav ul li {
            margin: 5px 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: white;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #575757;
            border-radius: 4px;
        }

        /* Property Details Section */
        .property-details {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .property-details h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .images img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .property-details p {
            font-size: 1.1em;
            margin: 10px 0;
        }

        .detail-icon {
            margin-right: 10px;
        }

        /* Agent Info */
        .agent-info {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .agent-info img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
        }

        /* Contact Agent Button */
        .contact-agent-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .contact-agent-button:hover {
            background-color: #007bff;
        }

        /* Footer */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .property-details {
                padding: 15px;
            }

            .images {
                flex-direction: column;
            }

            nav ul {
                flex-direction: column;
                text-align: center;
            }

            nav ul li {
                margin: 10px 0;
            }

            header h1 {
                font-size: 20px;
            }

            .property-details h2 {
                font-size: 1.6em;
            }

            .agent-info {
                flex-direction: column;
                text-align: center;
            }

            .agent-info img {
                margin-bottom: 10px;
            }

            .contact-agent-button {
                width: 100%;
                text-align: center;
            }

            footer {
                padding: 8px 0;
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
                <li><a href="listing.php">Listings</a></li>
                <li><a href="agents.php">Agents</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="property-details">
        <h2 style="text-transform: uppercase"><?php echo htmlspecialchars($property['address']); ?></h2>
        <div class="images">
            <?php
            // Unserialize the images field to get the array
            $imageUrls = unserialize($property['images']);
            if (!empty($imageUrls)) {
                foreach ($imageUrls as $imageUrl) {
                    echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Property Image">';
                }
            } else {
                echo '<p>No images available</p>';
            }
            ?>
        </div>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($property['property_type']); ?></p>
        <p><strong>Price:</strong> R<?php echo number_format($property['price'], 2); ?></p>

        <p>
            <span class="detail-icon">🛏 <?php echo htmlspecialchars($property['bedrooms']); ?></span>
            <span class="detail-icon">🛁 <?php echo htmlspecialchars($property['bathrooms']); ?></span>
            <span class="detail-icon">🚗 <?php echo htmlspecialchars($property['garage']); ?></span>
            <span class="detail-icon">📐 <?php echo htmlspecialchars($property['floor_size']); ?> m²</span>
        </p>

        <h3>Listed By:</h3>
        <div class="agent-info">
            <img src="<?php echo htmlspecialchars($property['agent_photo']); ?>" alt="Agent Image" class="agent-image">
            <p><?php echo htmlspecialchars($property['agent_name']); ?></p>
        </div>
        
        <!-- Contact Agent Button -->
        <a href="contact.php" class="contact-agent-button">Contact Agent</a>
    </div>

    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
