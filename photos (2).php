<?php
session_start ();
// Database connection
$host = "localhost";    
$user = "digita51_enock";        
$pass = "digita51_enock";                
$db   = "digita51_portal";  

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
     if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";  
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); 
        }

        $fileName = basename($_FILES["image"]["name"]);
         $targetFilePath = $targetDir . time() . "_" . $id_no . "_" . $fileName;  

        // Move uploaded file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            
            // Step 1: Check if a photo already exists for this ID
            $checkStmt = $conn->prepare("SELECT id_no FROM products WHERE id_no = ?");
            $checkStmt->bind_param("s", $id_no); // Use secure $id_no
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $checkStmt->close(); // Close immediately after getting result

            if ($result->num_rows > 0) {
                $updateStmt = $conn->prepare("UPDATE products SET image_path = ? WHERE id_no = ?");
                $updateStmt->bind_param("ss", $targetFilePath, $id_no); // Use secure $id_no
                
                if ($updateStmt->execute()) {
                    header("Location: displayProfile.php");
                    exit();
                } else {
                    echo "Database update failed: " . $conn->error;
                }
                $updateStmt->close();
            } else {
                // Step 2b: If no record exists, INSERT a new one
                $insertStmt = $conn->prepare("INSERT INTO products (id_no, image_path) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $id_no, $targetFilePath); // Use secure $id_no

                if ($insertStmt->execute()) {
                    header("Location: displayProfile.php");
                    exit(); 
                } else {
                    echo "Database insert failed: " . $conn->error;
                }
                $insertStmt->close();
            }
        } else {
            echo "Error uploading the file.";
        }
    } else {
        echo "No file selected or an error occurred.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Product</title>
    <style>
        /* New Color Palette:
           - Main color: #08072b (Dark Blue)
           - Accent color: #FFA500 (Orange)
           - Background: #f4f7f9
        */

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
            border-color: #FF5500; /* Orange focus border */
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
    </style>
</head>
<body>
    <div class="card">
        <h2>Upload Product</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="id_no">ID NO:</label>
                <input type="text" name="id_no" id="id_no" value="<?php echo htmlspecialchars($id_no); ?>" readonly required>
            </div>

            <div class="form-group">
                <label for="image">Select Image:</label>
                <input type="file" name="image" id="image" accept="image/*" required>
            </div>

            <button type="submit" name="submit" class="submit-btn">Upload</button>
        </form>
    </div>
</body>
</html>