<?php
// fetch_notes.php
header('Content-Type: application/json');
require_once 'connect.php'; // Include your database connection

$response = ["success" => false, "notes" => [], "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['member_id'])) {
    $member_id = $_GET['member_id'];

    try {
        if (!$con) throw new Exception("Database connection failed.");

        // SQL to fetch notes, ordered by date (newest first)
        $sql = "SELECT admin_name, message, created_at FROM member_admin_notes WHERE member_id = ? ORDER BY created_at DESC";
        $stmt = $con->prepare($sql);
        
        if (!$stmt) throw new Exception("Error preparing statement: " . $con->error);
        
        $stmt->bind_param("s", $member_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $notes[] = $row;
        }

        $response["success"] = true;
        $response["notes"] = $notes;
        $stmt->close();

    } catch (Exception $e) {
        $response["message"] = "Error fetching notes: " . $e->getMessage();
    } finally {
        if (isset($con) && is_object($con)) $con->close();
    }
} else {
    $response["message"] = "Invalid request or missing member ID.";
}

echo json_encode($response);
?>