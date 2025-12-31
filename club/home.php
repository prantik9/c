<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

$website_role = $_SESSION['website_role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
?>
<?php include 'header.php'; ?>


<section class="hero">
    <h1>Welcome to Our Club</h1>
    <p>Connect, collaborate, and grow with our community.</p>
    <a href="events.php" class="btn btn-primary">View Events</a>
    
</section>


<section class="register-section">
    <div class="register-card">
        <h2>Become a Club Member</h2>
        <p>Join our community and be part of our exciting events and activities. Register today to get started!</p>
        <a href="register.php" class="btn btn-primary">Register Now</a>
    </div>

</section>

<?php include 'footer.php'; ?>
