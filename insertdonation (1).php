<?php
// =======================================================
// === CONFIGURATION & DEBUGGING ===
// =======================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

const MAINTENANCE_FEE = 5.00;

// --- TalkSasa API Configuration (IMPORTANT: Replace with your actual credentials) ---
const API_KEY   = "568|EYdgWpoVxXxMAEJxPNV8GznW0GD1fk3PndtCmUBnb6656386";
const SENDER_ID = "DIGITALBODA";
const SMS_URL   = "https://bulksms.talksasa.com/api/v3/sms/send";


// --- DATABASE CONNECTION ---
// Assuming 'connect.php' defines and connects to $con (mysqli object)
include 'connect.php';

// Check database connection status
if ($con->connect_error) {
    die("Database Connection Failed: " . $con->connect_error);
}

// =======================================================
// === HELPER FUNCTIONS ===
// =======================================================

/**
 * Function to clean and format phone number for TalkSasa (expects +254XXXXXXXXX)
 */
function formatPhoneNumber($number): string {
    $number = (string) $number;
    // Remove all non-digit characters except the leading '+'
    $cleanedNumber = preg_replace('/[^\d+]/', '', $number);

    if (str_starts_with($cleanedNumber, '+254')) {
        return $cleanedNumber;
    } elseif (str_starts_with($cleanedNumber, '254')) {
        return '+' . $cleanedNumber;
    } elseif (str_starts_with($cleanedNumber, '07') || str_starts_with($cleanedNumber, '01')) {
        // Change 07... or 01... to +2547... or +2541...
        return '+254' . substr($cleanedNumber, 1);
    } elseif (strlen($cleanedNumber) === 9 && (str_starts_with($cleanedNumber, '7') || str_starts_with($cleanedNumber, '1'))) {
        // Handle 9-digit numbers starting with 7 or 1 (e.g., 7XXXXXXXX)
        return '+254' . $cleanedNumber;
    }

    return '';
}

/**
 * Sends an SMS using the TalkSasa API.
 * Returns true on successful API response, false otherwise.
 */
function sendSmsNotification($recipient, $message, $senderId, $apiUrl, $apiKey): bool {
    $postData = json_encode([
        'recipient' => $recipient,
        'message'   => $message,
        'sender_id' => $senderId
    ]);

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Keep this if TalkSasa uses a self-signed/untrusted cert

    $response = curl_exec($ch);
    $success = false;

    if (curl_errno($ch)) {
        error_log("cURL Error during SMS send to $recipient: " . curl_error($ch));
    } else {
        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
            $success = true;
        } else {
            // Log API specific error message
            error_log("TalkSasa API Error: " . ($decodedResponse['message'] ?? 'Unknown error') . " for recipient: " . $recipient);
        }
    }
    curl_close($ch);
    return $success;
}


// =======================================================
// === INPUT HANDLING AND VALIDATION ===
// =======================================================

$data = $_POST;

// Get fields from the POST data and sanitize/cast
$fullname = $data['fullname'] ?? '';
$national_id_input = $data['national_id'] ?? '';
$phone = $data['phone'] ?? '';
$donation_title = $data['donation_title'] ?? '';
$amount = (float)($data['amount'] ?? 0);
$mpesa_code = $data['mpesa_code'] ?? '';
$transaction_time = date('Y-m-d H:i:s');
// Use the constant MAINTENANCE_FEE for consistency
$maintenance_fee_charged = MAINTENANCE_FEE;

// --- VALIDATION CHECK ---
if (empty($mpesa_code)) {
    die("Error: M-Pesa code is required and cannot be empty.");
}

// =======================================================
// === DUPLICATE M-PESA CHECK ===
// =======================================================

$stmt_duplicate = $con->prepare("SELECT COUNT(*) FROM donations WHERE mpesa_code = ?");
if (!$stmt_duplicate) {
    die("Prepare failed for duplicate check: " . $con->error);
}
$stmt_duplicate->bind_param("s", $mpesa_code);
$stmt_duplicate->execute();
$stmt_duplicate->bind_result($count);
$stmt_duplicate->fetch();
$stmt_duplicate->close();

if ($count > 0) {
    die("Error: Donation with M-Pesa code **{$mpesa_code}** has already been recorded. This prevents duplicate transactions.");
}

// =======================================================
// === ID LOOKUP & FALLBACK ===
// =======================================================

$national_id = $national_id_input; // Start with the input value

// 1. Check if the input ID is the short 5-character user_code
if (strlen($national_id_input) === 5) {
    $stmt_lookup = $con->prepare("SELECT id_no FROM login WHERE user_code = ?");

    if ($stmt_lookup) {
        $stmt_lookup->bind_param("s", $national_id_input);
        $stmt_lookup->execute();
        $result_lookup = $stmt_lookup->get_result();
        $row = $result_lookup->fetch_assoc();
        
        if ($row && !empty($row['id_no'])) {
            // A match was found! Replace the 5-character user_code with the long id_no
            $national_id = $row['id_no'];
        } else {
            error_log("INFO: Donation processed using unlinked 5-char code: " . $national_id_input);
        }
        $stmt_lookup->close();
    }
}

// =======================================================
// === CORE LOGIC: INSERT DONATION & CALCULATE POINTS ===
// =======================================================

// Insert into donations table
$stmt = $con->prepare("INSERT INTO donations (fullname, national_id, phone, donation_title, amount, maintenance_fee, mpesa_code, transaction_time)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Prepare failed for donations insertion: " . $con->error);
}

// Note: binding 'sssddsss' (string, string, string, string, double, double, string, string)
$stmt->bind_param("ssssddss", $fullname, $national_id, $phone, $donation_title, $amount, $maintenance_fee_charged, $mpesa_code, $transaction_time);
$stmt->execute();

if ($stmt->error) {
    // Check if the error is a duplicate key error (e.g., if mpesa_code unique constraint failed after the initial check)
    if ($stmt->errno === 1062) {
        die("Error: This M-Pesa code has already been recorded (Database constraint error).");
    }
    die("Error inserting into donations: " . $stmt->error);
}

// Close the statement
$stmt->close();

// --- LOGIC TO CALCULATE POINTS ---
// Calculates 2 points for every KSh 50
$points = floor($amount / 50) * 2;
$total_paid = $amount + $maintenance_fee_charged;

// =======================================================
// === SMS NOTIFICATION TO DONOR (USING HELPER) ===
// =======================================================

$sms_message = "Hey $fullname, we've received your donation of KSh " . number_format($amount, 2) . ". You have acquired $points points. Thank you for your support!";

$formatted_phone = formatPhoneNumber($phone);
if (!empty($formatted_phone)) {
    sendSmsNotification(
        $formatted_phone, 
        $sms_message, 
        SENDER_ID, 
        SMS_URL, 
        API_KEY
    );
} else {
    error_log("Skipped Donor SMS: Invalid phone number format for user: " . $fullname . " (Input: " . $phone . ")");
}

// =======================================================
// === SMS NOTIFICATION TO SUPPORT CONTACT (IMPROVED) ===
// =======================================================

// 1. Find the support contact details
$stmt_support = $con->prepare("
    SELECT name, phone_no
    FROM support
    WHERE title = ?
    LIMIT 1
");

if (!$stmt_support) {
    error_log("Prepare failed for support lookup: " . $con->error);
} else {
    $stmt_support->bind_param("s", $donation_title);
    $stmt_support->execute();
    $result_support = $stmt_support->get_result();
    $support_data = $result_support->fetch_assoc();
    $stmt_support->close();

    if ($support_data) {
        $support_name = $support_data['name'];
        $support_phone = $support_data['phone_no'];
        $formatted_support_phone = formatPhoneNumber($support_phone);
        
        // 2. Calculate the total donation for the specific title *after* the current insertion
        $stmt_total = $con->prepare("
            SELECT SUM(amount) AS total_donation_amount
            FROM donations
            WHERE donation_title = ?
        ");
        
        if ($stmt_total) {
            $stmt_total->bind_param("s", $donation_title);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();
            $total_data = $result_total->fetch_assoc();
            $stmt_total->close();

            // Total amount must now include the current donation since it was just inserted
            $total_amount_for_title = (float)($total_data['total_donation_amount'] ?? $amount);

            if (!empty($formatted_support_phone)) {
                // Construct the SMS message for the support contact
                $support_sms_message = "Hello $support_name, you have received KSh " . number_format($amount, 2) . " from $fullname. The new total amount for **" . $donation_title . "** is now KSh " . number_format($total_amount_for_title, 2) . ".";

                // Send the SMS using the helper function
                sendSmsNotification(
                    $formatted_support_phone,
                    $support_sms_message,
                    SENDER_ID,
                    SMS_URL,
                    API_KEY
                );

            } else {
                error_log("Skipped Support SMS: Invalid phone number format for support contact: " . $support_name . " (Input: " . $support_phone . ")");
            }

        } else {
            error_log("Prepare failed for total donation lookup: " . $con->error);
        }

    } else {
        error_log("INFO: No support contact found for donation title: " . $donation_title);
    }
}


// Close database connection
$con->close();

// =======================================================
// === SUCCESS OUTPUT (HTML) ===
// =======================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🎉 Thank You!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
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
            color: #10b981; /* Tailwind green-500 */
            margin-bottom: 15px;
        }
        .card p {
            font-size: 16px;
            color: #333;
            margin: 15px 0;
            line-height: 1.6;
        }
        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 25px;
            background: #2563eb; /* Tailwind blue-600 */
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .back-btn:hover {
            background: #1d4ed8; /* Tailwind blue-700 */
        }
        strong {
            color: #059669; /* Tailwind green-600 */
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>🎉 Donation Received!</h2>
        <p>Thank you **<?= htmlspecialchars($fullname) ?>** for your generous donation of 
            **KSh <?= number_format($amount, 2) ?>** (towards **<?= htmlspecialchars($donation_title) ?>**).
        </p>
        <p>You have acquired **<?= $points ?>** points!</p>
        <a href="Login1.php" class="back-btn">Go to Dashboard</a>
    </div>
</body>
</html>
<?php exit; ?>