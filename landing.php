<?php
// You can add any PHP logic here for dynamic content
$current_year = date('Y');
$stats = [
    ['number' => '50K+', 'label' => 'Active Users'],
    ['number' => '99.9%', 'label' => 'Uptime'],
    ['number' => '24/7', 'label' => 'Support'],
    ['number' => '150+', 'label' => 'Countries']
];

$features = [
    [
        'icon' => 'fas fa-bolt',
        'title' => 'Lightning Fast',
        'description' => 'Optimized for speed with cutting-edge technology that delivers results in milliseconds.'
    ],
    [
        'icon' => 'fas fa-shield-alt',
        'title' => 'Secure & Reliable',
        'description' => 'Enterprise-grade security with 99.9% uptime guarantee and data protection.'
    ],
    [
        'icon' => 'fas fa-users',
        'title' => 'Team Collaboration',
        'description' => 'Work together seamlessly with real-time collaboration and sharing features.'
    ],
    [
        'icon' => 'fas fa-star',
        'title' => 'Premium Quality',
        'description' => 'Built with attention to detail and tested by thousands of satisfied users.'
    ],
    [
        'icon' => 'fas fa-check-circle',
        'title' => 'Easy Integration',
        'description' => 'Connect with your favorite tools and platforms with our simple API.'
    ],
    [
        'icon' => 'fas fa-headset',
        'title' => '24/7 Support',
        'description' => 'Get help whenever you need it with our dedicated support team.'
    ]
];

$pricing_plans = [
    [
        'name' => 'Starter',
        'price' => '$9',
        'period' => '/month',
        'description' => 'Perfect for individuals getting started',
        'features' => ['Up to 5 projects', 'Basic analytics', 'Email support', '1GB storage'],
        'popular' => false
    ],
    [
        'name' => 'Professional',
        'price' => '$29',
        'period' => '/month',
        'description' => 'Best for growing businesses',
        'features' => ['Unlimited projects', 'Advanced analytics', 'Priority support', '10GB storage', 'Team collaboration', 'API access'],
        'popular' => true
    ],
    [
        'name' => 'Enterprise',
        'price' => '$99',
        'period' => '/month',
        'description' => 'For large organizations',
        'features' => ['Everything in Professional', 'Custom integrations', 'Dedicated support', 'Unlimited storage', 'Advanced security', 'SLA guarantee'],
        'popular' => false
    ]
];

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $first_name = htmlspecialchars($_POST['first_name'] ?? '');
    $last_name = htmlspecialchars($_POST['last_name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    
    // Here you can add email sending logic or database storage
    $success_message = "Thank you for your message! We'll get back to you soon.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModernApp - Build Something Amazing Today</title>
    <meta name="description" content="Transform your ideas into reality with our cutting-edge platform. Join thousands of creators who trust us to bring their vision to life.">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }

        .navbar.scrolled .logo {
            color: #333;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dc2626, #000000);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar.scrolled .nav-links a {
            color: #333;
        }

        .nav-links a:hover {
            color: #dc2626;
        }

        .cta-btn {
            background: linear-gradient(135deg, #dc2626, #000000);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .navbar.scrolled .mobile-menu-btn {
            color: #333;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #dc2626 0%, #000000 50%, #fbbf24 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-bg-elements {
            position: absolute;
            inset: 0;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 { width: 80px; height: 80px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape-2 { width: 120px; height: 120px; top: 60%; right: 10%; animation-delay: 2s; }
        .shape-3 { width: 60px; height: 60px; bottom: 20%; left: 20%; animation-delay: 4s; }
        .shape-4 { width: 100px; height: 100px; top: 10%; right: 30%; animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .hero-content {
            text-align: center;
            color: white;
            max-width: 1200px;
            padding: 0 2rem;
            position: relative;
            z-index: 10;
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 6rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .hero-gradient-text {
            background: linear-gradient(135deg, #fbbf24, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #000000);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: white;
            color: #333;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            opacity: 0.8;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }

        /* Sections */
        .section {
            padding: 5rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            color: #333;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Features Section */
        .features {
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #000000);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }

        .feature-description {
            color: #666;
            line-height: 1.6;
        }

        /* About Section */
        .about {
            background: #f8f9fa;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .about-text p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .about-features {
            list-style: none;
            margin-top: 2rem;
        }

        .about-features li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .about-features i {
            color: #dc2626;
            font-size: 1.2rem;
        }

        .about-visual {
            position: relative;
        }

        .about-card {
            background: linear-gradient(135deg, #dc2626, #000000, #fbbf24);
            border-radius: 20px;
            padding: 2rem;
            aspect-ratio: 1;
        }

        .about-card-inner {
            background: white;
            border-radius: 16px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .about-stat {
            color: #333;
        }

        .about-stat-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626, #000000);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .about-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        /* Pricing Section */
        .pricing {
            background: white;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .pricing-card.popular {
            border-color: #dc2626;
            transform: scale(1.05);
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #dc2626, #000000);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .pricing-description {
            color: #666;
            margin-bottom: 1rem;
        }

        .pricing-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.25rem;
        }

        .pricing-amount {
            font-size: 3rem;
            font-weight: 800;
            color: #333;
        }

        .pricing-period {
            color: #666;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #333;
        }

        .pricing-features i {
            color: #dc2626;
        }

        .pricing-btn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .pricing-btn.primary {
            background: linear-gradient(135deg, #dc2626, #000000);
            color: white;
        }

        .pricing-btn.secondary {
            background: #f8f9fa;
            color: #333;
        }

        .pricing-btn:hover {
            transform: translateY(-2px);
        }

        /* Contact Section */
        .contact {
            background: #1a1a1a;
            color: white;
        }

        .contact .section-title {
            color: white;
        }

        .contact .section-subtitle {
            color: #ccc;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            color: #333;
        }

        .contact-form h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc2626;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc2626, #000000);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .contact-details h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .contact-details p {
            color: #ccc;
            white-space: pre-line;
        }

        .social-links {
            margin-top: 2rem;
        }

        .social-links h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: #333;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: linear-gradient(135deg, #dc2626, #000000);
            color: white;
        }

        /* Footer */
        .footer {
            background: #000;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #dc2626, #fbbf24);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .footer-brand-text {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .footer-description {
            color: #ccc;
            line-height: 1.6;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #333;
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }

        .footer-bottom-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: white;
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .about-content,
            .contact-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card.popular {
                transform: none;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .nav-container {
                padding: 0 1rem;
            }

            .hero-content {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#home" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                VFI-Support
            </a>
            
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="#" class="cta-btn">Get Started</a></li>
            </ul>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-bg-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
        
        <div class="hero-content">
            <h1 class="hero-title">
                Build Something<br>
                <span class="hero-gradient-text">Amazing Today</span>
            </h1>
            <p class="hero-subtitle">
                Transform your ideas into reality with our cutting-edge platform. Join thousands of creators who trust us to bring their vision to life.
            </p>
            
            <div class="hero-buttons">
                <a href="#contact" class="btn-primary">
                    Start Free Trial
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-play"></i>
                    Watch Demo
                </a>
            </div>
            
            <div class="hero-stats">
                <?php foreach ($stats as $stat): ?>
                <div class="stat-item">
                    <div class="stat-number"><?= $stat['number'] ?></div>
                    <div class="stat-label"><?= $stat['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="scroll-indicator">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Powerful Features</h2>
                <p class="section-subtitle">
                    Everything you need to succeed, built with modern technology and designed for performance.
                </p>
            </div>
            
            <div class="features-grid">
                <?php foreach ($features as $feature): ?>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="<?= $feature['icon'] ?>"></i>
                    </div>
                    <h3 class="feature-title"><?= $feature['title'] ?></h3>
                    <p class="feature-description"><?= $feature['description'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Our Mission</h2>
                    <p>
                        We're passionate about creating tools that empower people to achieve their goals. Our platform combines cutting-edge technology with intuitive design to deliver an experience that's both powerful and easy to use.
                    </p>
                    <p>
                        Founded by a team of experienced developers and designers, we understand the challenges of modern digital workflows and have built our solution from the ground up to address them.
                    </p>
                    
                    <ul class="about-features">
                        <li><i class="fas fa-check-circle"></i> Industry-leading performance</li>
                        <li><i class="fas fa-check-circle"></i> User-centric design approach</li>
                        <li><i class="fas fa-check-circle"></i> Continuous innovation</li>
                        <li><i class="fas fa-check-circle"></i> Exceptional customer support</li>
                    </ul>
                </div>
                
                <div class="about-visual">
                    <div class="about-card">
                        <div class="about-card-inner">
                            <div class="about-stat">
                                <div class="about-stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="about-stat-number">50,000+</div>
                                <p>Happy Customers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="section pricing">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Simple Pricing</h2>
                <p class="section-subtitle">
                    Choose the plan that's right for you. All plans include our core features with no hidden fees.
                </p>
            </div>
            
            <div class="pricing-grid">
                <?php foreach ($pricing_plans as $plan): ?>
                <div class="pricing-card <?= $plan['popular'] ? 'popular' : '' ?>">
                    <?php if ($plan['popular']): ?>
                    <div class="popular-badge">Most Popular</div>
                    <?php endif; ?>
                    
                    <div class="pricing-header">
                        <h3 class="pricing-name"><?= $plan['name'] ?></h3>
                        <p class="pricing-description"><?= $plan['description'] ?></p>
                        <div class="pricing-price">
                            <span class="pricing-amount"><?= $plan['price'] ?></span>
                            <span class="pricing-period"><?= $plan['period'] ?></span>
                        </div>
                    </div>
                    
                    <ul class="pricing-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?= $feature ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <a href="#contact" class="pricing-btn <?= $plan['popular'] ? 'primary' : 'secondary' ?>">
                        Get Started
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">
                    Ready to get started? Contact us today and let's discuss how we can help you achieve your goals.
                </p>
            </div>
            
            <div class="contact-content">
                <!-- Contact Form -->
                <div class="contact-form">
                    <h3>Send us a message</h3>
                    
                    <?php if (isset($success_message)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?= $success_message ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="contact_form" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" placeholder="John" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" placeholder="Doe" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="john@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Tell us about your project..." required></textarea>
                        </div>
                        
                        <button type="submit" class="cta-btn" style="width: 100%;">
                            Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Contact Information -->
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p>hello@modernapp.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone</h4>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Address</h4>
                            <p>123 Business St, Suite 100
San Francisco, CA 94105</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h4>Follow Us</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-brand">
                        <div class="footer-brand-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <span class="footer-brand-text">VFI-Support</span>
                    </div>
                    <p class="footer-description">
                        Building the future of digital experiences with cutting-edge technology and innovative design.
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Product</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">API</a></li>
                        <li><a href="#">Documentation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="#about">About</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#">Status</a></li>
                        <li><a href="#">Community</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= $current_year ?> ModernApp. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Form validation and enhancement
        const contactForm = document.querySelector('form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                submitBtn.disabled = true;
            });
        }

        // Add animation classes when elements come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature-card, .pricing-card, .about-text, .about-visual').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>