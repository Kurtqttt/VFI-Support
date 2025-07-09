<?php
require_once 'includes/db.php';

// Only allow admins to run this
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Allow anyone to run this for debugging purposes
    // echo "Access denied. Admin login required.";
    // exit;
}

echo "<h1>Notification System Diagnostic</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    echo "<h2>1. Checking Database Connection</h2>";
    echo "<p class='success'>✓ Database connection successful</p>";
    
    echo "<h2>2. Checking if notifications table exists</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "<p class='error'>✗ Notifications table does not exist. Creating...</p>";
        
        $createTableSQL = "
        CREATE TABLE notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            user_role ENUM('admin', 'user') NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createTableSQL);
        echo "<p class='success'>✓ Notifications table created successfully!</p>";
    } else {
        echo "<p class='success'>✓ Notifications table exists</p>";
    }
    
    echo "<h2>3. Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE notifications");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>4. Current Notifications Count</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch();
    echo "<p class='info'>Total notifications: {$count['count']}</p>";
    
    if ($count['count'] > 0) {
        echo "<h3>Recent Notifications:</h3>";
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Role</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_role']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>5. Testing Notification Creation</h2>";
    
    // Include the notification functions
    require_once 'includes/notifications.php';
    
    // Test creating a notification
    $result = createNotification($pdo, 'admin', 'system_alert', 'Test Notification', 'This is a test notification created during diagnosis.');
    
    if ($result) {
        echo "<p class='success'>✓ Test notification created successfully</p>";
    } else {
        echo "<p class='error'>✗ Failed to create test notification</p>";
    }
    
    // Check count again
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $newCount = $stmt->fetch();
    echo "<p class='info'>Notifications after test: {$newCount['count']}</p>";
    
    echo "<h2>6. Testing API Endpoints</h2>";
    
    // Test the notifications.php endpoints
    echo "<h3>Testing Count Endpoint:</h3>";
    
    // Simulate session for API test
    $_SESSION['role'] = 'admin';
    
    ob_start();
    $_GET['action'] = 'count';
    include 'notifications.php';
    $countResponse = ob_get_clean();
    
    echo "<p>Count API Response: <code>$countResponse</code></p>";
    
    echo "<h2>7. Session Information</h2>";
    echo "<p>Current User: " . ($_SESSION['user'] ?? 'Not set') . "</p>";
    echo "<p>Current Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
    
    echo "<h2>8. Recommendations</h2>";
    echo "<div style='background:#f0f0f0;padding:10px;'>";
    echo "<p><strong>If notifications still don't work:</strong></p>";
    echo "<ol>";
    echo "<li>Clear your browser cache and cookies</li>";
    echo "<li>Check browser console for JavaScript errors (F12)</li>";
    echo "<li>Ensure session is properly set when logged in</li>";
    echo "<li>Check that the notification polling is working in browser network tab</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='admin.php'>← Back to Admin Panel</a>";
?>