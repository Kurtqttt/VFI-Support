<?php
session_start();

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$response = array();

if (isset($_SESSION['user']) && isset($_SESSION['role'])) {
    $response['valid'] = true;
    $response['user'] = $_SESSION['user'];
    $response['role'] = $_SESSION['role'];
} else {
    $response['valid'] = false;
    $response['message'] = 'Session expired';
}

echo json_encode($response);
?>
3. Update your landing.php to be the default
<?php
// Prevent any session issues on landing page
session_start();

// If user is already logged in, redirect them
if (isset($_SESSION['user']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    } else {
        header("Location: user.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ System - Welcome</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="index-body">
    <div class="index-background">
        <div class="index-container">
            <div class="login-container">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h1 class="login-title">FAQ Knowledge Base</h1>
                    <p class="login-subtitle">Find answers to your questions</p>
                </div>
                
                <div class="login-form">
                    <a href="user.php" class="login-btn">
                        <i class="fas fa-search"></i>
                        <span>Browse FAQs</span>
                    </a>
                    <a href="login.php" class="login-btn" style="margin-top: 15px; background: linear-gradient(135deg, #000000, #374151);">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Login</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Background animation -->
        <div class="bg-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>
</body>
</html>