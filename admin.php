<?php
// Session configuration for longer sessions (MUST BE BEFORE session_start())
ini_set('session.gc_maxlifetime', 7200); // 2 hours
ini_set('session.cookie_lifetime', 7200); // 2 hours
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);

// Set secure session parameters
session_set_cookie_params([
    'lifetime' => 7200, // 2 hours
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once 'includes/db.php';

// Update last activity time on every page load
$_SESSION['last_activity'] = time();

require_once 'includes/notifications.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Set user_id if not already set (needed for notifications)
if (!isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $userStmt->execute([$_SESSION['user']]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
        $_SESSION['user_id'] = $userData['id'];
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $id = $_POST['update_user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $hashedPassword, $role, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $id]);
    }
}

// Include notification functions
require_once 'includes/notifications.php';

// Fetch all users (only once)
$userStmt = $pdo->query("SELECT * FROM users");
$allUsers = $userStmt->fetchAll(PDO::FETCH_ASSOC);


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadPath = 'uploads/';

    // CREATE user
    if ($_POST['action'] === 'create_user') {
        $newUsername = $_POST['new_username'];
        $newEmail = $_POST['new_email'];
        $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $newRole = $_POST['new_role'] ?? 'user';

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$newUsername, $newEmail, $newPassword, $newRole]);

        header("Location: admin.php?user_created=1");
        exit;
    }

   // UPDATE user
    if ($_POST['action'] === 'update_user') {
        $id = $_POST['update_user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $password = $_POST['password'];

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $id]);
        }

        header("Location: admin.php?user_updated=1");
        exit;
    }

    // DELETE user
    if ($_POST['action'] === 'delete_user') {
        $userId = $_POST['user_id'];
        
        // Get all users to find current user's ID
        $currentUserStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $currentUserStmt->execute([$_SESSION['user']]);
        $currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
        $currentUserId = $currentUser ? $currentUser['id'] : null;
        
        // Prevent deletion of the current logged-in user
        if ($userId && $userId != $currentUserId) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
        }
        
        header("Location: admin.php?user_deleted=1");
        exit;
    }

    if ($_POST['action'] === 'update_admin_faq') {
        $id = $_POST['id'];
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $s = $_POST['status'];
        $topic = $_POST['topic'];

        // Handle file upload for admin FAQ updates
        $updateFilename = '';
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['attachment']['name'], PATHINFO_FILENAME));
            $uniqueName = uniqid() . "_" . $safeName . '.' . $ext;
            $targetPath = $uploadPath . $uniqueName;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $updateFilename = $targetPath;
            }
        }
        
        // Check if user wants to remove existing attachment
        $removeExistingAttachment = isset($_POST['remove_existing_attachment']) && $_POST['remove_existing_attachment'] == '1';
        
        if ($removeExistingAttachment && !$updateFilename) {
            // Remove existing attachment only
            $stmt = $pdo->prepare("UPDATE admin_faqs SET question = ?, answer = ?, status = ?, topic = ?, attachment = NULL WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $id]);
        } elseif ($updateFilename) {
            // With new attachment (replaces existing if any)
            $stmt = $pdo->prepare("UPDATE admin_faqs SET question = ?, answer = ?, status = ?, topic = ?, attachment = ? WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $updateFilename, $id]);
        } else {
            // Without new attachment and not removing existing
            $stmt = $pdo->prepare("UPDATE admin_faqs SET question = ?, answer = ?, status = ?, topic = ? WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $id]);
        }

        header("Location: admin.php");
        exit;
    }

    if ($_POST['action'] === 'delete_admin_faq') {
        $id = $_POST['id'];

        // Get the current admin FAQ
        $stmt = $pdo->prepare("SELECT * FROM admin_faqs WHERE id = ?");
        $stmt->execute([$id]);
        $faq = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($faq) {
            $deletedBy = $_SESSION['user'] ?? 'admin';

            // Insert into deleted_faqs table
            $origin = isset($faq['is_admin']) && $faq['is_admin'] ? 'admin_faqs' : 'faqs';

            $origin = 'admin_faqs';

            $insertStmt = $pdo->prepare("
                INSERT INTO deleted_faqs (original_id, question, answer, status, topic, attachment, deleted_by, origin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $faq['id'],
                $faq['question'],
                $faq['answer'],
                $faq['status'],
                $faq['topic'],
                $faq['attachment'] ?? '',
                $deletedBy,
                $origin
            ]);

            // Delete from admin_faqs
            $deleteStmt = $pdo->prepare("DELETE FROM admin_faqs WHERE id = ?");
            $deleteStmt->execute([$id]);
        }
    }

    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Handle File Upload
    $filenames = [];

    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($name, PATHINFO_FILENAME));
                $uniqueName = uniqid() . "_" . $safeName . '.' . $ext;
                $targetPath = $uploadPath . $uniqueName;

                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $targetPath)) {
                    $filenames[] = $targetPath;
                }
            }
        }
    }

    $attachment = json_encode($filenames);
    $filename = !empty($filenames) ? $filenames[0] : '';

    if ($_POST['action'] === 'add') {
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $status = $_POST['status'] ?? 'not resolved';
        $topic = $_POST['topic'] ?? 'Others';
        $audience = $_POST['target_audience'] ?? 'user';

        if ($audience === 'admin') {
            $stmt = $pdo->prepare("INSERT INTO admin_faqs (question, answer, status, topic, attachment) VALUES (?, ?, ?, ?, ?)");
        } else {
            $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, status, topic, attachment, visibility) VALUES (?, ?, ?, ?, ?, 'user')");
        }

        $stmt->execute([$q, $a, $status, $topic, $filename]);

        // Create notifications
        $audienceText = ($audience === 'admin') ? 'Admin FAQ' : 'User FAQ';
        $notificationTitle = "New FAQ Added";
        $notificationMessage = "Topic: {$topic} | Type: {$audienceText} | Status: " . ucfirst($status);
        createNotification($pdo, 'admin', 'faq_added', $notificationTitle, $notificationMessage);
        
        // USER NOTIFICATION (only for user FAQs)
        if ($audience === 'user') {
            notifyUsersOfNewFaq($pdo, $q, $topic, $status);
        }
    }

    if ($_POST['action'] === 'update') {
        $id = $_POST['id'];
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $s = $_POST['status'] ?? 'not resolved';
        $topic = $_POST['topic'] ?? 'Others';
        
        // Handle file upload for updates (single file only)
        $updateFilename = '';
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['attachment']['name'], PATHINFO_FILENAME));
            $uniqueName = uniqid() . "_" . $safeName . '.' . $ext;
            $targetPath = $uploadPath . $uniqueName;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $updateFilename = $targetPath;
            }
        }
        
        // Check if user wants to remove existing attachment
        $removeExistingAttachment = isset($_POST['remove_existing_attachment']) && $_POST['remove_existing_attachment'] == '1';
        
        if ($removeExistingAttachment && !$updateFilename) {
            // Remove existing attachment only
            $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, status = ?, topic = ?, attachment = NULL WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $id]);
        } elseif ($updateFilename) {
            // With new attachment (replaces existing if any)
            $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, status = ?, topic = ?, attachment = ? WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $updateFilename, $id]);
        } else {
            // Without new attachment and not removing existing
            $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, status = ?, topic = ? WHERE id = ?");
            $stmt->execute([$q, $a, $s, $topic, $id]);
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = $_POST['id'];

        // Get current FAQ
        $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
        $stmt->execute([$id]);
        $faq = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($faq) {
            // Insert into deleted_faqs
            $deletedBy = $_SESSION['user'] ?? 'admin';
            $insertStmt = $pdo->prepare("
                INSERT INTO deleted_faqs (original_id, question, answer, status, topic, attachment, deleted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $faq['id'],
                $faq['question'],
                $faq['answer'],
                $faq['status'],
                $faq['topic'],
                $faq['attachment'] ?? '',
                $deletedBy
            ]);

            // Delete from faqs
            $deleteStmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
            $deleteStmt->execute([$id]);
        }
    }

    if ($_POST['action'] === 'restore') {
        $id = $_POST['id'];

        // Get deleted FAQ from deleted_faqs
        $stmt = $pdo->prepare("SELECT * FROM deleted_faqs WHERE id = ?");
        $stmt->execute([$id]);
        $faq = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($faq) {
            // Check origin field to decide where to restore
            if ($faq['origin'] === 'admin_faqs') {
                $insert = $pdo->prepare("INSERT INTO admin_faqs (question, answer, status, topic, attachment) VALUES (?, ?, ?, ?, ?)");
            } else {
                $insert = $pdo->prepare("INSERT INTO faqs (question, answer, status, topic, attachment) VALUES (?, ?, ?, ?, ?)");
            }

            $insert->execute([
                $faq['question'],
                $faq['answer'],
                $faq['status'],
                $faq['topic'],
                $faq['attachment']
            ]);

            // Remove from deleted_faqs
            $delete = $pdo->prepare("DELETE FROM deleted_faqs WHERE id = ?");
            $delete->execute([$id]);
        }
    }

    header("Location: admin.php");
    exit;
}

// Load all FAQs
$stmt = $pdo->query("SELECT * FROM faqs ORDER BY id DESC");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load Admin FAQs
$adminFaqs = $pdo->query("SELECT * FROM admin_faqs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Count user stats
$totalUsers = count($allUsers);
$adminCount = 0;
$userCount = 0;

foreach ($allUsers as $user) {
    if ($user['role'] === 'admin') {
        $adminCount++;
    } else {
        $userCount++;
    }
}

// Count FAQ stats (including Admin FAQs)
$totalFaqs = count($faqs) + count($adminFaqs);
$resolvedCount = 0;
$unsolvedCount = 0;

// Count regular FAQs
foreach ($faqs as $faq) {
    if ($faq['status'] === 'resolved') {
        $resolvedCount++;
    } else {
        $unsolvedCount++;
    }
}

// Count Admin FAQs
foreach ($adminFaqs as $faq) {
    if ($faq['status'] === 'resolved') {
        $resolvedCount++;
    } else {
        $unsolvedCount++;
    }
}

// Static deleted FAQs data (as requested)
$deletedStmt = $pdo->query("SELECT * FROM deleted_faqs ORDER BY deleted_date DESC");
$deletedFaqs = $deletedStmt->fetchAll(PDO::FETCH_ASSOC);

$monthlyQuery = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'not resolved' THEN 1 ELSE 0 END) as unsolved
    FROM faqs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
";

try {
    $monthlyStmt = $pdo->query($monthlyQuery);
    $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If created_at column doesn't exist, use empty array
    $monthlyStats = [];
}

// Calculate percentages
$resolvedPercentage = $totalFaqs > 0 ? round(($resolvedCount / $totalFaqs) * 100, 1) : 0;
$unsolvedPercentage = $totalFaqs > 0 ? round(($unsolvedCount / $totalFaqs) * 100, 1) : 0;

// Prepare data for JavaScript
$chartData = [
    'total' => $totalFaqs,
    'resolved' => $resolvedCount,
    'unsolved' => $unsolvedCount,
    'resolvedPercentage' => $resolvedPercentage,
    'unsolvedPercentage' => $unsolvedPercentage,
    'totalUsers' => $totalUsers,
    'adminCount' => $adminCount,
    'userCount' => $userCount,
    'monthly' => array_reverse($monthlyStats)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FAQ Management System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
    <!-- Animated Background -->
    <div class="admin-background">
        <div class="bg-animation">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
            <div class="floating-orb orb-3"></div>
            <div class="floating-orb orb-4"></div>
            <div class="floating-orb orb-5"></div>
        </div>

        <!-- Header Section -->
        <header class="admin-header">
            <div class="admin-header-content">
                <div class="admin-welcome">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                        <div class="avatar-status"></div>
                    </div>
                    <div class="admin-info">
                        <h1 class="admin-title">Welcome back, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
                        <p class="admin-subtitle">Manage your FAQ content with ease</p>
                        <div class="admin-meta">
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <?= date('l, F j, Y') ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-shield-alt"></i>
                                Administrator
                            </span>
                        </div>
                    </div>
                </div>
                <div class="admin-actions">
                    <!-- Notification Bell -->
                    <div class="notification-bell" id="notificationBell" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        
                        <!-- Notification Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4><i class="fas fa-bell"></i> Notifications</h4>
                                <button class="mark-all-read" onclick="markAllAsRead()">
                                    <i class="fas fa-check-double"></i> Mark all read
                                </button>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <button type="button" class="stats-btn" onclick="showStats()">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </button>

                    <form method="get" action="index.php" style="display: inline;">
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-container">
            <!-- Quick Stats Dashboard -->
            <section class="stats-dashboard">
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $totalFaqs ?></h3>
                            <p class="stat-label">Total FAQs</p>
                            <span class="stat-sublabel">Regular + Admin</span>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $resolvedCount ?></h3>
                            <p class="stat-label">Resolved</p>
                            <span class="stat-percentage"><?= $resolvedPercentage ?>%</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?= $resolvedPercentage ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $unsolvedCount ?></h3>
                            <p class="stat-label">Pending</p>
                            <span class="stat-percentage"><?= $unsolvedPercentage ?>%</span>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="width: <?= $unsolvedPercentage ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?= $totalUsers ?></h3>
                            <p class="stat-label">Total Users</p>
                            <span class="stat-sublabel"><?= $adminCount ?> Admins</span>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add New FAQ Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Create New FAQ
                        </h2>
                        <p class="card-description">Add a new frequently asked question to your knowledge base</p>
                    </div>
                </div>
                <div class="card-content">
                    <form method="post" class="modern-form" id="addFaqForm" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="question" class="form-label">
                                    <i class="fas fa-question-circle"></i>
                                    Question/Concern
                                </label>
                                <input type="text" name="question" id="question" placeholder="Enter your question or concern..." class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="answer" class="form-label">
                                    <i class="fas fa-comment-dots"></i>
                                    Answer/Solution
                                </label>
                                <textarea name="answer" id="answer" placeholder="Provide detailed answer or solution..." class="form-textarea" required></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status" class="form-label">
                                    <i class="fas fa-check-circle"></i>
                                    Status
                                </label>
                                <select name="status" class="form-select" required>
                                    <option value="resolved">✅ Resolved</option>
                                    <option value="not resolved" selected>⏳ Pending</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="target_audience" class="form-label">
                                    <i class="fas fa-users"></i>
                                    Target Audience
                                </label>
                                <select name="target_audience" id="target_audience" class="form-select" required>
                                    <option value="user" selected>👥 Users</option>
                                    <option value="admin">👨‍💼 Administrators</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="topic" class="form-label">
                                    <i class="fas fa-tags"></i>
                                    Category
                                </label>
                                <select name="topic" id="topic" class="form-select" required>
                                    <option value="Account Issues">🔐 Account Issues</option>
                                    <option value="Email Support">📧 Email Support</option>
                                    <option value="Printer & Scanner">🖨️ Printer & Scanner</option>
                                    <option value="Network & Internet">🌐 Network & Internet</option>
                                    <option value="Hardware Problems">💻 Hardware Problems</option>
                                    <option value="Software Installation">💿 Software Installation</option>
                                    <option value="System Access">🔑 System Access</option>
                                    <option value="Company Applications">📱 Company Applications</option>
                                    <option value="Password & Login">🔒 Password & Login</option>
                                    <option value="Security & Policy">🛡️ Security & Policy</option>
                                    <option value="Forms & Requests">📋 Forms & Requests</option>
                                    <option value="IT Procedures">⚙️ IT Procedures</option>
                                    <option value="Remote Access">🔗 Remote Access</option>
                                    <option value="Backup & Recovery">💾 Backup & Recovery</option>
                                    <option value="User Guides">📖 User Guides</option>
                                    <option value="Troubleshooting">🔧 Troubleshooting</option>
                                    <option value="Device Setup">📱 Device Setup</option>
                                    <option value="File Sharing & Drives">📁 File Sharing & Drives</option>
                                    <option value="IT Announcements">📢 IT Announcements</option>
                                    <option value="Others">📌 Others</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-paperclip"></i>
                                    Attachments
                                </label>
                                <div class="file-upload-area">
                                    <input type="file" name="attachments[]" class="file-input" multiple id="faqAttachments">
                                    <div class="file-upload-content">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Drop files here or click to browse</p>
                                        <span>Supports images, documents, and more</span>
                                    </div>
                                </div>
                                <div id="filePreviewList" class="file-preview-list"></div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="action" value="add">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                <span>Create FAQ</span>
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                                <span>Reset Form</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Create New User Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-user-plus"></i>
                            User Management
                        </h2>
                        <p class="card-description">Create and manage user accounts</p>
                    </div>
                </div>
                <div class="card-content">
                    <form method="post" class="modern-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Username
                                </label>
                                <input type="text" name="new_username" class="form-input" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" name="new_email" class="form-input" placeholder="Enter email address" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <input type="password" name="new_password" class="form-input" placeholder="Enter secure password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tag"></i>
                                    Role
                                </label>
                                <select name="new_role" class="form-select">
                                    <option value="user">👤 User</option>
                                    <option value="admin">👨‍💼 Administrator</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="action" value="create_user">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i>
                                <span>Create User</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Manage FAQs Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-cogs"></i>
                            FAQ Management
                            <span class="item-count"><?= count($faqs) ?> items</span>
                        </h2>
                        <p class="card-description">Manage and organize your FAQ content</p>
                    </div>
                    <div class="header-controls">
                        <div class="search-controls">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="adminSearch" placeholder="Search FAQs..." class="search-input">
                            </div>
                            <select id="adminStatusFilter" class="filter-select" onchange="filterAdminFaqs()">
                                <option value="all">All Status</option>
                                <option value="resolved">Resolved</option>
                                <option value="not resolved">Pending</option>
                            </select>
                            <select id="adminTopicFilter" class="filter-select" onchange="filterFaqs()">
                                <option value="all">All Categories</option>
                                <option value="Account Issues">Account Issues</option>
                                <option value="Email Support">Email Support</option>
                                <option value="Printer & Scanner">Printer & Scanner</option>
                                <option value="Network & Internet">Network & Internet</option>
                                <option value="Hardware Problems">Hardware Problems</option>
                                <option value="Software Installation">Software Installation</option>
                                <option value="System Access">System Access</option>
                                <option value="Company Applications">Company Applications</option>
                                <option value="Password & Login">Password & Login</option>
                                <option value="Security & Policy">Security & Policy</option>
                                <option value="Forms & Requests">Forms & Requests</option>
                                <option value="IT Procedures">IT Procedures</option>
                                <option value="Remote Access">Remote Access</option>
                                <option value="Backup & Recovery">Backup & Recovery</option>
                                <option value="User Guides">User Guides</option>
                                <option value="Troubleshooting">Troubleshooting</option>
                                <option value="Device Setup">Device Setup</option>
                                <option value="File Sharing & Drives">File Sharing & Drives</option>
                                <option value="IT Announcements">IT Announcements</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="faq-grid" id="adminFaqList">
                        <?php if (empty($faqs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3>No FAQs Found</h3>
                                <p>Start by creating your first FAQ using the form above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($faqs as $index => $faq): ?>
                                <div class="faq-card" data-index="<?= $index ?>" data-topic="<?= htmlspecialchars($faq['topic']) ?>">
                                    <div class="faq-card-header">
                                        <div class="faq-meta">
                                            <span class="faq-id">#<?= $faq['id'] ?></span>
                                            <span class="status-badge <?= $faq['status'] === 'resolved' ? 'resolved' : 'pending' ?>">
                                                <i class="fas <?= $faq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                <?= $faq['status'] === 'resolved' ? 'Resolved' : 'Pending' ?>
                                            </span>
                                        </div>
                                        <div class="faq-actions">
                                            <button type="button" class="action-btn edit" onclick="toggleEdit(<?= $index ?>)" title="Edit FAQ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn delete" onclick="confirmDelete(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>')" title="Delete FAQ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <form method="post" class="faq-form" id="faqForm<?= $index ?>" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Question</label>
                                            <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>" class="form-input" readonly required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Answer</label>
                                            <textarea name="answer" class="form-textarea" readonly required><?= htmlspecialchars($faq['answer']) ?></textarea>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required readonly>
                                                    <option value="resolved" <?= $faq['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                    <option value="not resolved" <?= $faq['status'] === 'not resolved' ? 'selected' : '' ?>>Pending</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Category</label>
                                                <select name="topic" class="form-select" required readonly>
                                                    <option value="Account Issues" <?= $faq['topic'] === 'Account Issues' ? 'selected' : '' ?>>Account Issues</option>
                                                    <option value="Email Support" <?= $faq['topic'] === 'Email Support' ? 'selected' : '' ?>>Email Support</option>
                                                    <option value="Printer & Scanner" <?= $faq['topic'] === 'Printer & Scanner' ? 'selected' : '' ?>>Printer & Scanner</option>
                                                    <option value="Network & Internet" <?= $faq['topic'] === 'Network & Internet' ? 'selected' : '' ?>>Network & Internet</option>
                                                    <option value="Hardware Problems" <?= $faq['topic'] === 'Hardware Problems' ? 'selected' : '' ?>>Hardware Problems</option>
                                                    <option value="Software Installation" <?= $faq['topic'] === 'Software Installation' ? 'selected' : '' ?>>Software Installation</option>
                                                    <option value="System Access" <?= $faq['topic'] === 'System Access' ? 'selected' : '' ?>>System Access</option>
                                                    <option value="Company Applications" <?= $faq['topic'] === 'Company Applications' ? 'selected' : '' ?>>Company Applications</option>
                                                    <option value="Password & Login" <?= $faq['topic'] === 'Password & Login' ? 'selected' : '' ?>>Password & Login</option>
                                                    <option value="Security & Policy" <?= $faq['topic'] === 'Security & Policy' ? 'selected' : '' ?>>Security & Policy</option>
                                                    <option value="Forms & Requests" <?= $faq['topic'] === 'Forms & Requests' ? 'selected' : '' ?>>Forms & Requests</option>
                                                    <option value="IT Procedures" <?= $faq['topic'] === 'IT Procedures' ? 'selected' : '' ?>>IT Procedures</option>
                                                    <option value="Remote Access" <?= $faq['topic'] === 'Remote Access' ? 'selected' : '' ?>>Remote Access</option>
                                                    <option value="Backup & Recovery" <?= $faq['topic'] === 'Backup & Recovery' ? 'selected' : '' ?>>Backup & Recovery</option>
                                                    <option value="User Guides" <?= $faq['topic'] === 'User Guides' ? 'selected' : '' ?>>User Guides</option>
                                                    <option value="Troubleshooting" <?= $faq['topic'] === 'Troubleshooting' ? 'selected' : '' ?>>Troubleshooting</option>
                                                    <option value="Device Setup" <?= $faq['topic'] === 'Device Setup' ? 'selected' : '' ?>>Device Setup</option>
                                                    <option value="File Sharing & Drives" <?= $faq['topic'] === 'File Sharing & Drives' ? 'selected' : '' ?>>File Sharing & Drives</option>
                                                    <option value="IT Announcements" <?= $faq['topic'] === 'IT Announcements' ? 'selected' : '' ?>>IT Announcements</option>
                                                    <option value="Others" <?= $faq['topic'] === 'Others' ? 'selected' : '' ?>>Others</option>
                                                </select>
                                            </div>
                                        </div>

                                        <?php if (!empty($faq['attachment'])): ?>
                                            <div class="form-group current-attachment-wrapper">
                                                <label class="form-label">Current Attachment:</label>
                                                <div class="attachment-preview">
                                                    <a href="<?= htmlspecialchars($faq['attachment']) ?>" target="_blank" class="attachment-link">
                                                        <i class="fas fa-file"></i>
                                                        View File
                                                    </a>
                                                    <button type="button" class="remove-attachment" onclick="removeAttachment(this)" title="Remove attachment">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="remove_existing_attachment" value="0">
                                            </div>
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label class="form-label">Update Attachment:</label>
                                            <input type="file" name="attachment" class="form-input" disabled>
                                        </div>
                                        
                                        <div class="form-actions" style="display: none;">
                                            <button type="submit" name="action" value="update" class="btn btn-success">
                                                <i class="fas fa-save"></i>
                                                <span>Save Changes</span>
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?= $index ?>)">
                                                <i class="fas fa-times"></i>
                                                <span>Cancel</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Admin FAQs Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-user-shield"></i>
                            Administrator FAQs
                            <span class="item-count"><?= count($adminFaqs) ?> items</span>
                        </h2>
                        <p class="card-description">Internal FAQs for administrative purposes</p>
                    </div>
                    <div class="header-controls">
                        <div class="search-controls">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="adminFaqSearch" placeholder="Search Admin FAQs..." class="search-input">
                            </div>
                            <select id="adminFaqStatusFilter" class="filter-select" onchange="filterAdminFaqs()">
                                <option value="all">All Status</option>
                                <option value="resolved">Resolved</option>
                                <option value="not resolved">Pending</option>
                            </select>
                            <select id="adminFaqTopicFilter" class="filter-select" onchange="filterAdminFaqs()">
                                <option value="all">All Categories</option>
                                <option value="Account Issues">Account Issues</option>
                                <option value="Email Support">Email Support</option>
                                <option value="Printer & Scanner">Printer & Scanner</option>
                                <option value="Network & Internet">Network & Internet</option>
                                <option value="Hardware Problems">Hardware Problems</option>
                                <option value="Software Installation">Software Installation</option>
                                <option value="System Access">System Access</option>
                                <option value="Company Applications">Company Applications</option>
                                <option value="Password & Login">Password & Login</option>
                                <option value="Security & Policy">Security & Policy</option>
                                <option value="Forms & Requests">Forms & Requests</option>
                                <option value="IT Procedures">IT Procedures</option>
                                <option value="Remote Access">Remote Access</option>
                                <option value="Backup & Recovery">Backup & Recovery</option>
                                <option value="User Guides">User Guides</option>
                                <option value="Troubleshooting">Troubleshooting</option>
                                <option value="Device Setup">Device Setup</option>
                                <option value="File Sharing & Drives">File Sharing & Drives</option>
                                <option value="IT Announcements">IT Announcements</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="faq-grid" id="adminFaqList">
                        <?php if (empty($adminFaqs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <h3>No Admin FAQs Yet</h3>
                                <p>Create admin-specific FAQs using the form above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($adminFaqs as $index => $faq): ?>
                                <div class="faq-card admin-faq" data-index="<?= $index ?>" data-topic="<?= htmlspecialchars($faq['topic']) ?>">
                                    <div class="faq-card-header">
                                        <div class="faq-meta">
                                            <span class="faq-id admin">#A<?= $faq['id'] ?></span>
                                            <span class="status-badge <?= $faq['status'] === 'resolved' ? 'resolved' : 'pending' ?>">
                                                <i class="fas <?= $faq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                <?= $faq['status'] === 'resolved' ? 'Resolved' : 'Pending' ?>
                                            </span>
                                        </div>
                                        <div class="faq-actions">
                                            <button type="button" class="action-btn edit" onclick="toggleEditAdmin(<?= $index ?>)" title="Edit FAQ">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn delete" onclick="confirmDeleteAdminFaq(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>')" title="Delete FAQ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <form method="post" class="faq-form" id="adminFaqForm<?= $index ?>" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                        <input type="hidden" name="action" value="update_admin_faq">

                                        <div class="form-group">
                                            <label class="form-label">Question</label>
                                            <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>" class="form-input" readonly required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Answer</label>
                                            <textarea name="answer" class="form-textarea" readonly required><?= htmlspecialchars($faq['answer']) ?></textarea>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" disabled required>
                                                    <option value="resolved" <?= $faq['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                    <option value="not resolved" <?= $faq['status'] === 'not resolved' ? 'selected' : '' ?>>Pending</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Category</label>
                                                <select name="topic" class="form-select" required disabled>
                                                    <option value="Account Issues" <?= $faq['topic'] === 'Account Issues' ? 'selected' : '' ?>>Account Issues</option>
                                                    <option value="Email Support" <?= $faq['topic'] === 'Email Support' ? 'selected' : '' ?>>Email Support</option>
                                                    <option value="Printer & Scanner" <?= $faq['topic'] === 'Printer & Scanner' ? 'selected' : '' ?>>Printer & Scanner</option>
                                                    <option value="Network & Internet" <?= $faq['topic'] === 'Network & Internet' ? 'selected' : '' ?>>Network & Internet</option>
                                                    <option value="Hardware Problems" <?= $faq['topic'] === 'Hardware Problems' ? 'selected' : '' ?>>Hardware Problems</option>
                                                    <option value="Software Installation" <?= $faq['topic'] === 'Software Installation' ? 'selected' : '' ?>>Software Installation</option>
                                                    <option value="System Access" <?= $faq['topic'] === 'System Access' ? 'selected' : '' ?>>System Access</option>
                                                    <option value="Company Applications" <?= $faq['topic'] === 'Company Applications' ? 'selected' : '' ?>>Company Applications</option>
                                                    <option value="Password & Login" <?= $faq['topic'] === 'Password & Login' ? 'selected' : '' ?>>Password & Login</option>
                                                    <option value="Security & Policy" <?= $faq['topic'] === 'Security & Policy' ? 'selected' : '' ?>>Security & Policy</option>
                                                    <option value="Forms & Requests" <?= $faq['topic'] === 'Forms & Requests' ? 'selected' : '' ?>>Forms & Requests</option>
                                                    <option value="IT Procedures" <?= $faq['topic'] === 'IT Procedures' ? 'selected' : '' ?>>IT Procedures</option>
                                                    <option value="Remote Access" <?= $faq['topic'] === 'Remote Access' ? 'selected' : '' ?>>Remote Access</option>
                                                    <option value="Backup & Recovery" <?= $faq['topic'] === 'Backup & Recovery' ? 'selected' : '' ?>>Backup & Recovery</option>
                                                    <option value="User Guides" <?= $faq['topic'] === 'User Guides' ? 'selected' : '' ?>>User Guides</option>
                                                    <option value="Troubleshooting" <?= $faq['topic'] === 'Troubleshooting' ? 'selected' : '' ?>>Troubleshooting</option>
                                                    <option value="Device Setup" <?= $faq['topic'] === 'Device Setup' ? 'selected' : '' ?>>Device Setup</option>
                                                    <option value="File Sharing & Drives" <?= $faq['topic'] === 'File Sharing & Drives' ? 'selected' : '' ?>>File Sharing & Drives</option>
                                                    <option value="IT Announcements" <?= $faq['topic'] === 'IT Announcements' ? 'selected' : '' ?>>IT Announcements</option>
                                                    <option value="Others" <?= $faq['topic'] === 'Others' ? 'selected' : '' ?>>Others</option>
                                                </select>
                                            </div>
                                        </div>

                                        <?php if (!empty($faq['attachment'])): ?>
                                            <div class="form-group current-attachment-wrapper">
                                                <label class="form-label">Current Attachment:</label>
                                                <div class="attachment-preview">
                                                    <a href="<?= htmlspecialchars($faq['attachment']) ?>" target="_blank" class="attachment-link">
                                                        <i class="fas fa-file"></i>
                                                        View File
                                                    </a>
                                                    <button type="button" class="remove-attachment" onclick="removeAdminAttachment(this)" title="Remove attachment">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="remove_existing_attachment" value="0">
                                            </div>
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label class="form-label">Update Attachment:</label>
                                            <input type="file" name="attachment" class="form-input" disabled>
                                        </div>

                                        <div class="form-actions" style="display: none;">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i>
                                                <span>Save Changes</span>
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="cancelEditAdmin(<?= $index ?>)">
                                                <i class="fas fa-times"></i>
                                                <span>Cancel</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- User Management Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-users-cog"></i>
                            User Management
                        </h2>
                        <p class="card-description">Manage user accounts and permissions</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): ?>
                                    <form method="POST" action="admin.php">
                                    <tr>
                                            <td class="user-id"><?= $user['id'] ?></td>
                                            <td>
                                                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="table-input" required>
                                            </td>
                                            <td>
                                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="table-input" required>
                                            </td>
                                            <td>
                                                <input type="password" name="password" placeholder="New Password" class="table-input">
                                            </td>
                                            <td>
                                                <select name="role" class="table-select">
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>👨‍💼 Admin</option>
                                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>👤 User</option>
                                                </select>
                                            </td>
                                            <td class="table-actions">
    <input type="hidden" name="action" value="update_user">
    <input type="hidden" name="update_user_id" value="<?= $user['id'] ?>">
    <button type="submit" class="action-btn save" title="Save Changes">
        <i class="fas fa-save"></i>
    </button>

                                                <button type="button" class="action-btn delete" onclick="confirmDeleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                    </tr>
                                    </form>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Deleted FAQs Section -->
            <section class="admin-card">
                <div class="card-header">
                    <div class="header-content">
                        <h2 class="card-title">
                            <i class="fas fa-trash-restore"></i>
                            Deleted FAQs
                            <span class="item-count"><?= count($deletedFaqs) ?> items</span>
                        </h2>
                        <p class="card-description">Restore or permanently remove deleted FAQs</p>
                    </div>
                    <div class="header-controls">
                        <div class="search-controls">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="deletedSearch" placeholder="Search deleted FAQs..." class="search-input">
                            </div>
                            <select id="deletedStatusFilter" class="filter-select" onchange="filterDeletedFaqs()">
                                <option value="all">All Status</option>
                                <option value="resolved">Resolved</option>
                                <option value="not resolved">Pending</option>
                            </select>
                            <select id="deletedTopicFilter" class="filter-select" onchange="filterDeletedFaqs()">
                                <option value="all">All Categories</option>
                                <option value="Account Issues">Account Issues</option>
                                <option value="Email Support">Email Support</option>
                                <option value="Printer & Scanner">Printer & Scanner</option>
                                <option value="Network & Internet">Network & Internet</option>
                                <option value="Hardware Problems">Hardware Problems</option>
                                <option value="Software Installation">Software Installation</option>
                                <option value="System Access">System Access</option>
                                <option value="Company Applications">Company Applications</option>
                                <option value="Password & Login">Password & Login</option>
                                <option value="Security & Policy">Security & Policy</option>
                                <option value="Forms & Requests">Forms & Requests</option>
                                <option value="IT Procedures">IT Procedures</option>
                                <option value="Remote Access">Remote Access</option>
                                <option value="Backup & Recovery">Backup & Recovery</option>
                                <option value="User Guides">User Guides</option>
                                <option value="Troubleshooting">Troubleshooting</option>
                                <option value="Device Setup">Device Setup</option>
                                <option value="File Sharing & Drives">File Sharing & Drives</option>
                                <option value="IT Announcements">IT Announcements</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="table-container">
                        <table class="modern-table deleted-table" id="deletedFaqsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Status</th>
                                    <th>Category</th>
                                    <th>Deleted Date</th>
                                    <th>Deleted By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="deletedFaqsBody">
                                <?php if (empty($deletedFaqs)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-state-cell">
                                            <div class="empty-state">
                                                <div class="empty-icon">
                                                    <i class="fas fa-inbox"></i>
                                                </div>
                                                <h3>No Deleted FAQs</h3>
                                                <p>No FAQs have been deleted yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deletedFaqs as $index => $deletedFaq): ?>
                                        <tr class="deleted-faq-row" 
                                            data-status="<?= strtolower($deletedFaq['status']) ?>"
                                            data-topic="<?= htmlspecialchars($deletedFaq['topic']) ?>">
                                            <td class="faq-id-cell">#<?= $deletedFaq['id'] ?></td>
                                            <td class="question-cell">
                                                <div class="question-content">
                                                    <?= htmlspecialchars($deletedFaq['question']) ?>
                                                </div>
                                            </td>
                                            <td class="answer-cell">
                                                <div class="answer-content">
                                                    <?= htmlspecialchars(mb_strimwidth($deletedFaq['answer'], 0, 100, '...')) ?>
                                                </div>
                                            </td>
                                            <td class="status-cell">
                                                <span class="status-badge <?= $deletedFaq['status'] === 'resolved' ? 'resolved' : 'pending' ?>">
                                                    <i class="fas <?= $deletedFaq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                    <?= $deletedFaq['status'] === 'resolved' ? 'Resolved' : 'Pending' ?>
                                                </span>
                                            </td>
                                            <td class="topic-cell">
                                                <span class="category-tag"><?= htmlspecialchars($deletedFaq['topic']) ?></span>
                                            </td>
                                            <td class="date-cell"><?= date('M d, Y', strtotime($deletedFaq['deleted_date'])) ?></td>
                                            <td class="user-cell"><?= htmlspecialchars($deletedFaq['deleted_by']) ?></td>
                                            <td class="actions-cell">
                                                <div class="table-actions">
                                                    <button type="button" class="action-btn restore" title="Restore FAQ" onclick="restoreFaq(<?= $deletedFaq['id'] ?>)">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    <button type="button" class="action-btn view" 
                                                            onclick="viewDeletedFaq('<?= htmlspecialchars($deletedFaq['question'], ENT_QUOTES) ?>', '<?= htmlspecialchars($deletedFaq['answer'], ENT_QUOTES) ?>')" 
                                                            title="View Full Content">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Deletion
                </h3>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="warning-content">
                        <h4>Are you sure you want to delete this FAQ?</h4>
                        <p id="delete-question-preview">This action cannot be undone.</p>
                        <div class="warning-note">
                            <i class="fas fa-info-circle"></i>
                            <span>This will move the FAQ to the deleted items section.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button class="btn btn-danger" onclick="deleteFaq()">
                    <i class="fas fa-trash"></i>
                    <span>Delete FAQ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- View Deleted FAQ Modal -->
    <div id="viewDeletedModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-eye"></i>
                    FAQ Details
                </h3>
                <button class="close-modal" onclick="closeViewDeletedModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="faq-details">
                    <div class="detail-group">
                        <label class="detail-label">Question:</label>
                        <div class="detail-content" id="viewDeletedQuestion"></div>
                    </div>
                    <div class="detail-group">
                        <label class="detail-label">Answer:</label>
                        <div class="detail-content" id="viewDeletedAnswer"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewDeletedModal()">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Modal -->
    <div id="statsModal" class="modal">
        <div class="modal-content stats-modal">
            <div class="modal-header">
                <h3><i class="fas fa-chart-bar"></i> Analytics Dashboard</h3>
                <button class="close-modal" onclick="closeStatsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-container">
                    <!-- Summary Stats -->
                    <div class="analytics-summary">
                        <div class="summary-card">
                            <div class="summary-icon total">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $totalFaqs ?></h4>
                                <p>Total FAQs</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $resolvedCount ?></h4>
                                <p>Resolved</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $unsolvedCount ?></h4>
                                <p>Pending</p>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon users">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $totalUsers ?></h4>
                                <p>Total Users</p>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Container -->
                    <div class="charts-container">
                        <div class="chart-item">
                            <h4><i class="fas fa-chart-pie"></i> Status Distribution</h4>
                            <div class="chart-wrapper">
                                <canvas id="statusPieChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-item">
                            <h4><i class="fas fa-chart-bar"></i> Resolution Progress</h4>
                            <div class="chart-wrapper">
                                <canvas id="progressBarChart"></canvas>
                            </div>
                        </div>
                        
                        <?php if (!empty($monthlyStats)): ?>
                        <div class="chart-item full-width">
                            <h4><i class="fas fa-chart-line"></i> Monthly Trends</h4>
                            <div class="chart-wrapper">
                                <canvas id="monthlyLineChart"></canvas>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeStatsModal()">
                    <i class="fas fa-check"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content delete-modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm User Deletion
                </h3>
                <button class="close-modal" onclick="closeDeleteUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="warning-content">
                        <h4>Are you sure you want to delete this user?</h4>
                        <p id="delete-user-question-preview">This action cannot be undone.</p>
                        <div class="warning-note">
                            <i class="fas fa-info-circle"></i>
                            <span>This will permanently remove the user from your system.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteUserModal()">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button class="btn btn-danger" onclick="deleteUser()">
                    <i class="fas fa-trash"></i>
                    <span>Delete User</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Update User Modal -->
    <div id="updateUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-save"></i>
                    Confirm User Update
                </h3>
                <button class="close-modal" onclick="closeUpdateUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="update-warning">
                    <div class="warning-icon success">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="warning-content">
                        <h4>Are you sure you want to save changes?</h4>
                        <p id="update-user-preview">This will update the user's information.</p>
                        <div class="warning-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Make sure all information is correct before saving.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUpdateUserModal()">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button class="btn btn-success" onclick="updateUser()">
                <i class="fas fa-save"></i>
                <span>Save Changes</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let deleteUserId = null;
        let deleteUserName = '';
        let updateUserId = null;
        let updateUserName = '';
        let updateUserForm = null;
        let deleteId = null;
        let deleteQuestion = '';
        let charts = {};

        // PHP data for JavaScript
        const chartData = <?= json_encode($chartData) ?>;

        // User management functions
        function confirmDeleteUser(id, username) {
            deleteUserId = id;
            deleteUserName = username;
            document.getElementById('delete-user-question-preview').innerHTML = `<strong>"${username}"</strong>`;
            document.getElementById('deleteUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function deleteUser() {
            if (deleteUserId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${deleteUserId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            deleteUserId = null;
            deleteUserName = '';
        }

        function confirmUpdateUser(id, username, buttonElement) {
            updateUserId = id;
            updateUserName = username;
            updateUserForm = buttonElement.closest('form');
            document.getElementById('update-user-preview').innerHTML = `<strong>"${username}"</strong>`;
            document.getElementById('updateUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function updateUser() {
            if (updateUserForm) {
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_user';
                updateUserForm.appendChild(actionInput);
                updateUserForm.submit();
            }
        }

        function closeUpdateUserModal() {
            document.getElementById('updateUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            updateUserId = null;
            updateUserName = '';
            updateUserForm = null;
        }

        // FAQ filtering functions
        function filterFaqs() {
            const query = document.getElementById('adminSearch').value.toLowerCase();
            const selectedStatus = document.getElementById('adminStatusFilter')?.value || 'all';
            const selectedTopic = document.getElementById('adminTopicFilter')?.value || 'all';
            const faqs = document.querySelectorAll('.faq-card:not(.admin-faq)');
            let visibleCount = 0;

            faqs.forEach(faq => {
                const question = faq.querySelector('input[name="question"]').value.toLowerCase();
                const answer = faq.querySelector('textarea[name="answer"]').value.toLowerCase();
                const status = faq.querySelector('select[name="status"]').value;
                const topic = faq.getAttribute('data-topic') || '';
                
                const matchesQuery = question.includes(query) || answer.includes(query);
                const matchesStatus = (selectedStatus === 'all' || status === selectedStatus);
                const matchesTopic = (selectedTopic === 'all' || topic === selectedTopic);

                if (matchesQuery && matchesStatus && matchesTopic) {
                    faq.style.display = 'block';
                    visibleCount++;
                } else {
                    faq.style.display = 'none';
                }
            });

            updateItemCount('.admin-card:nth-of-type(3) .item-count', visibleCount);
        }

        function filterDeletedFaqs() {
            const query = document.getElementById('deletedSearch').value.toLowerCase();
            const selectedStatus = document.getElementById('deletedStatusFilter')?.value || 'all';
            const selectedTopic = document.getElementById('deletedTopicFilter')?.value || 'all';
            const rows = document.querySelectorAll('.deleted-faq-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const question = row.querySelector('.question-content').textContent.toLowerCase();
                const answer = row.querySelector('.answer-content').textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const topic = row.getAttribute('data-topic') || '';
                
                const matchesQuery = question.includes(query) || answer.includes(query);
                const matchesStatus = (selectedStatus === 'all' || status === selectedStatus);
                const matchesTopic = (selectedTopic === 'all' || topic === selectedTopic);

                if (matchesQuery && matchesStatus && matchesTopic) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterAdminFaqs() {
            const query = document.getElementById('adminFaqSearch').value.toLowerCase();
            const selectedStatus = document.getElementById('adminFaqStatusFilter')?.value || 'all';
            const selectedTopic = document.getElementById('adminFaqTopicFilter')?.value || 'all';
            const faqs = document.querySelectorAll('.admin-faq');
            let visibleCount = 0;

            faqs.forEach(faq => {
                const question = faq.querySelector('input[name="question"]').value.toLowerCase();
                const answer = faq.querySelector('textarea[name="answer"]').value.toLowerCase();
                const status = faq.querySelector('select[name="status"]').value;
                const topic = faq.getAttribute('data-topic') || '';
                
                const matchesQuery = question.includes(query) || answer.includes(query);
                const matchesStatus = (selectedStatus === 'all' || status === selectedStatus);
                const matchesTopic = (selectedTopic === 'all' || topic === selectedTopic);

                if (matchesQuery && matchesStatus && matchesTopic) {
                    faq.style.display = 'block';
                    visibleCount++;
                } else {
                    faq.style.display = 'none';
                }
            });

            updateItemCount('.admin-card:nth-of-type(4) .item-count', visibleCount);
        }

        function updateItemCount(selector, count) {
            const countElement = document.querySelector(selector);
            if (countElement) {
                countElement.textContent = `${count} items`;
            }
        }

        // Edit mode functions
        function toggleEdit(index) {
            const form = document.getElementById(`faqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const selects = form.querySelectorAll('select');
            const fileInputs = form.querySelectorAll('input[type="file"]');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-card').querySelector('.edit');

            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.add('editing');
            });

            selects.forEach(select => {
                select.disabled = false;
                select.classList.add('editing');
            });

            fileInputs.forEach(input => {
                input.disabled = false;
            });

            actions.style.display = 'flex';
            editBtn.innerHTML = '<i class="fas fa-eye"></i>';
            editBtn.onclick = () => cancelEdit(index);
        }

        function cancelEdit(index) {
            location.reload();
        }

        function toggleEditAdmin(index) {
            const form = document.getElementById(`adminFaqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const selects = form.querySelectorAll('select');
            const fileInputs = form.querySelectorAll('input[type="file"]');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-card').querySelector('.edit');

            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.add('editing');
            });

            selects.forEach(select => {
                select.disabled = false;
                select.classList.add('editing');
            });

            fileInputs.forEach(input => {
                input.disabled = false;
            });

            actions.style.display = 'flex';
            editBtn.innerHTML = '<i class="fas fa-eye"></i>';
            editBtn.onclick = () => cancelEditAdmin(index);
        }

        function cancelEditAdmin(index) {
            location.reload();
        }

        // Delete confirmation functions
        function confirmDelete(id, question) {
            deleteId = id;
            deleteQuestion = question;
            document.getElementById('delete-question-preview').innerHTML = `<strong>"${question}"</strong>`;
            document.getElementById('deleteModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function confirmDeleteAdminFaq(id, question) {
            if (confirm(`Are you sure you want to delete the Admin FAQ: "${question}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_admin_faq">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteFaq() {
            if (deleteId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${deleteId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            deleteId = null;
            deleteQuestion = '';
        }

        // View deleted FAQ functions
        function viewDeletedFaq(question, answer) {
            document.getElementById('viewDeletedQuestion').textContent = question;
            document.getElementById('viewDeletedAnswer').textContent = answer;
            document.getElementById('viewDeletedModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeViewDeletedModal() {
            document.getElementById('viewDeletedModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Restore FAQ function
        function restoreFaq(id) {
            if (confirm("Are you sure you want to restore this FAQ?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Attachment functions
        function removeAttachment(btn) {
            const wrapper = btn.closest('.current-attachment-wrapper');
            const fileInput = wrapper.parentElement.querySelector('input[type="file"]');
            const hiddenInput = wrapper.querySelector('input[name="remove_existing_attachment"]');

            if (hiddenInput) {
                hiddenInput.value = "1";
                wrapper.parentElement.appendChild(hiddenInput);
            }

            wrapper.remove();

            if (fileInput) {
                fileInput.disabled = false;
                fileInput.style.display = "block";
            }
        }

        function removeAdminAttachment(btn) {
            const wrapper = btn.closest('.current-attachment-wrapper');
            const fileInput = wrapper.parentElement.querySelector('input[type="file"]');
            const hiddenInput = wrapper.querySelector('input[name="remove_existing_attachment"]');

            if (hiddenInput) {
                hiddenInput.value = "1";
                wrapper.parentElement.appendChild(hiddenInput);
            }

            wrapper.remove();

            if (fileInput) {
                fileInput.disabled = false;
                fileInput.style.display = "block";
            }
        }

        // File upload preview
        const attachmentInput = document.getElementById('faqAttachments');
        const previewList = document.getElementById('filePreviewList');

        if (attachmentInput) {
            attachmentInput.addEventListener('change', function () {
                previewList.innerHTML = '';

                Array.from(attachmentInput.files).forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.classList.add('file-preview-item');
                    fileItem.innerHTML = `
                        <div class="file-info">
                            <i class="fas fa-file"></i>
                            <span class="file-name">${file.name}</span>
                            <span class="file-size">${(file.size / 1024).toFixed(1)} KB</span>
                        </div>
                        <button type="button" class="remove-file-btn" onclick="removeFile(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    previewList.appendChild(fileItem);
                });
            });
        }


        function confirmUpdateUser(id, username, buttonElement) {
    updateUserForm = buttonElement.closest('form');
    document.getElementById('update-user-preview').textContent = `"${username}"`;
    document.getElementById('updateUserModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function updateUser() {
    if (updateUserForm) {
        updateUserForm.submit();
    }
}


        function removeFile(index) {
            const dt = new DataTransfer();
            const currentFiles = attachmentInput.files;

            for (let i = 0; i < currentFiles.length; i++) {
                if (i !== index) dt.items.add(currentFiles[i]);
            }

            attachmentInput.files = dt.files;
            attachmentInput.dispatchEvent(new Event('change'));
        }

        // Stats modal and charts
        function showStats() {
            document.getElementById('statsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                initializeCharts();
            }, 100);
        }

        function closeStatsModal() {
            document.getElementById('statsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            charts = {};
        }

        function initializeCharts() {
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });

            // Status Pie Chart
            const pieCtx = document.getElementById('statusPieChart');
            if (pieCtx) {
                charts.pie = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Resolved', 'Pending'],
                        datasets: [{
                            data: [chartData.resolved, chartData.unsolved],
                            backgroundColor: ['#10b981', '#f59e0b'],
                            borderColor: ['#059669', '#d97706'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }

            // Progress Bar Chart
            const barCtx = document.getElementById('progressBarChart');
            if (barCtx) {
                charts.bar = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Resolution Progress'],
                        datasets: [
                            {
                                label: 'Resolved',
                                data: [chartData.resolved],
                                backgroundColor: '#10b981',
                                borderColor: '#059669',
                                borderWidth: 1
                            },
                            {
                                label: 'Pending',
                                data: [chartData.unsolved],
                                backgroundColor: '#f59e0b',
                                borderColor: '#d97706',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Monthly Line Chart
            const lineCtx = document.getElementById('monthlyLineChart');
            if (lineCtx && chartData.monthly && chartData.monthly.length > 0) {
                const months = chartData.monthly.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                });
                
                charts.line = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: 'Total FAQs',
                                data: chartData.monthly.map(item => item.count),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Resolved',
                                data: chartData.monthly.map(item => item.resolved),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Pending',
                                data: chartData.monthly.map(item => item.unsolved),
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }

        // Search event listeners
        document.getElementById('adminSearch').addEventListener('input', () => {
            filterFaqs();
            animateSearchIcon();
        });

        document.getElementById('deletedSearch').addEventListener('input', () => {
            filterDeletedFaqs();
            animateSearchIcon();
        });

        document.getElementById('adminFaqSearch').addEventListener('input', () => {
            filterAdminFaqs();
            animateSearchIcon();
        });

        function animateSearchIcon() {
            const searchIcons = document.querySelectorAll('.search-box i');
            searchIcons.forEach(icon => {
                icon.classList.add('fa-spin');
                setTimeout(() => {
                    icon.classList.remove('fa-spin');
                }, 300);
            });
        }

        // Filter dropdown listeners
        document.getElementById('adminStatusFilter').addEventListener('change', filterFaqs);
        document.getElementById('deletedStatusFilter').addEventListener('change', filterDeletedFaqs);
        document.getElementById('deletedTopicFilter').addEventListener('change', filterDeletedFaqs);
        document.getElementById('adminFaqStatusFilter').addEventListener('change', filterAdminFaqs);
        document.getElementById('adminFaqTopicFilter').addEventListener('change', filterAdminFaqs);

        // Form submission loader
        document.getElementById('addFaqForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Creating...</span>';
            submitBtn.disabled = true;
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const modals = ['deleteModal', 'statsModal', 'viewDeletedModal', 'deleteUserModal', 'updateUserModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = ['deleteModal', 'statsModal', 'viewDeletedModal', 'deleteUserModal', 'updateUserModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal.style.display === 'flex') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Prevent back button
        history.pushState(null, "", location.href);
        window.onpopstate = function () {
            history.pushState(null, "", location.href);
        };

        // Session management
        let sessionTimer;
        let activityTimer;

        function refreshSession() {
            fetch('session_refresh.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=refresh'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'error') {
                    window.location.href = 'index.php?timeout=1';
                }
            })
            .catch(error => {
                console.log('Session refresh failed:', error);
            });
        }

        function startSessionTimer() {
            if (sessionTimer) clearInterval(sessionTimer);
            sessionTimer = setInterval(refreshSession, 300000); // 5 minutes
        }

        function resetActivityTimer() {
            if (activityTimer) clearTimeout(activityTimer);
            activityTimer = setTimeout(() => {
                alert('Your session will expire soon due to inactivity. Click OK to stay logged in.');
                refreshSession();
                resetActivityTimer();
            }, 28800000); // 8 hours
        }

        // Track user activity
        ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
            document.addEventListener(event, () => {
                resetActivityTimer();
            }, true);
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            startSessionTimer();
            resetActivityTimer();
            
            // Initialize notification manager if available
            if (window.notificationManager) {
                window.notificationManager = new NotificationManager();
            }
        });

        // Keep session alive when page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                refreshSession();
            }
        });

        // Notification System
        class NotificationManager {
            constructor() {
                this.isOpen = false;
                this.pollInterval = 5000;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.startPolling();
                this.loadNotifications();
            }
            
            bindEvents() {
                const bell = document.getElementById('notificationBell');
                const dropdown = document.getElementById('notificationDropdown');
                
                if (!bell) return;
                
                bell.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown();
                });
                
                document.addEventListener('click', (e) => {
                    if (!bell.contains(e.target)) {
                        this.closeDropdown();
                    }
                });
            }
            
            toggleDropdown() {
                const dropdown = document.getElementById('notificationDropdown');
                if (!dropdown) return;
                
                this.isOpen = !this.isOpen;
                
                if (this.isOpen) {
                    dropdown.classList.add('show');
                    this.loadNotifications();
                } else {
                    dropdown.classList.remove('show');
                }
            }
            
            closeDropdown() {
                const dropdown = document.getElementById('notificationDropdown');
                if (!dropdown) return;
                
                dropdown.classList.remove('show');
                this.isOpen = false;
            }
            
            async loadNotifications() {
                try {
                    const response = await fetch('notifications.php?action=get');
                    const data = await response.json();
                    
                    if (data.notifications) {
                        this.renderNotifications(data.notifications);
                    }
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            }
            
            async updateBadge() {
                try {
                    const response = await fetch('notifications.php?action=count');
                    const data = await response.json();
                    
                    const badge = document.getElementById('notificationBadge');
                    if (!badge) return;
                    
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error updating badge:', error);
                }
            }
            
            renderNotifications(notifications) {
                const list = document.getElementById('notificationList');
                if (!list) return;
                
                if (notifications.length === 0) {
                    list.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No new notifications</h3>
                            <p>Check back later for updates</p>
                        </div>
                    `;
                    return;
                }
                
                list.innerHTML = notifications.map(notification => `
                    <div class="notification-item unread" onclick="markAsRead(${notification.id})">
                        <div class="notification-content">
                            <div class="notification-icon ${notification.type}">
                                <i class="fas ${this.getIcon(notification.type)}"></i>
                            </div>
                            <div class="notification-text">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-message">${notification.message}</div>
                                <div class="notification-time">${notification.time_ago}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
            
            getIcon(type) {
                const icons = {
                    'faq_added': 'fa-plus',
                    'faq_updated': 'fa-edit',
                    'faq_deleted': 'fa-trash',
                    'faq_restored': 'fa-undo',
                    'user_created': 'fa-user-plus',
                    'user_deleted': 'fa-user-minus',
                    'user_updated': 'fa-user-edit',
                    'system_alert': 'fa-exclamation-triangle'
                };
                return icons[type] || 'fa-info';
            }
            
            startPolling() {
                setInterval(() => {
                    this.updateBadge();
                    if (this.isOpen) {
                        this.loadNotifications();
                    }
                }, this.pollInterval);
            }
        }

        // Mark notification as read
        async function markAsRead(id) {
            try {
                await fetch('notifications.php?action=mark_read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                });
                
                if (window.notificationManager) {
                    notificationManager.loadNotifications();
                    notificationManager.updateBadge();
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        }

        // Mark all notifications as read
        async function markAllAsRead() {
            try {
                await fetch('notifications.php?action=mark_all_read', {
                    method: 'POST'
                });
                
                if (window.notificationManager) {
                    notificationManager.loadNotifications();
                    notificationManager.updateBadge();
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }

        // Initialize notification manager
        window.notificationManager = new NotificationManager();
    </script>
    
</body>
</html>
