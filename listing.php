<?php
require 'db_connection.php';

// Fetch search parameters
$address = isset($_GET['addressSearch']) ? $_GET['addressSearch'] : '';
$propertyType = isset($_GET['propertyType']) ? $_GET['propertyType'] : '';
$minPrice = isset($_GET['minPrice']) ? $_GET['minPrice'] : '';
$maxPrice = isset($_GET['maxPrice']) ? $_GET['maxPrice'] : '';

// Prepare the SQL query
$sql = "SELECT * FROM properties WHERE 1=1";

if (!empty($address)) {
    $sql .= " AND address LIKE ?";
}

if (!empty($propertyType)) {
    $sql .= " AND property_type = ?";
}

if (!empty($minPrice)) {
    $sql .= " AND price >= ?";
}

if (!empty($maxPrice)) {
    $sql .= " AND price <= ?";
}

$stmt = $conn->prepare($sql);

// Bind parameters
$params = [];
$types = '';

if (!empty($address)) {
    $params[] = "%$address%";
    $types .= 's';
}

if (!empty($propertyType)) {
    $params[] = $propertyType;
    $types .= 's';
}

if (!empty($minPrice)) {
    $params[] = $minPrice;
    $types .= 'i';
}

if (!empty($maxPrice)) {
    $params[] = $maxPrice;
    $types .= 'i';
}

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Listings</title>
    <link rel="stylesheet" href="listing_style.css">
    <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style type="text/css">
        body {
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }
        
        /* General body styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header styling */
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
            flex-wrap: wrap;
        }

        nav ul li {
            margin: 0 10px;
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

        /* Listing container styling */
        .listing-container {
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 20px auto;
            width: 90%;
        }

        /* Heading style */
        .listing-container h2 {
            text-align: center;
            color: white;
            margin-top: 0;
        }

        /* Search form styling */
        #searchForm {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        #searchForm label {
            display: block;
            color: white;
            margin-bottom: 5px;
        }

        #searchForm input[type="text"],
        #searchForm input[type="number"],
        #searchForm select {
            width: 100%; /* Full width on mobile */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        #searchForm button {
            width: 100%;
            padding: 15px;
            border: none;
            background-color: #007BFF;
            color: white;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #searchForm button:hover {
            background-color: #0056b3;
        }

        /* Properties container */
        #properties {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        /* Individual property card */
        .property-card {
            background-color: rgba(0, 0, 0, 0.6);
            border: 4px solid transparent;
            border-radius: 8px;
            padding: 20px;
            width: calc(33.33% - 20px);
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: white;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        /* Property image */
        .property-card .property-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Property information */
        .property-card p {
            margin: 5px 0;
            color: white;
        }

        .property-card p:first-of-type {
            font-weight: bold;
        }

        /* Detail icons */
        .detail-icon {
            display: inline-block;
            margin: 0 5px;
            color: white;
        }

        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #575757;
            border-color: #575757;
        }

        .pagination .active {
            background-color: #007BFF;
            color: white;
            border-color: #007BFF;
        }

        .pagination .disabled {
            color: #888;
            cursor: not-allowed;
        }

        /* Footer */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
        }

        /* Responsive Styles for Mobile */
        @media screen and (max-width: 768px) {
            /* Stack nav items vertically */
            nav ul {
                flex-direction: column;
            }

            /* Make property cards 50% width */
            .property-card {
                width: calc(50% - 20px);
            }
        }

        @media screen and (max-width: 480px) {
            /* Make property cards full width */
            .property-card {
                width: 100%;
            }

            /* Reduce font size in header for smaller screens */
            header h1 {
                font-size: 20px;
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

    <div class="listing-container">
        <h2 style="text-transform: uppercase">Property Listings</h2>
        <form id="searchForm" action="listing.php" method="GET">
            <label for="addressSearch">Area:</label>
            <input type="text" id="addressSearch" name="addressSearch" value="<?php echo htmlspecialchars($address); ?>">

            <label for="propertyType">Property Type:</label>
            <select id="propertyType" name="propertyType">
                <option value="">Select Property Type</option>
                <option value="House" <?php echo ($propertyType == 'House') ? 'selected' : ''; ?>>House</option>
                <option value="Apartment" <?php echo ($propertyType == 'Apartment') ? 'selected' : ''; ?>>Apartment</option>
                <option value="Commercial" <?php echo ($propertyType == 'Commercial') ? 'selected' : ''; ?>>Commercial</option>
            </select>

            <label for="minPrice">Min Price:</label>
            <input type="number" id="minPrice" name="minPrice" value="<?php echo htmlspecialchars($minPrice); ?>">

            <label for="maxPrice">Max Price:</label>
            <input type="number" id="maxPrice" name="maxPrice" value="<?php echo htmlspecialchars($maxPrice); ?>">

            <button type="submit">Search</button>
        </form>

        <div id="properties">
            <?php
            if ($result->num_rows > 0) {
                while ($property = $result->fetch_assoc()) {
                    // Unserialize images
                    $imageUrls = unserialize($property['images']);
                    $propertyId = $property['id']; // Assuming there's an 'id' field

                    echo '<div class="property-card">';
                    if (!empty($imageUrls)) {
                        echo '<a href="property_details.php?id=' . $propertyId . '">';
                        foreach ($imageUrls as $imageUrl) {
                            echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Property Image" class="property-image">';
                        }
                        echo '</a>'; // Close the link
                    } else {
                        echo '<p>No images available</p>';
                    }
                    echo '<p><strong>Area:</strong> ' . htmlspecialchars($property['address']) . '</p>';
                    echo '<p><strong>Type:</strong> ' . htmlspecialchars($property['property_type']) . '</p>';
                    echo '<p><strong>Price:</strong> R' . number_format($property['price'], 2) . '</p>';
                    
                    // Display bedrooms, bathrooms, garage space, and floor size
                    echo '<p>
                        <span class="detail-icon">🛏 ' . htmlspecialchars($property['bedrooms']) . '</span>
                        <span class="detail-icon">🛁 ' . htmlspecialchars($property['bathrooms']) . '</span>
                        <span class="detail-icon">🚗 ' . htmlspecialchars($property['garage']) . '</span>
                        <span class="detail-icon">📐 ' . htmlspecialchars($property['floor_size']) . ' m²</span>
                    </p>';

                    echo '<a style="display: inline-block;
                    margin-top: 10px; /* Space above the button */
                    padding: 10px 15px;
                    background-color: #007bff; /* Button color */
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    transition: background-color 0.3s ease;
                    float: right;" href="property_details.php?id=' . $propertyId . '">View Details</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>No properties found.</p>';
            }
            ?>
        </div>

        <div class="pagination">
            <!-- Example pagination links (you should implement actual pagination logic) -->
            <a href="#" class="disabled">« Previous</a>
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">Next »</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>
</body>
</html>
