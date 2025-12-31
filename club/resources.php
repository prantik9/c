<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['website_role'])) {
    header("Location: login.php?redirect=resources.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['website_role'];


$tbl_check = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resource_requests'");
if ($tbl_check && $tbl_check->num_rows === 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS resource_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        event_id INT NOT NULL,
        resource_description TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTable);
}


$member = null;
$approved_event = null;

if ($user_role === 'member') {
    $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if ($member && strtolower($member['member_type']) === 'general') {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.name
            FROM event_responses r
            JOIN events e ON e.id = r.event_id
            WHERE r.member_id = ?
              AND r.status = 'approved'
              AND r.activity IS NOT NULL
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $approved_event = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_resource'])) {
    if (!$member || strtolower($member['member_type']) !== 'general' || !$approved_event) {
        $error = "You are not eligible to request resources.";
    } else {
        $resource_desc = trim($_POST['resource_description'] ?? '');
        if (!$resource_desc) {
            $error = "Please describe what resource you need.";
        } else {
            $event_id = (int)$approved_event['id'];
            $stmt = $conn->prepare("
                INSERT INTO resource_requests (member_id, event_id, resource_description, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->bind_param("iis", $user_id, $event_id, $resource_desc);

            if ($stmt->execute()) {
                $success = "Resource request submitted! Admin will review your request.";
            } else {
                $error = "Error submitting request. Please try again.";
            }
            $stmt->close();
        }
    }
}


$sql = "SELECT * FROM resources ORDER BY id DESC";
$result = $conn->query($sql);

$error = $error ?? null;
$success = $success ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resources | Club Management</title>

<link rel="stylesheet" href="style.css">

<style>

:root{
  --bg1:#0b1220;
  --bg2:#070b14;
  --panel: rgba(255,255,255,0.06);
  --panel2: rgba(255,255,255,0.08);
  --border: rgba(255,255,255,0.12);
  --text: #e5e7eb;
  --muted: rgba(148,163,184,0.95);
  --primary:#60a5fa;   /* light blue highlight */
  --primary2:#3b82f6;
  --success:#22c55e;
  --danger:#ef4444;
}

body{
  margin:0;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  background:
    radial-gradient(1000px 700px at 20% 20%, rgba(96,165,250,0.22), transparent 60%),
    radial-gradient(900px 600px at 80% 30%, rgba(34,197,94,0.14), transparent 60%),
    linear-gradient(180deg, var(--bg2) 0%, var(--bg1) 100%);
  color: var(--text);
}

/* Top nav */
.navbar{
  position: sticky;
  top: 0;
  z-index: 9;
  backdrop-filter: blur(10px) saturate(1.2);
  background: rgba(2,6,23,0.55);
  border-bottom: 1px solid rgba(255,255,255,0.10);
}
.navbar-inner{
  max-width: 1100px;
  margin: 0 auto;
  padding: 12px 16px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 12px;
}
.brand{
  display:flex;
  align-items:center;
  gap:10px;
  font-weight: 900;
  letter-spacing: .02em;
  color: rgba(229,231,235,0.95);
}
.brand-dot{
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 0 4px rgba(96,165,250,0.20);
}
.nav-links{
  display:flex;
  gap:10px;
  flex-wrap: wrap;
  justify-content:flex-end;
}
.nav-links a{
  text-decoration:none;
  color: rgba(229,231,235,0.92);
  padding: 9px 10px;
  border-radius: 12px;
  border: 1px solid transparent;
  background: rgba(255,255,255,0.03);
  transition: transform .12s ease, background .12s ease, border-color .12s ease;
}
.nav-links a:hover{
  transform: translateY(-1px);
  background: rgba(96,165,250,0.18);
  border-color: rgba(96,165,250,0.40);
}
.nav-links a.active{
  background: rgba(96,165,250,0.22);
  border-color: rgba(96,165,250,0.45);
}

/* Page container */
.wrap{
  max-width: 1100px;
  margin: 22px auto;
  padding: 0 16px 28px;
}
.page-title{
  margin: 6px 0 14px 0;
}
.page-title h1{
  margin:0;
  font-size: 28px;
}
.page-title p{
  margin:6px 0 0 0;
  color: var(--muted);
}

/* Cards */
.card{
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 18px 60px rgba(0,0,0,0.25);
  backdrop-filter: blur(8px) saturate(1.15);
}
.stack{ display:grid; gap:14px; }

/* Alerts */
.alert{
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.06);
}
.alert.success{
  border-color: rgba(34,197,94,0.35);
  background: rgba(34,197,94,0.10);
}
.alert.error{
  border-color: rgba(239,68,68,0.35);
  background: rgba(239,68,68,0.10);
}

/* Request card */
.request-card{
  border: 1px solid rgba(96,165,250,0.30);
  background: linear-gradient(90deg, rgba(96,165,250,0.18), rgba(255,255,255,0.04));
}
.request-card h3{ margin:0 0 8px 0; }
label{
  display:block;
  margin: 8px 0 6px;
  color: rgba(229,231,235,0.92);
  font-size: 13px;
}
textarea, input, select{
  width:100%;
  padding: 10px 10px;
  font-size: 13px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(2,6,23,0.35);
  color: rgba(229,231,235,0.95);
  outline:none;
}
textarea{ resize: vertical; }

.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  font-weight: 900;
  background: rgba(96,165,250,0.90);
  color: #081427;
  transition: transform .12s ease, opacity .12s ease;
}
.btn:hover{ transform: translateY(-1px); opacity:.95; }

/* Table */
.table-wrap{
  overflow:auto;
  border-radius: 16px;
  border: 1px solid var(--border);
}
table{
  width:100%;
  border-collapse: collapse;
  background: rgba(11,18,32,0.35);
  min-width: 720px;
}
th, td{
  padding: 10px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  font-size: 13px;
}
th{
  text-align:left;
  position: sticky;
  top: 0;
  background: rgba(30,41,59,0.85);
  z-index: 1;
}
tr:hover td{ background: rgba(96,165,250,0.06); }

a{ color: rgba(147,197,253,0.95); }
a:hover{ opacity:.9; }

.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(255,255,255,0.06);
  color: rgba(229,231,235,0.92);
}
.badge.ok{
  border-color: rgba(34,197,94,0.35);
  background: rgba(34,197,94,0.10);
}
@media(max-width:700px){
  table{ min-width: 640px; }
  .navbar-inner{ align-items:flex-start; flex-direction: column; }
  .nav-links{ justify-content:flex-start; }
}
</style>
</head>

<body>

<div class="navbar">
  <div class="navbar-inner">
    <div class="brand"><span class="brand-dot"></span> Club Management</div>
    <div class="nav-links">
      <a href="home.php">Home</a>
      <a href="events.php">Events</a>
      <a class="active" href="resources.php">Resources</a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
</div>

<div class="wrap">
  <div class="page-title">
    <h1>Resources</h1>
    <p>Browse documents, links, and forms shared by the club.</p>
  </div>

  <div class="stack">

    <?php if ($error): ?>
      <div class="alert error"><strong>⚠️</strong> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert success"><strong>✅</strong> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($user_role === 'member' && $member && strtolower($member['member_type']) === 'general' && $approved_event): ?>
      <div class="card request-card">
        <h3>Request Resources <span class="badge ok">Approved for: <?= htmlspecialchars($approved_event['name']) ?></span></h3>
        <form method="POST">
          <label>Describe what resources you need</label>
          <textarea
            name="resource_description"
            placeholder="Example: projector, meeting room, printing materials, software license..."
            required
            rows="3"
          ></textarea>
          <button class="btn" name="request_resource" type="submit">Submit Resource Request</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3 style="margin:0 0 10px 0;">📚 Resource List</h3>

      <div class="table-wrap">
        <table>
          <tr>
            <th style="width:70px;">ID</th>
            <th>Title</th>
            <th style="width:140px;">Type</th>
            <th>URL / File</th>
          </tr>

          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['type']) ?></td>
            <td>
              <?php if($row['type'] === 'Document' || $row['type'] === 'Form'): ?>
                <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener">View</a>
              <?php else: ?>
                <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener">
                  <?= htmlspecialchars($row['url']) ?>
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>

        </table>
      </div>
    </div>

  </div>
</div>

</body>
</html>


