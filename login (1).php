<?php
session_start();

// Enable error reporting (for development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted properly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_no'], $_POST['user_code'])) {
    $id_no = trim($_POST['id_no']);
    $user_code = trim($_POST['user_code']);

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT * FROM login WHERE id_no = ? AND user_code = ?");
    $stmt->bind_param("ss", $id_no, $user_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // If login successful
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // You can store session variables here
        $_SESSION['user_id_no'] = $user['id_no']; // or user ID/username

        // Redirect to user dashboard
        header("Location: userdashboard.php?id_no=" . urlencode($user['id_no']));
        exit();
    } else {
        // Login failed
        header("Location: Login1.php?error=1");
        exit();
    }
} else {
    // Direct access without POST
    header("Location: Login1.php");
    exit();
}
?>
