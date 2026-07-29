<?php
// Set headers for JSON response
header('Content-Type: application/json');

@include 'connect.php'; // Include your database connection file

// Check for database connection
if ($con->connect_error) {
    echo json_encode(['success' => false, 'message' => "Database connection failed."]);
    exit;
}

// Function to generate a unique filename
function generateUniqueFileName($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $unique_name = md5(uniqid(rand(), true)) . '.' . $extension;
    return $unique_name;
}

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo']) && isset($_POST['id_no'])) {
    $id_no = $con->real_escape_string($_POST['id_no']);
    $photo = $_FILES['profile_photo'];

    // Define upload directory relative to the script
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
    }

    // File validation
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($photo['error'] !== UPLOAD_ERR_OK) {
        $response = ['success' => false, 'message' => 'File upload failed with error code: ' . $photo['error']];
    } elseif (!in_array($photo['type'], $allowed_types)) {
        $response = ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    } elseif ($photo['size'] > $max_size) {
        $response = ['success' => false, 'message' => 'File size exceeds the maximum limit (5MB).'];
    } else {
        // All checks passed, proceed with saving the file
        $new_file_name = generateUniqueFileName($photo['name']);
        $destination = $upload_dir . $new_file_name;

        // Move the uploaded file to the destination directory
        if (move_uploaded_file($photo['tmp_name'], $destination)) {
            // Update the database with the photo path
            $sql_update = "UPDATE personal SET photo_path = ? WHERE id_no = ?";
            $stmt = $con->prepare($sql_update);
            
            if ($stmt) {
                $stmt->bind_param("ss", $destination, $id_no);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Photo uploaded and record updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Database update failed: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Failed to prepare database statement.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Failed to save the uploaded file.'];
        }
    }

    $con->close();
    echo json_encode($response);
    exit;
} else {
    // Invalid request
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>