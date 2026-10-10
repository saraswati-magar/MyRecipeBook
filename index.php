<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to My Recipe Book</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 100%;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #ffffff;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Top Navigation Bar */
        .landing-nav {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 3rem;
            z-index: 100;
            background: linear-gradient(to bottom, rgba(15,23,42,0.85), rgba(15,23,42,0));
        }

        .landing-logo {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-outline {
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: #ffffff;
            background: transparent;
        }

        .btn-outline:hover {
            background: #ffffff;
            color: #0f172a;
            border-color: #ffffff;
        }

        .btn-solid {
            background: #6366f1;
            color: #ffffff;
            border: 2px solid #6366f1;
        }

        .btn-solid:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        /* Hero Section - Exact Full Viewport (100vh) */
        .hero-section {
            position: relative;
            width: 100%;
            height: 100vh;
            background: url('https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0 20px;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.75) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.2rem;
            color: #cbd5e1;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-cta {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
        }

        /* About Us Section - Full-Width Edge-to-Edge, Connected Directly Below */
        .about-section {
            width: 100%;
            background: #1e293b;
            padding: 90px 80px;
            color: #cbd5e1;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .about-content-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            text-align: left;
        }

        .about-section h2 {
            color: #ffffff;
            font-size: 2.5rem;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .about-section p {
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        /* Footer */
        .site-footer {
            width: 100%;
            background: #070a10;
            color: #64748b;
            padding: 35px 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-content h1 { font-size: 2.3rem; }
            .about-section { padding: 60px 20px; }
            .landing-nav { padding: 1rem 1.5rem; }
            .hero-cta { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <header class="landing-nav">
        <a href="index.php" class="landing-logo">🍳 My Recipe Book</a>
        <div class="nav-buttons">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-solid">Go to Dashboard →</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Log In</a>
                <a href="register.php" class="btn btn-solid">Sign Up</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Hero Landing Section (Takes full 100vh screen) -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Your Personal Digital Culinary Vault</h1>
            <p>Welcome to <strong>My Recipe Book</strong>—the ultimate platform designed for cooking enthusiasts to store family recipes, instantly scale ingredients for any serving size, and digitize your cooking journey securely.</p>
            
            <div class="hero-cta">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-solid" style="padding: 0.85rem 2.5rem; font-size: 1.05rem;">Enter Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-solid" style="padding: 0.85rem 2.5rem; font-size: 1.05rem;">Get Started Free</a>
                    <a href="login.php" class="btn btn-outline" style="padding: 0.85rem 2.5rem; font-size: 1.05rem;">Log In to Account</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Us Section Connected Directly Below -->
    <section class="about-section">
        <div class="about-content-wrapper">
            <h2>About Us & The Website</h2>
            <p><strong>My Recipe Book</strong> is a private, secure web application designed to help cooking enthusiasts organize, manage, and scale their favorite recipes in one centralized digital space.</p>
            <p>Developed as a comprehensive web project using PHP, MySQL, HTML, CSS, and JavaScript, the platform focuses on robust user authentication, relational database architecture, and a seamless user experience for managing culinary collections.</p>
            <p>Whether you are saving family traditions or experimenting with new dishes, our platform provides a reliable and responsive toolset for your everyday cooking needs.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 My Recipe Book. All rights reserved. Built with PHP & MySQL.</p>
    </footer>

</body>
</html>