<?php
/**
 * Notification helper library.
 * This file is included from admin.php / user.php.
 */

if (!function_exists('createNotification')) {
    /**
     * Insert a notification into the DB.
     *
     * @param PDO    $pdo  PDO connection
     * @param string $role Target role: 'admin' or 'user'
     * @param string $type Type code used by frontend to pick icon
     * @param string $title Short title
     * @param string $message  Body text
     * @param int    $userId Optional user-specific notification (null for all of that role)
     */
    function createNotification(PDO $pdo, string $role, string $type, string $title, string $message, ?int $userId = null): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, user_role, type, title, message) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $role, $type, $title, $message]);
    }
}

if (!function_exists('notifyUsersOfNewFaq')) {
    /**
     * Shortcut to broadcast a new FAQ notification to all regular users.
     */
    function notifyUsersOfNewFaq(PDO $pdo, string $question, string $topic, string $status): void
    {
        $title   = 'New FAQ Added';
        $message = "Topic: {$topic} | Status: " . ucfirst($status);
        createNotification($pdo, 'user', 'new_faq', $title, $message);
    }
}
?>