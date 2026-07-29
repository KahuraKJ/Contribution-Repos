<?php
$data = file_get_contents("php://input");
$log = json_decode($data, true);

function formatPhoneNumber($phone) {
    // Remove non-digits
    $phone = preg_replace('/\D/', '', $phone);

    // Convert 2547XXXXXXXX to 07XXXXXXXX
    if (preg_match('/^254(7|1)\d{8}$/', $phone)) {
        return '0' . substr($phone, 3);
    }

    // Return as-is if already in correct 07 format
    if (preg_match('/^(07|01)\d{8}$/', $phone)) {
        return $phone;
    }

    // Invalid or unsupported format
    return null;
}

if (isset($log['Body']['stkCallback']['ResultCode']) && $log['Body']['stkCallback']['ResultCode'] == 0) {
  $mpesa_code = $log['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
  $amount = $log['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
  $raw_phone = $log['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];


   $formatted_phone = formatPhoneNumber($raw_phone);

    if ($formatted_phone) {
        // Save to database
$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
  $stmt = $conn->prepare("INSERT INTO payment_registration (mpesa_code, phone, amount) VALUES (?, ?, ?)");
  $stmt->bind_param("ssi", $mpesa_code, $formatted_phone, $amount);
  $stmt->execute();
  $stmt->close();
  $conn->close();
}
}
?>
