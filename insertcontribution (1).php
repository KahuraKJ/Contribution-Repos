<?php
// Define the fixed fee (can be passed via POST but hardcoding here is safer against client manipulation)
$MAINTENANCE_FEE = 5.00;

include 'connect.php';

// --- TalkSasa API Configuration (IMPORTANT: Replace with your actual credentials) ---
$apiKey = "568|EYdgWpoVxXxMAEJxPNV8GznW0GD1fk3PndtCmUBnb6656386";
$senderId = "DIGITALBODA";
$url = "https://bulksms.talksasa.com/api/v3/sms/send";

// Function to clean and format phone number for TalkSasa (expects +254XXXXXXXXX)
function formatPhoneNumber($number) {
  // FIX: Ensure $number is a string to avoid the Deprecated warning
  $number = (string) $number;
  $cleanedNumber = preg_replace('/[^\d+]/', '', $number);

  if (str_starts_with($cleanedNumber, '+254')) {
    return $cleanedNumber;
  } elseif (str_starts_with($cleanedNumber, '254')) {
    return '+' . $cleanedNumber;
  } elseif (str_starts_with($cleanedNumber, '07') || str_starts_with($cleanedNumber, '01')) {
    return '+254' . substr($cleanedNumber, 1);
  } elseif (strlen($cleanedNumber) === 9 && (str_starts_with($cleanedNumber, '7') || str_starts_with($cleanedNumber, '1'))) {
    return '+254' . $cleanedNumber;
  }

  return '';
}

// Function to calculate days between two dates
function calculateDays($startDate) {
  if (!$startDate) return 0;
  try {
    $today = new DateTime();
    $start = new DateTime($startDate);
    $interval = $start->diff($today);
    return $interval->days;
  } catch (Exception $e) {
    return 0;
  }
}

$data = $_POST;

// Get fields from the POST data
$fullname = $data['fullname'] ?? '';
$national_id_input = $data['national_id'] ?? '';
$phone = $data['phone'] ?? '';
// $data['amount'] is the user's input (e.g., 1.00), which we will confirm later.
$frequency = $data['contribution_type'] ?? '';
$mpesa_code = $data['mpesa_code'] ?? '';
$transaction_time = date('Y-m-d H:i:s');
$maintenance_fee_charged = (float)($data['maintenance_fee'] ?? $MAINTENANCE_FEE);

// =======================================================
// === CORE FIX START: VERIFY AND CALCULATE AMOUNT ===
// =======================================================

if (empty($mpesa_code)) {
  die("Error: M-Pesa code is required and cannot be empty.");
}

// 1. Fetch the total paid amount from the M-Pesa log table (payment_contribution)
$stmt_log = $con->prepare("SELECT amount FROM payment_contribution WHERE mpesa_code = ? AND used = 1");
if (!$stmt_log) {
  die("Prepare failed for payment log check: " . $con->error);
}
$stmt_log->bind_param("s", $mpesa_code);
$stmt_log->execute();
$result_log = $stmt_log->get_result();
$log_row = $result_log->fetch_assoc();
$stmt_log->close();

if (!$log_row) {
  die("Error: Could not verify M-Pesa transaction **{$mpesa_code}** in payment log. Payment may not be marked as used or transaction ID is wrong.");
}

$total_paid = (float)($log_row['amount'] ?? 0); // e.g., 6.00

// 2. Calculate the base contribution amount (amount that goes to the member's credit)
$amount = $total_paid - $MAINTENANCE_FEE; // e.g., 6.00 - 5.00 = 1.00

if ($amount < 1) {
  // Requires a minimum contribution of 1 KES after fee is deducted
  die("Error: Calculated contribution amount (KSh " . number_format($amount, 2) . ") is too low after maintenance fee.");
}

// =======================================================
// === CORE FIX END: VERIFY AND CALCULATE AMOUNT ===
// =======================================================


// =======================================================
// === MODIFICATION START: DUPLICATE M-PESA CHECK ===
// =======================================================

$stmt_duplicate = $con->prepare("SELECT COUNT(*) FROM contributions WHERE mpesa_code = ?");
if (!$stmt_duplicate) {
  die("Prepare failed for duplicate check: " . $con->error);
}
$stmt_duplicate->bind_param("s", $mpesa_code);
$stmt_duplicate->execute();
$stmt_duplicate->bind_result($count);
$stmt_duplicate->fetch();
$stmt_duplicate->close();

if ($count > 0) {
  // Stop the script and display the error message.
  die("Error: Contribution with M-Pesa code **{$mpesa_code}** has already been recorded. This prevents duplicate transactions.");
}

// =======================================================
// === MODIFICATION END: DUPLICATE M-PESA CHECK ===
// =======================================================


// =======================================================
// === CORE LOGIC: ID LOOKUP AND FALLBACK (No Change) ===
// =======================================================

$final_national_id = $national_id_input;

// 1. Check if the input ID is the short 5-character user_code
if (strlen($national_id_input) === 5) {
  // Attempt to find the corresponding long ID (id_no)
  $stmt_lookup = $con->prepare("SELECT id_no FROM login WHERE user_code = ?");

  if ($stmt_lookup) {
    $stmt_lookup->bind_param("s", $national_id_input);
    $stmt_lookup->execute();
    $result_lookup = $stmt_lookup->get_result();
    $row = $result_lookup->fetch_assoc();
   
    if ($row && !empty($row['id_no'])) {
      // A match was found! Replace the 5-character user_code with the 7/8-character id_no
      $final_national_id = $row['id_no'];
    } else {
      error_log("INFO: Contribution processed using unlinked 5-char code: " . $national_id_input);
    }
    $stmt_lookup->close();
  }
}

// IMPORTANT: ALL further database operations must now use $final_national_id
$national_id = $final_national_id;

$daily_fee = 10;
$points = 0;
$end_date = 'N/A';


// 1. Fetch member's historical data from the database
$stmt_fetch = $con->prepare("
  SELECT SUM(amount) as total_paid, MIN(transaction_time) as first_transaction_time
  FROM contributions
  WHERE national_id = ? AND contribution_type = 'Advocacy(MCC)'
");
$stmt_fetch->bind_param("s", $national_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
$member_data = $result->fetch_assoc();

// NOTE: $amount is now the calculated base contribution (e.g., 1.00)
$total_paid_so_far = (float)($member_data['total_paid'] ?? 0);
$first_transaction_time = $member_data['first_transaction_time'] ?? date('Y-m-d');

// 2. Calculate the member's financial standing *before* the new contribution
$days_active = calculateDays($first_transaction_time);
$total_expected = $days_active * $daily_fee;
$current_balance = $total_paid_so_far - $total_expected;

// 3. Insert the new contribution into the contributions table
// IMPORTANT: We insert the calculated base contribution ($amount = 1.00)
$stmt_insert = $con->prepare("INSERT INTO contributions (fullname, national_id, phone, amount,maintenance_fee, contribution_type, mpesa_code, transaction_time)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt_insert) {
  die("Prepare failed for contributions: " . $con->error);
}
// We use $amount, which is the calculated base contribution (e.g., 1.00)
$stmt_insert->bind_param("ssddssss", $fullname, $national_id, $phone, $amount,$MAINTENANCE_FEE, $frequency, $mpesa_code, $transaction_time);
$stmt_insert->execute();

if ($stmt_insert->error) {
  die("Error inserting into contributions: " . $stmt_insert->error);
}


// 4. Recalculate the member's financial standing *with* the new contribution
$new_total_paid = $total_paid_so_far + $amount; // $amount is the base contribution (e.g., 1.00)
$new_balance = $new_total_paid - $total_expected;

// 5. Determine points and end date based on the new balance
if ($new_balance >= 0) {
  // Debt is cleared, calculate points from the surplus
  $remaining_for_points = $new_balance;
  $points = floor($remaining_for_points / $daily_fee);

  // Get the end date from the current date plus the new points
  $end_date = date('Y-m-d', strtotime("+$points days"));

    // --- SMS MESSAGE UPDATE (Success) ---
    $sms_message = "Hey $fullname, your contribution of KSh "  . number_format($amount, 2) . " has cleared any past debt. You've earned $points, and your membership is now valid until $end_date.";
} else {
  // Contribution did not fully clear the debt
  $debt_remaining = abs($new_balance);
  $points = 0;
  $end_date = 'N/A';
    
    // --- SMS MESSAGE UPDATE (Debt) ---
  $sms_message = "Hey $fullname, your contribution of KSh " . number_format($amount, 2) ."has been applied to your outstanding debt. Your remaining debt is KSh " . number_format($debt_remaining, 2) . ".";
  
}

// --- SMS SENDING LOGIC (No Change) ---
$formatted_phone = formatPhoneNumber($phone);

if (!empty($formatted_phone)) {
  $postData = json_encode([
    'recipient' => $formatted_phone,
    'message' => $sms_message,
    'sender_id' => $senderId
  ]);

  $headers = [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

  $response = curl_exec($ch);

  if (curl_errno($ch)) {
    error_log("cURL Error during SMS send: " . curl_error($ch));
  } else {
    $decodedResponse = json_decode($response, true);
    if (isset($decodedResponse['status']) && $decodedResponse['status'] !== 'success') {
      error_log("TalkSasa API Error: " . ($decodedResponse['message'] ?? 'Unknown error'));
    }
  }
  curl_close($ch);
} else {
  error_log("Skipped SMS: Invalid phone number format for user: " . $fullname);
}


// Success card message
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contribution Received!</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
     font-family: Arial, sans-serif;
     background: #f3f4f6;
     display: flex;
     justify-content: center;
     align-items: center;
     height: 100vh;
    }
    .card {
     background: #fff;
     padding: 30px 40px;
     border-radius: 12px;
     box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
     text-align: center;
     max-width: 500px;
    }
    .card h2 {
     color: green;
     margin-bottom: 10px;
    }
    .card p {
     font-size: 18px;
     color: #333;
     margin: 10px 0;
    }
    .back-btn {
     display: inline-block;
     margin-top: 20px;
     padding: 10px 25px;
     background: #2563eb;
      color: white;
      border: none;
      border-radius: 5px;
      text-decoration: none;
    }
    .back-btn:hover {
      background: #1d4ed8;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>🎉 Contribution Received!</h2>
        <p>Thank you <strong><?= htmlspecialchars($fullname) ?></strong> for your contribution of <strong>KSh <?= number_format($amount, 2) ?></strong> 
           <p>You have earned <strong><?= $points ?></strong> points, and your membership will last until <strong><?= $end_date ?></strong>.</p>
    
    <a href="userdashboard.php" class="back-btn">Go to Dashboard</a>
  </div>
</body>
</html>
<?php exit; ?>