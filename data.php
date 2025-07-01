<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['faqs'])) {
    $_SESSION['faqs'] = [
        ['question' => 'How to reset password?', 'answer' => 'Click Forgot Password.'],
        ['question' => 'How to contact support?', 'answer' => 'Email support@example.com.']
    ];
}