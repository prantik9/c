<?php
session_start();
include('db.php');

// event_sponsors
$tbl = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_sponsors'");
if ($tbl && $tbl->num_rows === 0) {
    $createSql = "CREATE TABLE IF NOT EXISTS event_sponsors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        alumni_id INT NOT NULL,
        organization VARCHAR(255) DEFAULT NULL,
        amount DECIMAL(10,2) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createSql);
}

//event_responses
$tbl2 = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_responses'");
if ($tbl2 && $tbl2->num_rows === 0) {
    $createResponses = "CREATE TABLE IF NOT EXISTS event_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        member_id INT NOT NULL,
        role VARCHAR(100) DEFAULT NULL,
        availability VARCHAR(255) DEFAULT NULL,
        activity TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_event_member (event_id, member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createResponses);
}

// status
$colCheck = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_responses' AND COLUMN_NAME = 'status'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE event_responses ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
    $conn->query("ALTER TABLE event_responses ADD COLUMN decided_at DATETIME NULL");
}

// status
$tbl3 = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_sponsors' AND COLUMN_NAME = 'status'");
if ($tbl3 && $tbl3->num_rows === 0) {
    $conn->query("ALTER TABLE event_sponsors ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
    $conn->query("ALTER TABLE event_sponsors ADD COLUMN decided_at DATETIME NULL");
    $conn->query("ALTER TABLE event_sponsors ADD COLUMN admin_comment TEXT NULL");
}


if (!isset($_SESSION['user_id']) || $_SESSION['website_role'] === 'admin') {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['user_id'];
$message = '';

$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone']);
    $skill_set = trim($_POST['skill_set']);

    $stmt = $conn->prepare("UPDATE members SET phone = ?, skill_set = ? WHERE id = ?");
    $stmt->bind_param("ssi", $phone, $skill_set, $member_id);
    $stmt->execute();
    $stmt->close();

    $message = "Profile updated successfully.";
}


  // UPDATE PASSWORD

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!$new_password || !$confirm_password) {
        $message = "Password fields cannot be empty.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("UPDATE members SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $member_id);
        $stmt->execute();
        $stmt->close();

        $message = "Password updated successfully.";
    }
}


  // EVENT RESPONSE (GENERAL / VOLUNTEER)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_event'])) {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $availability = $_POST['availability'] ?? null;
    $activity = $_POST['activity'] ?? null;

     
    $check_stmt = $conn->prepare("SELECT id FROM event_responses WHERE event_id = ? AND member_id = ?"); //  already responded
    $check_stmt->bind_param("ii", $event_id, $member_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$existing) {

        $response_status = 'pending';
        if (!empty($availability) && empty($activity)) {
            $response_status = 'approved';
        }

        $stmt = $conn->prepare("
            INSERT INTO event_responses (event_id, member_id, role, availability, activity, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissss",
            $event_id,
            $member_id,
            $member['member_type'],
            $availability,
            $activity,
            $response_status
        );
        $stmt->execute();
        $stmt->close();

        $message = "Event response submitted.";
    } else {
        $message = "You have already responded to this event.";
    }
}
   //ALUMNI SPONSORSHIP

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sponsor_event'])) {

    if (strtolower($member['member_type']) !== 'alumni') {
        $message = "Only alumni can submit sponsorship.";
    } else {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $org = trim($_POST['organization'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0 || $amount >= 100000) {
            $message = "Sponsorship amount must be less than 100000 Tk.";
        } else {

  
            $check_stmt = $conn->prepare("SELECT id FROM event_sponsors WHERE event_id = ? AND alumni_id = ?");
            $check_stmt->bind_param("ii", $event_id, $member_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if (!$existing) {
  
                if ($designation) {
                    $notes = trim(($notes ? $notes . "\n" : "") . "Designation: " . $designation);
                }

                $sponsor_status = 'pending';
                $stmt = $conn->prepare("
                    INSERT INTO event_sponsors (event_id, alumni_id, organization, amount, notes, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iisdss", $event_id, $member_id, $org, $amount, $notes, $sponsor_status);
                $stmt->execute();
                $stmt->close();

                $message = "Sponsorship submitted successfully (Pending admin approval).";
            } else {
                $message = "You have already sponsored this event.";
            }
        }
    }
}

   //ASSIGNED TASKS
$stmt = $conn->prepare("
    SELECT r.type, r.title, r.url, ra.assigned_at
    FROM resource_assignments ra
    JOIN resources r ON r.id = ra.resource_id
    WHERE ra.member_id = ?
    ORDER BY ra.assigned_at DESC
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$assigned_tasks = $stmt->get_result();
$stmt->close();


 
$events = $conn->query("SELECT * FROM events WHERE status != 'completed'");//  FETCH EVENTS

$notifications = null;
$notificationCols = ['member_id', 'user_id', 'recipient_id', 'memberid', 'member']; // FETCH NOTIFICATIONS
foreach ($notificationCols as $col) {
    try {
        $sql = "SELECT n.*, e.name AS event_name
                FROM event_notifications n
                JOIN events e ON e.id = n.event_id
                WHERE n." . $col . " = ?
                ORDER BY n.created_at DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { continue; }
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $notifications = $stmt->get_result();
        $stmt->close();
        break;
    } catch (Exception $ex) { continue; }
}

if (!$notifications) {
    $notifications = $conn->query("
        SELECT n.*, e.name AS event_name
        FROM event_notifications n
        JOIN events e ON e.id = n.event_id
        ORDER BY n.created_at DESC
    ");
}

$my_sponsorships = null;
if ($member && strtolower($member['member_type']) === 'alumni') {
    $stmt = $conn->prepare("
        SELECT s.*, e.name AS event_name
        FROM event_sponsors s
        JOIN events e ON e.id = s.event_id
        WHERE s.alumni_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $my_sponsorships = $stmt->get_result();
    $stmt->close();
}
?>
<?php $hide_nav = true; include 'header.php'; ?>

<style>

:root{
  --bg1:#0b1220;
  --bg2:#070b14;
  --panel: rgba(255,255,255,0.06);
  --panel2: rgba(255,255,255,0.08);
  --border: rgba(255,255,255,0.12);
  --text: #e5e7eb;
  --muted: rgba(148,163,184,0.95);
  --primary:#60a5fa; 
  --primary2:#3b82f6;
  --success:#22c55e;
  --danger:#ef4444;
  --warn:#f59e0b;
}

body{
  background:
    radial-gradient(1000px 700px at 20% 20%, rgba(96,165,250,0.22), transparent 60%),
    radial-gradient(900px 600px at 80% 30%, rgba(34,197,94,0.14), transparent 60%),
    linear-gradient(180deg, var(--bg2) 0%, var(--bg1) 100%);
  color: var(--text);
}

.dashboard{
  display:flex;
  gap:22px;
  padding:24px;
  align-items:flex-start;
}

.sidebar{
  width: 280px;
  position: sticky;
  top: 18px;
  border-radius: 16px;
  padding: 18px;
  background: var(--panel);
  border: 1px solid var(--border);
  box-shadow: 0 18px 60px rgba(0,0,0,0.35);
  backdrop-filter: blur(10px) saturate(1.2);
}
.sidebar .brand{
  font-size: 14px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(229,231,235,0.75);
  margin-bottom: 14px;
}
.sidebar .profile{
  padding: 14px;
  border-radius: 14px;
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--border);
}
.sidebar .profile .name{ font-weight: 800; font-size: 16px; }
.sidebar .profile .email{ margin-top: 6px; color: var(--muted); font-size: 13px; }

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
  background: rgba(96,165,250,0.18);
  border-color: rgba(96,165,250,0.40);
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

.main{ flex:1; min-width: 0; }
.welcome{ margin-bottom: 14px; }
.welcome h1{ font-size: 30px; margin: 0 0 6px 0; }
.welcome p{ margin:0; color: var(--muted); }

.info-card{
  margin-top: 10px;
  padding: 18px;
  border-radius: 16px;
  background: linear-gradient(90deg, rgba(96,165,250,0.20), rgba(34,197,94,0.14));
  border: 1px solid rgba(255,255,255,0.12);
  box-shadow: 0 18px 50px rgba(0,0,0,0.20);
}
.info-card h3{ color: rgba(229,231,235,0.95) !important; }

.card{
  margin-top: 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 18px 60px rgba(0,0,0,0.25);
  backdrop-filter: blur(8px) saturate(1.15);
}
.card h3{ margin: 0 0 10px 0; }
.card h4{ margin: 10px 0 8px 0; }

.success{
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(34,197,94,0.35);
  background: rgba(34,197,94,0.10);
  color: rgba(229,231,235,0.95);
}

hr{ border: none; border-top: 1px solid rgba(255,255,255,0.10); margin: 14px 0; }

.table-wrap{
  overflow:auto;
  border-radius: 14px;
  border: 1px solid var(--border);
}
table{
  width: 100%;
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

/* forms */
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
label{ display:block; margin: 8px 0 6px; color: rgba(229,231,235,0.90); font-size: 13px; }

button, .btn{
  display:inline-block;
  border:none;
  border-radius: 12px;
  padding: 10px 12px;
  cursor:pointer;
  font-weight: 800;
  transition: transform .12s ease, opacity .12s ease;
  background: rgba(96,165,250,0.90);
  color: #081427;
}
button:hover, .btn:hover{ transform: translateY(-1px); opacity:.95; }

/* links */
a{ color: rgba(147,197,253,0.95); }
a:hover{ opacity: .9; }

.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(255,255,255,0.06);
}
.badge.pending{ background: rgba(245,158,11,0.12); border-color: rgba(245,158,11,0.30); }
.badge.approved{ background: rgba(34,197,94,0.12); border-color: rgba(34,197,94,0.30); }
.badge.denied{ background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.30); }

@media (max-width: 1020px){
  .dashboard{ flex-direction:column; }
  .sidebar{ width:100%; position: static; }
  table{ min-width: 660px; }
}
</style>

<div class="dashboard">
  <aside class="sidebar">
    <div class="brand">Club Management</div>
    <div class="profile">
      <div class="name"><?= htmlspecialchars($member['name']) ?></div>
      <div class="email"><?= htmlspecialchars($member['email']) ?></div>
    </div>
    <nav>
      <a href="members.php">View Profile</a>
      <a href="members.php#tasks">Assigned Tasks</a>
      <a href="events.php">Events</a>
      <a href="resources.php">Resources</a>
    </nav>
    <a class="logout" href="?logout=true">Logout</a>
  </aside>

  <main class="main">
    <section class="welcome">
      <h1>Hi, <?= htmlspecialchars(strtoupper($member['name'])) ?> <span style="font-size:22px">👋</span></h1>
      <p>This is your member dashboard. From here you can keep your details up to date and track events or tasks assigned to you.</p>
    </section>

    <section class="info-card">
      <h3 style="margin:0 0 8px 0;">Need Help?</h3>
      <p style="margin:0; color:rgba(229,231,235,0.86);">Make sure your contact details and address are correct so volunteers and donors can reach you quickly during emergencies.</p>
    </section>

    <div style="margin-top:22px;">

      <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <div class="card" id="notifications">
        <h3>🔔 Notifications</h3>
        <?php if ($notifications && $notifications->num_rows > 0): ?>
          <?php while ($n = $notifications->fetch_assoc()): ?>
            <p>
              <strong><?= htmlspecialchars($n['event_name'] ?? 'Event') ?></strong>
              — <?= htmlspecialchars($n['message'] ?? '') ?>
            </p>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color: rgba(229,231,235,0.86);">No notifications</p>
        <?php endif; ?>
      </div>

      <?php if ($member && strtolower($member['member_type']) === 'alumni'): ?>
      <div class="card" id="my-sponsorships">
        <h3>💰 My Sponsorship Requests</h3>

        <?php if ($my_sponsorships && $my_sponsorships->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <tr>
                <th>Event</th>
                <th>Organization</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Admin Comment</th>
                <th>Submitted</th>
                <th>Decided At</th>
              </tr>
              <?php while ($s = $my_sponsorships->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($s['event_name']) ?></td>
                <td><?= htmlspecialchars($s['organization'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['amount']) ?></td>
                <td>
                  <?php
                    $st = strtolower($s['status'] ?? 'pending');
                    if ($st === 'approved') echo '<span class="badge approved">✅ Approved</span>';
                    elseif ($st === 'denied') echo '<span class="badge denied">❌ Denied</span>';
                    else echo '<span class="badge pending">⏳ Pending</span>';
                  ?>
                </td>
                <td><?= htmlspecialchars($s['admin_comment'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['created_at']) ?></td>
                <td><?= htmlspecialchars($s['decided_at'] ?? '') ?></td>
              </tr>
              <?php endwhile; ?>
            </table>
          </div>
        <?php else: ?>
          <p style="color: rgba(229,231,235,0.86);">No sponsorship submitted yet.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="card" id="profile">
        <h3>👤 My Profile</h3>
        <div class="table-wrap">
          <table>
            <tr><th>Name</th><td><?= htmlspecialchars($member['name']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($member['email']) ?></td></tr>
            <tr><th>Phone</th><td><?= htmlspecialchars($member['phone']) ?></td></tr>
            <tr><th>Type</th><td><?= htmlspecialchars($member['member_type']) ?></td></tr>
            <tr><th>Skills</th><td><?= htmlspecialchars($member['skill_set']) ?></td></tr>
          </table>
        </div>
      </div>

      <div class="card">
        <h3>✏️ Update Profile</h3>
        <form method="POST">
          <label>Phone</label>
          <input name="phone" value="<?= htmlspecialchars($member['phone']) ?>">

          <label>Skills</label>
          <textarea name="skill_set" rows="3"><?= htmlspecialchars($member['skill_set']) ?></textarea>

          <button name="update_profile" type="submit">Update</button>
        </form>
      </div>

      <div class="card">
        <h3>🔑 Update Password</h3>
        <form method="POST">
          <label>New Password</label>
          <input type="password" name="new_password" required>

          <label>Confirm Password</label>
          <input type="password" name="confirm_password" required>

          <button name="update_password" type="submit">Change Password</button>
        </form>
      </div>

      <div class="card" id="tasks">
        <h3>📌 Assigned Tasks</h3>
        <div class="table-wrap">
          <table>
            <tr><th>Type</th><th>Title</th><th>Link</th><th>Date</th></tr>
            <?php while ($t = $assigned_tasks->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($t['type']) ?></td>
              <td><?= htmlspecialchars($t['title']) ?></td>
              <td><a href="<?= htmlspecialchars($t['url']) ?>" target="_blank">Open</a></td>
              <td><?= htmlspecialchars($t['assigned_at']) ?></td>
            </tr>
            <?php endwhile; ?>
          </table>
        </div>
      </div>

      <div class="card">
        <h3>📅 Events</h3>
        <?php while ($e = $events->fetch_assoc()): ?>
          <hr>
          <h4><?= htmlspecialchars($e['name']) ?> (<?= htmlspecialchars($e['type']) ?>)</h4>

          <form method="POST">
            <input type="hidden" name="event_id" value="<?= htmlspecialchars($e['id']) ?>">

            <?php if (strtolower($member['member_type']) === 'volunteer'): ?>
              <label>Availability</label>
              <select name="availability">
                <option value="available">Available</option>
                <option value="not_available">Not Available</option>
              </select>
              <button name="respond_event" type="submit">Submit</button>

            <?php elseif (strtolower($member['member_type']) === 'general'): ?>
              <label>Describe your proposed activity for this event</label>
              <textarea name="activity" placeholder="Describe your activity" required rows="3"></textarea>
              <button name="respond_event" type="submit">Submit for Approval</button>

            <?php elseif (strtolower($member['member_type']) === 'alumni'): ?>
              <?php
                $google_form_url = '';
                $event_prefill = isset($e['name']) ? urlencode($e['name']) : '';
              ?>

              <?php if ($google_form_url): ?>
                <a class="btn" target="_blank" href="<?= htmlspecialchars($google_form_url . (strpos($google_form_url, '?') === false ? '?' : '&') . 'event=' . $event_prefill) ?>">Sponsor via Google Form</a>
              <?php else: ?>
                <label>Organization / Company</label>
                <input name="organization" placeholder="Organization / Company" required>

                <label>Designation</label>
                <input name="designation" placeholder="Your designation (optional)">

                <label>Amount (Taka) — must be &lt; 10000</label>
                <input name="amount" type="number" min="1" max="9999" step="1" placeholder="Amount" required>

                <label>Notes</label>
                <textarea name="notes" placeholder="Additional notes (optional)" rows="2"></textarea>

                <button name="sponsor_event" type="submit">Submit Sponsorship Request</button>
              <?php endif; ?>
            <?php endif; ?>
          </form>

        <?php endwhile; ?>
      </div>

    </div>
  </main>
</div>

<?php include 'footer.php'; ?>


