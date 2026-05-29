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

$property = null;

// Get property ID from URL
if (!isset($_GET['property_id'])) {
    echo "Error: Property ID not provided.";
    exit();
}

$property_id = intval($_GET['property_id']);

// Fetch property details
$query = $conn->prepare("SELECT * FROM properties WHERE id = ?");
$query->bind_param("i", $property_id);
$query->execute();
$property = $query->get_result()->fetch_assoc();

if (!$property) {
    echo "Error: Property not found.";
    exit();
}

// Ensure the logged-in agent owns this listing
if ($property['agent_username'] !== $_SESSION['username']) {
    echo "Error: Unauthorized access to this listing.";
    exit();
}

// Unified image parsing function
function getPropertyImages($images_field) {
    if (empty($images_field)) {
        return [];
    }
    $images = @unserialize($images_field);
    if ($images !== false && is_array($images)) {
        return $images;
    }
    $images = json_decode($images_field, true);
    if (is_array($images)) {
        return $images;
    }
    if (strpos($images_field, ',') !== false) {
        return array_map('trim', explode(',', $images_field));
    }
    return [$images_field];
}

$existing_images = getPropertyImages($property['images']);

// Handle photo removal request if submitted via GET
if (isset($_GET['remove_image_idx'])) {
    $remove_idx = intval($_GET['remove_image_idx']);
    if (isset($existing_images[$remove_idx])) {
        // Delete the physical file if it exists
        $file_to_delete = $existing_images[$remove_idx];
        if (file_exists($file_to_delete) && is_file($file_to_delete)) {
            @unlink($file_to_delete);
        }
        
        // Remove from array and reindex
        unset($existing_images[$remove_idx]);
        $existing_images = array_values($existing_images);
        
        // Save back to DB
        $images_serialized = serialize($existing_images);
        $updateQuery = $conn->prepare("UPDATE properties SET images = ? WHERE id = ?");
        $updateQuery->bind_param("si", $images_serialized, $property_id);
        $updateQuery->execute();
        
        // Redirect to avoid refresh resubmissions
        header("Location: edit_property.php?property_id=" . $property_id);
        exit();
    }
}

// Check if the main form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get property details from the form
    $property_type = !empty($_POST['propertyType']) ? $_POST['propertyType'] : $property['property_type'];
    $listing_type = !empty($_POST['listingType']) ? $_POST['listingType'] : $property['listing_type'];
    $address = !empty($_POST['address']) ? trim($_POST['address']) : $property['address'];
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : $property['price'];
    $bedrooms = !empty($_POST['bedrooms']) ? intval($_POST['bedrooms']) : $property['bedrooms'];
    $bathrooms = !empty($_POST['bathrooms']) ? intval($_POST['bathrooms']) : $property['bathrooms'];
    $garage_capacity = !empty($_POST['garageCapacity']) ? intval($_POST['garageCapacity']) : $property['garage'];
    $floor_size = !empty($_POST['floorSize']) ? floatval($_POST['floorSize']) : $property['floor_size'];

    // Handle file uploads (optional)
    $uploaded_images = [];
    $image_dir = 'uploads/';

    if (!empty($_FILES['images']['name'][0])) {
        if (!is_dir($image_dir)) {
            mkdir($image_dir, 0777, true);
        }
        
        foreach ($_FILES['images']['name'] as $key => $image) {
            if (empty($_FILES['images']['tmp_name'][$key])) continue;
            $image_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $_FILES['images']['name'][$key]);
            $image_tmp_name = $_FILES['images']['tmp_name'][$key];
            $image_destination = $image_dir . $image_name;

            if (move_uploaded_file($image_tmp_name, $image_destination)) {
                $uploaded_images[] = $image_destination;
            }
        }
    }

    // Merge uploads into existing photos array
    if (!empty($uploaded_images)) {
        $existing_images = array_merge($existing_images, $uploaded_images);
    }

    // Convert the image array back to a legacy compatible PHP serialization string
    $images_serialized = serialize($existing_images);

    // Update the property in the database
    $updateQuery = $conn->prepare("UPDATE properties 
                                   SET property_type = ?, listing_type = ?, address = ?, price = ?, bedrooms = ?, bathrooms = ?, garage = ?, floor_size = ?, images = ? 
                                   WHERE id = ?");
    $updateQuery->bind_param("sssiiiiiis", $property_type, $listing_type, $address, $price, $bedrooms, $bathrooms, $garage_capacity, $floor_size, $images_serialized, $property_id);

    if ($updateQuery->execute()) {
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
    <title>Edit Property #<?php echo $property_id; ?> - RealHome</title>
    <link rel="stylesheet" href="global.css">
    <link rel="icon" type="image/png" href="images/logo.png">
</head>
<body>
    <div class="body-bg-overlay"></div>

    <!-- Preloader -->
    <div id="preloader">
        <div class="loader-content">
            <div class="loader-ring"></div>
            <div class="loader-text">REALHOME</div>
        </div>
    </div>

    <!-- Navigation Header -->
    <header class="site-header">
        <div class="logo-container">
            <a href="index.php">
                <span class="logo-icon">⌂</span>
                <span class="logo-text">REALHOME<span class="logo-dot">.</span></span>
            </a>
        </div>
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="listing.php">Listings</a></li>
                <li><a href="agents.php">Agents</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li class="active"><a href="profile.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="nav-actions">
            <button class="wishlist-toggle-btn" title="View Saved Properties">
                🤍 <span class="wishlist-badge" style="display: none;">0</span>
            </button>
        </div>
    </header>

    <div style="padding: 60px 5%; max-width: 700px; margin: 0 auto;">
        
        <!-- Form container -->
        <div class="glass-panel reveal active">
            <h2 style="font-family: var(--font-serif); font-size: 30px; font-weight:700; color:white; text-align:center; text-transform:uppercase; letter-spacing:1px; margin-bottom: 25px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                ✏ Edit Listing details
            </h2>

            <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Property Category</label>
                        <select id="propertyType" name="propertyType" class="form-input" required>
                            <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
                            <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="townhouse" <?php echo $property['property_type'] === 'townhouse' ? 'selected' : ''; ?>>Townhouse</option>
                            <option value="commercial" <?php echo $property['property_type'] === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Listing Mode</label>
                        <select id="listingType" name="listingType" class="form-input" required>
                            <option value="buy" <?php echo (strtolower($property['listing_type']) == 'buy' || strtolower($property['listing_type']) == 'sale') ? 'selected' : ''; ?>>For Sale</option>
                            <option value="rent" <?php echo strtolower($property['listing_type']) === 'rent' ? 'selected' : ''; ?>>For Rent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Location / Address</label>
                    <input type="text" id="address" name="address" class="form-input" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Price (ZAR)</label>
                        <input type="number" id="price" name="price" class="form-input" value="<?php echo floatval($property['price']); ?>" required step="0.01">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Floor Size (m²)</label>
                        <input type="number" id="floorSize" name="floorSize" class="form-input" value="<?php echo floatval($property['floor_size']); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" id="bedrooms" name="bedrooms" class="form-input" value="<?php echo intval($property['bedrooms']); ?>" required min="0">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" id="bathrooms" name="bathrooms" class="form-input" value="<?php echo intval($property['bathrooms']); ?>" required min="0">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Garages</label>
                        <input type="number" id="garageCapacity" name="garageCapacity" class="form-input" value="<?php echo intval($property['garage']); ?>" required min="0">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Add More Images</label>
                    <input type="file" id="images" name="images[]" class="form-input" multiple accept="image/*">
                </div>

                <!-- Existing Image Gallery with Direct Delete anchors -->
                <h3 style="font-size: 15px; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; margin-top: 10px;">
                    Manage Active Images
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 15px; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--glass-border); padding: 15px; border-radius: var(--radius-sm);">
                    <?php if (!empty($existing_images)): ?>
                        <?php foreach ($existing_images as $idx => $image): ?>
                            <div style="position: relative; width: 100%; height: 90px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--glass-border);">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Listing Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                <a href="edit_property.php?property_id=<?php echo $property_id; ?>&remove_image_idx=<?php echo $idx; ?>" 
                                   onclick="return confirm('Remove this image from listing?');"
                                   style="position: absolute; top: 4px; right: 4px; background: rgba(239, 68, 68, 0.85); color: white; border-radius: var(--radius-full); width: 22px; height: 22px; display: flex; justify-content: center; align-items: center; text-decoration: none; font-weight: 800; font-size: 12px;"
                                   title="Delete image">&times;</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); font-size:12px;">No images loaded.</p>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <a href="profile.php" class="btn-secondary" style="flex: 1; padding: 12px; border-radius: var(--radius-sm);">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary" style="flex: 2; padding: 12px; border-radius: var(--radius-sm);">
                        <span>💾 Save Updates</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sliding Wishlist Drawer Components -->
    <div class="wishlist-overlay"></div>
    <div class="wishlist-drawer">
        <div class="wishlist-drawer-header">
            <h3>Saved Properties</h3>
            <button class="wishlist-close-btn">&times;</button>
        </div>
        <div class="wishlist-items-container">
            <!-- Populated via js/app.js -->
        </div>
    </div>

    <!-- Premium Footer -->
    <footer class="site-footer">
        <div class="footer-content">
            <div>
                <div class="logo-container footer-logo">
                    <a href="index.php">
                        <span class="logo-icon">⌂</span>
                        <span class="logo-text">REALHOME<span class="logo-dot">.</span></span>
                    </a>
                </div>
                <p class="footer-text">
                    Bringing you the premium, high-end visual real estate experience in South Africa. Browse, calculate, enquire, and find your perfect home seamlessly.
                </p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="listing.php">Property Listings</a></li>
                    <li><a href="agents.php">RealHome Agents</a></li>
                    <li><a href="contact.php">Contact Agent</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Contact Us</h4>
                <p class="footer-text" style="margin-bottom: 10px;">📍 100 Luxury Drive, Sandton, 2196</p>
                <p class="footer-text" style="margin-bottom: 10px;">✉ info@realhome.co.za</p>
                <p class="footer-text">📞 +27 (0) 11 555 0192</p>
            </div>
        </div>
        
        <div class="footer-copyright">
            &copy; 2026 RealHome. All rights reserved.
        </div>
    </footer>

    <!-- Master Scripts -->
    <script src="js/app.js"></script>
</body>
</html>
