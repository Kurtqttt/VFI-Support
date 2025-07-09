<?php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? null;
if (!$role) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'count':
        // count unread notifications for current role / user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_role = ? OR (user_id IS NOT NULL AND user_id = ?))");
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt->execute([$role, $userId]);
        $count = (int)$stmt->fetchColumn();
        echo json_encode(['count' => $count]);
        break;

    case 'get':
        // return latest 20 notifications for role
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_role = ? OR (user_id IS NOT NULL AND user_id = ?) ORDER BY created_at DESC LIMIT 20");
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt->execute([$role, $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // format time ago
        foreach ($rows as &$row) {
            $row['time_ago'] = timeAgo(strtotime($row['created_at']));
        }
        echo json_encode(['notifications' => $rows]);
        break;

    case 'mark_read':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_role = ? OR user_id = ?)");
            $stmt->execute([$id, $role, $_SESSION['user_id'] ?? 0]);
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'mark_all_read':
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_role = ? OR user_id = ?)");
        $stmt->execute([$role, $_SESSION['user_id'] ?? 0]);
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

/********************** Helper ************************/ 
function timeAgo($ts): string {
    $diff = time() - $ts;
    if ($diff < 60) return $diff . 's ago';
    $diff = floor($diff/60); // minutes
    if ($diff < 60) return $diff . 'm ago';
    $diff = floor($diff/60); // hours
    if ($diff < 24) return $diff . 'h ago';
    $diff = floor($diff/24); // days
    return $diff . 'd ago';
}
?>