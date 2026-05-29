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
$propertiesQuery = $conn->prepare("SELECT * FROM properties WHERE agent_username = ? ORDER BY created_at DESC");
$propertiesQuery->bind_param("s", $username);
$propertiesQuery->execute();
$propertiesResult = $propertiesQuery->get_result();

$successMessage = '';
$errorMessage = '';

// Handle property listing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $propertyType = isset($_POST['property_type']) ? $_POST['property_type'] : 'house';
    $listingType = isset($_POST['listing_type']) ? $_POST['listing_type'] : 'sale';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : 0;
    $bathrooms = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : 0;
    $garage = isset($_POST['garage']) ? intval($_POST['garage']) : 0;
    $floorSize = isset($_POST['floor_size']) ? floatval($_POST['floor_size']) : 0;
    $images = isset($_FILES['images']) ? $_FILES['images'] : null;
    
    // Validation
    if (empty($address) || $price <= 0) {
        $errorMessage = 'Address and a valid Price are required.';
    } else {
        // Process images
        $imagePaths = [];
        $uploadsDir = 'uploads/';
        
        // Check if directory exists
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        if ($images && !empty($images['tmp_name'][0])) {
            foreach ($images['tmp_name'] as $key => $tmpName) {
                if (empty($tmpName)) continue;
                $imageName = basename($images['name'][$key]);
                // Create unique file name to prevent collision
                $uploadFile = $uploadsDir . uniqid() . '-' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $imageName);
                if (move_uploaded_file($tmpName, $uploadFile)) {
                    $imagePaths[] = $uploadFile;
                }
            }
        }

        // Serialize image paths for database storage (Legacy compatible PHP serialization)
        $imagesSerialized = serialize($imagePaths);

        // Insert property into database
        $insertQuery = $conn->prepare("INSERT INTO properties (agent_username, property_type, listing_type, address, price, bedrooms, bathrooms, garage, floor_size, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertQuery->bind_param("ssssdiiiss", $username, $propertyType, $listingType, $address, $price, $bedrooms, $bathrooms, $garage, $floorSize, $imagesSerialized);

        if ($insertQuery->execute()) {
            $successMessage = "Property listed successfully!";
            // Re-fetch properties
            $propertiesQuery->execute();
            $propertiesResult = $propertiesQuery->get_result();
        } else {
            $errorMessage = "Error: Could not list property. " . $conn->error;
        }
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - RealHome</title>
    <link rel="stylesheet" href="global.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 5%;
        }

        .agent-profile-box {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 15px;
            display: flex;
            gap: 15px;
            position: relative;
        }

        .dashboard-card-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .dashboard-card-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex-grow: 1;
        }

        .dashboard-card-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            justify-content: center;
        }

        .btn-mini {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .btn-mini-edit {
            background: var(--accent);
            color: white;
            border: none;
        }
        .btn-mini-edit:hover {
            background: var(--accent-hover);
        }
        .btn-mini-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-mini-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* Stats counters styling */
        .agent-quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            width: 100%;
            margin-top: 20px;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            padding: 10px;
            border-radius: var(--radius-sm);
            text-align: center;
        }
        .stat-num {
            font-size: 20px;
            font-weight: 800;
            color: var(--accent);
        }
        .stat-lbl {
            font-size: 9px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
        }

        @media (max-width: 991px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

    <div class="dashboard-layout">
        <!-- LEFT COLUMN: Profile & Upload Block -->
        <div>
            <!-- Agent profile information block -->
            <div class="glass-panel agent-profile-box reveal active">
                <img src="<?php echo htmlspecialchars($agent['photo']); ?>" 
                     alt="Agent Avatar" 
                     style="width: 110px; height: 110px; object-fit: cover; border-radius: var(--radius-full); border: 3px solid var(--accent); box-shadow: var(--accent-glow); margin-bottom: 15px;">
                
                <h3 style="font-family: var(--font-serif); font-size: 22px; font-weight:700; color: white;"><?php echo htmlspecialchars($agent['name']); ?></h3>
                <p style="color: var(--accent); font-size: 13px; font-weight:600; margin-bottom: 5px;">@<?php echo htmlspecialchars($agent['username']); ?></p>
                
                <div style="font-size:13px; color:var(--text-secondary); margin-top: 10px; display:flex; flex-direction:column; gap:4px; text-align:left; width: 100%;">
                    <div>✉ <?php echo htmlspecialchars($agent['email']); ?></div>
                    <div>📞 <?php echo htmlspecialchars($agent['phone']); ?></div>
                </div>

                <div class="agent-quick-stats">
                    <div class="stat-box">
                        <div class="stat-num"><?php echo $propertiesResult->num_rows; ?></div>
                        <div class="stat-lbl">Listings</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-num">12.5k</div>
                        <div class="stat-lbl">Mock Views</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-num">48</div>
                        <div class="stat-lbl">Inquiries</div>
                    </div>
                </div>
            </div>

            <!-- List New Property Form -->
            <div class="glass-panel reveal" style="margin-top: 25px;">
                <h3 style="font-family: var(--font-serif); font-size: 20px; font-weight:700; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px; color: white;">
                    ➕ List a New Property
                </h3>

                <!-- Status Alerts -->
                <?php if (!empty($successMessage)): ?>
                    <div style="padding: 10px 15px; border-radius: var(--radius-sm); margin-bottom: 15px; background: rgba(13, 148, 136, 0.15); border: 1px solid var(--accent); color: #2dd4bf; font-size:13px; text-align:center;">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errorMessage)): ?>
                    <div style="padding: 10px 15px; border-radius: var(--radius-sm); margin-bottom: 15px; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; font-size:13px; text-align:center;">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px;">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size: 11px;">Category</label>
                            <select name="property_type" class="form-input" style="padding: 8px 12px; font-size: 13px;" required>
                                <option value="house">House</option>
                                <option value="apartment">Apartment</option>
                                <option value="townhouse">Townhouse</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size: 11px;">Listing Mode</label>
                            <select name="listing_type" class="form-input" style="padding: 8px 12px; font-size: 13px;" required>
                                <option value="buy">For Sale</option>
                                <option value="rent">For Rent</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Location / Address</label>
                        <input type="text" name="address" class="form-input" style="padding: 8px 12px; font-size: 13px;" required placeholder="e.g. 15 Ocean Drive, Camps Bay">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 11px;">Price (ZAR)</label>
                            <input type="number" name="price" class="form-input" style="padding: 8px 12px; font-size: 13px;" required placeholder="e.g. 2400000" step="0.01">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 11px;">Floor Size (m²)</label>
                            <input type="number" name="floor_size" class="form-input" style="padding: 8px 12px; font-size: 13px;" required placeholder="e.g. 140">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 10px;">Beds</label>
                            <input type="number" name="bedrooms" class="form-input" style="padding: 8px 12px; font-size: 13px;" required value="2" min="0">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 10px;">Baths</label>
                            <input type="number" name="bathrooms" class="form-input" style="padding: 8px 12px; font-size: 13px;" required value="1" min="0">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 10px;">Garages</label>
                            <input type="number" name="garage" class="form-input" style="padding: 8px 12px; font-size: 13px;" required value="1" min="0">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Upload Images</label>
                        <input type="file" name="images[]" class="form-input" style="padding: 8px 12px; font-size: 13px;" multiple required accept="image/*">
                    </div>

                    <button type="submit" class="btn-primary" style="padding: 12px; font-size:14px; border-radius: var(--radius-sm);">
                        <span>🚀 List Property Now</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: Listed Properties Dashboard -->
        <div>
            <div class="glass-panel reveal active" style="min-height: 100%;">
                <h3 style="font-family: var(--font-serif); font-size: 26px; font-weight:700; color: white; margin-bottom: 25px; border-bottom: 1px solid var(--glass-border); padding-bottom: 12px;">
                    🏡 Your Active Listed Properties
                </h3>

                <div class="dashboard-grid">
                    <?php if ($propertiesResult && $propertiesResult->num_rows > 0): ?>
                        <?php while ($property = $propertiesResult->fetch_assoc()): 
                            $images = getPropertyImages($property['images']);
                            $first_image = !empty($images) ? htmlspecialchars($images[0]) : 'images/hero_house_bg.png';
                            $priceStr = 'R' . number_format($property['price'], 2);
                            ?>
                            <div class="dashboard-card">
                                <img src="<?php echo $first_image; ?>" alt="Listing Image" class="dashboard-card-img">
                                
                                <div class="dashboard-card-info">
                                    <div style="font-size: 15px; font-weight: 700; color: var(--accent);"><?php echo $priceStr; ?></div>
                                    <div style="font-size: 13px; font-weight: 600; color: white; margin: 2px 0;"><?php echo htmlspecialchars(ucfirst($property['property_type'])); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($property['address']); ?></div>
                                    
                                    <div style="font-size: 10px; color: var(--text-secondary); margin-top: 5px; display: flex; gap: 8px;">
                                        <span>🛏 <?php echo intval($property['bedrooms']); ?></span>
                                        <span>🛁 <?php echo intval($property['bathrooms']); ?></span>
                                        <span>🚗 <?php echo intval($property['garage']); ?></span>
                                        <span>📐 <?php echo intval($property['floor_size']); ?> m²</span>
                                    </div>
                                </div>

                                <div class="dashboard-card-actions">
                                    <a href="edit_property.php?property_id=<?php echo $property['id']; ?>" class="btn-mini btn-mini-edit">Edit</a>
                                    <!-- Use a secure post request to remove the listing, pointing to remove_property.php -->
                                    <form action="remove_property.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this listing permanently?');" style="margin:0; padding:0; display:inline;">
                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                        <button type="submit" class="btn-mini btn-mini-delete" style="width:100%;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted);">
                            <p style="font-size: 40px; margin-bottom: 10px;">📦</p>
                            <p>You have not listed any properties yet.</p>
                            <p style="font-size:12px; margin-top: 5px;">Use the form on the left to add your first premium real estate listing!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
