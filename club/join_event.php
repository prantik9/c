<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['member_type'] !== 'General') {
    die("Access denied.");
}

if (empty($_POST['event_id'])) {
    die("Invalid request.");
}

$member_id = intval($_SESSION['user_id']);
$event_id  = intval($_POST['event_id']);


$check = $conn->prepare( //prevent duplicate 
    "SELECT id FROM event_participation WHERE member_id = ? AND event_id = ?"
);
$check->bind_param("ii", $member_id, $event_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header("Location: ../events.php?msg=already_joined");
    exit;
}
$check->close();

$stmt = $conn->prepare( // Insert participation
    "INSERT INTO event_participation (member_id, event_id) VALUES (?, ?)" 
);
$stmt->bind_param("ii", $member_id, $event_id);

if ($stmt->execute()) {
    header("Location: ../events.php?msg=success");
} else {
    die("Something went wrong.");
}

$stmt->close();
$conn->close();
