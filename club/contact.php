<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('db.php');

$website_role = $_SESSION['website_role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Example contact data
$contacts = [
    ['name' => 'Farha Jannat', 'phone' => '017XXXXXXXX', 'email' => 'farha.jannat@example.com'],
    ['name' => 'Aurpon Sharma', 'phone' => '019YYYYYYYY', 'email' => 'aurpon234@example.com'],
    ['name' => 'Rahabar Islam', 'phone' => '018ZZZZZZZZ', 'email' => 'rahabarzzz@example.com'],
    ['name' => 'Alice Johnson', 'phone' => '013ZZZZZZZZ', 'email' => 'alice_j@example.com'],
    ['name' => 'Abdur Rahman', 'phone' => '019ZZZZZZZZ', 'email' => 'arahman@example.com'],
    ['name' => 'Zarin Tasnim', 'phone' => '017ZZZZZZZZ', 'email' => 'zarin@example.com'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us | Club Management</title>
<style>
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: 'Arial', sans-serif; display: flex; flex-direction: column; min-height: 100vh; background: #f0f4f8; }

    /* NAVBAR */
    nav { background-color: #1f2937; display: flex; justify-content: space-between; align-items: center; padding: 10px 30px; }
    nav .logo { color: #fff; font-size: 20px; font-weight: bold; text-decoration: none; }
    nav ul { list-style: none; display: flex; gap: 15px; }
    nav ul li a { color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 5px; transition: background 0.3s; }
    nav ul li a:hover { background-color: #2563eb; }

    /* HERO SECTION */
    .hero { text-align: center; padding: 80px 20px; background: linear-gradient(to right, #2563eb, #3b82f6); color: white; margin-bottom: 40px; }
    .hero h1 { font-size: 40px; margin-bottom: 15px; }
    .hero p { font-size: 20px; }

    /* CONTACT CARDS */
    .contacts-section { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 0 20px 40px; }
    .contact-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 250px; text-align: center; }
    .contact-card h3 { margin-bottom: 10px; color: #1e3a8a; }
    .contact-card p { color: #374151; margin-bottom: 5px; }

    /* FOOTER */
    footer { text-align: center; padding: 20px; background-color: #1f2937; color: #fff; margin-top: auto; }

    @media(max-width:768px){ .hero h1{ font-size:32px;} .hero p{font-size:16px;} }
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
    <h1>Contact Us</h1>
    <p>Reach out to our team for any queries or support.</p>
</section>

<!-- CONTACT CARDS -->
<section class="contacts-section">
    <?php foreach($contacts as $contact): ?>
    <div class="contact-card">
        <h3><?= htmlspecialchars($contact['name']) ?></h3>
        <p><strong>Phone:</strong> <?= htmlspecialchars($contact['phone']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($contact['email']) ?></p>
    </div>
    <?php endforeach; ?>
</section>

<!-- FOOTER -->
<footer>
    &copy; 2025 Club Management System | All Rights Reserved
</footer>

</body>
</html>
