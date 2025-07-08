<?php
function createNotification($pdo, $userRole, $type, $title, $message, $userId = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_role, type, title, message) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $userRole, $type, $title, $message]);
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

// Admin notification types
class NotificationType {
    const FAQ_ADDED = 'faq_added';
    const FAQ_UPDATED = 'faq_updated';
    const FAQ_DELETED = 'faq_deleted';
    const FAQ_RESTORED = 'faq_restored';
    const USER_CREATED = 'user_created';
    const USER_DELETED = 'user_deleted';
    const USER_UPDATED = 'user_updated';
    const SYSTEM_ALERT = 'system_alert';
}

// User notification types
class UserNotificationType {
    const NEW_FAQ = 'new_faq';
    const FAQ_UPDATED = 'faq_updated';
    const SYSTEM_ANNOUNCEMENT = 'system_announcement';
    const HELPFUL_TIP = 'helpful_tip';
    const MAINTENANCE_NOTICE = 'maintenance_notice';
    const NEW_FEATURE = 'new_feature';
    const GENERAL_UPDATE = 'general_update';
}

// Helper function to create user notifications when new FAQs are added
function notifyUsersOfNewFaq($pdo, $question, $topic, $status) {
    $title = "New FAQ Available!";
    $message = "Topic: {$topic} | New solution added for: " . substr($question, 0, 60) . "...";
    
    createNotification($pdo, 'user', UserNotificationType::NEW_FAQ, $title, $message);
}

// Helper function to create system announcements for users
function createSystemAnnouncement($pdo, $title, $message) {
    createNotification($pdo, 'user', UserNotificationType::SYSTEM_ANNOUNCEMENT, $title, $message);
}

// Helper function to create helpful tips for users
function createHelpfulTip($pdo, $title, $message) {
    createNotification($pdo, 'user', UserNotificationType::HELPFUL_TIP, $title, $message);
}