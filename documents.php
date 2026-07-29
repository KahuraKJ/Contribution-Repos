<?php
session_start();
// Enable error reporting for debugging (REMOVE in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CONFIGURATION ---
// Database connection
$host = "localhost";    
$user = "digita51_enock";        
$pass = "digita51_enock";                
$db   = "digita51_portal";  

// NEW: Set the maximum allowed file size (in MB)
$MAX_FILE_SIZE_MB = 5; 
$MAX_FILE_SIZE_BYTES = $MAX_FILE_SIZE_MB * 1024 * 1024; // Convert to bytes
// ---------------------

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Function to handle file upload and database update for a specific document type
function handleFileUpload($conn, $id_no, $fileInputName, $dbColumnName, $maxBytes) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
        
        $allowedType = 'application/pdf';
        $fileType = $_FILES[$fileInputName]['type'];
        $fileSize = $_FILES[$fileInputName]['size']; // NEW: Get file size
        
        // 1. Validate File Type
        if ($fileType != $allowedType) {
            return "Error: Only PDF files are allowed for " . $fileInputName . ".";
        }

        // 2. Validate File Size (NEW LOGIC)
        if ($fileSize > $maxBytes) {
            $maxMB = round($maxBytes / 1024 / 1024);
            return "Error: File size for " . $fileInputName . " exceeds the limit of " . $maxMB . "MB.";
        }
        
        $targetDir = "uploads/";  
        if (!is_dir($targetDir)) {
            // Note: Use a secure permission like 0755 or 0770 instead of 0777
            if (!mkdir($targetDir, 0755, true)) {
                return "Error: Could not create upload directory.";
            } 
        }

        // Whitelist check for column name security
        $allowedColumns = ['image_path', 'dl_path'];
        if (!in_array($dbColumnName, $allowedColumns)) {
            return "Error: Invalid database column name provided.";
        }

        $fileName = basename($_FILES[$fileInputName]["name"]);
        // Unique file path generation: time_idno_documentType_fileName
        $targetFilePath = $targetDir . time() . "_" . $id_no . "_" . $fileInputName . "_" . $fileName;  

        // Move uploaded file
        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFilePath)) {
            
            // 3. Check if a record exists for this ID
            $checkStmt = $conn->prepare("SELECT id_no FROM documents WHERE id_no = ?");
            $checkStmt->bind_param("s", $id_no);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $checkStmt->close();

            // Note: Using backticks (`) for column name is good practice
            if ($result->num_rows > 0) {
                // Update existing record
                $updateStmt = $conn->prepare("UPDATE documents SET `$dbColumnName` = ? WHERE id_no = ?");
                $updateStmt->bind_param("ss", $targetFilePath, $id_no);
                
                if (!$updateStmt->execute()) {
                    return "Database update failed for $dbColumnName: " . $updateStmt->error;
                }
                $updateStmt->close();
            } else {
                // Insert new record
                $insertStmt = $conn->prepare("INSERT INTO documents (id_no, `$dbColumnName`) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $id_no, $targetFilePath);

                if (!$insertStmt->execute()) {
                    return "Database insert failed for $dbColumnName: " . $insertStmt->error;
                }
                $insertStmt->close();
            }
            return "Success"; // Successfully uploaded and saved path
        } else {
            // Check for common move_uploaded_file errors
            $error = ($_FILES[$fileInputName]["error"] !== UPLOAD_ERR_OK) ? " (PHP Error Code: " . $_FILES[$fileInputName]["error"] . ")" : "";
            return "Error uploading the file for " . $fileInputName . "." . $error;
        }
    }
    // No file was selected for this input
    return null; 
}

if (isset($_POST['submit'])) {
    $messages = [];
    $redirect = true; // Assume success until a failure occurs

    // Handle National ID Upload
    $result_id = handleFileUpload($conn, $id_no, 'national_id', 'image_path', $MAX_FILE_SIZE_BYTES);

    if ($result_id !== null) {
        if ($result_id !== "Success") {
            $messages[] = $result_id;
            $redirect = false;
        }
    }

    // Handle DL Upload
    $result_dl = handleFileUpload($conn, $id_no, 'dl_document', 'dl_path', $MAX_FILE_SIZE_BYTES);

    if ($result_dl !== null) {
        if ($result_dl !== "Success") {
            $messages[] = $result_dl;
            $redirect = false;
        }
    }

    if ($redirect && ($result_id == "Success" || $result_dl == "Success")) {
        // Only redirect if at least one file was successfully uploaded
        header("Location: userdashboard.php");
        exit();
    } elseif (!empty($messages)) {
        // Display any errors that occurred
        echo "<div class='card'>";
        echo "<h3>Upload Errors:</h3><ul>";
        foreach ($messages as $msg) {
            echo "<li>" . htmlspecialchars($msg) . "</li>";
        }
        echo "</ul>";
        echo "<a href='#' onclick='window.history.back()' class='back-link'>&larr; Go Back</a>";
        echo "</div>";
        // Close connection and exit to prevent HTML form from rendering below the error
        $conn->close();
        exit(); 
    } else {
        echo "<div class='card'>";
        echo "No documents were selected for upload.";
        echo "</div>";
        $conn->close();
        exit();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload ID and DL (PDFs)</title>
    <style>
        /* CSS styles remain the same for aesthetics */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f7f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            box-sizing: border-box;
        }

        .card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #08072b; /* Dark Blue heading */
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        
        /* Note added for user guidance */
        .file-note {
            display: block;
            font-size: 12px;
            color: #FF5500;
            margin-top: -15px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #FFA500; /* Orange focus border */
            box-shadow: 0 0 5px rgba(255, 165, 0, 0.2);
        }

        .form-group input[type="file"] {
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #08072b; /* Dark Blue button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #FF5500; /* Orange hover effect */
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #08072b;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Upload National ID & DL (PDFs)</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="id_no">User ID NO:</label>
                <input type="text" name="id_no" id="id_no" value="<?php echo htmlspecialchars($id_no); ?>" readonly required>
            </div>

            <hr>

            <div class="form-group">
                <label for="national_id">Select **National ID** (PDF):</label>
                <span class="file-note">Max file size: <?php echo $MAX_FILE_SIZE_MB; ?> MB</span>
                <input type="file" name="national_id" id="national_id" accept="application/pdf" required>
            </div>

            <div class="form-group">
                <label for="dl_document">Select **Driver's License** (PDF):</label>
                <span class="file-note">Max file size: <?php echo $MAX_FILE_SIZE_MB; ?> MB</span>
                <input type="file" name="dl_document" id="dl_document" accept="application/pdf" required>
            </div>

            <button type="submit" name="submit" class="submit-btn">Upload Documents</button>
        </form>
    </div>
</body>
</html>