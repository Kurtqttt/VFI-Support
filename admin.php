<?php
session_start();

require 'includes/db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// Fetch all users (except admin himself if needed)
$userStmt = $pdo->query("SELECT * FROM users");
$allUsers = $userStmt->fetchAll(PDO::FETCH_ASSOC);


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

// Include notification functions
require_once 'includes/notifications.php';


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

    if ($_POST['action'] === 'update_admin_faq') {
    $id = $_POST['id'];
    $q = $_POST['question'];
    $a = $_POST['answer'];
    $s = $_POST['status'];
    $topic = $_POST['topic'];

    $stmt = $pdo->prepare("UPDATE admin_faqs SET question = ?, answer = ?, status = ?, topic = ? WHERE id = ?");
    $stmt->execute([$q, $a, $s, $topic, $id]);

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

    // DELETE user
    if ($_POST['action'] === 'delete_user') {
        $deleteId = $_POST['delete_user_id'];

        // Optional: Prevent deleting own account
        if ($_SESSION['user_id'] != $deleteId) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);
        }

    header("Location: admin.php");
    exit;
}

    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Handle File Upload
    $filenames = [];

    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION); // Get file extension
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($name, PATHINFO_FILENAME)); // Sanitize filename
                $uniqueName = uniqid() . "_" . $safeName . '.' . $ext; // Add extension back
                $targetPath = $uploadPath . $uniqueName;

                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $targetPath)) {
                    $filenames[] = $targetPath;
                }
            }
        }
    }


    // Store the first file path as a simple string for consistency
    $filename = !empty($filenames) ? $filenames[0] : '';

   if ($_POST['action'] === 'add') {
    $q = $_POST['question'];
    $a = $_POST['answer'];
    $status = $_POST['status'] ?? 'not resolved';
    $topic = $_POST['topic'] ?? 'Others';
    $audience = $_POST['target_audience'] ?? 'user'; // 'admin' or 'user'

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

// Count stats
$totalFaqs = count($faqs);
$resolvedCount = 0;
$unsolvedCount = 0;

foreach ($faqs as $faq) {
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
    'monthly' => array_reverse($monthlyStats)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage FAQs</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
    <div class="admin-background">
        <!-- Header Section -->
        <div class="admin-header">
            <div class="admin-header-content">
                <div class="admin-welcome">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h1 class="admin-title">Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
                        <p class="admin-subtitle">Manage your FAQ content</p>
                    </div>
                </div>
                <div class="admin-actions">
    
    <!-- Notification Bell -->
<div class="notification-bell" id="notificationBell" title="Notifications">
     <span style="color: white; font-size: 20px; font-weight: bold;">🔔</span>
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

    <form method="get" action="index.php" style="display: inline;">
        <button type="submit" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </form>
</div>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="admin-container">
            <div class="stats-cards-container">
                <div class="stats-card">
                    <div class="stats-card-icon total">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stats-card-content">
                        <h3><?= $totalFaqs ?></h3>
                        <p>Total FAQs</p>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-card-icon resolved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-card-content">
                        <h3><?= $resolvedCount ?></h3>
                        <p>Resolved</p>
                        <span class="percentage"><?= $resolvedPercentage ?>%</span>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-card-icon unsolved">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stats-card-content">
                        <h3><?= $unsolvedCount ?></h3>
                        <p>Unsolved</p>
                        <span class="percentage"><?= $unsolvedPercentage ?>%</span>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-card-icon rate">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-card-content">
                        <h3><?= $resolvedPercentage ?>%</h3>
                        <p>Resolution Rate</p>
                    </div>
                </div>
            </div>

            <!-- Add FAQ Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Add New FAQ
                    </h2>
                </div>
                <div class="card-content">
                    <form method="post" class="add-faq-form" id="addFaqForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="question" class="form-label">
                                <i class="fas fa-question-circle"></i>
                                Concern
                            </label>
                            <input type="text" name="question" id="question" placeholder="Enter your concern here..." class="modern-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="answer" class="form-label">
                                <i class="fas fa-comment-dots"></i>
                                Details
                            </label>
                            <textarea name="answer" id="answer" placeholder="Enter a details or provide a link..." class="modern-textarea" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">
                                <i class="fas fa-check-circle"></i> Status
                            </label>
                            <select name="status" class="modern-input" required>
                                <option value="resolved">Resolved</option>
                                <option value="not resolved" selected>Unresolved</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="target_audience" class="form-label">
                                <i class="fas fa-user-cog"></i> Target Audience
                            </label>
                            <select name="target_audience" id="target_audience" class="modern-input" required>
                                <option value="user" selected>User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="topic" class="form-label">
                                <i class="fas fa-tags"></i> Topic
                            </label>
                            <select name="topic" id="topic" class="modern-input" required>
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

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-paperclip"></i> Upload Attachment (Image/File)
                            </label>
                            <input type="file" name="attachments[]" class="modern-input" multiple id="faqAttachments">
                        <div id="filePreviewList"></div>
                        </div>
                        
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="modern-btn primary">
                            <i class="fas fa-plus"></i>
                            <span>Add FAQ</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Create New User Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-plus"></i>
                        Create New User
                    </h2>
                </div>
                <div class="card-content">
                    <form method="post" class="add-user-form">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="new_username" class="modern-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="new_email" class="modern-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" name="new_password" class="modern-input" required>
                        </div>
                        <input type="hidden" name="action" value="create_user">
                        <button type="submit" class="modern-btn primary">
                            <i class="fas fa-user-plus"></i>
                            <span>Create User</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Manage FAQs Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-cogs"></i>
                        Manage FAQs
                        <span class="faq-count">(<?= count($faqs) ?> items)</span>
                    </h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="adminSearch" class="search-input" placeholder="Search FAQs...">
                        <select id="adminStatusFilter" class="status-filter-dropdown" onchange="filterAdminFaqs()">
                            <option value="all">All Status</option>
                            <option value="resolved">Resolved</option>
                            <option value="not resolved">Unresolved</option>
                        </select>
                        <select id="adminTopicFilter" class="status-filter-dropdown" onchange="filterFaqs()">
                            <option value="all">All Topics</option>
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
                
                <div class="card-content">
                    <div class="faq-list-container" id="adminFaqList">
                        <?php if (empty($faqs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No FAQs Found</h3>
                                <p>Start by adding your first FAQ above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($faqs as $index => $faq): ?>
                                <div class="faq-item" data-index="<?= $index ?>" data-topic="<?= htmlspecialchars($faq['topic']) ?>">
                                    <div class="faq-item-header">
                                        <span class="faq-number">#<?= $faq['id'] ?></span>
                                        <span class="status-badge <?= $faq['status'] === 'resolved' ? 'resolved' : 'unsolved' ?>">
                                            <i class="fas <?= $faq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                            <?= ucfirst(str_replace('_', ' ', $faq['status'])) ?>
                                        </span>
                                        <div class="faq-actions">
                                            <button type="button" class="action-btn edit" onclick="toggleEdit(<?= $index ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn delete" onclick="confirmDelete(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <form method="post" class="faq-form" id="faqForm<?= $index ?>" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Concern</label>
                                            <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>" class="modern-input" readonly required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Details</label>
                                            <textarea name="answer" class="modern-textarea" readonly required><?= htmlspecialchars($faq['answer']) ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="modern-input" required readonly>
                                                <option value="resolved" <?= $faq['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                <option value="not resolved" <?= $faq['status'] === 'not resolved' ? 'selected' : '' ?>>Unresolved</option>
                                            </select>
                                        </div>

                                        <?php if (!empty($faq['attachment'])): ?>
                                            <div class="form-group current-attachment-wrapper">
                                                <label class="form-label">Current Attachment:</label>
                                                <div class="attachment-view">
                                                    <a href="<?= htmlspecialchars($faq['attachment']) ?>" target="_blank">View Current File</a>
                                                    <button type="button" class="close-attachment" onclick="removeAttachment(this)" title="Remove this file">
                                                        &times;
                                                    </button>
                                                </div>
                                                <input type="hidden" name="remove_existing_attachment" value="0">
                                            </div>
                                        <?php endif; ?>


                                        <div class="form-group">
                                            <label class="form-label">Attached File:</label>
                                            <input type="file" name="attachment" class="modern-input" <?= $faq['status'] !== 'not resolved' ? 'disabled' : '' ?>>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Tag</label>
                                            <select name="topic" class="modern-input" required readonly>
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
                                        
                                        <div class="form-actions" style="display: none;">
                                            <button type="submit" name="action" value="update" class="modern-btn success">
                                                <i class="fas fa-save"></i>
                                                <span>Update</span>
                                            </button>
                                            <button type="button" class="modern-btn secondary" onclick="cancelEdit(<?= $index ?>)">
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
            </div>

            <!-- Admin-Only FAQs -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-shield"></i> Admin FAQs</h2>
                </div>
                <div class="card-content">
                <?php
                $adminFaqs = $pdo->query("SELECT * FROM admin_faqs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                if (empty($adminFaqs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Admin FAQs Yet</h3>
                    </div>
                <?php else: ?>
                <?php foreach ($adminFaqs as $index => $faq): ?>
                <div class="faq-item" data-index="<?= $index ?>">
                    <div class="faq-item-header">
                        <span class="faq-number">#<?= $faq['id'] ?></span>
                        <span class="status-badge <?= $faq['status'] === 'resolved' ? 'resolved' : 'unsolved' ?>">
                            <i class="fas <?= $faq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                            <?= ucfirst($faq['status']) ?>
                        </span>
                        <div class="faq-actions">
                            <button type="button" class="action-btn edit" onclick="toggleEditAdmin(<?= $index ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="action-btn delete" onclick="confirmDeleteAdminFaq(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <form method="post" class="faq-form" id="adminFaqForm<?= $index ?>" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                        <input type="hidden" name="action" value="update_admin_faq">

                    <div class="form-group">
                        <label class="form-label">Question</label>
                        <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>" class="modern-input" readonly required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Answer</label>
                        <textarea name="answer" class="modern-textarea" readonly required><?= htmlspecialchars($faq['answer']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="modern-input" disabled required>
                            <option value="resolved" <?= $faq['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="not resolved" <?= $faq['status'] === 'not resolved' ? 'selected' : '' ?>>Unresolved</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Topic</label>
                        <select name="topic" class="modern-input" required disabled>
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

                <?php if (!empty($faq['attachment'])): ?>
                    <div class="form-group">
                        <label class="form-label">Attachment:</label>
                        <p><a href="<?= $faq['attachment'] ?>" target="_blank">View File</a></p>
                    </div>
                <?php endif; ?>

                <div class="form-actions" style="display: none;">
                    <button type="submit" class="modern-btn success">
                        <i class="fas fa-save"></i> <span>Update</span>
                    </button>
                    <button type="button" class="modern-btn secondary" onclick="cancelEditAdmin(<?= $index ?>)">
                        <i class="fas fa-times"></i> <span>Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-users-cog"></i> Manage Users
        </h2>
    </div>

    <div class="card-content">
        <table class="deleted-faqs-table">
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
                    <tr>
                        <form method="POST">
                            <td><?= $user['id'] ?></td>
                            <td>
                                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </td>
                            <td>
                                <input type="password" name="password" placeholder="New Password">
                            </td>
                            <td>
                                <select name="role">
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="update_user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="modern-btn success" name="action" value="update_user">
                                    <i class="fas fa-save"></i>
                                </button>
                            </td>
                            
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- NEW: Deleted FAQs Section -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-trash-restore"></i>
                    Deleted FAQs
                <span class="faq-count">(<?= count($deletedFaqs) ?> items)</span>
            </h2>
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                        <input type="text" id="deletedSearch" class="search-input" placeholder="Search deleted FAQs...">
                        <select id="deletedStatusFilter" class="status-filter-dropdown" onchange="filterDeletedFaqs()">
                            <option value="all">All Status</option>
                            <option value="resolved">Resolved</option>
                            <option value="not resolved">Unresolved</option>
                        </select>
                        <select id="deletedTopicFilter" class="status-filter-dropdown" onchange="filterDeletedFaqs()">
                            <option value="all">All Topics</option>
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
                
                <div class="card-content">
                    <div class="deleted-faqs-table-wrapper">
                        <table class="deleted-faqs-table" id="deletedFaqsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Status</th>
                                    <th>Topic</th>
                                    <th>Deleted Date</th>
                                    <th>Deleted By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="deletedFaqsBody">
                                <?php if (empty($deletedFaqs)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-state-row">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
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
                                                <span class="status-badge <?= $deletedFaq['status'] === 'resolved' ? 'resolved' : 'unsolved' ?>">
                                                    <i class="fas <?= $deletedFaq['status'] === 'resolved' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $deletedFaq['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="topic-cell">
                                                <span class="faq-tag"><?= htmlspecialchars($deletedFaq['topic']) ?></span>
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
            </div>
        </div>

        <div class="bg-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>

    <!-- View Deleted Admin FAQ Modal -->
    <div id="viewDeletedAdminModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Deleted Admin FAQ Details</h3>
            <button class="close-modal" onclick="closeViewDeletedAdminModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="deleted-faq-details">
                <div class="detail-group">
                    <label class="detail-label">Question:</label>
                    <div class="detail-content" id="adminDeletedQuestion"></div>
                </div>
                <div class="detail-group">
                    <label class="detail-label">Answer:</label>
                    <div class="detail-content" id="adminDeletedAnswer"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modern-btn secondary" onclick="closeViewDeletedAdminModal()">
                <i class="fas fa-times"></i>
                <span>Close</span>
            </button>
        </div>
    </div>
</div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content delete-modal-content">
            <div class="modal-header delete-modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Confirm Delete</span>
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
                            <span>This will permanently remove the FAQ from your database.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button class="modern-btn danger" onclick="deleteFaq()">
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
                    <span>Deleted FAQ Details</span>
                </h3>
                <button class="close-modal" onclick="closeViewDeletedModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="deleted-faq-details">
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
                <button type="button" class="modern-btn secondary" onclick="closeViewDeletedModal()">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Stats Modal with Charts -->
    <div id="statsModal" class="modal">
        <div class="modal-content stats-modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chart-bar"></i> FAQ Analytics Dashboard</h3>
                <button class="close-modal" onclick="closeStatsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analytics-container">
                    <!-- Summary Stats -->
                    <div class="analytics-summary">
                        <div class="summary-item">
                            <div class="summary-icon total">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $totalFaqs ?></h4>
                                <p>Total FAQs</p>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $resolvedCount ?></h4>
                                <p>Resolved</p>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon unsolved">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= $unsolvedCount ?></h4>
                                <p>Unsolved</p>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-icon admin">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="summary-content">
                                <h4><?= htmlspecialchars($_SESSION['user']) ?></h4>
                                <p>Admin User</p>
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
                <button type="button" class="modern-btn primary" onclick="closeStatsModal()">
                    <i class="fas fa-check"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // PHP data for JavaScript
        const chartData = <?= json_encode($chartData) ?>;
        
        let deleteId = null;
        let deleteQuestion = '';
        let charts = {};

        // Filter FAQs by both search and status
        function filterFaqs() {
            const query = document.getElementById('adminSearch').value.toLowerCase();
            const selectedStatus = document.getElementById('adminStatusFilter')?.value || 'all';
            const selectedTopic = document.getElementById('adminTopicFilter')?.value || 'all';
            const faqs = document.querySelectorAll('.faq-item');
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

            const countElement = document.querySelector('.faq-count');
            if (countElement) {
                countElement.textContent = `(${visibleCount} items)`;
            }
        }

        // NEW: Filter deleted FAQs
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

        // NEW: View deleted FAQ details
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

        // Search bar listeners
        document.getElementById('adminSearch').addEventListener('input', () => {
            filterFaqs();
            const searchIcon = document.querySelector('.search-icon');
            searchIcon.classList.add('fa-spin');
            setTimeout(() => {
                searchIcon.classList.remove('fa-spin');
            }, 300);
        });

        // NEW: Deleted FAQs search listener
        document.getElementById('deletedSearch').addEventListener('input', () => {
            filterDeletedFaqs();
            const searchIcon = document.querySelector('.search-icon');
            searchIcon.classList.add('fa-spin');
            setTimeout(() => {
                searchIcon.classList.remove('fa-spin');
            }, 300);
        });

        // Dropdown filter listeners
        document.getElementById('adminStatusFilter').addEventListener('change', filterFaqs);
        document.getElementById('deletedStatusFilter').addEventListener('change', filterDeletedFaqs);
        document.getElementById('deletedTopicFilter').addEventListener('change', filterDeletedFaqs);

        // Edit mode functions
        function toggleEdit(index) {
            const form = document.getElementById(`faqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const selects = form.querySelectorAll('select');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-item').querySelector('.edit');

            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.add('editing');
            });

            selects.forEach(select => {
                select.disabled = false;
                select.classList.add('editing');
            });

            actions.style.display = 'flex';
            editBtn.innerHTML = '<i class="fas fa-eye"></i>';
            editBtn.onclick = () => cancelEdit(index);
        }

        function cancelEdit(index) {
            const form = document.getElementById(`faqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const selects = form.querySelectorAll('select');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-item').querySelector('.edit');

            inputs.forEach(input => {
                input.readOnly = true;
                input.classList.remove('editing');
            });

            selects.forEach(select => {
                select.disabled = true;
                select.classList.remove('editing');
            });

            actions.style.display = 'none';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.onclick = () => toggleEdit(index);
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

        // Stats modal and charts
        function showStats() {
            document.getElementById('statsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Initialize charts after modal is shown
            setTimeout(() => {
                initializeCharts();
            }, 100);
        }

        function closeStatsModal() {
            document.getElementById('statsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Destroy existing charts
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            charts = {};
        }

        function initializeCharts() {
            // Destroy existing charts first
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });

            // Status Pie Chart
            const pieCtx = document.getElementById('statusPieChart');
            if (pieCtx) {
                charts.pie = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Resolved', 'Unsolved'],
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
                                label: 'Unsolved',
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

            // Monthly Line Chart (if data available)
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
                                label: 'Unsolved',
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

        // Add form loader
        document.getElementById('addFaqForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Adding...</span>';
            submitBtn.disabled = true;
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const deleteModal = document.getElementById('deleteModal');
            const statsModal = document.getElementById('statsModal');
            const viewDeletedModal = document.getElementById('viewDeletedModal');
            if (e.target === deleteModal) closeDeleteModal();
            if (e.target === statsModal) closeStatsModal();
            if (e.target === viewDeletedModal) closeViewDeletedModal();
        });

        // Close modals with Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeStatsModal();
                closeViewDeletedModal();
            }
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
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

function removeAttachment(btn) {
    const wrapper = btn.closest('.current-attachment-wrapper');
    const fileInput = wrapper.parentElement.querySelector('input[type="file"]');
    const hiddenInput = wrapper.querySelector('input[name="remove_existing_attachment"]');

    // Mark that the current attachment should be removed BEFORE removing the wrapper
    if (hiddenInput) {
        hiddenInput.value = "1";
        // Move the hidden input outside the wrapper so it doesn't get removed
        wrapper.parentElement.appendChild(hiddenInput);
    }

    // Remove the current display
    wrapper.remove();

    // Enable the file input
    if (fileInput) {
        fileInput.disabled = false;
        fileInput.style.display = "block";
    }
}

function toggleEditAdmin(index) {
    const form = document.getElementById(`adminFaqForm${index}`);
    const inputs = form.querySelectorAll('input[type="text"], textarea');
    const selects = form.querySelectorAll('select');
    const actions = form.querySelector('.form-actions');
    const editBtn = form.closest('.faq-item').querySelector('.edit');

    inputs.forEach(input => {
        input.readOnly = false;
        input.classList.add('editing');
    });

    selects.forEach(select => {
        select.disabled = false;
        select.classList.add('editing');
    });

    actions.style.display = 'flex';
    editBtn.innerHTML = '<i class="fas fa-eye"></i>';
    editBtn.onclick = () => cancelEditAdmin(index);
}

function cancelEditAdmin(index) {
    location.reload(); // Simple reload is easiest for now
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

function viewDeletedAdminFaq(question, answer) {
    document.getElementById('adminDeletedQuestion').textContent = question;
    document.getElementById('adminDeletedAnswer').textContent = answer;
    document.getElementById('viewDeletedAdminModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeViewDeletedAdminModal() {
    document.getElementById('viewDeletedAdminModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

    const attachmentInput = document.getElementById('faqAttachments');
    const previewList = document.getElementById('filePreviewList');

    attachmentInput.addEventListener('change', function () {
        previewList.innerHTML = ''; // Clear list on new selection

        Array.from(attachmentInput.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-preview-item');
            fileItem.innerHTML = `
                ${file.name} 
                <button type="button" class="remove-btn" onclick="removeFile(${index})">X</button>
            `;
            previewList.appendChild(fileItem);
        });
    });

    function removeFile(index) {
        const dt = new DataTransfer();
        const currentFiles = attachmentInput.files;

        for (let i = 0; i < currentFiles.length; i++) {
            if (i !== index) dt.items.add(currentFiles[i]);
        }

        attachmentInput.files = dt.files;
        attachmentInput.dispatchEvent(new Event('change')); // Refresh preview
    }

</script>
<script>
    history.pushState(null, "", location.href);
    window.onpopstate = function () {
    history.pushState(null, "", location.href);
};
</script>

<script>
// Notification System
class NotificationManager {
    constructor() {
        this.isOpen = false;
        this.pollInterval = 5000; // 5 seconds
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
        
        if (!bell) return; // Exit if elements don't exist
        
        // Toggle dropdown
        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Close dropdown when clicking outside
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
                    <p>No new notifications</p>
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

// Initialize notification manager when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
});
</script>
</body>
</html>