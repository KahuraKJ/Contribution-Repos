<?php
// post.php - Handles saving notifications to the database

// Start the session (if 'admin_name' is used from session)
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// --- Database Configuration (Make sure these are correct) ---
$dbHost = 'localhost';
$dbUser = 'digita51_enock';
$dbPass = 'digita51_enock';
$dbName = 'digita51_portal';

// Read raw JSON input from the frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate presence of title and message
if (!isset($data['title']) || empty(trim($data['title'])) || !isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['status' => 'error', 'message' => 'Both notification title and message are required for database entry.']);
    exit;
}
$title = trim($data['title']);
$message = trim($data['message']);

// --- Database Connection ---
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    // Log the error internally for debugging, but provide a generic message to the client
    error_log("DB Connection Error (post.php): " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please try again later.']);
    exit;
}

// --- Save Notification to Database ---
// Get admin name from session, defaulting to 'Admin' if not set
$posted_by = $_SESSION['admin_name'] ?? 'Admin';

$stmt = $conn->prepare("INSERT INTO post (title, message, posted_by, created_at) VALUES (?, ?, ?, NOW())");
if ($stmt) {
    $stmt->bind_param("sss", $title, $message, $posted_by);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Notification successfully saved to database.']);
    } else {
        error_log("DB Insert Error (post.php): " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save notification to database: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    error_log("DB Prepare Error (post.php): " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database statement: ' . $conn->error]);
}

$conn->close(); // Close database connection
?>