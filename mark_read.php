<?php
session_start ();
include 'connect.php';

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];
// Make sure the member exists
$stmt = $con->prepare("SELECT id_no FROM login WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "Invalid user.";
    exit;
}

// Insert unread posts into post_reads for this specific user (using id_no)
$sql = "
    INSERT INTO post_reads (id_no, post_id)
    SELECT ?, p.id
    FROM post p
    WHERE p.status = 'unread'
    AND p.id NOT IN (
        SELECT post_id FROM post_reads WHERE id_no = ?
    )
";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $id_no, $id_no);
$stmt->execute();
$stmt->close();

$con->close();
echo "Marked as read for $id_no";
?>
