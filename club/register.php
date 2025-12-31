<?php
session_start();
include('db.php');

$error = $success = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $member_type = $_POST['member_type'];
    $join_date = date('Y-m-d');

  
    $position = $_POST['position'] ?? null;
    $dept = $_POST['dept'] ?? null;
    $status = $_POST['status'] ?? null;
    $availability_status = $_POST['availability_status'] ?? null;
    $assigned_hours = $_POST['assigned_hours'] ?? null;
    $skill_set = $_POST['skill_set'] ?? null;
    $graduation_year = $_POST['graduation_year'] ?? null;
    $degree = $_POST['degree'] ?? null;
    $designation = $_POST['designation'] ?? null;
    $current_org = $_POST['current_org'] ?? null;

    //duplicate email
    $stmt = $conn->prepare("SELECT id FROM members WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $error = "Email already exists.";
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO members
        (name,email,phone,password,member_type,join_date,position,dept,status,availability_status,assigned_hours,skill_set,graduation_year,degree,designation,current_org,approved)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)");
        $stmt->bind_param("ssssssssssisssss",
            $name,$email,$phone,$password,$member_type,$join_date,$position,$dept,$status,$availability_status,$assigned_hours,$skill_set,$graduation_year,$degree,$designation,$current_org
        );

        if($stmt->execute()){
            $success = "Registration successful! Pending admin approval.";
        } else {
            $error = "Error during registration. Please try again.";
        }
        $stmt->close();
    }
}
?>
<?php $hide_nav = true; include 'header.php'; ?>

<style>
:root{
  --bg1:#0b1220;
  --bg2:#070b14;
  --panel: rgba(255,255,255,0.06);
  --border: rgba(255,255,255,0.12);
  --text: #e5e7eb;
  --muted: rgba(148,163,184,0.95);
  --primary:#60a5fa;
  --success:#22c55e;
  --danger:#ef4444;
}

body{
  background:
    radial-gradient(1000px 700px at 20% 20%, rgba(96,165,250,0.22), transparent 60%),
    radial-gradient(900px 600px at 80% 30%, rgba(34,197,94,0.14), transparent 60%),
    linear-gradient(180deg, var(--bg2) 0%, var(--bg1) 100%);
  color: var(--text);
}

.auth-wrap{
  min-height: calc(100vh - 60px);
  display:flex;
  align-items:center;
  justify-content:center;
  padding: 28px 16px 40px;
}

.topbar{
  position: fixed;
  top:0; left:0; right:0;
  height:64px;
  z-index: 50;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding: 10px 16px;
  background: rgba(2,6,23,0.55);
  border-bottom: 1px solid rgba(255,255,255,0.10);
  backdrop-filter: blur(10px) saturate(1.2);
}
.topbar .logo{
  display:flex;
  align-items:center;
  gap:10px;
  font-weight: 900;
  color: rgba(229,231,235,0.95);
  text-decoration:none;
}
.dot{
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 0 4px rgba(96,165,250,0.20);
}
.topbar .links a{
  text-decoration:none;
  color: rgba(229,231,235,0.92);
  padding: 9px 10px;
  border-radius: 12px;
  border: 1px solid transparent;
  background: rgba(255,255,255,0.03);
  margin-left: 8px;
}
.topbar .links a:hover{
  background: rgba(96,165,250,0.18);
  border-color: rgba(96,165,250,0.40);
}

.card{
  width: 720px;
  max-width: 100%;
  border-radius: 18px;
  padding: 18px;
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--border);
  box-shadow: 0 18px 60px rgba(0,0,0,0.30);
  backdrop-filter: blur(10px) saturate(1.15);
  margin-top: 70px; /* keep below topbar */
}

.header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.header h2{
  margin:0;
  font-size: 26px;
}
.header p{
  margin:6px 0 0 0;
  color: var(--muted);
  font-size: 14px;
}

.alert{
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.06);
  margin-bottom: 12px;
}
.alert.success{
  border-color: rgba(34,197,94,0.35);
  background: rgba(34,197,94,0.10);
}
.alert.error{
  border-color: rgba(239,68,68,0.35);
  background: rgba(239,68,68,0.10);
}

.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media(max-width: 720px){
  .grid{ grid-template-columns: 1fr; }
}

label{
  display:block;
  margin: 10px 0 6px;
  font-size: 13px;
  color: rgba(229,231,235,0.92);
}

input, select{
  width: 100%;
  padding: 11px 12px;
  font-size: 13px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.14);
  background: rgba(2,6,23,0.35);
  color: rgba(229,231,235,0.95);
  outline: none;
}
input::placeholder{ color: rgba(148,163,184,0.85); }

.actions{
  display:flex;
  gap:10px;
  align-items:center;
  margin-top: 14px;
  flex-wrap: wrap;
}

.btnx{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding: 11px 14px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  font-weight: 900;
  background: rgba(96,165,250,0.92);
  color: #081427;
  transition: transform .12s ease, opacity .12s ease;
}
.btnx:hover{ transform: translateY(-1px); opacity: .95; }

.link{
  color: rgba(147,197,253,0.95);
  text-decoration:none;
}
.link:hover{ opacity:.9; }

.note{
  margin-top: 12px;
  color: var(--muted);
}
</style>

<div class="topbar">
  <a class="logo" href="home.php"><span class="dot"></span> Club Management</a>
  <div class="links">
    <a href="login.php">Login</a>
    <a href="home.php">Home</a>
  </div>
</div>

<div class="auth-wrap">
  <div class="card">
    <div class="header">
      <div>
        <h2>Create your account</h2>
        <p>Choose your member type and submit your registration. Admin approval is required.</p>
      </div>
    </div>

    <?php if($error): ?>
      <div class="alert error"><strong>⚠️</strong> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert success"><strong>✅</strong> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="grid">
        <div>
          <label>Full Name</label>
          <input type="text" name="name" placeholder="Full Name" required>
        </div>

        <div>
          <label>Email</label>
          <input type="email" name="email" placeholder="Email" required>
        </div>

        <div>
          <label>Phone</label>
          <input type="text" name="phone" placeholder="Phone">
        </div>

        <div>
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required>
        </div>
      </div>

      <label style="margin-top:12px;">Member Type</label>
      <select name="member_type" required>
        <option value="">--Select--</option>
        <option value="General">General Member</option>
        <option value="Volunteer">Volunteer</option>
        <option value="Alumni">Alumni</option>
      </select>

      <div class="actions">
        <button type="submit" class="btnx">Register</button>
        <a class="link" href="login.php">Already have an account? Login</a>
      </div>

      <div class="note">
        After registration, your account will remain <strong>pending</strong> until an admin approves it.
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>


