<?php
session_start();
require 'includes/db.php';

// Set a test user session
$_SESSION['user'] = 'test_user';
$_SESSION['role'] = 'user';

echo "<h2>Testing Notification System</h2>";

// Check if notifications table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Notifications table exists</p>";
        
        // Check current notifications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
        $result = $stmt->fetch();
        echo "<p>Current notifications: " . $result['count'] . "</p>";
        
        // Add a test notification if none exist
        if ($result['count'] == 0) {
            $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, user_role, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute(['new_faq', 'Test Notification', 'This is a test notification to verify the badge system is working.', 'user', 0]);
            echo "<p>✅ Added test notification</p>";
        }
        
        // Test the count API
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_role = ? AND is_read = 0");
        $stmt->execute(['user']);
        $result = $stmt->fetch();
        echo "<p>Unread notifications for user: " . $result['count'] . "</p>";
        
    } else {
        echo "<p>❌ Notifications table does not exist</p>";
        
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
        
        // Add a test notification
        $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, user_role, is_read, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['new_faq', 'Test Notification', 'This is a test notification to verify the badge system is working.', 'user', 0]);
        echo "<p>✅ Added test notification</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

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
?>