<?php
session_start();
require_once 'db.php';
include 'header.php';

$memberId = $_SESSION['user_id'] ?? null;
$website_role = $_SESSION['website_role'] ?? null;
$counts = [];
$statusList = ['upcoming', 'ongoing', 'completed'];

foreach ($statusList as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $counts[$status] = $count;
    $stmt->close();
}

$eventsByStatus = [];

foreach ($statusList as $status) {
    $stmt = $conn->prepare(
        "SELECT id, name, event_date, venue, description, type
         FROM events
         WHERE status = ?
         ORDER BY event_date ASC"
    );
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $eventsByStatus[$status] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<main style="max-width: 1200px; margin: 0 auto; padding: 30px 20px;">

    <section class="welcome">
        <h1>📅 Events</h1>
        <p>Stay updated with upcoming events, ongoing activities, and completed sessions.</p>
    </section>

    <section class="info-card">
        <h3 style="margin:0 0 8px 0; color:#042532;">Event Stats</h3>
        <p style="margin:0; color:rgba(2,18,18,0.8);">
            📌 <strong><?= $counts['upcoming'] ?></strong> Upcoming | 
            ⏱️ <strong><?= $counts['ongoing'] ?></strong> Ongoing | 
            ✅ <strong><?= $counts['completed'] ?></strong> Completed
        </p>
    </section>

    <style>

    .status-card .status-title { display:inline-block; padding:8px 12px; border-radius:8px; font-weight:700; }
    .status-card .event-name { padding:10px; border-radius:6px; }
    .status-card.status-upcoming .status-title { background: linear-gradient(90deg,#fff7ed,#fffbeb); color:#92400e; }
    .status-card.status-ongoing .status-title { background: linear-gradient(90deg,#ecfeff,#cffafe); color:#065f46; }
    .status-card.status-completed .status-title { background: linear-gradient(90deg,#f0fdf4,#dcfce7); color:#065f46; }
    .status-card.status-upcoming .event-name { background: rgba(255,243,199,0.4); }
    .status-card.status-ongoing .event-name { background: rgba(207,250,254,0.4); }
    .status-card.status-completed .event-name { background: rgba(220,252,231,0.4); }
    .event-card { background: #fff; border-radius:8px; padding:8px; border:1px solid #eef2f7; }
    .status-list { display:flex; flex-direction:column; gap:10px; }
    .status-title { margin-bottom:8px; }
    .empty-note { color:#6b7280; font-style:italic; }
    @media (max-width:1000px){ .event-section { grid-template-columns:1fr 1fr; } }
    @media (max-width:700px){ .event-section { grid-template-columns:1fr; } }
    </style>

    <div class="event-section" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">

        <?php foreach ($statusList as $status): ?>
        <div class="event-column">
            <div class="status-card status-<?= htmlspecialchars($status) ?>">
                <div class="status-title"><?= ucfirst($status) ?> (<?= intval($counts[$status]) ?>)</div>
                <div class="status-list">
                    <?php if (!empty($eventsByStatus[$status])): ?>
                        <?php foreach ($eventsByStatus[$status] as $event): ?>
                            <div class="event-card">
                                <div class="event-name" onclick="toggleEvent('event-<?= $event['id'] ?>')">
                                    <span><?= htmlspecialchars($event['name']) ?></span>
                                    <span class="mini-arrow" id="arrow-event-<?= $event['id'] ?>">▾</span>
                                </div>
                                <div class="event-details" id="event-<?= $event['id'] ?>">
                                    <table class="theme-attachment">
                                        <tr><th>Date</th><td><?= htmlspecialchars($event['event_date']) ?></td></tr>
                                        <tr><th>Venue</th><td><?= htmlspecialchars($event['venue']) ?></td></tr>
                                        <tr><th>Type</th><td><?= htmlspecialchars($event['type']) ?></td></tr>
                                        <tr><th>Description</th><td><?= htmlspecialchars($event['description']) ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-note">No <?= htmlspecialchars($status) ?> events.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

</main>

<?php include 'footer.php'; ?>

<script>
function toggleStatus(status) {
    const body = document.getElementById('status-' + status);
    const arrow = document.getElementById('arrow-' + status);
    body.classList.toggle('active');
    arrow.classList.toggle('active');
}

function toggleEvent(id) {
    const el = document.getElementById(id);
    const arrow = document.getElementById('arrow-' + id);
    el.classList.toggle('active');
    arrow.classList.toggle('active');
}
</script>
