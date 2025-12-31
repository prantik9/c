<?php
session_start();
include('db.php');
if(!isset($_SESSION['user_id']) || $_SESSION['website_role'] !== 'user'){
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM members WHERE id=$user_id AND member_type='Volunteer'");
$volunteer = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Volunteer Dashboard</title>
<style>
body { font-family:'Segoe UI', sans-serif; background:#f4f4f4; margin:0; }
header { background:#10b981; color:white; text-align:center; padding:20px; }
.container { max-width:800px; margin:30px auto; background:white; padding:20px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; }
p { font-size:16px; margin:8px 0; }
</style>
</head>
<body>
<header><h1>Volunteer Dashboard</h1></header>
<div class="container">
<h2>Welcome <?= htmlspecialchars($volunteer['name']) ?></h2>
<p><strong>Department:</strong> <?= htmlspecialchars($volunteer['dept']) ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($volunteer['status']) ?></p>
<p><strong>Availability:</strong> <?= htmlspecialchars($volunteer['availability_status']) ?></p>
<p><strong>Assigned Hours:</strong> <?= htmlspecialchars($volunteer['assigned_hours']) ?></p>
<p><strong>Skill Set:</strong> <?= htmlspecialchars($volunteer['skill_set']) ?></p>
</div>
</body>
</html>
