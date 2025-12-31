<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('db.php');

$website_role = $_SESSION['website_role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us | Club Management</title>
<style>
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: 'Arial', sans-serif; display: flex; flex-direction: column; min-height: 100vh; background: #f0f4f8; }

    /* NAVBAR */
    nav { background-color: #1f2937; display: flex; justify-content: space-between; align-items: center; padding: 10px 30px; }
    nav .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
    nav ul { list-style: none; display: flex; gap: 15px; }
    nav ul li a { color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 5px; transition: background 0.3s; }
    nav ul li a:hover { background-color: #2563eb; }

    .hero { text-align: center; padding: 80px 20px; background: linear-gradient(to right, #2563eb, #3b82f6); color: white; margin-bottom: 40px; }
    .hero h1 { font-size: 40px; margin-bottom: 15px; }
    .hero p { font-size: 20px; }

    /* ABOUT CONTENT */
    .about-section { max-width: 900px; margin: 0 auto 60px; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .about-section h2 { color: #1e3a8a; margin-bottom: 20px; text-align: center; }
    .about-section p { margin-bottom: 15px; color: #374151; font-size: 18px; line-height: 1.6; }

    .about-section ul { margin-left: 20px; margin-bottom: 20px; }
    .about-section ul li { margin-bottom: 10px; }

    /* FOOTER */
    footer { text-align: center; padding: 20px; background-color: #1f2937; color: #fff; margin-top: auto; }

    @media(max-width:768px){ 
        .hero h1{ font-size:32px;} 
        .hero p{font-size:16px;} 
        .about-section p{font-size:16px;} 
    }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
    <a href="home.php" class="logo">Club Management</a>
    <ul>
        <li><a href="events.php">Events</a></li>
        <li><a href="resources.php">Resources</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if($website_role==='admin'): ?>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php elseif($website_role==='user'): ?>
            <li><a href="user_dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- HERO -->
<section class="hero">
    <h1>About Our Club</h1>
    <p>Connecting Members, Organizing Events, and Building a Strong Community</p>
</section>

<!-- ABOUT CONTENT -->
<section class="about-section">
    <h2>Welcome to Club Management</h2>
    <p>Our club is dedicated to fostering collaboration and growth among members. We provide a platform for students, alumni, and volunteers to connect, share knowledge, and contribute to meaningful projects.</p>

    <h2>Our Mission</h2>
    <p>To create an inclusive environment where members can enhance their skills, participate in impactful events, and expand their professional and personal network.</p>

    <h2>Our Vision</h2>
    <p>To be recognized as a leading community-driven organization that empowers members to achieve their full potential and make a positive impact in society.</p>

    <h2>Core Values</h2>
    <ul>
        <li>Collaboration – We work together to achieve common goals.</li>
        <li>Integrity – We uphold transparency and honesty in all actions.</li>
        <li>Excellence – We strive for the highest quality in our projects and events.</li>
        <li>Inclusivity – We welcome and respect all members.</li>
        <li>Community Engagement – We aim to contribute positively to society.</li>
    </ul>

    <h2>Contact Us</h2>
    <p><strong>Email:</strong> info@clubmanagement.com</p>
    <p><strong>Phone:</strong> +880123456789</p>
</section>

<!-- FOOTER -->
<footer>
    &copy; 2025 Club Management System | All Rights Reserved
</footer>

</body>
</html>
