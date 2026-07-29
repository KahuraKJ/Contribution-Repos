<?php
include 'connect.php';

$member_id = $_GET['member_id'] ?? '';

if (!$member_id) {
  echo json_encode([]);
  exit;
}

$stmt = $con->prepare("SELECT id, member_id, admin_id, admin_name, message, created_at 
                        FROM member_admin_notes 
                        WHERE member_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
  $comments[] = $row;
}

echo json_encode($comments);
?>
