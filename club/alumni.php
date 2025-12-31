<?php
session_start();
include('db.php');

if(!isset($_SESSION['user_id']) || $_SESSION['website_role'] !== 'user'){
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ? AND member_type = 'Alumni'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$alumni = $result->fetch_assoc();
$stmt->close();

if (!$alumni) {
    header("Location: login.php");
    exit;
}

// sponsorship requests
$stmt = $conn->prepare(" 
    SELECT s.*, e.name AS event_name
    FROM event_sponsors s
    JOIN events e ON e.id = s.event_id
    WHERE s.alumni_id = ?
    ORDER BY s.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sponsorships = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alumni Dashboard</title>
<style>
body { font-family:'Segoe UI', sans-serif; background:#f4f4f4; margin:0; }
header { background:#f59e0b; color:white; text-align:center; padding:20px; }
.container { max-width:900px; margin:30px auto; background:white; padding:20px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; }
p { font-size:16px; margin:8px 0; }

.card {
    margin-top: 22px;
    padding: 16px;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 8px 18px rgba(0,0,0,0.06);
}

.table {
    width:100%;
    border-collapse: collapse;
    margin-top: 12px;
}
.table th, .table td {
    border: 1px solid #e6e6e6;
    padding: 10px;
    text-align: left;
    font-size: 14px;
}
.table th { background: #111827; color: #fff; }

.badge {
    display:inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.badge.pending { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }
.badge.approved { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
.badge.denied { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

.small { color:#64748b; font-size: 13px; }
</style>
</head>
<body>
<header><h1>Alumni Dashboard</h1></header>

<div class="container">
    <h2>Welcome <?= htmlspecialchars($alumni['name']) ?></h2>

    <p><strong>Graduation Year:</strong> <?= htmlspecialchars($alumni['graduation_year']) ?></p>
    <p><strong>Degree:</strong> <?= htmlspecialchars($alumni['degree']) ?></p>
    <p><strong>Designation:</strong> <?= htmlspecialchars($alumni['designation']) ?></p>
    <p><strong>Current Organization:</strong> <?= htmlspecialchars($alumni['current_org']) ?></p>
    <p><strong>Department:</strong> <?= htmlspecialchars($alumni['dept']) ?></p>

    <div class="card">
        <h3 style="margin:0 0 8px 0;">💰 My Sponsorship Requests</h3>
        <p class="small" style="margin:0;">Rule: sponsorship amount must be less than <strong>10000 Tk</strong>. Admin will approve one sponsorship per event.</p>

        <?php if ($sponsorships && $sponsorships->num_rows > 0): ?>
            <table class="table">
                <tr>
                    <th>Event</th>
                    <th>Organization</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Admin Comment</th>
                    <th>Submitted</th>
                    <th>Decided At</th>
                </tr>

                <?php while($s = $sponsorships->fetch_assoc()): ?>
                    <?php
                        $st = strtolower($s['status'] ?? 'pending');
                        if ($st !== 'approved' && $st !== 'denied') $st = 'pending';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['event_name'] ?? 'Event') ?></td>
                        <td><?= htmlspecialchars($s['organization'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['amount'] ?? '') ?></td>
                        <td>
                            <?php if ($st === 'approved'): ?>
                                <span class="badge approved">✅ APPROVED</span>
                            <?php elseif ($st === 'denied'): ?>
                                <span class="badge denied">❌ DENIED</span>
                            <?php else: ?>
                                <span class="badge pending">⏳ PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($s['admin_comment'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['created_at'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['decided_at'] ?? '') ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="margin-top:12px;">No sponsorship requests found.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>


