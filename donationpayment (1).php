<?php
$data = json_decode(file_get_contents("php://input"), true);
$phone = $data['phone'];
$amount = $data['amount'];

$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);

// Fetch latest unused matching payment
$stmt = $conn->prepare("SELECT mpesa_code FROM payment_donation 
                        WHERE phone = ? AND amount = ? AND used = 0 
                        ORDER BY id DESC LIMIT 1");
$stmt->bind_param("si", $phone, $amount);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  // Optionally mark as used to prevent reuse
  $update = $conn->prepare("UPDATE payment_donation SET used = 1 WHERE mpesa_code = ?");
  $update->bind_param("s", $row['mpesa_code']);
  $update->execute();

  echo json_encode(["found" => true, "mpesa_code" => $row['mpesa_code']]);
} else {
  echo json_encode(["found" => false]);
}
?>
