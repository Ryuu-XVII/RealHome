<?php
// Include the database connection and session start
include('db_connection.php');
session_start();

// Fetch property listings from the database for display
$query = "SELECT * FROM properties";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealHome: A Real Estate Listing Platform</title>
    <link rel="stylesheet" href="style1.css">
    <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style type="text/css">
        body {
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }

        /* Global Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        header {
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
        }

        nav ul li {
            margin: 0 15px;
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

        .property-listings {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* Center the properties */
            gap: 20px;
            margin-top: 20px;
        }

        .property {
            background-color: rgba(0, 0, 0, 0.4); /* Light background for better contrast */
            border: 1px solid transparent;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: calc(33.333% - 20px); /* Three columns on larger screens */
        }

        /* More Details Button Styles */
        .more-details-button {
            display: inline-block;
            margin-top: 10px; /* Space above the button */
            padding: 10px 15px;
            background-color: #007bff; /* Button color */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            float: right;
        }

        .more-details-button:hover {
            background-color: #0056b3; /* Darker color on hover */
        }

        /* Detail Icon Styles */
        .detail-icon {
            margin-right: 10px; /* Space between icons */
            color: white; /* Change icon color */
        }

        /* Responsive Styles */
        /* Mobile Styles (max-width: 768px) */
        @media (max-width: 768px) {
            nav ul {
                flex-direction: column;
                text-align: center;
            }

            nav ul li {
                margin: 10px 0;
            }

            .property {
                width: 100%; /* Full width for mobile */
            }
        }

        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
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
                <li><a href="profile.php">Profile</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <section id="home">
        <h2 style="text-align: center; font-size: 80px;">Welcome to RealHome</h2>
        <p style="text-align: center; font-size: 40px">Your one-stop platform for buying, selling, and renting properties.</p>
    </section>

    <section id="properties">
        <h2 style="font-size: 30px">Latest Properties</h2>
        <div class="property-listings">
            <?php
            if ($result->num_rows > 0) {
                while ($property = $result->fetch_assoc()) {
                    // Display each property
                    echo '<div class="property">';
                    echo '<h3 style="color: white; text-transform: uppercase">' . htmlspecialchars($property['property_type']) . ' for ' . htmlspecialchars($property['listing_type']) . '</h3>';
                    echo '<p>Area: ' . htmlspecialchars($property['address']) . '</p>';
                    echo '<p>Price: R' . number_format($property['price'], 2) . '</p>'; // Display price in ZAR
                    
                    // Display details with icons
                    echo '<p>';
                    echo '<span class="detail-icon">🛏 ' . intval($property['bedrooms']) . '</span>'; // Rooms
                    echo '<span class="detail-icon">🛁 ' . intval($property['bathrooms']) . '</span>'; // Bathrooms
                    echo '<span class="detail-icon">🚗 ' . intval($property['garage']) . '</span>'; // Garage Capacity
                    echo '<span class="detail-icon">📐 ' . intval($property['floor_size']) . ' m²</span>'; // Floor Size
                    echo '</p>';

                    // Display images (assuming they're stored as a serialized array)
                    $images = unserialize($property['images']);
                    if (!empty($images) && is_array($images)) {
                        foreach ($images as $image) {
                            echo '<img src="' . htmlspecialchars($image) . '" alt="Property Image" width="200px">';
                        }
                    }
                    
                    // More Details Button
                    echo '<a href="property_details.php?id=' . intval($property['id']) . '" class="more-details-button">More Details</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>No properties available at the moment.</p>';
            }
            ?>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>

    <script src="js/app.js"></script>

    <script>
        // Listen for the page unload event
        window.addEventListener('beforeunload', function (event) {
            // Perform an AJAX request to log out the user
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "logout.php", true); // Sending a request to the logout page
            xhr.send(); // No need for response handling, as the user is leaving the page
        });
    </script>
</body>
</html>
