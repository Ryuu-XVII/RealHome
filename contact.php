<?php
session_start(); // Start session for showing success message
require 'db_connection.php';

$agents = [];
$messageSent = false; // Flag to check if the message was sent successfully

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Fetch agents from the database for the dropdown
    $result = $conn->query("SELECT id, name, email FROM agents");

    if ($result->num_rows > 0) {
        while ($agent = $result->fetch_assoc()) {
            $agents[] = $agent;
        }
    } else {
        $agents = [];
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission
    $agentEmail = $_POST['agent'] ?? '';
    $name = $_POST['name'] ?? '';
    $senderEmail = $_POST['email'] ?? ''; // The sender's email
    $message = $_POST['message'] ?? '';

    // Validate data
    if (empty($agentEmail) || empty($name) || empty($senderEmail) || empty($message)) {
        $_SESSION['message'] = 'Please fill in all fields.';
        header('Location: contact.php'); // Redirect back to the form
        exit();
    }

    // Send email to the agent
    $to = $agentEmail;
    $subject = 'Property Inquiry';
    $body = "You have received a new message from $name ($senderEmail).\n\nMessage:\n$message";
    $headers = "From: $senderEmail";

    $agentEmailSent = mail($to, $subject, $body, $headers); // Send email to the agent

    // Auto-response to the sender
    $autoResponseSubject = "Your query has been received";
    $autoResponseBody = "Dear $name,\n\nThank you for contacting us. Your query has been received, and the agent will respond to you shortly.\n\nBest regards,\nRealHome";
    $autoResponseHeaders = "From: no-reply@realhome.com"; // A no-reply email

    $autoResponseSent = mail($senderEmail, $autoResponseSubject, $autoResponseBody, $autoResponseHeaders); // Send auto-response to the sender

    // Check if both emails were sent successfully
    if ($agentEmailSent && $autoResponseSent) {
        $_SESSION['message'] = 'Message sent successfully.';
    } else {
        $_SESSION['message'] = 'Failed to send message.';
    }

    header('Location: contact.php'); // Redirect to avoid form resubmission
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
    <link rel="stylesheet" href="contact_style.css">
     <link rel="icon" type="image/x-icon" href="images/logo.jpeg">
    <style type="text/css">
        body {
            background: url(images/pic1.jpeg);
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f0f8ff;
            color: green;
        }
        .error {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f8d7da;
            color: red;
        }
        
        form button {
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
}

form button:hover {
    background-color: #007bff;
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

    <section id="contact">
        <h2 style="text-transform: uppercase">Contact Agent</h2>

        <!-- Display Success or Failure Message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo strpos($_SESSION['message'], 'successfully') !== false ? 'message' : 'error'; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); // Clear message after displaying ?>
        <?php endif; ?>

        <form id="contact-form" action="contact.php" method="POST">
            <label for="agent">Select Agent:</label>
            <select id="agent" name="agent" required>
                <?php
                if (!empty($agents)) {
                    foreach ($agents as $agent) {
                        echo '<option value="' . htmlspecialchars($agent['email']) . '">' . htmlspecialchars($agent['name']) . '</option>';
                    }
                } else {
                    echo '<option value="">No agents available</option>';
                }
                ?>
            </select>
            
            <label for="name">Name:</label>
            <input style="width: 378px" type="text" id="name" name="name" required>
            
            <label for="email">Email:</label>
            <input style="width: 378px" type="email" id="email" name="email" required>
            
            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>
            
            <button type="submit">Send Message</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>

    <script src="js/app.js"></script>
</body>
</html>
