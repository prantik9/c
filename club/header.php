<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$website_role = $_SESSION['website_role'] ?? null;
$hide_nav = $hide_nav ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if (!$hide_nav): ?>
<nav>
    <a href="home.php" class="logo">Club Management</a>
    <ul>
        <li><a href="events.php">Events</a></li>
        <li><a href="resources.php">Resources</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if ($website_role === 'admin'): ?>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php elseif ($website_role === 'user'): ?>
            <li><a href="user_dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
