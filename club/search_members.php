<?php
include('db.php');

$term = trim($_GET['term'] ?? '');
if ($term === '') exit;

$stmt = $conn->prepare(
    "SELECT id, name, email
     FROM members
     WHERE name LIKE ? OR email LIKE ?
     ORDER BY name ASC
     LIMIT 10"
);

$like = "%{$term}%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo '<div class="search-item"
              data-id="'.$row['id'].'"
              data-name="'.htmlspecialchars($row['name']).'">
          '.htmlspecialchars($row['name']).' ('.$row['email'].')
          </div>';
}
