<?php
/**
 * Homepage - Inventory Management System
 * Professional Landing Page
 */
session_start();

// Don't redirect logged-in users - let them see the homepage
// They can access dashboard through the login button or direct URL
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMS - Inventory Management System</title>
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Import Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e40af;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .nav-menu a:hover {
            color: #1e40af;
        }

        .login-btn {
            background: linear-gradient(135deg, #1e40af, #fbbf24);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 30%, #fbbf24 70%, #f59e0b 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .welcome-message {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .welcome-message i {
            color: #10b981;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: #1e40af;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: white;
            color: #1e40af;
        }

        .hero-image {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-graphic {
            width: 100%;
            max-width: 500px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position: relative;
        }

        .hero-graphic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
            opacity: 0.9;
        }

        .hero-graphic i {
            position: absolute;
            font-size: 8rem;
            color: rgba(255, 255, 255, 0.8);
            animation: pulse 2s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Image Gallery Styles */
        .gallery-section {
            padding: 5rem 0;
            background: #f8fafc;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 3rem;
        }

        .gallery-item {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: white;
        }

        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: contain;
            background: #f8fafc;
            padding: 1rem;
        }

        /* Product Showcase */
        .product-showcase {
            padding: 5rem 0;
            background: white;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .product-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 80%;
            height: 80%;
            object-fit: contain;
        }

        .product-info {
            padding: 1.5rem;
            text-align: center;
        }

        .product-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .product-info p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .features {
            padding: 5rem 0;
            background: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #6b7280;
            font-weight: normal;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e40af, #fbbf24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
        }

        .feature-icon i {
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #6b7280;
            line-height: 1.6;
        }

        /* About Section */
        .about {
            padding: 5rem 0;
            background: white;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .about-text p {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .about-image {
            display: flex;
            justify-content: center;
        }

        .about-graphic {
            width: 100%;
            max-width: 400px;
            height: 300px;
            background: linear-gradient(135deg, #1e40af, #fbbf24);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .about-graphic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
            opacity: 0.8;
        }

        .about-graphic i {
            position: absolute;
            font-size: 6rem;
            color: white;
            z-index: 2;
        }

        /* FAQ Section */
        .faq {
            padding: 5rem 0;
            background: #f8fafc;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            margin-bottom: 1rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .faq-question {
            padding: 1.5rem;
            background: white;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: #f8fafc;
        }

        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-answer.active {
            padding: 1.5rem;
            max-height: 200px;
        }

        .faq-answer p {
            color: #6b7280;
            line-height: 1.6;
        }

        /* Contact Section */
        .contact {
            padding: 5rem 0;
            background: white;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1e40af, #fbbf24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .contact-form {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 15px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1f2937;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e40af;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 0 1rem 0;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #fbbf24;
        }

        .footer-section p,
        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            line-height: 1.8;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 1rem;
            text-align: center;
            color: #9ca3af;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-menu {
                display: none;
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .contact-content {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                justify-content: center;
            }
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Section Spacing */
        section {
            scroll-margin-top: 80px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-boxes"></i>
                <span>IMS</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#products">Products</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#faq">FAQ</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'admin/dashboard.php' : 'auth/login.php'; ?>" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="welcome-message">
                        <i class="fas fa-user-check"></i>
                        <span>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    </div>
                <?php endif; ?>
                
                <h1>Professional Inventory Management</h1>
                <p>Streamline your business operations with our comprehensive inventory management system. Track products, manage suppliers, and generate detailed reports with ease.</p>
                <div class="hero-buttons">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'admin/dashboard.php' : 'auth/login.php'; ?>" class="btn-primary">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                    <a href="#features" class="btn-secondary">
                        <i class="fas fa-play"></i> Learn More
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-graphic">
                    <img src="assets/images/homepage/store.jpg" alt="Modern Inventory Management">
                    <i class="fas fa-warehouse"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Showcase Section -->
    <section class="product-showcase" id="products">
        <div class="container">
            <div class="section-title">
                <h2>Sample Products in Our Company</h2>
                <p>See how your products will look in our professional inventory management platform</p>
            </div>
            <div class="product-grid">
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/elegant-modern-laptop-open-on-a-wooden-desk-in-soft-light-free-photo.jpg" alt="Modern Laptop">
                    </div>
                    <div class="product-info">
                        <h4>Dell Inspiron Laptop</h4>
                        <p>High-performance laptop for business use</p>
                    </div>
                </div>
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/smartphone-12-pro-mockups-free-photo.jpg" alt="Smartphone">
                    </div>
                    <div class="product-info">
                        <h4>iPhone 12 Pro</h4>
                        <p>Latest smartphone technology</p>
                    </div>
                </div>
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/headphones-on-white-background-free-photo.jpg" alt="Headphones">
                    </div>
                    <div class="product-info">
                        <h4>Wireless Headphones</h4>
                        <p>Premium audio experience</p>
                    </div>
                </div>
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/rgb-gaming-mouse-black-white-on-transparent-background-png.png" alt="Gaming Mouse">
                    </div>
                    <div class="product-info">
                        <h4>RGB Gaming Mouse</h4>
                        <p>Professional gaming peripheral</p>
                    </div>
                </div>
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/slim-thai-and-english-keyboard-isolated-on-white-background-png.png" alt="Keyboard">
                    </div>
                    <div class="product-info">
                        <h4>Wireless Keyboard</h4>
                        <p>Ergonomic design for productivity</p>
                    </div>
                </div>
                <div class="product-item">
                    <div class="product-image">
                        <img src="assets/images/products/a-3d-monitor-displayed-on-a-transparent-background-free-png.png" alt="Monitor">
                    </div>
                    <div class="product-info">
                        <h4>4K Monitor</h4>
                        <p>Ultra-high definition display</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Gallery Section -->
    <section class="gallery-section">
        <div class="container">
            <div class="section-title">
                <h2>Our Product Categories</h2>
                <p>Manage diverse inventory with our comprehensive system</p>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="assets/images/products/elegant-modern-laptop-open-on-a-wooden-desk-in-soft-light-free-photo.jpg" alt="Laptops & Computers">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/smartphone-12-pro-mockups-free-photo.jpg" alt="Mobile Devices">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/headphones-on-white-background-free-photo.jpg" alt="Audio Equipment">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/rgb-gaming-mouse-black-white-on-transparent-background-png.png" alt="Gaming Accessories">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/a-3d-monitor-displayed-on-a-transparent-background-free-png.png" alt="Displays & Monitors">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/wireless-earbuds-in-charging-case-on-dark-background-photo.jpg" alt="Wireless Audio">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/slim-thai-and-english-keyboard-isolated-on-white-background-png.png" alt="Input Devices">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/products/vintage-computer-tower-display-isolated-free-png.png" alt="Computer Hardware">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-title">
                <h2>Powerful Features</h2>
                <p>Everything you need to manage your inventory efficiently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Product Management</h3>
                    <p>Easily add, edit, and organize your products with detailed information, categories, and images.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time Analytics</h3>
                    <p>Get instant insights with comprehensive reports and analytics to make informed business decisions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Low Stock Alerts</h3>
                    <p>Never run out of stock with automatic notifications when inventory levels reach minimum thresholds.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi-User Access</h3>
                    <p>Collaborate with your team using role-based access control for administrators and staff members.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Supplier Management</h3>
                    <p>Maintain detailed supplier information and track purchase history for better vendor relationships.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Responsive</h3>
                    <p>Access your inventory system from any device with our fully responsive web application.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Inventory Pro</h2>
                    <p>We are dedicated to providing businesses with the most efficient and user-friendly inventory management solution. Our system is designed to help companies of all sizes streamline their operations and improve productivity.</p>
                    <p>With years of experience in business management software, we understand the challenges that businesses face in managing their inventory. That's why we've created a comprehensive solution that addresses all your inventory needs.</p>
                    <div class="about-stats">
                        <div class="stat-item">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Happy Clients</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">5★</div>
                            <div class="stat-label">Rating</div>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="about-graphic">
                        <img src="assets/images/homepage/store1.jpg" alt="Business Analytics">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq">
        <div class="container">
            <div class="section-title">
                <h2>Frequently Asked Questions</h2>
                <p>Find answers to common questions about our inventory management system</p>
            </div>
            <div class="faq-container">
                <div class="faq-item">
                    <button class="faq-question">
                        How do I get started with Inventory Pro?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Getting started is easy! Simply click the "Get Started" button, create your account, and you'll have immediate access to our inventory management system. Our intuitive interface makes it easy to add your first products and start managing your inventory right away.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        Can multiple users access the system?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! Our system supports multiple users with different access levels. You can create admin accounts with full access and staff accounts with limited permissions, ensuring your team can collaborate effectively while maintaining security.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        Is my data secure?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Absolutely! We use industry-standard security measures including encrypted passwords, secure database connections, and regular security updates to ensure your business data is always protected.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        Can I export my inventory data?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, you can export your inventory data in various formats including CSV and PDF. This makes it easy to create backups, share reports, or integrate with other business systems.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">
                        Do you provide customer support?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We provide comprehensive customer support through multiple channels. You can reach us via email, phone, or our contact form. Our support team is dedicated to helping you get the most out of our system.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-title">
                <h2>Contact Us</h2>
                <p>Get in touch with our team for support or inquiries</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <strong>Address</strong><br>
                            Aksum, Tigray, Ethiopia 🇪🇹
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <strong>Phone</strong><br>
                            +251949802587
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <strong>Email</strong><br>
                            support@inventoryPro.com
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <strong>Business Hours</strong><br>
                            Mon - Fri: 9:00 AM - 6:00 PM
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" class="btn-primary" id="submitBtn" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                        <div id="contactMessage" style="margin-top: 15px; padding: 10px; border-radius: 8px; display: none;"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>IMS</h3>
                    <p>Professional inventory management system designed to streamline your business operations and improve efficiency.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="#home">Home</a></p>
                    <p><a href="#products">Products</a></p>
                    <p><a href="#about">About Us</a></p>
                    <p><a href="#features">Features</a></p>
                    <p><a href="#faq">FAQ</a></p>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <p><a href="#contact">Contact Us</a></p>
                    <p><a href="auth/login.php">Login</a></p>
                    <p><a href="#">Documentation</a></p>
                    <p><a href="#">Help Center</a></p>
                </div>
                <div class="footer-section">
                    <h3>Connect</h3>
                    <p><a href="#">Facebook</a></p>
                    <p><a href="#">Twitter</a></p>
                    <p><a href="#">LinkedIn</a></p>
                    <p><a href="#">Instagram</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 IMS (Inventory Management System). All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const answer = button.nextElementSibling;
                const icon = button.querySelector('i');
                
                // Close all other FAQ items
                document.querySelectorAll('.faq-answer').forEach(item => {
                    if (item !== answer) {
                        item.classList.remove('active');
                    }
                });
                
                document.querySelectorAll('.faq-question i').forEach(item => {
                    if (item !== icon) {
                        item.style.transform = 'rotate(0deg)';
                    }
                });
                
                // Toggle current FAQ item
                answer.classList.toggle('active');
                icon.style.transform = answer.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
            });
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

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('contactMessage');
            const form = this;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            messageDiv.style.display = 'none';
            
            // Get form data
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch('contact_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Show message
                messageDiv.style.display = 'block';
                messageDiv.innerHTML = data.message;
                
                if (data.success) {
                    messageDiv.style.background = 'linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%)';
                    messageDiv.style.color = '#065f46';
                    messageDiv.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                    
                    // Reset form on success
                    form.reset();
                } else {
                    messageDiv.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%)';
                    messageDiv.style.color = '#991b1b';
                    messageDiv.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                }
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Show error message
                messageDiv.style.display = 'block';
                messageDiv.innerHTML = 'Sorry, there was an error sending your message. Please try again later.';
                messageDiv.style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%)';
                messageDiv.style.color = '#991b1b';
                messageDiv.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });
    </script>
</body>
</html>
