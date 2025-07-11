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

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$error = '';

// Show timeout message sent from admin.php / user.php
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please log-in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; 

    // Fetch user from DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role']; 

        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: user.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VFI Support - Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <!-- Animated Background -->
    <div class="login-background">
        <div class="bg-animation">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
            <div class="floating-orb orb-3"></div>
            <div class="floating-orb orb-4"></div>
            <div class="floating-orb orb-5"></div>
        </div>

        <!-- Main Login Container -->
        <div class="login-container">
            <!-- Brand Section -->
            <div class="brand-section">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="logo-text">
                        <h1 class="brand-title">VFI Support</h1>
                        <p class="brand-subtitle">Technical Support & FAQ System</p>
                    </div>
                </div>
                <div class="brand-features">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Access</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Support</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Team Collaboration</span>
                    </div>
                </div>
            </div>

            <!-- Login Form Section -->
            <div class="login-form-section">
                <div class="form-container">
                    <div class="form-header">
                        <h2 class="form-title">Welcome Back!</h2>
                        <p class="form-subtitle">Sign in to access your dashboard</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <div class="error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="error-content">
                                <h4>Login Failed</h4>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="login-form" id="loginForm">
                        <div class="form-group">
                            <label for="username" class="form-label">
                                <i class="fas fa-user"></i>
                                Username
                            </label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       placeholder="Enter your username" 
                                       required 
                                       class="form-input"
                                       autocomplete="username">
                                <div class="input-focus-border"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                Password
                            </label>
                            <div class="input-wrapper">
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       placeholder="Enter your password" 
                                       required 
                                       class="form-input"
                                       autocomplete="current-password">
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                                <div class="input-focus-border"></div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="login-btn" id="loginBtn">
                                <span class="btn-text">Sign In</span>
                                <span class="btn-icon">
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                                <span class="btn-loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                    </form>

                    <div class="form-footer">
                        <div class="security-notice">
                            <i class="fas fa-shield-alt"></i>
                            <span>Your session is secured with industry-standard encryption</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="decorative-elements">
            <div class="floating-card card-1">
                <i class="fas fa-question-circle"></i>
                <span>FAQ Support</span>
            </div>
            <div class="floating-card card-2">
                <i class="fas fa-tools"></i>
                <span>Technical Help</span>
            </div>
            <div class="floating-card card-3">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const btnIcon = loginBtn.querySelector('.btn-icon');
            const btnLoader = loginBtn.querySelector('.btn-loader');
            
            // Show loading state
            btnText.style.display = 'none';
            btnIcon.style.display = 'none';
            btnLoader.style.display = 'inline-flex';
            loginBtn.disabled = true;
            loginBtn.classList.add('loading');
        });

        // Input focus animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Add filled class if input has value on page load
            if (input.value !== '') {
                input.parentElement.classList.add('focused');
            }
        });

        // Add floating animation to decorative cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.floating-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.5}s`;
            });
        });

        // Add typing effect to brand title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            type();
        }

        // Initialize typing effect when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const brandTitle = document.querySelector('.brand-title');
            if (brandTitle) {
                typeWriter(brandTitle, 'VFI Support', 150);
            }
        });
    </script>
</body>
</html>