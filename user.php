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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Enhanced session check
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, question, answer, status, topic, attachment FROM faqs WHERE visibility = 'user' ORDER BY id DESC");
$stmt->execute();
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ Center - Modern Interface</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="index-body">
<div class="index-background">
    <!-- Animated Background Elements -->
    <div class="bg-animation">
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
        <div class="floating-orb orb-4"></div>
        <div class="floating-orb orb-5"></div>
    </div>

    <!-- Header Section -->
    <div class="index-header">
        <div class="index-header-content">
            <div class="index-brand">
                <div class="brand-icon">
                    <i class="fas fa-question-circle"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="brand-info">
                    <h1 class="brand-title">FAQ Center</h1>
                    <p class="brand-subtitle">Find answers to frequently asked questions</p>
                </div>
            </div>
            <div class="header-actions">
                <!-- User Notification Bell -->
                <div class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4><i class="fas fa-bell"></i> Updates</h4>
                            <button class="mark-all-read" onclick="markAllAsRead()">
                                <i class="fas fa-check-double"></i> Mark all read
                            </button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <!-- Notifications will be loaded here -->
                        </div>
                    </div>
                </div>

                <a href="index.php" class="admin-login-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="index-container">
        <!-- Search Section -->
        <div class="search-card">
            <div class="search-header">
                <h2 class="search-title">
                    <i class="fas fa-search"></i>
                    Search FAQs
                </h2>
                <p class="search-description">Type your question or keywords to find relevant answers</p>
            </div>
            <div class="search-content">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-input-icon"></i>
                    <input type="text" id="faqSearch" onkeyup="filterFAQs()" placeholder="Search FAQs..." class="modern-search-input">
                    <select id="statusFilter" onchange="filterFAQs()" class="modern-filter-dropdown">
                        <option value="all">All Status</option>
                        <option value="resolved">Resolved</option>
                        <option value="not resolved">Unresolved</option>
                    </select>
                    <select id="topicFilter" onchange="filterFAQs()" class="modern-filter-dropdown">
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
                    <div class="search-results-count" id="resultsCount">
                        <?= count($faqs) ?> FAQs available
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ List Section -->
        <div class="faq-card">
            <div class="faq-card-header">
                <h2 class="faq-card-title">
                    <i class="fas fa-list"></i>
                    Frequently Asked Questions
                    <span class="total-count">(<?= count($faqs) ?> total)</span>
                </h2>
            </div>
            
            <div class="faq-card-content">
                <div id="no-results" class="no-results-message" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h3>No matching FAQs found</h3>
                    <p>Try adjusting your search terms or browse all available FAQs below.</p>
                </div>

                <!-- Scrollable FAQ List -->
                <div class="faq-list-wrapper" id="faqList">
                    <?php if (empty($faqs)): ?>
                        <div class="empty-faq-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No FAQs Available</h3>
                            <p>Check back later for frequently asked questions and answers.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($faqs as $index => $faq): ?>
                            <?php 
                                // Handle both JSON format (legacy) and string format (current)
                                $firstAttachment = '';
                                if (!empty($faq['attachment'])) {
                                    $decoded = json_decode($faq['attachment'], true);
                                    if (is_array($decoded) && !empty($decoded)) {
                                        $firstAttachment = $decoded[0]; // Legacy JSON format
                                    } else {
                                        $firstAttachment = $faq['attachment']; // Current string format
                                    }
                                }
                            ?>
                            <div class="faq-box modern-faq-item" 
                                data-index="<?= $index ?>" 
                                data-status="<?= strtolower($faq['status']) ?>"
                                data-topic="<?= htmlspecialchars($faq['topic']) ?>"
                                onclick="showModal(
                                    '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($faq['answer'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($firstAttachment, ENT_QUOTES) ?>'
                                )">
                                <div class="faq-item-content">
                                    <div class="faq-question">
                                        <h3><?= htmlspecialchars($faq['question']) ?></h3>
                                        <div class="faq-preview">
                                            <?= htmlspecialchars(mb_strimwidth($faq['answer'], 0, 100, '...')) ?>
                                        </div>
                                    </div>
                                    <div class="faq-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                                <div class="faq-item-footer">
                                    <span class="faq-id">#<?= $faq['id'] ?></span>

                                    <?php if (!empty($faq['topic'])): ?>
                                        <span class="faq-tag"><?= htmlspecialchars($faq['topic']) ?></span>
                                    <?php endif; ?>

                                    <span class="faq-status <?= $faq['status'] === 'resolved' ? 'status-resolved' : 'status-unsolved' ?>">
                                        <?= ucfirst($faq['status']) ?>
                                    </span>
                                    <span class="click-hint">Click to read full answer</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Decorative Elements -->
    <img src="images/hotdog.png" alt="Hotdog" id="hotdog-float">
    <img src="images/popperz.png" alt="Hotdog" class="hotdog-float hotdog-right">
</div>

<!-- Enhanced Modal -->
<div id="faq-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-question">
                <i class="fas fa-question-circle"></i>
                <span></span>
            </h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modal-answer" class="modal-body-scrollable"></div>
        </div>
        <div class="modal-footer">
            <button class="modern-btn secondary" onclick="closeModal()">
                <i class="fas fa-times"></i>
                Close
            </button>
        </div>
    </div>
</div>

<script>
    let totalFaqs = <?= count($faqs) ?>;

    function showModal(question, answer, attachment) {
        document.getElementById("modal-question").querySelector('span').textContent = question;

        const answerContainer = document.getElementById("modal-answer");

        // Clear previous content
        answerContainer.innerHTML = '';

        // Add the answer as a paragraph
        const para = document.createElement('p');
        para.textContent = answer;
        answerContainer.appendChild(para);

        // If attachment exists, show it
        if (attachment && attachment.trim() !== "") {
            const link = document.createElement('a');
            link.href = attachment;
            link.target = "_blank";
            link.className = "attachment-link";
            link.innerHTML = `<i class="fas fa-paperclip"></i> View Attachment`;

            const wrapper = document.createElement('div');
            wrapper.appendChild(link);
            wrapper.style.marginTop = "10px";

            answerContainer.appendChild(wrapper);
        }

        document.getElementById("faq-modal").style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        document.getElementById("faq-modal").style.display = "none";
        document.body.style.overflow = "auto";
    }

    function filterFAQs() {
        const query = document.getElementById("faqSearch").value.toLowerCase();
        const statusFilter = document.getElementById("statusFilter").value;
        const topicFilter = document.getElementById("topicFilter").value;
        const faqs = document.querySelectorAll(".faq-box");
        const resultsCount = document.getElementById("resultsCount");
        const noResults = document.getElementById("no-results");
        let found = 0;

        faqs.forEach(faq => {
            const question = faq.querySelector("h3").textContent.toLowerCase();
            const preview = faq.querySelector(".faq-preview").textContent.toLowerCase();
            const status = faq.getAttribute('data-status');
            const topic = faq.getAttribute('data-topic');

            const matchesText = question.includes(query) || preview.includes(query);
            const matchesStatus = (statusFilter === "all" || status === statusFilter);
            const matchesTopic = (topicFilter === "all" || topic === topicFilter);

            if (matchesText && matchesStatus && matchesTopic) {
                faq.style.display = "block";
                found++;
            } else {
                faq.style.display = "none";
            }
        });

        if (query.trim() === "" && statusFilter === "all" && topicFilter === "all") {
            resultsCount.textContent = `${faqs.length} FAQs available`;
            noResults.style.display = "none";
        } else {
            resultsCount.textContent = `${found} result${found !== 1 ? 's' : ''} found`;
            noResults.style.display = found === 0 ? "block" : "none";
        }
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('faq-modal');
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Add loading animation to search
    document.getElementById('faqSearch').addEventListener('input', function() {
        const searchIcon = document.querySelector('.search-input-icon');
        searchIcon.classList.add('fa-spin');
        
        setTimeout(() => {
            searchIcon.classList.remove('fa-spin');
        }, 300);
    });

    // Add hover effects to FAQ items
    document.querySelectorAll('.faq-box').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const arrow = this.querySelector('.faq-arrow i');
            if (arrow) {
                arrow.classList.remove('fa-chevron-right');
                arrow.classList.add('fa-arrow-right');
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const arrow = this.querySelector('.faq-arrow i');
            if (arrow) {
                arrow.classList.remove('fa-arrow-right');
                arrow.classList.add('fa-chevron-right');
            }
        });
    });

    // Prevent going back after logout
    history.pushState(null, document.title, location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, document.title, location.href);
    });

    // Session Keep-Alive System for Users
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

    // Start timers when page loads
    document.addEventListener('DOMContentLoaded', function() {
        startSessionTimer();
        resetActivityTimer();
    });

    // Keep session alive when page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshSession();
        }
    });

    // User Notification System
    class UserNotificationManager {
        constructor() {
            this.isOpen = false;
            this.pollInterval = 8000; // 8 seconds for users (less frequent than admin)
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.startPolling();
            this.loadNotifications();
        }
        
        bindEvents() {
            const bell = document.getElementById('notificationBell');
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
                const list = document.getElementById('notificationList');
                if (list) {
                    list.innerHTML = `
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    `;
                }
                
                const response = await fetch('notifications.php?action=get');
                const data = await response.json();
                
                if (data.notifications) {
                    this.renderNotifications(data.notifications);
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                const list = document.getElementById('notificationList');
                if (list) {
                    list.innerHTML = `
                        <div class="notification-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Failed to load notifications</p>
                        </div>
                    `;
                }
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
                        <h3>No new updates</h3>
                        <p>You're all caught up!</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" onclick="markAsRead(${notification.id})">
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
                'faq_added': 'fa-plus-circle',
                'faq_updated': 'fa-edit',
                'faq_deleted': 'fa-trash',
                'faq_restored': 'fa-undo',
                'system_alert': 'fa-exclamation-triangle',
                'new_faq': 'fa-question-circle'
            };
            return icons[type] || 'fa-info-circle';
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
            
            if (window.userNotificationManager) {
                userNotificationManager.loadNotifications();
                userNotificationManager.updateBadge();
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
            
            if (window.userNotificationManager) {
                userNotificationManager.loadNotifications();
                userNotificationManager.updateBadge();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    // Initialize user notification manager
    window.userNotificationManager = new UserNotificationManager();
</script>
</body>
</html>
