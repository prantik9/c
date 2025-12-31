<?php
session_start();
require_once 'db.php';
$admin_id = $_SESSION['user_id'] ?? null;
$admin_name = $_SESSION['name'] ?? 'Admin';

if (!isset($_SESSION['website_role']) || $_SESSION['website_role'] !== 'admin') { // Admin protection
    header("Location: login.php");
    exit;
}

$tblN = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications'");
if ($tblN && $tblN->num_rows === 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (member_id),
        INDEX (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

   
if (isset($_POST['update_member'])) { // UPDATE member type
    $member_type = $_POST['member_type'] ?? '';
    $member_id = (int)($_POST['member_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE members SET member_type=? WHERE id=?");
    $stmt->bind_param("si", $member_type, $member_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php"); //prevent resubmission
    exit;
}
   
if (isset($_POST['approve_sponsorship'])) { // APPROVE / DENY HANDLERS sponsorships

    $sponsor_id = (int)($_POST['sponsor_id'] ?? 0);
    $admin_comment = trim($_POST['admin_comment'] ?? '');

    // Get sponsor + event info
    $stmt = $conn->prepare("
        SELECT s.id, s.event_id, s.alumni_id, s.amount, e.name AS event_name
        FROM event_sponsors s
        JOIN events e ON e.id = s.event_id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $sponsor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sponsor) {
        $event_id   = (int)$sponsor['event_id'];
        $alumni_id  = (int)$sponsor['alumni_id'];
        $amount     = $sponsor['amount'];
        $event_name = $sponsor['event_name'];
        // Approve
        $stmt = $conn->prepare(" 
            UPDATE event_sponsors
            SET status='approved', decided_at=NOW(), admin_comment=?
            WHERE id=?
        ");
        $stmt->bind_param("si", $admin_comment, $sponsor_id);
        $stmt->execute();
        $stmt->close();

        // Deny other 
        $auto_deny_comment = "Not selected. Another sponsorship was approved.";
        $stmt = $conn->prepare("
            UPDATE event_sponsors
            SET status='denied', decided_at=NOW(), admin_comment=?
            WHERE event_id=? AND id<>? AND status='pending'
        ");
        $stmt->bind_param("sii", $auto_deny_comment, $event_id, $sponsor_id);
        $stmt->execute();
        $stmt->close();

        // Notify approved alumni
        $msg  = "✅ Your sponsorship for event '{$event_name}' (৳" . $amount . ") has been APPROVED.";
        $type = "sponsorship_approved";
        $stmt = $conn->prepare("INSERT INTO notifications (member_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $alumni_id, $type, $msg);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_dashboard.php#sponsorships");
    exit;
}

if (isset($_POST['deny_sponsorship'])) {

    $sponsor_id = (int)($_POST['sponsor_id'] ?? 0);
    $admin_comment = trim($_POST['admin_comment'] ?? '');
    if ($admin_comment === '') {
        $admin_comment = "Denied by admin.";
    }
    $stmt = $conn->prepare("
        SELECT s.id, s.event_id, s.alumni_id, s.amount, e.name AS event_name
        FROM event_sponsors s
        JOIN events e ON e.id = s.event_id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $sponsor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Deny sponsorship
    $stmt = $conn->prepare("UPDATE event_sponsors SET status='denied', decided_at=NOW(), admin_comment=? WHERE id=?");
    $stmt->bind_param("si", $admin_comment, $sponsor_id);
    $stmt->execute();
    $stmt->close();

    // Notify denied alumni
    if ($sponsor) {
        $alumni_id  = (int)$sponsor['alumni_id'];
        $amount     = $sponsor['amount'];
        $event_name = $sponsor['event_name'];

        $msg  = "❌ Your sponsorship for event '{$event_name}' (৳" . $amount . ") was DENIED. Comment: " . $admin_comment;
        $type = "sponsorship_denied";
        $stmt = $conn->prepare("INSERT INTO notifications (member_id, type, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $alumni_id, $type, $msg);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_dashboard.php#sponsorships");
    exit;
}

if (isset($_POST['approve_activity'])) {
    $response_id = (int)($_POST['response_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE event_responses SET status='approved', decided_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $response_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#activities"); exit;
}

if (isset($_POST['deny_activity'])) {
    $response_id = (int)($_POST['response_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE event_responses SET status='denied', decided_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $response_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#activities"); exit;
}

if (isset($_POST['approve_member_reg'])) {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE members SET approved=1 WHERE id=?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute(); $stmt->close();
    header("Location: admin_dashboard.php#newregs"); exit;
}

if (isset($_POST['deny_member_reg'])) {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE members SET approved=-1 WHERE id=?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute(); $stmt->close();
    header("Location: admin_dashboard.php#newregs"); exit;
}

if (isset($_POST['approve_resource_request'])) {
    $resource_req_id = (int)($_POST['resource_req_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE resource_requests SET status='approved', approved_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $resource_req_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#resourcereqs"); exit;
}

if (isset($_POST['deny_resource_request'])) {
    $resource_req_id = (int)($_POST['resource_req_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE resource_requests SET status='denied', approved_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $resource_req_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#resourcereqs"); exit;
}

if (isset($_POST['delete_member'])) { //DELETE member
    $member_id = (int)($_POST['member_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM members WHERE id=?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit;
}

if (isset($_POST['assign_resource'])) { // ASSIGN RESOURCE
    $resource_id = (int)($_POST['resource_id'] ?? 0);
    $member_id = (int)($_POST['member_id'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO resource_assignments (resource_id, member_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $resource_id, $member_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit;
}

if (isset($_POST['create_event'])) { //CREATE EVENT

    $event_name = $_POST['event_name'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_venue = $_POST['event_venue'] ?? '';
    $event_description = $_POST['event_description'] ?? '';
    $event_status = $_POST['event_status'] ?? 'upcoming';
    $event_type = $_POST['event_type'] ?? '';

    $stmt = $conn->prepare("INSERT INTO events (name, event_date, venue, description, status, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $event_name, $event_date, $event_venue, $event_description, $event_status, $event_type);
    $stmt->execute();
    $event_id = $stmt->insert_id;
    $stmt->close();

    
    $message = "New event created: " . ($event_name ?: 'Event'); // notification  new event
    $notify_sql = "INSERT INTO event_notifications (event_id, message, created_at) VALUES (?, ?, NOW())";
    $notify_stmt = $conn->prepare($notify_sql);
    if ($notify_stmt) {
        $notify_stmt->bind_param("is", $event_id, $message);
        $notify_stmt->execute();
        $notify_stmt->close();
    }

    header("Location: admin_dashboard.php");
    exit;
}

if (isset($_POST['delete_event'])) { // DELETE EVENT
    $event_id = (int)($_POST['event_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php#events");
    exit;
}

$members   = $conn->query("SELECT * FROM members ORDER BY id ");
$resources = $conn->query("SELECT * FROM resources ORDER BY id ");
$events    = $conn->query("SELECT * FROM events ORDER BY event_date ");

//pending sponsorships
$pending_sponsors = $conn->query(
    "SELECT s.*, e.name AS event_name, m.name AS alumni_name, m.email AS alumni_email
     FROM event_sponsors s
     JOIN events e ON e.id = s.event_id
     JOIN members m ON m.id = s.alumni_id
     WHERE s.status = 'pending'
     ORDER BY s.created_at "
);

//  pending activity requests
$pending_activities = $conn->query(
    "SELECT r.*, e.name AS event_name, m.name AS member_name, m.email AS member_email
     FROM event_responses r
     JOIN events e ON e.id = r.event_id
     JOIN members m ON m.id = r.member_id
     WHERE r.status = 'pending' AND (r.activity IS NOT NULL AND r.activity <> '')
     ORDER BY r.created_at "
);

//  New Registrations approval
$new_regs = $conn->query("SELECT * FROM members WHERE approved = 0 ORDER BY id ");

// available volunteers 
$available_volunteers = $conn->query(
    "SELECT DISTINCT r.*, e.name AS event_name, m.name AS volunteer_name, m.email AS volunteer_email, m.phone AS volunteer_phone
     FROM event_responses r
     JOIN events e ON e.id = r.event_id
     JOIN members m ON m.id = r.member_id
     WHERE r.availability = 'available' AND m.member_type = 'Volunteer'
     ORDER BY e.event_date , r.created_at "
);

// resource_requests 
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

$pending_resource_reqs = $conn->query(
    "SELECT rq.*, m.name AS member_name, m.email AS member_email, e.name AS event_name
     FROM resource_requests rq
     JOIN members m ON m.id = rq.member_id
     JOIN events e ON e.id = rq.event_id
     WHERE rq.status = 'pending'
     ORDER BY rq.created_at DESC"
);
?>
<?php $hide_nav = true; include 'header.php'; ?>

<style>
:root{
    --bg: #0b1220;
    --panel: rgba(255,255,255,0.06);
    --panel2: rgba(255,255,255,0.08);
    --border: rgba(255,255,255,0.10);
    --text: #e5e7eb;
    --muted: #a5b4fc;
    --muted2:#94a3b8;
    --primary:#3b82f6;
    --primary2:#2563eb;
    --danger:#ef4444;
    --success:#22c55e;
    --warning:#f59e0b;
}

body{
    background: radial-gradient(1200px 700px at 20% 20%, rgba(59,130,246,0.25), transparent 60%),
                radial-gradient(900px 600px at 80% 30%, rgba(34,197,94,0.16), transparent 60%),
                linear-gradient(180deg, #070b14 0%, #0b1220 100%);
    color: var(--text);
}

/* keep your dashboard layout but make it modern */
.dashboard{
    display:flex;
    gap:22px;
    padding:24px;
    align-items:flex-start;
}

/* Sidebar */
.sidebar{
    width: 280px;
    position: sticky;
    top: 18px;
    border-radius: 16px;
    padding: 18px;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    box-shadow: 0 18px 60px rgba(0,0,0,0.35);
    backdrop-filter: blur(10px) saturate(1.2);
}
.sidebar .brand{
    font-size: 14px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(229,231,235,0.78);
    margin-bottom: 14px;
}
.sidebar .profile{
    padding: 14px;
    border-radius: 14px;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
}
.sidebar .profile .name{
    font-weight: 800;
    font-size: 16px;
}
.sidebar .profile .email{
    margin-top: 6px;
    color: rgba(148,163,184,0.95);
    font-size: 13px;
}
.sidebar nav{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top: 16px;
}
.sidebar nav a{
    display:flex;
    align-items:center;
    gap:10px;
    padding: 10px 12px;
    border-radius: 12px;
    text-decoration:none;
    color: rgba(229,231,235,0.92);
    border: 1px solid transparent;
    background: rgba(255,255,255,0.03);
    transition: transform .15s ease, background .15s ease, border-color .15s ease;
}
.sidebar nav a:hover{
    transform: translateY(-1px);
    background: rgba(59,130,246,0.16);
    border-color: rgba(59,130,246,0.35);
}
.sidebar .logout{
    display:block;
    margin-top: 18px;
    padding: 10px 12px;
    text-align:center;
    border-radius: 12px;
    text-decoration:none;
    color:white;
    background: rgba(239,68,68,0.95);
    box-shadow: 0 12px 24px rgba(239,68,68,0.18);
}
.sidebar .logout:hover{ opacity: .92; }

/* Main */
.main{
    flex:1;
    min-width: 0;
}
.welcome{
    margin-bottom: 14px;
}
.welcome h1{
    font-size: 32px;
    margin: 0 0 6px 0;
}
.welcome p{
    color: rgba(148,163,184,0.95);
    margin: 0;
}
.info-card{
    margin-top: 10px;
    padding: 18px;
    border-radius: 16px;
    background: linear-gradient(90deg, rgba(59,130,246,0.20), rgba(34,197,94,0.14));
    border: 1px solid rgba(255,255,255,0.12);
    box-shadow: 0 18px 50px rgba(0,0,0,0.20);
}

/* Sections */
.section-header{
    display:block;
    padding: 12px 14px;
    border-radius: 14px;
    margin: 22px 0 12px;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    font-size: 16px;
    font-weight: 800;
    letter-spacing: .02em;
}

/* Cards */
.card-ui{
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.25);
    backdrop-filter: blur(8px) saturate(1.15);
}

/* Tables */
.table-wrap{
    overflow:auto;
    border-radius: 14px;
    border: 1px solid var(--border);
}
table{
    width:100%;
    border-collapse: collapse;
    min-width: 860px;
    background: rgba(11,18,32,0.35);
}
th, td{
    padding: 10px 10px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    vertical-align: top;
    font-size: 13px;
}
th{
    text-align:left;
    position: sticky;
    top: 0;
    background: rgba(30,41,59,0.85);
    color: rgba(229,231,235,0.95);
    z-index: 1;
}
tr:hover td{
    background: rgba(59,130,246,0.06);
}

/* Inputs */
input, textarea, select{
    width: 100%;
    padding: 10px 10px;
    font-size: 13px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(2,6,23,0.35);
    color: rgba(229,231,235,0.95);
    outline: none;
}
textarea{ resize: vertical; }
input::placeholder, textarea::placeholder{ color: rgba(148,163,184,0.85); }
select option{ color:#111827; }

button{
    border: none;
    border-radius: 12px;
    padding: 10px 12px;
    cursor: pointer;
    font-weight: 700;
    transition: transform .12s ease, opacity .12s ease;
    white-space: nowrap;
}
button:hover{ transform: translateY(-1px); opacity: .95; }

.btn-primary{ background: var(--primary); color: white; }
.update{ background: rgba(59,130,246,0.22); color: rgba(229,231,235,0.96); border: 1px solid rgba(59,130,246,0.35); }
.delete{ background: rgba(239,68,68,0.92); color: white; }
.assign{ background: rgba(34,197,94,0.90); color: white; }

.actions-inline{
    display:flex;
    gap:10px;
    align-items:center;
}
.actions-inline input[type="text"]{
    max-width: 220px;
}

.member-search{ max-width: 260px; }
.search-results{
    position:absolute;
    background: rgba(15,23,42,0.98);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    width: 260px;
    max-height: 180px;
    overflow-y: auto;
    display: none;
    z-index: 999;
    box-shadow: 0 18px 50px rgba(0,0,0,0.35);
}
.search-item{
    padding: 10px 10px;
    cursor: pointer;
    color: rgba(229,231,235,0.95);
}
.search-item:hover{ background: rgba(59,130,246,0.18); }

.create-event-form{
    margin-top: 10px;
    display:grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 12px;
}
.create-event-form h3{
    grid-column: 1 / -1;
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 800;
}
.create-event-form button{ grid-column: 1 / -1; }

@media (max-width: 1020px){
    .dashboard{ flex-direction: column; }
    .sidebar{ width:100%; position: static; }
    table{ min-width: 760px; }
    .create-event-form{ grid-template-columns: 1fr; }
}
</style>

<div class="dashboard">
    <aside class="sidebar">
        <div class="brand">Club Management</div>
        <div class="profile">
            <div class="name">Admin</div>
            <div class="email"><?= htmlspecialchars($admin_name) ?></div>
        </div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_dashboard.php#members">Members</a>
            <a href="admin_dashboard.php#sponsorships">Sponsorships</a>
            <a href="admin_dashboard.php#activities">Activities</a>
            <a href="admin_dashboard.php#resourcereqs">Resource Requests</a>
            <a href="admin_dashboard.php#resources">Resources</a>
            <a href="admin_dashboard.php#events">Events</a>
        </nav>
        <a class="logout" href="logout.php">Logout</a>
    </aside>

    <main class="main">
        <section class="welcome">
            <h1>Hi, Admin <span style="font-size:22px"></span></h1>
            <p>Manage members, resources, and events from this dashboard.</p>
        </section>

        <section class="info-card">
            <h3 style="margin:0 0 8px 0;">System Overview</h3>
            <p style="margin:0; color:rgba(229,231,235,0.86);">Create and manage events, assign resources to members, and track member activities.</p>
        </section>

        <h2 class="section-header" id="members">Members Management</h2>
        <div class="card-ui">
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
                        <th>Member Type</th><th>Dept</th><th>Action</th>
                    </tr>
                    <?php while ($m = $members->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <td><?= $m['id'] ?></td>
                            <td><?= htmlspecialchars($m['name']) ?></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= htmlspecialchars($m['phone']) ?></td>

                            <td>
                                <select name="member_type">
                                    <option <?= $m['member_type']=='Alumni'?'selected':'' ?>>Alumni</option>
                                    <option <?= $m['member_type']=='General'?'selected':'' ?>>General</option>
                                    <option <?= $m['member_type']=='Volunteer'?'selected':'' ?>>Volunteer</option>
                                </select>
                            </td>

                            <td><?= htmlspecialchars($m['dept'] ?? '') ?></td>

                            <td>
                                <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                <div class="actions-inline">
                                    <button name="update_member" class="update">Update</button>
                                    <button name="delete_member" class="delete" onclick="return confirm('Delete this member?')">Delete</button>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <h2 class="section-header" id="sponsorships">Pending Sponsorship Requests</h2>
        <div class="card-ui">
            <?php if ($pending_sponsors && $pending_sponsors->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>ID</th><th>Alumni</th><th>Email</th><th>Event</th><th>Organization</th><th>Amount</th><th>Notes</th><th>Submitted</th><th>Action</th>
                        </tr>
                        <?php while ($s = $pending_sponsors->fetch_assoc()): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><?= htmlspecialchars($s['alumni_name']) ?></td>
                            <td><?= htmlspecialchars($s['alumni_email']) ?></td>
                            <td><?= htmlspecialchars($s['event_name']) ?></td>
                            <td><?= htmlspecialchars($s['organization']) ?></td>
                            <td><?= htmlspecialchars($s['amount']) ?></td>
                            <td><?= htmlspecialchars($s['notes']) ?></td>
                            <td><?= htmlspecialchars($s['created_at']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="sponsor_id" value="<?= $s['id'] ?>">
                                    <div class="actions-inline">
                                        <input type="text" name="admin_comment" placeholder="Comment (optional)">
                                        <button name="approve_sponsorship" class="btn-primary">Approve</button>
                                        <button name="deny_sponsorship" class="delete" onclick="return confirm('Deny this sponsorship?')">Deny</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="margin:0; color:rgba(229,231,235,0.86);">No pending sponsorships.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-header" id="activities">Pending Activity Requests</h2>
        <div class="card-ui">
            <?php if ($pending_activities && $pending_activities->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>ID</th><th>Member</th><th>Email</th><th>Event</th><th>Activity</th><th>Submitted</th><th>Action</th></tr>
                        <?php while ($r = $pending_activities->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['member_name']) ?></td>
                            <td><?= htmlspecialchars($r['member_email']) ?></td>
                            <td><?= htmlspecialchars($r['event_name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['activity'])) ?></td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="response_id" value="<?= $r['id'] ?>">
                                    <div class="actions-inline">
                                        <button name="approve_activity" class="btn-primary">Approve</button>
                                        <button name="deny_activity" class="delete" onclick="return confirm('Deny this activity request?')">Deny</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="margin:0; color:rgba(229,231,235,0.86);">No pending activity requests.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-header" id="newregs">New Registrations</h2>
        <div class="card-ui">
            <?php if ($new_regs && $new_regs->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Joined</th><th>Action</th></tr>
                        <?php while ($nr = $new_regs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $nr['id'] ?></td>
                            <td><?= htmlspecialchars($nr['name']) ?></td>
                            <td><?= htmlspecialchars($nr['email']) ?></td>
                            <td><?= htmlspecialchars($nr['member_type']) ?></td>
                            <td><?= htmlspecialchars($nr['join_date']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="member_id" value="<?= $nr['id'] ?>">
                                    <div class="actions-inline">
                                        <button name="approve_member_reg" class="btn-primary">Approve</button>
                                        <button name="deny_member_reg" class="delete" onclick="return confirm('Deny registration?')">Deny</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="margin:0; color:rgba(229,231,235,0.86);">No new registrations.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-header" id="volunteers">Available Volunteers</h2>
        <div class="card-ui">
            <?php if ($available_volunteers && $available_volunteers->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>ID</th><th>Volunteer</th><th>Email</th><th>Phone</th><th>Event</th><th>Availability</th><th>Confirmed</th></tr>
                        <?php while ($v = $available_volunteers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= htmlspecialchars($v['volunteer_name']) ?></td>
                            <td><?= htmlspecialchars($v['volunteer_email']) ?></td>
                            <td><?= htmlspecialchars($v['volunteer_phone']) ?></td>
                            <td><?= htmlspecialchars($v['event_name']) ?></td>
                            <td><?= htmlspecialchars($v['availability']) ?></td>
                            <td><?= htmlspecialchars($v['created_at']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="margin:0; color:rgba(229,231,235,0.86);">No available volunteers registered for events.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-header" id="resourcereqs">Pending Resource Requests</h2>
        <div class="card-ui">
            <?php if ($pending_resource_reqs && $pending_resource_reqs->num_rows > 0): ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>ID</th><th>Member</th><th>Email</th><th>Event</th><th>Resource Description</th><th>Submitted</th><th>Action</th></tr>
                        <?php while ($rq = $pending_resource_reqs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $rq['id'] ?></td>
                            <td><?= htmlspecialchars($rq['member_name']) ?></td>
                            <td><?= htmlspecialchars($rq['member_email']) ?></td>
                            <td><?= htmlspecialchars($rq['event_name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($rq['resource_description'])) ?></td>
                            <td><?= htmlspecialchars($rq['created_at']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="resource_req_id" value="<?= $rq['id'] ?>">
                                    <div class="actions-inline">
                                        <button name="approve_resource_request" class="btn-primary">Approve</button>
                                        <button name="deny_resource_request" class="delete" onclick="return confirm('Deny this request?')">Deny</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="margin:0; color:rgba(229,231,235,0.86);">No pending resource requests.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-header" id="resources">Resources & Assignment</h2>
        <div class="card-ui">
            <div class="table-wrap">
                <table>
                    <tr><th>ID</th><th>Type</th><th>Title</th><th>Link</th><th>Assign</th></tr>
                    <?php while ($r = $resources->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><a style="color:#93c5fd;" href="<?= htmlspecialchars($r['url']) ?>" target="_blank">Open</a></td>
                        <td>
                            <form method="POST" style="position:relative;">
                                <input type="hidden" name="resource_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="member_id">

                                <div class="actions-inline">
                                    <input type="text" class="member-search" placeholder="Search member..." autocomplete="off">
                                    <div class="search-results"></div>
                                    <button name="assign_resource" class="assign">Assign</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <h2 class="section-header" id="events">Events Management</h2>
        <div class="card-ui">
            <div class="create-event-form">
                <h3>Create New Event</h3>
                <form method="POST" style="display: contents;">
                    <input type="text" name="event_name" placeholder="Event Name" required>
                    <input type="date" name="event_date" required>
                    <input type="text" name="event_venue" placeholder="Venue" required>
                    <input type="text" name="event_type" placeholder="Type (e.g., Workshop, Meetup)" required>
                    <select name="event_status" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <textarea name="event_description" placeholder="Event Description" rows="3"></textarea>
                    <button name="create_event" class="btn-primary">Create Event</button>
                </form>
            </div>
        </div>

        <div class="card-ui" style="margin-top:12px;">
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>ID</th><th>Name</th><th>Date</th><th>Venue</th>
                        <th>Type</th><th>Status</th><th>Description</th><th>Action</th>
                    </tr>
                    <?php while ($e = $events->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <td><?= $e['id'] ?></td>
                            <td><input type="text" name="name" value="<?= htmlspecialchars($e['name']) ?>"></td>
                            <td><input type="date" name="event_date" value="<?= htmlspecialchars($e['event_date']) ?>"></td>
                            <td><input type="text" name="venue" value="<?= htmlspecialchars($e['venue']) ?>"></td>
                            <td><input type="text" name="type" value="<?= htmlspecialchars($e['type']) ?>"></td>
                            <td>
                                <select name="status">
                                    <option value="upcoming" <?= $e['status']=='upcoming'?'selected':'' ?>>Upcoming</option>
                                    <option value="ongoing" <?= $e['status']=='ongoing'?'selected':'' ?>>Ongoing</option>
                                    <option value="completed" <?= $e['status']=='completed'?'selected':'' ?>>Completed</option>
                                </select>
                            </td>
                            <td><textarea name="description" rows="2"><?= htmlspecialchars($e['description']) ?></textarea></td>
                            <td>
                                <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                                <div class="actions-inline">
                                    <button name="update_event" class="update">Save</button>
                                    <button name="delete_event" class="delete" onclick="return confirm('Delete this event?')">Delete</button>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <script>
        document.querySelectorAll('.member-search').forEach(input => {
            const form = input.closest('form');
            const hiddenMember = form.querySelector('input[name="member_id"]');
            const resultsBox = form.querySelector('.search-results');

            input.addEventListener('keyup', () => {
                const q = input.value.trim();
                if (!q) { resultsBox.style.display = 'none'; return; }

                fetch('search_members.php?term=' + encodeURIComponent(q))
                    .then(res => res.text())
                    .then(html => {
                        resultsBox.innerHTML = html;
                        resultsBox.style.display = 'block';

                        resultsBox.querySelectorAll('.search-item').forEach(item => {
                            item.onclick = () => {
                                input.value = item.dataset.name;
                                hiddenMember.value = item.dataset.id;
                                resultsBox.style.display = 'none';
                            };
                        });
                    });
            });

            document.addEventListener('click', e => {
                if (!form.contains(e.target)) resultsBox.style.display = 'none';
            });
        });
        </script>

    </main>
</div>

<?php include 'footer.php'; ?>

