<?php
include 'db.php';
$result = $conn->query('SELECT id, name FROM events ORDER BY id DESC LIMIT 10');
while($row = $result->fetch_assoc()) {
    echo 'ID: ' . $row['id'] . ', Name: ' . $row['name'] . PHP_EOL;
}
?>