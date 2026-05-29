<?php
$servername = "localhost";
$username = "ryuuxvii_Adnaan_Home";
$password = "Arise@10m";
$dbname = "ryuuxvii_Real_Home";

// Disable automatic mysqli exception throws for graceful fallbacks
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt connection 1: Primary production/local user config
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Attempt connection 2: Local development default fallback (Laragon / XAMPP standard root with empty password)
    $conn = @new mysqli("127.0.0.1", "root", "", $dbname);
    
    if ($conn->connect_error) {
        // Attempt connection 3: Fallback to the alternative database name 'real_estate' 
        $conn = @new mysqli("127.0.0.1", "root", "", "real_estate");
        
        if ($conn->connect_error) {
            // All connection attempts failed
            die("<h3>🚫 Database Connection Refused</h3>
                 <p>Please check the following:</p>
                 <ol>
                    <li>Ensure your local server environment (like <b>Laragon</b>) is open and you have clicked the <b>'Start All'</b> button.</li>
                    <li>Verify that MySQL is running on port 3306.</li>
                    <li>Ensure you have created the database (<code>ryuuxvii_Real_Home</code> or <code>real_estate</code>) and imported the SQL file.</li>
                 </ol>
                 <br>
                 <i>Technical Error details: " . $conn->connect_error . "</i>");
        }
    }
}

// Restore default mysqli error reporting behavior
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
