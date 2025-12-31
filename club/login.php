<?php
session_start();
include('db.php'); 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {

         //  ADMIN LOGIN
        $stmt = $conn->prepare(
            "SELECT id, name, password FROM admin WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && $password === $admin['password']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['name'];
            $_SESSION['website_role'] = 'admin';

            header("Location: admin_dashboard.php");
            exit;
        }

          // MEMBER LOGIN
        $stmt = $conn->prepare(
            "SELECT id, name, password, member_type FROM members WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        $stmt->close();
        

        if ($member && $password === $member['password']) {
        $_SESSION['user_id'] = $member['id'];
        $_SESSION['username'] = $member['name'];
        $_SESSION['website_role'] = strtolower($member['member_type']);

        // Check if the user is an admin
        if (strtolower($member['member_type']) === 'admin') {
            // Redirect admins to their specific page (e.g., admin_dashboard.php)
            header("Location: admin_dashboard.php"); 
        } else {
            // Everyone else (alumni, students, etc.) goes to members.php
            header("Location: members.php");
        }
        exit;
}}

}
?>
<?php $hide_nav = true; include 'header.php'; ?>

<div class="topbar-small" role="banner">
    <a href="home.php" class="logo">Club Management</a>
    <nav>
        <a href="events.php">Events</a>
        <a href="resources.php">Resources</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</div>

<div class="auth-bg" aria-hidden="true"></div>
<div class="auth-overlay" aria-hidden="true"></div>

<main class="auth-page">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <?php if ($error): ?><p class="error" style="text-align:center;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn-primary">Login</button>
        </form>
        <p class="muted">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>
