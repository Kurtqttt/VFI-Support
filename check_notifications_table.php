<?php
require_once 'includes/db.php';

try {
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Notifications table does not exist. Creating...\n";
        
        // Create notifications table
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
        echo "Notifications table created successfully!\n";
    } else {
        echo "Notifications table already exists.\n";
    }
    
    // Show table structure
    echo "\nNotifications table structure:\n";
    $stmt = $pdo->query("DESCRIBE notifications");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($structure as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Check current notification count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch();
    echo "\nCurrent notifications in database: {$count['count']}\n";
    
    // Show existing notifications if any
    if ($count['count'] > 0) {
        echo "\nExisting notifications:\n";
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notifications as $notif) {
            echo "- ID: {$notif['id']}, Role: {$notif['user_role']}, Type: {$notif['type']}, Title: {$notif['title']}, Read: {$notif['is_read']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>