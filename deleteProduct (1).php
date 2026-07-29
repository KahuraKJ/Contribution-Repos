<?php
// Database connection
$host = "localhost";
$user = "digita51_enock";
$pass = "digita51_enock";
$db   = "digita51_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_GET['id_no'])) {
    $id_no = $_GET['id_no'];

    // Optional: Fetch the image path to delete the file from server
    $sql = "SELECT image_path FROM products WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product && file_exists($product['image_path'])) {
        unlink($product['image_path']); // delete the image file
    }

    // Delete product record
    $sql = "DELETE FROM products WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);

    if ($stmt->execute()) {
        header("Location: viewPhotos.php?msg=Product+deleted+successfully");
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
