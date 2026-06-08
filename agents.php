<?php
require 'db_connection.php'; // Include your database connection
session_start();

// Query to get all agents along with their active listings count dynamically!
$query = $conn->query("
    SELECT agents.*, COUNT(properties.id) AS listing_count 
    FROM agents 
    LEFT JOIN properties ON agents.username = properties.agent_username 
    GROUP BY agents.id
");

if ($query === false) {
    die("Database Error: " . $conn->error);
}

$agents = $query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meet Our Agents - RealHome</title>
    <link rel="stylesheet" href="global.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .agent-premium-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: 30px;
            text-align: center;
            position: relative;
            transition: var(--transition-slow);
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
        }

        .agent-premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--accent-gradient);
            opacity: 0;
            transition: var(--transition-normal);
        }

        .agent-premium-card:hover {
            transform: translateY(-8px);
            border-color: var(--glass-border-hover);
            box-shadow: var(--shadow-lg), 0 0 30px rgba(13, 148, 136, 0.05);
        }

        .agent-premium-card:hover::before {
            opacity: 1;
        }

        .agent-avatar-wrapper {
            position: relative;
            width: 130px;
            height: 130px;
            margin-bottom: 20px;
        }

        .agent-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-full);
            border: 3px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition-normal);
        }

        .agent-premium-card:hover .agent-avatar {
            border-color: var(--accent);
            box-shadow: var(--accent-glow);
            transform: scale(1.05);
        }

        .agent-badge {
            position: absolute;
            bottom: -5px;
            background: var(--accent-gradient);
            color: white;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        .agent-stats {
            display: flex;
            width: 100%;
            justify-content: center;
            gap: 15px;
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
            padding: 12px 0;
            margin: 20px 0;
        }

        .agent-stat-item {
            text-align: center;
        }

        .agent-stat-num {
            font-size: 18px;
            font-weight: 800;
            color: var(--accent);
        }

        .agent-stat-lbl {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .agent-shortcuts {
            display: flex;
            gap: 10px;
            width: 100%;
            margin-top: auto;
        }
        .agent-shortcuts a {
            flex: 1;
            padding: 10px;
            font-size: 13px;
            text-align: center;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }
        .btn-shortcut-email {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid var(--glass-border);
        }
        .btn-shortcut-email:hover {
            background: var(--glass-bg);
            border-color: var(--glass-border-hover);
        }
        .btn-shortcut-whatsapp {
            background: #25d366;
            color: white;
        }
        .btn-shortcut-whatsapp:hover {
            background: #128c7e;
            box-shadow: 0 0 15px rgba(37, 211, 102, 0.3);
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
                <li class="active"><a href="agents.php">Agents</a></li>
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

    <div style="padding: 60px 5%; max-width: 1200px; margin: 0 auto;">
        <!-- Top intro -->
        <div style="text-align: center; margin-bottom: 50px;" class="reveal active">
            <h2 style="font-family: var(--font-serif); font-size: 42px; font-weight: 700; color: white;">
                Meet Our Professional Agents
            </h2>
            <p style="color: var(--text-muted); font-size: 16px; max-width: 600px; margin: 10px auto 0;">
                Our certified premium agents are trained to help you discover, buy, sell, or manage properties with luxury service.
            </p>
        </div>

        <!-- Grid of Agents -->
        <div class="agents-grid">
            <?php 
            $count = 0;
            foreach ($agents as $agent): 
                $count++;
                $agent_photo = !empty($agent['photo']) ? htmlspecialchars($agent['photo']) : 'images/logo.png';
                ?>
                <div class="agent-premium-card reveal" style="transition-delay: <?php echo ($count % 3) * 0.1; ?>s;">
                    <div class="agent-avatar-wrapper">
                        <img src="<?php echo $agent_photo; ?>" alt="Profile Picture" class="agent-avatar">
                        <span class="agent-badge">Partner Agent</span>
                    </div>

                    <h3 style="font-size: 20px; font-weight: 700; color: white; margin-bottom: 4px;"><?php echo htmlspecialchars($agent['name']); ?></h3>
                    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 15px;">@<?php echo htmlspecialchars($agent['username']); ?></p>

                    <!-- Stats Ribbon -->
                    <div class="agent-stats">
                        <div class="agent-stat-item" style="border-right:1px solid var(--glass-border); padding-right: 20px;">
                            <div class="agent-stat-num"><?php echo intval($agent['listing_count']); ?></div>
                            <div class="agent-stat-lbl">Listings</div>
                        </div>
                        <div class="agent-stat-item">
                            <div class="agent-stat-num">★★★★★</div>
                            <div class="agent-stat-lbl">Rating</div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div style="text-align: left; width: 100%; margin-bottom: 20px; font-size: 14px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 8px;">
                        <div>✉ <?php echo htmlspecialchars($agent['email']); ?></div>
                        <div>📞 <?php echo htmlspecialchars($agent['phone']); ?></div>
                    </div>

                    <!-- Direct Shortcuts -->
                    <div class="agent-shortcuts">
                        <a href="mailto:<?php echo htmlspecialchars($agent['email']); ?>?subject=RealHome Property Inquiry" class="btn-shortcut-email">
                            Email
                        </a>
                        <!-- Clean Whatsapp simulated link using standard international formats -->
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $agent['phone']); ?>?text=Hi%20<?php echo urlencode($agent['name']); ?>,%20I'm%20inquiring%20about%20a%20property%20on%20RealHome." 
                           target="_blank" 
                           class="btn-shortcut-whatsapp">
                            WhatsApp
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
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
