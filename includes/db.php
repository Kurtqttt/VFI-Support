<?php
// Session configuration for longer sessions (ADD THIS SECTION)
ini_set('session.gc_maxlifetime', 7200); // 2 hours
ini_set('session.cookie_lifetime', 7200); // 2 hours
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);

// Set secure session parameters
session_set_cookie_params([
    'lifetime' => 7200, // 2 hours
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Your existing database connection (KEEP THIS AS IS)
$host = '127.0.0.1';  // Use IP instead of localhost to avoid socket issues
$port = 3307;         // Your custom MySQL port
$dbname = 'faq_system_v2'; // Replace with your actual DB name
$user = 'root';
$pass = ''; // Leave blank if you're using default XAMPP credentials
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>