<?php
// Include DB connection
include 'connect.php';

// Use POST data
$data = $_POST;

// Sanitize input
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$target = filter_var($data['target'] ?? '', FILTER_VALIDATE_FLOAT);
$date = trim($data['endDate'] ?? '');  // Use 'endDate' from form
$type = trim($data['type'] ?? '');

// Validate input
if (
    empty($title) ||
    empty($description) ||
    $target === false || $target <= 0 ||
    empty($date) ||
    empty($type)
) {
    http_response_code(400);
    echo "Invalid input data.";
    exit;
}

// Prepare and execute insert
$stmt = $con->prepare("INSERT INTO campaigns (title, description, target, date, type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdss", $title, $description, $target, $date, $type);

if ($stmt->execute()) {
    
$campaignId = $stmt->insert_id; // Get the inserted campaign ID
header("Location: donationAdminSuccess.php?id=" . $campaignId);
exit();


} else {
    http_response_code(500);
    echo "Failed to create campaign.";
}

// Close connections
$stmt->close();
$con->close();

?>
