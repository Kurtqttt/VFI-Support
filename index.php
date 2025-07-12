<?php
require_once 'includes/db.php'; // Session config included here - LOAD FIRST
session_start(); // Start session AFTER loading configuration

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
        // no hashing for demo
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
    <title>VFI-Support Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <!-- Enhanced Background Animation -->
    <div class="bg-animation">
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
        <div class="floating-orb orb-4"></div>
        <div class="floating-orb orb-5"></div>
        <div class="floating-orb orb-6"></div>
        <div class="floating-orb orb-7"></div>
    </div>

    <!-- Main Login Container -->
    <div class="login-wrapper">
        <!-- Left Side - Brand Section -->
        <div class="login-brand-section">
            <div class="brand-content">
                <div class="brand-icon-large">
                    <i class="fas fa-shield-alt"></i>
                    <div class="icon-glow"></div>
                </div>
                <h1 class="brand-title-large">VFI-Support</h1>
                <p class="brand-subtitle-large">Your trusted support platform</p>
                <div class="brand-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Secure Authentication</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>24/7 Support Access</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Real-time Updates</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-form-section">
            <div class="login-container-modern">
                <div class="login-header-modern">
                    <div class="login-icon-modern">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h2 class="login-title-modern">Welcome Back!</h2>
                    <p class="login-subtitle-modern">Sign in to access your account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message-modern">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="modern-form-login">
                    <div class="form-group-modern">
                        <label for="username" class="form-label-modern">
                            <i class="fas fa-user"></i>
                            <span>Username</span>
                        </label>
                        <div class="input-wrapper-modern">
                            <input type="text" id="username" name="username" 
                                   placeholder="Enter your username" required 
                                   class="form-input-modern">
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label for="password" class="form-label-modern">
                            <i class="fas fa-lock"></i>
                            <span>Password</span>
                        </label>
                        <div class="input-wrapper-modern">
                            <input type="password" id="password" name="password" 
                                   placeholder="Enter your password" required 
                                   class="form-input-modern">
                            <button type="button" class="toggle-password-modern" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="form-actions-modern">
                        <button type="submit" class="btn-login-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Sign In</span>
                            <div class="btn-loading-spinner"></div>
                        </button>
                    </div>
                </form>

                <div class="login-footer">
                    <p class="footer-text">Need help? <a href="#" class="footer-link">Contact Support</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Elements -->
    <img src="images/hotdog.png" alt="Hotdog" id="hotdog-float">
    <img src="images/popperz.png" alt="Popperz" class="hotdog-float hotdog-right">

    <style>
        /* Enhanced Login Styles */
        .login-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 25%, #991b1b 50%, #7f1d1d 75%, #450a0a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 1200px;
            height: 600px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(220, 38, 38, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            position: relative;
            z-index: 10;
        }

        .login-brand-section {
            flex: 1;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.9) 0%, rgba(127, 29, 29, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .login-brand-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .brand-content {
            text-align: center;
            color: white;
            position: relative;
            z-index: 2;
        }

        .brand-icon-large {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            position: relative;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .brand-icon-large i {
            font-size: 3rem;
            color: white;
        }

        .icon-glow {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            animation: glow 3s ease-in-out infinite alternate;
        }

        .brand-title-large {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .brand-subtitle-large {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .feature-item i {
            color: #4ade80;
            font-size: 1.1rem;
        }

        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: rgba(0, 0, 0, 0.05);
        }

        .login-container-modern {
            width: 100%;
            max-width: 400px;
        }

        .login-header-modern {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-icon-modern {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }

        .login-icon-modern i {
            font-size: 2rem;
            color: white;
        }

        .login-title-modern {
            font-size: 2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }

        .login-subtitle-modern {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
        }

        .error-message-modern {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fca5a5;
        }

        .error-message-modern i {
            font-size: 1.1rem;
        }

        .modern-form-login {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group-modern {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-label-modern i {
            color: #dc2626;
        }

        .input-wrapper-modern {
            position: relative;
        }

        .form-input-modern {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input-modern::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-input-modern:focus {
            outline: none;
            border-color: #dc2626;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .input-focus-border {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .form-input-modern:focus + .input-focus-border {
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.3);
        }

        .toggle-password-modern {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .toggle-password-modern:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .form-actions-modern {
            margin-top: 1rem;
        }

        .btn-login-primary {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .btn-login-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login-primary:hover::before {
            left: 100%;
        }

        .btn-login-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4);
        }

        .btn-login-primary:active {
            transform: translateY(0);
        }

        .btn-loading-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn-login-primary.loading .btn-loading-spinner {
            display: block;
        }

        .btn-login-primary.loading span {
            display: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .footer-link {
            color: #dc2626;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: #fca5a5;
        }

        /* Additional Floating Orbs */
        .orb-6 {
            width: 100px;
            height: 100px;
            top: 30%;
            right: 25%;
            animation-delay: -15s;
            animation-duration: 35s;
        }

        .orb-7 {
            width: 70px;
            height: 70px;
            top: 70%;
            right: 30%;
            animation-delay: -20s;
            animation-duration: 28s;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
                height: auto;
                max-width: 500px;
                margin: 2rem;
            }

            .login-brand-section {
                padding: 2rem;
            }

            .brand-title-large {
                font-size: 2.5rem;
            }

            .brand-features {
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                margin: 1rem;
                border-radius: 16px;
            }

            .login-brand-section {
                padding: 1.5rem;
            }

            .brand-title-large {
                font-size: 2rem;
            }

            .brand-subtitle-large {
                font-size: 1rem;
            }

            .login-form-section {
                padding: 2rem;
            }

            .brand-features {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                margin: 0.5rem;
                border-radius: 12px;
            }

            .login-brand-section {
                padding: 1rem;
            }

            .login-form-section {
                padding: 1.5rem;
            }

            .brand-icon-large {
                width: 80px;
                height: 80px;
            }

            .brand-icon-large i {
                font-size: 2rem;
            }

            .brand-title-large {
                font-size: 1.75rem;
            }

            .login-title-modern {
                font-size: 1.5rem;
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
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

        document.querySelector('.modern-form-login').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.btn-login-primary');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input-modern');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>