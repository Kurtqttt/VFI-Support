<?php

require 'auth.php';
require 'includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $stmt = $pdo->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
        $stmt->execute([$q, $a]);
    }
    
    if ($_POST['action'] === 'update') {
        $id = $_POST['id'];
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
        $stmt->execute([$q, $a, $id]);
    }
    
    if ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    header("Location: admin.php");
    exit;
}

// Load all FAQs
$stmt = $pdo->query("SELECT * FROM faqs ORDER BY id DESC");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage FAQs</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <button class="stats-btn" onclick="showStats()">
                        <i class="fas fa-chart-bar"></i>
                        <span>Stats</span>
                    </button>
                    <form method="get" action="logout.php" style="display: inline;">
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-container">
            <!-- Add FAQ Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Add New FAQ
                    </h2>
                </div>
                <div class="card-content">
                    <form method="post" class="add-faq-form" id="addFaqForm">
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
                        
                        <button type="submit" name="action" value="add" class="modern-btn primary">
                            <i class="fas fa-plus"></i>
                            <span>Add FAQ</span>
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
                                <div class="faq-item" data-index="<?= $index ?>">
                                    <div class="faq-item-header">
                                        <span class="faq-number">#<?= $faq['id'] ?></span>
                                        <div class="faq-actions">
                                            <button type="button" class="action-btn edit" onclick="toggleEdit(<?= $index ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn delete" onclick="confirmDelete(<?= $faq['id'] ?>, '<?= htmlspecialchars($faq['question'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <form method="post" class="faq-form" id="faqForm<?= $index ?>">
                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Concern</label>
                                            <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>" class="modern-input" readonly required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Details</label>
                                            <textarea name="answer" class="modern-textarea" readonly required><?= htmlspecialchars($faq['answer']) ?></textarea>
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
        </div>

        <!-- Animated background elements -->
        <div class="bg-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>

    <!-- Enhanced Delete Confirmation Modal -->
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

    <!-- Stats Modal -->
    <div id="statsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chart-bar"></i> FAQ Statistics</h3>
                <button class="close-modal" onclick="closeStatsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?= count($faqs) ?></h4>
                            <p>Total FAQs</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?= htmlspecialchars($_SESSION['user']) ?></h4>
                            <p>Admin User</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h4><?= date('H:i') ?></h4>
                            <p>Current Time</p>
                        </div>
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
        let deleteId = null;
        let deleteQuestion = '';

        // Search functionality
        document.getElementById('adminSearch').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const faqs = document.querySelectorAll('.faq-item');
            let visibleCount = 0;
            
            faqs.forEach(faq => {
                const question = faq.querySelector('input[name="question"]').value.toLowerCase();
                const answer = faq.querySelector('textarea[name="answer"]').value.toLowerCase();
                
                if (question.includes(query) || answer.includes(query)) {
                    faq.style.display = 'block';
                    visibleCount++;
                } else {
                    faq.style.display = 'none';
                }
            });
            
            // Update count
            const countElement = document.querySelector('.faq-count');
            if (countElement) {
                countElement.textContent = `(${visibleCount} items)`;
            }
        });

        // Edit functionality
        function toggleEdit(index) {
            const form = document.getElementById(`faqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-item').querySelector('.edit');
            
            inputs.forEach(input => {
                input.readOnly = false;
                input.classList.add('editing');
            });
            
            actions.style.display = 'flex';
            editBtn.innerHTML = '<i class="fas fa-eye"></i>';
            editBtn.onclick = () => cancelEdit(index);
        }

        function cancelEdit(index) {
            const form = document.getElementById(`faqForm${index}`);
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.closest('.faq-item').querySelector('.edit');
            
            inputs.forEach(input => {
                input.readOnly = true;
                input.classList.remove('editing');
            });
            
            actions.style.display = 'none';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.onclick = () => toggleEdit(index);
            
            // Reset form to original values
            form.reset();
            location.reload(); // Simple way to reset form values
        }

        // Enhanced Delete functionality
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

        // Stats functionality
        function showStats() {
            document.getElementById('statsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeStatsModal() {
            document.getElementById('statsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Form submission animation
        document.getElementById('addFaqForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Adding...</span>';
            submitBtn.disabled = true;
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const deleteModal = document.getElementById('deleteModal');
            const statsModal = document.getElementById('statsModal');
            
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
            if (e.target === statsModal) {
                closeStatsModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeStatsModal();
            }
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Add search animation
        document.getElementById('adminSearch').addEventListener('input', function() {
            const searchIcon = document.querySelector('.search-icon');
            searchIcon.classList.add('fa-spin');
            
            setTimeout(() => {
                searchIcon.classList.remove('fa-spin');
            }, 300);
        });
    </script>
</body>
</html>
