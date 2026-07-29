<?php
// fetch_admins.php
header('Content-Type: application/json');
require_once 'connect.php'; // Include your database connection file

$sql = "SELECT id_no, user_code, full_name FROM admin"; // Assuming your table is 'admin' and columns are id_no, user_code, admin_name
$result = $conn->query($sql);

$admins = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

echo json_encode($admins);

$conn->close();
?>