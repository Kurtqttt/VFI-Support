<?php
$host = 'localhost';
$dbname = 'faq_system';
$user = 'root';
$pass = ''; // XAMPP default, leave empty unless you set a password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
