<?php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refresh') {
    if (isset($_SESSION['user']) && isset($_SESSION['role'])) {
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(false);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Session refreshed',
            'last_activity' => $_SESSION['last_activity']
        ]);
    } else {
        session_unset();
        session_destroy();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Session invalid'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request'
    ]);
}
?>