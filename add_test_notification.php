<?php
session_start();
require 'includes/db.php';

// Set a test user session if not already set
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'test_user';
    $_SESSION['role'] = 'user';
}

echo "<h2>Adding Test Notification</h2>";

try {
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        // Create the notifications table
        $sql = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            user_role VARCHAR(50) NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p>✅ Created notifications table</p>";
    }
    
    // Add a test notification
    $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, user_role, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['new_faq', 'Test Notification', 'This is a test notification to verify the badge system is working properly.', 'user', 0]);
    
    echo "<p>✅ Added test notification</p>";
    
    // Check current unread notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_role = ? AND is_read = 0");
    $stmt->execute(['user']);
    $result = $stmt->fetch();
    
    echo "<p>Current unread notifications: " . $result['count'] . "</p>";
    
    // Test the API endpoints
    echo "<h3>Testing API Endpoints:</h3>";
    
    // Test count endpoint
    $url = 'notifications.php?action=count';
    echo "<p>Testing: $url</p>";
    $response = file_get_contents($url);
    echo "<p>Response: $response</p>";
    
    // Test get endpoint
    $url = 'notifications.php?action=get';
    echo "<p>Testing: $url</p>";
    $response = file_get_contents($url);
    echo "<p>Response: $response</p>";
    
    echo "<p><a href='user.php'>Go to user page to test badge</a></p>";
    echo "<p><a href='test_badge.html'>Go to badge test page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>