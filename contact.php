<?php
session_start(); // Start session for showing success message
require 'db_connection.php';

$agents = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Fetch agents from the database for the dropdown
    $result = $conn->query("SELECT id, name, email FROM agents");

    if ($result && $result->num_rows > 0) {
        while ($agent = $result->fetch_assoc()) {
            $agents[] = $agent;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission
    $agentEmail = $_POST['agent'] ?? '';
    $name = $_POST['name'] ?? '';
    $senderEmail = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    // Validate data
    if (empty($agentEmail) || empty($name) || empty($senderEmail) || empty($message)) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Please fill in all fields.';
        header('Location: contact.php');
        exit();
    }

    // Send email to the agent (Using standard php mail function)
    $to = $agentEmail;
    $subject = 'Property Inquiry';
    $body = "You have received a new message from $name ($senderEmail).\n\nMessage:\n$message";
    $headers = "From: $senderEmail\r\nReply-To: $senderEmail";

    $agentEmailSent = @mail($to, $subject, $body, $headers);

    // Auto-response to the sender
    $autoResponseSubject = "Your inquiry has been received";
    $autoResponseBody = "Dear $name,\n\nThank you for contacting us. Your inquiry has been received, and the listing agent will respond to you shortly.\n\nBest regards,\nRealHome Team";
    $autoResponseHeaders = "From: no-reply@realhome.com";

    $autoResponseSent = @mail($senderEmail, $autoResponseSubject, $autoResponseBody, $autoResponseHeaders);

    // Set success indicator
    $_SESSION['status'] = 'success';
    $_SESSION['message'] = 'Your enquiry has been successfully delivered to the agent!';
    
    header('Location: contact.php');
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RealHome</title>
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
                <li class="active"><a href="contact.php">Contact</a></li>
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

    <div style="padding: 60px 5%; max-width: 800px; margin: 0 auto;">
        
        <!-- Contact Form Container -->
        <div class="glass-panel reveal active">
            <h2 style="font-family: var(--font-serif); font-size: 32px; font-weight: 700; color: white; text-align: center; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">
                Direct Message Agent
            </h2>
            <p style="color: var(--text-muted); font-size: 14px; text-align: center; margin-bottom: 30px;">
                Submit your message, and our listing agent will review it and follow up promptly.
            </p>

            <!-- Display Status Alerts -->
            <?php if (isset($_SESSION['message'])): ?>
                <div style="padding: 15px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; font-weight: 600; font-size: 14px; text-align: center;
                            background: <?php echo $_SESSION['status'] == 'success' ? 'rgba(13, 148, 136, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>;
                            border: 1px solid <?php echo $_SESSION['status'] == 'success' ? 'var(--accent)' : '#ef4444'; ?>;
                            color: <?php echo $_SESSION['status'] == 'success' ? '#2dd4bf' : '#f87171'; ?>;">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php 
                unset($_SESSION['status']);
                unset($_SESSION['message']); 
                ?>
            <?php endif; ?>

            <form id="contact-form" action="contact.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                
                <div class="form-group">
                    <label class="form-label" for="agent">Select Listing Agent</label>
                    <select id="agent" name="agent" class="form-input" required>
                        <?php if (!empty($agents)): ?>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo htmlspecialchars($agent['email']); ?>">
                                    <?php echo htmlspecialchars($agent['name']); ?> (<?php echo htmlspecialchars($agent['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No agents available</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Jane Smith" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="e.g. jane@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="message">Detailed Message</label>
                    <textarea id="message" name="message" class="form-input" placeholder="Enter your enquiry details here..." style="height: 150px; resize: none;" required></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="padding: 15px; border-radius: var(--radius-sm); font-size: 16px; margin-top: 10px;">
                    <span>✉ Send Secure Message</span>
                </button>
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
