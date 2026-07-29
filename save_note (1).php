<?php
// save_note.php
session_start();
header('Content-Type: application/json');
require_once 'connect.php'; // Include your database connection

$response = ["success" => false, "message" => ""];

// --- ⚠️ IMPORTANT: Replace these with your actual session variables ⚠️ ---
$admin_id = $_SESSION['admin_id'] ?? 'A_001';
$admin_name = $_SESSION['admin_name'] ?? 'System Admin'; 
// ----------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);

    $member_id = trim($data['member_id'] ?? '');
    $message = trim($data['message'] ?? '');

    if (empty($member_id) || empty($message)) {
        $response["message"] = "Member ID and message are required.";
        echo json_encode($response);
        exit;
    }

    try {
        if (!$con) throw new Exception("Database connection failed.");

        $sql = "INSERT INTO member_admin_notes (member_id, admin_id, admin_name, message, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $con->prepare($sql);
        
        if (!$stmt) throw new Exception("Error preparing statement: " . $con->error);
        
        $stmt->bind_param("ssss", $member_id, $admin_id, $admin_name, $message);

        if ($stmt->execute()) {
            $response["success"] = true;
            $response["message"] = "Note saved successfully!";
            $stmt->close();
        } else {
            throw new Exception("Database execution failed: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response["message"] = "Error saving note: " . $e->getMessage();
    } finally {
        if (isset($con) && is_object($con)) $con->close();
    }
} else {
    $response["message"] = "Invalid request method.";
}

echo json_encode($response);
?>