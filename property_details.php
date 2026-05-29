<?php
require 'db_connection.php';
session_start();

// Get the property ID from the URL
$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($propertyId <= 0) {
    die("Invalid property ID.");
}

// Prepare the SQL query to get the property along with full agent contact details
$sql = "SELECT properties.*, agents.name AS agent_name, agents.photo AS agent_photo, agents.email AS agent_email, agents.phone AS agent_phone 
        FROM properties 
        JOIN agents ON properties.agent_username = agents.username 
        WHERE properties.id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
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

// Unified image parsing
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

$images = getPropertyImages($property['images']);
$first_image = !empty($images) ? htmlspecialchars($images[0]) : 'images/hero_house_bg.png';
$priceVal = floatval($property['price']);
$priceStr = 'R' . number_format($priceVal, 2);
$title = htmlspecialchars(ucfirst($property['property_type'])) . ' in ' . htmlspecialchars($property['address']);

$bedrooms = isset($property['bedrooms']) ? intval($property['bedrooms']) : 0;
$bathrooms = isset($property['bathrooms']) ? intval($property['bathrooms']) : 0;
$garage = isset($property['garage']) ? intval($property['garage']) : 0;
$floor_size = isset($property['floor_size']) ? floatval($property['floor_size']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['address']); ?> - Property Details - RealHome</title>
    
    <!-- Global Style Sheet -->
    <link rel="stylesheet" href="global.css">
    <link rel="icon" type="image/png" href="images/logo.png">

    <!-- Leaflet.js Maps CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        .details-container {
            max-width: 1200px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            padding: 0 5%;
        }

        .gallery-main-wrapper {
            position: relative;
            width: 100%;
            height: 480px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--glass-border);
            margin-bottom: 15px;
        }

        .gallery-main-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-thumbs-grid {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .gallery-thumb {
            width: 90px;
            height: 70px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: 2px solid transparent;
            opacity: 0.6;
            transition: var(--transition-fast);
        }

        .gallery-thumb.active, .gallery-thumb:hover {
            opacity: 1;
            border-color: var(--accent);
        }

        /* Detail features specs */
        .specs-ribbon {
            display: flex;
            gap: 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            padding: 15px 25px;
            border-radius: var(--radius-sm);
            margin: 25px 0;
            flex-wrap: wrap;
        }
        .specs-ribbon .spec-badge {
            font-size: 15px;
        }
        .specs-ribbon .spec-badge svg {
            width: 20px;
            height: 20px;
        }

        /* Leaflet map container */
        #propertyMockMap {
            height: 350px;
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            margin-top: 20px;
            z-index: 10;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .details-container {
                grid-template-columns: 1fr;
            }
            .gallery-main-wrapper {
                height: 350px;
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

    <div class="details-container">
        <!-- LEFT: Primary details and tools -->
        <div>
            <!-- Gallery Component -->
            <div class="reveal active">
                <div class="gallery-main-wrapper">
                    <img src="<?php echo $first_image; ?>" alt="Active Showcase Image" class="gallery-main-img" id="activeGalleryImg">
                </div>
                
                <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs-grid">
                        <?php foreach ($images as $index => $img): ?>
                            <img src="<?php echo htmlspecialchars($img); ?>" 
                                 alt="Thumbnail" 
                                 class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-src="<?php echo htmlspecialchars($img); ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details Block -->
            <div class="glass-panel reveal" style="margin-top: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 20px;">
                    <div>
                        <span class="card-label-tag tag-<?php echo strtolower($property['listing_type']) == 'rent' ? 'rent' : 'sale'; ?>" style="font-size: 13px; display: inline-block; margin-bottom: 10px;">
                            For <?php echo htmlspecialchars($property['listing_type'] == 'rent' ? 'Rent' : 'Sale'); ?>
                        </span>
                        <h2 style="font-family: var(--font-serif); font-size: 32px; font-weight: 700; color: white;">
                            <?php echo htmlspecialchars($property['address']); ?>
                        </h2>
                        <p style="color: var(--text-muted); font-size: 15px; margin-top: 5px;">
                            Modern <?php echo htmlspecialchars(ucfirst($property['property_type'])); ?> situated in a premium suburb
                        </p>
                    </div>

                    <div style="text-align: right;">
                        <div style="font-size: 32px; font-weight: 800; color: var(--accent);"><?php echo $priceStr; ?></div>
                        <button class="btn-secondary detail-wishlist-btn" 
                                data-id="<?php echo $propertyId; ?>" 
                                onclick="toggleWishlist('<?php echo $propertyId; ?>', '<?php echo addslashes($title); ?>', '<?php echo addslashes($priceStr); ?>', '<?php echo addslashes($first_image); ?>', '<?php echo addslashes($property['listing_type']); ?>', '<?php echo addslashes($property['address']); ?>')"
                                style="margin-top: 10px; padding: 8px 16px; font-size: 13px;">
                            🤍 Save to Favorites
                        </button>
                    </div>
                </div>

                <!-- Specs Ribbon -->
                <div class="specs-ribbon">
                    <div class="spec-badge" title="Bedrooms">
                        <svg viewBox="0 0 24 24"><path d="M7 13a3 3 0 1 1-3-3 3 3 0 0 1 3 3zm13-3a3 3 0 1 0 3 3 3 3 0 0 0-3-3zM2 17h20a1 1 0 0 0 1-1v-4a5 5 0 0 0-10 0 5 5 0 0 0-10 0v4a1 1 0 0 0 1 1zm18-7H4a2 2 0 0 0-2 2v6h20v-6a2 2 0 0 0-2-2z"/></svg>
                        <strong>Bedrooms:</strong> <span><?php echo $bedrooms; ?></span>
                    </div>
                    <div class="spec-badge" title="Bathrooms">
                        <svg viewBox="0 0 24 24"><path d="M21 11H3a1 1 0 0 0-1 1v6a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1zm-1 7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-5h16zM7 7a3 3 0 1 0-3 3 3 3 0 0 0 3-3zm13-5h-2v2h-4V2h-2v2H7V2H5v2H3v2h18V2z"/></svg>
                        <strong>Bathrooms:</strong> <span><?php echo $bathrooms; ?></span>
                    </div>
                    <div class="spec-badge" title="Garages">
                        <svg viewBox="0 0 24 24"><path d="M19 8h-1.18l-1.63-3.26A3 3 0 0 0 13.5 3h-3a3 3 0 0 0-2.68 1.66L6.18 8H5a3 3 0 0 0-3 3v5a2 2 0 0 0 2 2h1a2 2 0 0 0 4 0h8a2 2 0 0 0 4 0h1a2 2 0 0 0 2-2v-5a3 3 0 0 0-3-3zm-9.35-3.34A1 1 0 0 1 10.5 4h3a1 1 0 0 1 .9 1.45L15.68 8H8.32zM6 18a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm12 0a1 1 0 1 1 1-1 1 1 0 0 1-1 1zM20 15H4v-4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1z"/></svg>
                        <strong>Garage spaces:</strong> <span><?php echo $garage; ?></span>
                    </div>
                    <div class="spec-badge" title="Floor Size">
                        <svg viewBox="0 0 24 24"><path d="M21 2H3a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zm-1 18H4V4h16zM6 6h12v2H6zm0 4h12v2H6zm0 4h8v2H6z"/></svg>
                        <strong>Floor Area:</strong> <span><?php echo $floor_size > 0 ? $floor_size . ' m²' : 'N/A'; ?></span>
                    </div>
                </div>

                <h3 style="font-family: var(--font-serif); font-size: 22px; margin-bottom: 12px; color: white;">Property Description</h3>
                <p style="color: var(--text-secondary); font-size: 15px; margin-bottom: 25px;">
                    This beautifully presented <?php echo htmlspecialchars($property['property_type']); ?> offers architectural distinction and functional excellence. Situated at <?php echo htmlspecialchars($property['address']); ?>, the property boasts bright spacious living spaces, a designer kitchen, high ceilings, and beautiful outdoor zones. An ideal residence for those seeking premium standard living.
                </p>
            </div>

            <!-- Bond Repayments Calculator Block -->
            <div class="glass-panel reveal" style="margin-top: 25px;">
                <h3 style="font-family: var(--font-serif); font-size: 24px; font-weight:700; color: white; margin-bottom: 5px;">
                    📈 Repayment & Bond Calculator
                </h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">
                    Estimate your monthly mortgage payments in South African Rands (ZAR).
                </p>

                <!-- Hidden Input loading Price -->
                <input type="hidden" id="calcPrice" value="<?php echo $priceVal; ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Deposit (%)</label>
                        <input type="number" id="calcDeposit" class="form-input" min="0" max="100" value="10">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Interest Rate (%)</label>
                        <input type="number" id="calcInterest" class="form-input" step="0.25" min="1" max="30" value="11.75">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Loan Term (Years)</label>
                        <select id="calcTerm" class="form-input">
                            <option value="10">10 Years</option>
                            <option value="15">15 Years</option>
                            <option value="20" selected>20 Years</option>
                            <option value="30">30 Years</option>
                        </select>
                    </div>
                </div>

                <!-- Calculator outputs panel -->
                <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--glass-border); padding: 25px; border-radius: var(--radius-sm); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="text-align: center; border-right: 1px solid var(--glass-border);">
                        <p style="color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase;">Est. Monthly Payment</p>
                        <p id="calcMonthlyRepayment" style="font-size: 26px; font-weight: 800; color: var(--accent); margin-top: 5px;">R 0.00</p>
                    </div>
                    <div style="text-align: center; border-right: 1px solid var(--glass-border);">
                        <p style="color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase;">Min Salary Required</p>
                        <p id="calcMinSalary" style="font-size: 20px; font-weight: 700; color: white; margin-top: 5px;">R 0.00</p>
                    </div>
                    <div style="text-align: center;">
                        <p style="color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase;">Est. Transfer Duty</p>
                        <p id="calcTransferDuty" style="font-size: 20px; font-weight: 700; color: #3b82f6; margin-top: 5px;">R 0.00</p>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 15px; font-size: 12px; color: var(--text-muted);">
                    Total estimated bond cost: <span id="calcTotalPaid" style="font-weight:600; color: var(--text-secondary);">R 0.00</span> over selected term.
                </div>
            </div>

            <!-- Interactive Proximity Map using Leaflet.js -->
            <div class="glass-panel reveal" style="margin-top: 25px;">
                <h3 style="font-family: var(--font-serif); font-size: 24px; font-weight:700; color: white; margin-bottom: 5px;">
                    📍 Neighborhood Location Map
                </h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">
                    Explore proximity circles surrounding the <?php echo htmlspecialchars($property['address']); ?> location.
                </p>
                <div id="propertyMockMap"></div>
            </div>
        </div>

        <!-- RIGHT: Agent box and quick enquiry form -->
        <div>
            <div class="glass-panel reveal" style="position: sticky; top: 90px; padding: 25px;">
                <h3 style="font-family: var(--font-serif); font-size: 22px; font-weight:700; color: white; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    Listed Agent
                </h3>
                
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                    <img src="<?php echo htmlspecialchars($property['agent_photo']); ?>" 
                         alt="Agent Avatar" 
                         style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-full); border: 2px solid var(--accent); box-shadow: var(--accent-glow);">
                    <div>
                        <h4 style="font-size: 18px; font-weight: 700; color: white;"><?php echo htmlspecialchars($property['agent_name']); ?></h4>
                        <p style="color: var(--text-muted); font-size: 13px;">RealHome Premium Agent</p>
                        <p style="color: var(--accent); font-size: 13px; font-weight: 600; margin-top: 2px;"><?php echo htmlspecialchars($property['agent_phone']); ?></p>
                    </div>
                </div>

                <!-- Instant Enquiry Form -->
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px;">
                    Enquire About This Property
                </h4>
                
                <form id="detailsEnquiryForm" action="contact.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <!-- Pre-select the listing agent email -->
                    <input type="hidden" name="agent" value="<?php echo htmlspecialchars($property['agent_email']); ?>">
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 12px;">Your Name</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. John Doe">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 12px;">Email Address</label>
                        <input type="email" name="email" class="form-input" required placeholder="e.g. john@domain.com">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 12px;">Enquiry Message</label>
                        <textarea name="message" class="form-input" required style="height: 100px; resize: none; font-size: 14px;" placeholder="I'm interested in <?php echo $title; ?>. Please contact me with more information."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; border-radius: var(--radius-sm); padding: 12px;">
                        <span>✉ Send Enquiry</span>
                    </button>
                </form>
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

    <!-- Leaflet.js Interactive Mapping Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Set up custom Leaflet Map centered on coordinates close to South African centers (Simulating random geographic points near Cape Town or Joburg based on ID)
        const idOffset = <?php echo $propertyId; ?>;
        // Mock latitude and longitude surrounding Johannesburg (Sandton area)
        const mockLat = -26.1076 + (idOffset % 7) * 0.005;
        const mockLng = 28.0567 + (idOffset % 5) * 0.004;

        // Initialize Map
        const map = L.map('propertyMockMap').setView([mockLat, mockLng], 14);

        // Load Tile layers (CartoDB Dark Matter tiles match our gorgeous dark visual aesthetics perfectly!)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Custom Glowing Icon
        const marker = L.marker([mockLat, mockLng]).addTo(map);
        marker.bindPopup("<div style='color:#030712; font-family:sans-serif; font-size:12px;'><b>Approximate Area</b><br>Address: <?php echo addslashes(htmlspecialchars($property['address'])); ?></div>").openPopup();

        // Add a smooth Proximity circle around address (1km radius)
        L.circle([mockLat, mockLng], {
            color: '#0d9488',
            fillColor: '#0d9488',
            fillOpacity: 0.15,
            radius: 800
        }).addTo(map);
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
