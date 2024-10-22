<?php
require 'db_connection.php'; // Include your database connection

// Query to get all agents from the database
$query = $conn->query("SELECT * FROM agents");
$agents = $query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealHome Agents</title>
    <link rel="stylesheet" href="agents_style.css">
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
/* Agents container styling */
.agents-container {
    background-color: rgba(0, 0, 0, 0.4); /* Semi-transparent background */
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin: 20px auto;
    width: 90%;
}

/* Heading style */
h2 {
    text-align: center;
    color: white;
    margin-top: 0;
}

/* Agents list grid */
#agentsList {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

/* Individual agent card */
.agent {
    background-color: rgba(0, 0, 0, 0.6);
    border: 4px solid transparent;
    border-radius: 8px;
    padding: 20px;
    width: calc(33.33% - 20px); /* Three columns */
    box-sizing: border-box;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    color: white;
}

.agent:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
}

/* Agent profile picture */
.agent-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Agent information */
.agent p {
    margin: 5px 0;
    color: white;
}

.agent p:first-of-type {
    font-weight: bold;
    color: white;
}

/* Footer styles */
footer {
    background-color: #333;
    color: white;
    text-align: center;
    padding: 10px 0;
    margin-top: auto; /* Makes sure footer stays at the bottom */
}

footer p {
    margin: 0;
}

/* Media query for responsive design */
@media screen and (max-width: 768px) {
    .agent {
        width: calc(50% - 20px); /* Two columns on smaller screens */
    }
}

@media screen and (max-width: 480px) {
    .agent {
        width: 100%; /* Full width on very small screens */
    }
}

   
    </style>
</head>
<body>
    <!-- Header with navigation menu -->
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

    <!-- Main content: Display agents -->
    <div class="agents-container">
        <h2 style="text-transform: uppercase">Our Agents</h2>
        <div id="agentsList">
            <?php foreach ($agents as $agent): ?>
                <div class="agent">
                    <img src="<?php echo $agent['photo']; ?>" alt="Profile Picture" class="agent-photo">
                    <p>Name: <?php echo $agent['name']; ?></p>
                    <p>Username: <?php echo $agent['username']; ?></p>
                    <p>Email: <?php echo $agent['email']; ?></p>
                    <p>Phone: <?php echo $agent['phone']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer section -->
    <footer>
        <p>&copy; 2024 RealHome. All rights reserved.</p>
    </footer>
</body>
</html>
