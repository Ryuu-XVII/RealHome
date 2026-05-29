<?php
// Include the database connection and session start
include('db_connection.php');
session_start();

// Fetch property listings from the database for display
$query = "SELECT * FROM properties ORDER BY created_at DESC LIMIT 6";
$result = $conn->query($query);

// Unified image parsing function to prevent database formatting mismatches
function getPropertyImages($images_field) {
    if (empty($images_field)) {
        return [];
    }
    // Try PHP unserialize first
    $images = @unserialize($images_field);
    if ($images !== false && is_array($images)) {
        return $images;
    }
    // Try JSON decode second
    $images = json_decode($images_field, true);
    if (is_array($images)) {
        return $images;
    }
    // Try split by comma
    if (strpos($images_field, ',') !== false) {
        return array_map('trim', explode(',', $images_field));
    }
    // Return single element array if it's a string
    return [$images_field];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealHome - Premium Real Estate Portal</title>
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
                <li class="active"><a href="index.php">Home</a></li>
                <li><a href="listing.php">Listings</a></li>
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

    <!-- Hero Showcase Section -->
    <section class="reveal active" style="padding: 100px 5% 80px; text-align: center; position: relative;">
        <!-- Large Background Image Element for Hero -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; opacity: 0.25; background: url('images/hero_house_bg.png') no-repeat center center; background-size: cover; filter: blur(4px);"></div>
        
        <h2 style="font-family: var(--font-serif); font-size: 56px; font-weight: 700; line-height: 1.2; margin-bottom: 15px; color: var(--text-primary);">
            Find Your <span style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Dream Haven</span>
        </h2>
        <p style="font-size: 20px; color: var(--text-secondary); max-width: 600px; margin: 0 auto 40px;">
            Discover luxury properties, contemporary apartments, and modern commercial developments in prime locations.
        </p>

        <!-- Glassmorphic Search Widget -->
        <div class="glass-panel" style="max-width: 900px; margin: 0 auto; padding: 25px;">
            <form action="listing.php" method="GET" style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group" style="margin-bottom: 0; text-align: left;">
                        <label class="form-label">Search Location</label>
                        <input type="text" name="addressSearch" class="form-input" placeholder="e.g., Cape Town, Sandton...">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0; text-align: left;">
                        <label class="form-label">Listing Type</label>
                        <select name="listingType" class="form-input">
                            <option value="">Any Type</option>
                            <option value="buy">For Sale</option>
                            <option value="rent">For Rent</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0; text-align: left;">
                        <label class="form-label">Property Type</label>
                        <select name="propertyType" class="form-input">
                            <option value="">Any Category</option>
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="townhouse">Townhouse</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" name="minPrice" class="form-input" placeholder="Min Price (ZAR)" style="width: 160px;">
                        <span style="color: var(--text-muted);">to</span>
                        <input type="number" name="maxPrice" class="form-input" placeholder="Max Price (ZAR)" style="width: 160px;">
                    </div>
                    
                    <button type="submit" class="btn-primary" style="height: 48px; min-width: 180px;">
                        <span>🔍 Search Properties</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Latest Properties Grid Section -->
    <section style="padding: 60px 5%;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;" class="reveal">
            <div>
                <h3 style="font-family: var(--font-serif); font-size: 36px; font-weight: 700; color: var(--text-primary);">
                    Featured Listings
                </h3>
                <p style="color: var(--text-muted); font-size: 16px;">
                    Hand-picked premium listings updated daily
                </p>
            </div>
            <a href="listing.php" class="btn-secondary">
                <span>View All Properties →</span>
            </a>
        </div>

        <div class="properties-grid">
            <?php
            if ($result && $result->num_rows > 0) {
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
                    
                    <!-- Property Card -->
                    <div class="real-property-card reveal" style="transition-delay: <?php echo $count * 0.1; ?>s;">
                        <div class="card-image-wrapper">
                            <img src="<?php echo $first_image; ?>" alt="Property Showcase">
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
                                    <!-- Bed SVG -->
                                    <svg viewBox="0 0 24 24"><path d="M7 13a3 3 0 1 1-3-3 3 3 0 0 1 3 3zm13-3a3 3 0 1 0 3 3 3 3 0 0 0-3-3zM2 17h20a1 1 0 0 0 1-1v-4a5 5 0 0 0-10 0 5 5 0 0 0-10 0v4a1 1 0 0 0 1 1zm18-7H4a2 2 0 0 0-2 2v6h20v-6a2 2 0 0 0-2-2z"/></svg>
                                    <span><?php echo $bedrooms; ?></span>
                                </div>
                                <div class="spec-badge" title="Bathrooms">
                                    <!-- Bath SVG -->
                                    <svg viewBox="0 0 24 24"><path d="M21 11H3a1 1 0 0 0-1 1v6a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1zm-1 7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-5h16zM7 7a3 3 0 1 0-3 3 3 3 0 0 0 3-3zm13-5h-2v2h-4V2h-2v2H7V2H5v2H3v2h18V2z"/></svg>
                                    <span><?php echo $bathrooms; ?></span>
                                </div>
                                <div class="spec-badge" title="Garage Capacity">
                                    <!-- Car SVG -->
                                    <svg viewBox="0 0 24 24"><path d="M19 8h-1.18l-1.63-3.26A3 3 0 0 0 13.5 3h-3a3 3 0 0 0-2.68 1.66L6.18 8H5a3 3 0 0 0-3 3v5a2 2 0 0 0 2 2h1a2 2 0 0 0 4 0h8a2 2 0 0 0 4 0h1a2 2 0 0 0 2-2v-5a3 3 0 0 0-3-3zm-9.35-3.34A1 1 0 0 1 10.5 4h3a1 1 0 0 1 .9 1.45L15.68 8H8.32zM6 18a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm12 0a1 1 0 1 1 1-1 1 1 0 0 1-1 1zM20 15H4v-4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1z"/></svg>
                                    <span><?php echo $garage; ?></span>
                                </div>
                                <div class="spec-badge" title="Floor Size">
                                    <!-- Size SVG -->
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
                echo '<p style="text-align: center; color: var(--text-muted); width: 100%; padding: 40px 0;">No properties available at the moment. Check back soon!</p>';
            }
            ?>
        </div>
    </section>

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
        // Log out user on beforeunload to keep sessions safe
        window.addEventListener('beforeunload', function () {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "logout.php", true);
            xhr.send();
        });
    </script>
</body>
</html>
