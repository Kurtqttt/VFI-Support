<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$userRole = $_SESSION['role'];

switch ($action) {
    case 'get':
        // Get unread notifications for current user role
        $stmt = $pdo->prepare("
            SELECT id, type, title, message, created_at 
            FROM notifications 
            WHERE user_role = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userRole]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format timestamps
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = timeAgo($notification['created_at']);
        }
        
        echo json_encode(['notifications' => $notifications]);
        break;
        
    case 'mark_read':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_role = ?");
            $stmt->execute([$id, $userRole]);
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'mark_all_read':
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_role = ?");
        $stmt->execute([$userRole]);
        echo json_encode(['success' => true]);
        break;
        
    case 'count':
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_role = ? AND is_read = 0");
        $stmt->execute([$userRole]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['count' => $result['count']]);
        break;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
?>