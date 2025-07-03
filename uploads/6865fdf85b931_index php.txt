<?php
require_once 'includes/db.php';

session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}


$stmt = $pdo->query("SELECT * FROM faqs ORDER BY id DESC");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="index-body">
<div class="index-background">
    <!-- Header Section -->
    <div class="index-header">
        <div class="index-header-content">
            <div class="index-brand">
                <div class="brand-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="brand-info">
                    <h1 class="brand-title">FAQ Center</h1>
                    <p class="brand-subtitle">Find answers to frequently asked questions</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="mainlogin.php" class="admin-login-btn">
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

                <!-- Scrollable FAQ List (preserving original functionality) -->
                <div class="faq-list-wrapper" id="faqList">
                    <?php if (empty($faqs)): ?>
                        <div class="empty-faq-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No FAQs Available</h3>
                            <p>Check back later for frequently asked questions and answers.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($faqs as $index => $faq): ?>
                            <div class="faq-box modern-faq-item" 
                            data-index="<?= $index ?>" 
                            data-status="<?= strtolower($faq['status']) ?>"
                            data-topic="<?= htmlspecialchars($faq['topic']) ?>"
                            onclick="showModal(
    '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>',
    '<?= htmlspecialchars($faq['answer'], ENT_QUOTES) ?>',
    '<?= htmlspecialchars($faq['attachment'] ?? '', ENT_QUOTES) ?>'
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

    <img src="images/hotdog.png" alt="Hotdog" id="hotdog-float">
    <img src="images/popperz.png" alt="Hotdog" class="hotdog-float hotdog-right">


    <!-- Animated background elements -->
    <div class="bg-animation">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
    </div>
</div>

<!-- Enhanced Modal (preserving original functionality) -->
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
    answerContainer.innerText = answer;

    if (attachment) {
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
        document.body.style.overflow = "auto"; // Restore scrolling
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
</script>
</body>
</html>