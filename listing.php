<?php
require 'db_connection.php';
session_start();

// Fetch search parameters
$address = isset($_GET['addressSearch']) ? trim($_GET['addressSearch']) : '';
$propertyType = isset($_GET['propertyType']) ? trim($_GET['propertyType']) : '';
$listingType = isset($_GET['listingType']) ? trim($_GET['listingType']) : '';
$minPrice = isset($_GET['minPrice']) && $_GET['minPrice'] !== '' ? floatval($_GET['minPrice']) : '';
$maxPrice = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== '' ? floatval($_GET['maxPrice']) : '';
$bedrooms = isset($_GET['bedrooms']) && $_GET['bedrooms'] !== '' ? intval($_GET['bedrooms']) : '';
$bathrooms = isset($_GET['bathrooms']) && $_GET['bathrooms'] !== '' ? intval($_GET['bathrooms']) : '';

// Prepare the SQL query
$sql = "SELECT * FROM properties WHERE 1=1";
$params = [];
$types = '';

if (!empty($address)) {
    $sql .= " AND address LIKE ?";
    $params[] = "%$address%";
    $types .= 's';
}

if (!empty($propertyType)) {
    $sql .= " AND property_type = ?";
    $params[] = $propertyType;
    $types .= 's';
}

if (!empty($listingType)) {
    // Normalise listing type matching
    $sql .= " AND (listing_type = ? OR listing_type = ?)";
    if (strtolower($listingType) == 'rent') {
        $params[] = 'rent';
        $params[] = 'For Rent';
    } else {
        $params[] = 'buy';
        $params[] = 'For Sale';
    }
    $types .= 'ss';
}

if ($minPrice !== '') {
    $sql .= " AND price >= ?";
    $params[] = $minPrice;
    $types .= 'd';
}

if ($maxPrice !== '') {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

if ($bedrooms !== '') {
    $sql .= " AND bedrooms >= ?";
    $params[] = $bedrooms;
    $types .= 'i';
}

if ($bathrooms !== '') {
    $sql .= " AND bathrooms >= ?";
    $params[] = $bathrooms;
    $types .= 'i';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

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
    <title>Property Listings - RealHome</title>
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
                <li class="active"><a href="listing.php">Listings</a></li>
                <li><a href="agents.php">Agents</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li><a href="profile.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.html">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="nav-actions">
            <button class="wishlist-toggle-btn" title="View Saved Properties">
                🤍 <span class="wishlist-badge" style="display: none;">0</span>
            </button>
        </div>
    </header>

    <div style="padding: 40px 5%;">
        <!-- Advanced Filtering Panel -->
        <div class="glass-panel reveal active" style="margin-bottom: 40px;">
            <h2 style="font-family: var(--font-serif); font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px; text-align: center; text-transform: uppercase; letter-spacing: 1px;">
                Advanced Search Filter
            </h2>
            <form id="searchForm" action="listing.php" method="GET">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: flex-end;">
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Location / Area</label>
                        <input type="text" name="addressSearch" class="form-input" placeholder="e.g. Cape Town" value="<?php echo htmlspecialchars($address); ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Listing Mode</label>
                        <select name="listingType" class="form-input">
                            <option value="">Any listing mode</option>
                            <option value="buy" <?php echo ($listingType == 'buy') ? 'selected' : ''; ?>>For Sale</option>
                            <option value="rent" <?php echo ($listingType == 'rent') ? 'selected' : ''; ?>>For Rent</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Property Category</label>
                        <select name="propertyType" class="form-input">
                            <option value="">Any property category</option>
                            <option value="house" <?php echo (strtolower($propertyType) == 'house') ? 'selected' : ''; ?>>House</option>
                            <option value="apartment" <?php echo (strtolower($propertyType) == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                            <option value="townhouse" <?php echo (strtolower($propertyType) == 'townhouse') ? 'selected' : ''; ?>>Townhouse</option>
                            <option value="commercial" <?php echo (strtolower($propertyType) == 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Min Bedrooms</label>
                        <select name="bedrooms" class="form-input">
                            <option value="">Any beds</option>
                            <?php for($i=1; $i<=5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($bedrooms === $i) ? 'selected' : ''; ?>><?php echo $i; ?>+</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Min Bathrooms</label>
                        <select name="bathrooms" class="form-input">
                            <option value="">Any baths</option>
                            <?php for($i=1; $i<=5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($bathrooms === $i) ? 'selected' : ''; ?>><?php echo $i; ?>+</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Min Price (ZAR)</label>
                        <input type="number" name="minPrice" class="form-input" placeholder="Min" value="<?php echo htmlspecialchars($minPrice); ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Max Price (ZAR)</label>
                        <input type="number" name="maxPrice" class="form-input" placeholder="Max" value="<?php echo htmlspecialchars($maxPrice); ?>">
                    </div>

                    <button type="submit" class="btn-primary" style="height: 48px; width: 100%; border-radius: var(--radius-sm); font-size: 16px;">
                        <span>🔍 Apply Filters</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Properties Grid Display -->
        <h3 style="font-family: var(--font-serif); font-size: 28px; margin-bottom: 25px; color: var(--text-primary); border-left: 4px solid var(--accent); padding-left: 15px;" class="reveal">
            Search Results (<?php echo $result->num_rows; ?> found)
        </h3>

        <div class="properties-grid" id="propertiesListContainer">
            <?php
            if ($result->num_rows > 0) {
                $count = 0;
                while ($property = $result->fetch_assoc()) {
                    $count++;
                    $images = getPropertyImages($property['images']);
                    $first_image = !empty($images) ? htmlspecialchars($images[0]) : 'images/hero_house_bg.png';
                    $propertyId = $property['id'];
                    $priceStr = 'R' . number_format($property['price'], 2);

                    $bedrooms = isset($property['bedrooms']) ? intval($property['bedrooms']) : 0;
                    $bathrooms = isset($property['bathrooms']) ? intval($property['bathrooms']) : 0;
                    $garage = isset($property['garage']) ? intval($property['garage']) : 0;
                    $floor_size = isset($property['floor_size']) ? floatval($property['floor_size']) : 0;

                    $title = htmlspecialchars(ucfirst($property['property_type'])) . ' in ' . htmlspecialchars($property['address']);
                    ?>

                    <!-- Individual Property Card -->
                    <div class="real-property-card reveal" style="transition-delay: <?php echo ($count % 3) * 0.1; ?>s;">
                        <div class="card-image-wrapper">
                            <img src="<?php echo $first_image; ?>" alt="Property Image">
                            <div class="card-labels">
                                <span class="card-label-tag tag-<?php echo strtolower($property['listing_type']) == 'rent' ? 'rent' : 'sale'; ?>">
                                    For <?php echo htmlspecialchars($property['listing_type'] == 'rent' ? 'Rent' : 'Sale'); ?>
                                </span>
                                <span class="card-label-tag tag-type">
                                    <?php echo htmlspecialchars(ucfirst($property['property_type'])); ?>
                                </span>
                            </div>
                            <button class="card-wishlist-btn" 
                                    data-id="<?php echo $propertyId; ?>" 
                                    onclick="toggleWishlist('<?php echo $propertyId; ?>', '<?php echo addslashes($title); ?>', '<?php echo addslashes($priceStr); ?>', '<?php echo addslashes($first_image); ?>', '<?php echo addslashes($property['listing_type']); ?>', '<?php echo addslashes($property['address']); ?>')">
                                🤍
                            </button>
                        </div>
                        
                        <div class="card-details">
                            <div class="card-price"><?php echo $priceStr; ?></div>
                            <h4 class="card-title"><?php echo htmlspecialchars(ucfirst($property['property_type'])); ?></h4>
                            <div class="card-address">
                                <span style="font-size: 16px;">📍</span> <?php echo htmlspecialchars($property['address']); ?>
                            </div>
                            
                            <div class="card-specs">
                                <div class="spec-badge" title="Bedrooms">
                                    <svg viewBox="0 0 24 24"><path d="M7 13a3 3 0 1 1-3-3 3 3 0 0 1 3 3zm13-3a3 3 0 1 0 3 3 3 3 0 0 0-3-3zM2 17h20a1 1 0 0 0 1-1v-4a5 5 0 0 0-10 0 5 5 0 0 0-10 0v4a1 1 0 0 0 1 1zm18-7H4a2 2 0 0 0-2 2v6h20v-6a2 2 0 0 0-2-2z"/></svg>
                                    <span><?php echo $bedrooms; ?></span>
                                </div>
                                <div class="spec-badge" title="Bathrooms">
                                    <svg viewBox="0 0 24 24"><path d="M21 11H3a1 1 0 0 0-1 1v6a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1zm-1 7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-5h16zM7 7a3 3 0 1 0-3 3 3 3 0 0 0 3-3zm13-5h-2v2h-4V2h-2v2H7V2H5v2H3v2h18V2z"/></svg>
                                    <span><?php echo $bathrooms; ?></span>
                                </div>
                                <div class="spec-badge" title="Garage Space">
                                    <svg viewBox="0 0 24 24"><path d="M19 8h-1.18l-1.63-3.26A3 3 0 0 0 13.5 3h-3a3 3 0 0 0-2.68 1.66L6.18 8H5a3 3 0 0 0-3 3v5a2 2 0 0 0 2 2h1a2 2 0 0 0 4 0h8a2 2 0 0 0 4 0h1a2 2 0 0 0 2-2v-5a3 3 0 0 0-3-3zm-9.35-3.34A1 1 0 0 1 10.5 4h3a1 1 0 0 1 .9 1.45L15.68 8H8.32zM6 18a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm12 0a1 1 0 1 1 1-1 1 1 0 0 1-1 1zM20 15H4v-4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1z"/></svg>
                                    <span><?php echo $garage; ?></span>
                                </div>
                                <div class="spec-badge" title="Floor Size">
                                    <svg viewBox="0 0 24 24"><path d="M21 2H3a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zm-1 18H4V4h16zM6 6h12v2H6zm0 4h12v2H6zm0 4h8v2H6z"/></svg>
                                    <span><?php echo $floor_size > 0 ? intval($floor_size) . ' m²' : 'N/A'; ?></span>
                                </div>
                            </div>
                            
                            <div class="card-action-bar">
                                <a href="property_details.php?id=<?php echo $propertyId; ?>" class="card-view-btn">
                                    <span>Explore Details</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php
                }
            } else {
                echo '
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;" class="glass-panel reveal active">
                    <p style="font-size: 50px;">🔍</p>
                    <h4 style="font-size: 20px; font-weight:700; margin: 10px 0;">No properties match your search criteria</h4>
                    <p style="color: var(--text-muted); font-size:14px; margin-bottom: 20px;">Try adjusting your locations, price boundaries, or categories to view more listings.</p>
                    <a href="listing.php" class="btn-primary">Clear All Filters</a>
                </div>';
            }
            ?>
        </div>
        
        <!-- Elegant Skeleton Loader overlay (for dynamic effects) -->
        <div id="filterSkeletonLoader" style="display: none;" class="properties-grid">
            <?php for($i=0; $i<3; $i++): ?>
                <div class="skeleton-card">
                    <div class="skeleton-anim"></div>
                    <div class="skeleton-img"></div>
                    <div class="skeleton-line price"></div>
                    <div class="skeleton-line title"></div>
                    <div class="skeleton-line address"></div>
                    <div class="skeleton-line specs"></div>
                </div>
            <?php endfor; ?>
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
    <script>
        // Smooth skeleton filtering animation mock-up if filtering form is submitted
        const searchForm = document.getElementById('searchForm');
        const grid = document.getElementById('propertiesListContainer');
        const loader = document.getElementById('filterSkeletonLoader');

        if (searchForm && grid && loader) {
            searchForm.addEventListener('submit', function() {
                grid.style.display = 'none';
                loader.style.display = 'grid';
            });
        }
    </script>
</body>
</html>
