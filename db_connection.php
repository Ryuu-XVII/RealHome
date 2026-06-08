<?php
// Simple .env parser for local development (Vercel will inject these automatically)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value, '"'), "'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Fetch Supabase PostgreSQL credentials from environment variables
$host = getenv('POSTGRES_HOST') ?: getenv('DB_HOST') ?: "127.0.0.1";
$user = getenv('POSTGRES_USER') ?: getenv('DB_USER') ?: "postgres";
$pass = getenv('POSTGRES_PASSWORD') ?: getenv('DB_PASS') ?: "";
$dbname = getenv('POSTGRES_DATABASE') ?: getenv('DB_NAME') ?: "postgres";
$port = getenv('DB_PORT') ?: "5432"; 

// If POSTGRES_URL is provided (like in Vercel), it's the safest way to get the IPv4 Pooler URL
$postgresUrl = getenv('POSTGRES_URL');
if ($postgresUrl) {
    $parsed = parse_url($postgresUrl);
    if ($parsed && isset($parsed['host'])) {
        $host = $parsed['host'];
        $port = $parsed['port'] ?? 5432;
        $user = $parsed['user'] ?? $user;
        $pass = $parsed['pass'] ?? $pass;
        $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $dbname;
    }
}

// ------------------------------------------------------------------
// PostgreSQL (PDO) to MySQLi compatibility shim
// This allows the rest of the app to use Postgres without a rewrite
// ------------------------------------------------------------------

class mysqli_result_shim {
    private $stmt;
    public $num_rows;
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->num_rows = $stmt->rowCount();
    }
    public function fetch_assoc() {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function fetch_array() {
        return $this->stmt->fetch(PDO::FETCH_BOTH);
    }
    public function fetch_all($mode = 1) { // 1 = MYSQLI_ASSOC in most standard setups
        // We'll just return ASSOC if mode is not specified since that's what's used
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class mysqli_stmt_shim {
    private $pdo_stmt;
    private $params = [];
    public $error = null;
    
    public function __construct($pdo_stmt) {
        $this->pdo_stmt = $pdo_stmt;
    }
    public function bind_param($types, &...$vars) {
        $this->params = &$vars;
    }
    public function execute() {
        try {
            $vals = [];
            foreach($this->params as $p) { $vals[] = $p; }
            $res = $this->pdo_stmt->execute($vals);
            return $res;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    public function get_result() {
        return new mysqli_result_shim($this->pdo_stmt);
    }
    public function close() {
        $this->pdo_stmt->closeCursor();
    }
}

class mysqli_shim {
    private $pdo;
    public $connect_error = null;
    public $error = null;

    public function __construct($host, $user, $pass, $dbname, $port) {
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            $this->connect_error = $e->getMessage();
        }
    }
    public function prepare($query) {
        try {
            $stmt = $this->pdo->prepare($query);
            return new mysqli_stmt_shim($stmt);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    public function query($query) {
        try {
            $stmt = $this->pdo->query($query);
            if ($stmt) {
                if (preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $query)) {
                    return true;
                }
                return new mysqli_result_shim($stmt);
            }
            return false;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    public function close() {
        $this->pdo = null;
    }
}

// Mock standard mysqli functions/constants
if (!function_exists('mysqli_report')) {
    function mysqli_report($flags) {}
}
if (!defined('MYSQLI_REPORT_OFF')) define('MYSQLI_REPORT_OFF', 0);
if (!defined('MYSQLI_REPORT_ERROR')) define('MYSQLI_REPORT_ERROR', 1);
if (!defined('MYSQLI_REPORT_STRICT')) define('MYSQLI_REPORT_STRICT', 2);

// Initialize the mock connection
$conn = new mysqli_shim($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("<h3>🚫 Database Connection Refused</h3>
         <p>Please check your Vercel Environment Variables or local .env file.</p>
         <br>
         <i>Technical Error details: " . $conn->connect_error . "</i>");
}
?>
