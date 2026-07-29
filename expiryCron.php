<?php
set_time_limit(0);

// Error reporting for Cron execution (logs errors instead of printing to screen)
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', 'daily_alerts_error.log');

// --- DB config ---
define('DB_HOST', 'localhost');
define('DB_USER', 'digita51_enock');
define('DB_PASS', 'digita51_enock');
define('DB_NAME', 'digita51_portal');

// --- TalkSasa API Configuration ---
$talksasaApiKey= "568|EYdgWpoVxXxMAEJxPNV8GznW0GD1fk3PndtCmUBnb6656386";
$talksasaSenderId = "DIGITALBODA";
$talksasaUrl = "https://bulksms.talksasa.com/api/v3/sms/send";


// --- PDO Connection ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("CRON DB Connection Failed: " . $e->getMessage());
    exit(1); // Exit with error status
}


// =================================================================
// --- HELPER FUNCTIONS (Copy from displayProfile.php) ---
// =================================================================

function formatPhoneNumber($number) {
    $cleanedNumber = preg_replace('/[^\d+]/', '', $number);
    // ... (rest of your formatPhoneNumber logic)
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

function sendSmsNotification($recipient, $message, $apiKey, $senderId, $url) {
    // ... (rest of your sendSmsNotification logic)
    $formattedPhone = formatPhoneNumber($recipient);
    if (empty($formattedPhone)) {
        error_log("SMS failed: Invalid recipient phone number provided: " . $recipient);
        return false;
    }

    $postData = json_encode([
        'recipient' => $formattedPhone,
        'message' => $message,
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
    curl_setopt(CURLOPT_RETURNTRANSFER, true);
    curl_setopt(CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
        error_log("Daily SMS Alert SUCCESS to {$recipient}.");
        return true;
    } else {
        error_log("Daily SMS Alert FAILED to {$recipient}. Code: {$httpCode}, Error: {$error}");
        return false;
    }
}


// function getStatus - simplified for CRON check
function getStatus($expiryDate) {
    if (empty($expiryDate)) {
        return false; // Not expired
    }
    try {
        $today = new DateTime('today');
        $expiry = new DateTime($expiryDate);
        return ($today > $expiry); // Returns true if expired
    } catch (\Exception $e) {
        return false; // Not expired (due to bad date)
    }
}


$sql = "SELECT
    p.full_name, c.contact_number, t.driving_license_number, t.policy_number,
    t.insurance_company, t.license_expiry_date, t.policy_expiry_date
FROM personal p
JOIN contact_address c ON p.id_no = c.id_no
JOIN mode_of_travel t ON p.id_no = t.id_no
WHERE t.license_expiry_date IS NOT NULL OR t.policy_expiry_date IS NOT NULL";

$stmt = $pdo->query($sql);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$alertsSent = 0;

error_log("Starting Daily Alert Check. Found " . count($members) . " members to check.");

foreach ($members as $data) {
    $notificationMessage = '';
    $fullName = $data['full_name'];
    $contactNumber = $data['contact_number'];

    $isLicenseExpired = getStatus($data['license_expiry_date']);
    $isPolicyExpired = getStatus($data['policy_expiry_date']);

    // Check if License is expired
    if ($isLicenseExpired) {
        $licenseNumber = $data['driving_license_number'] ?? 'N/A';
        $notificationMessage .= "Hi {$fullName}, your Driving License (No: {$licenseNumber}) has EXPIRED. Please renew it immediately. ";
    }

    // Check if Policy is expired
    if ($isPolicyExpired) {
        $policyNumber = $data['policy_number'] ?? 'N/A';
        $insuranceCompany = $data['insurance_company'] ?? 'N/A';
        
        if (!empty($notificationMessage)) {
             $notificationMessage .= "Also, "; 
        } else {
            $notificationMessage .= "Hi {$fullName}, ";
        }
        
        $notificationMessage .= "your Insurance Policy (No: {$policyNumber} with {$insuranceCompany}) has EXPIRED. Please renew it immediately.";
    }

    // If any document is expired, send the aggregated SMS
    if (!empty($notificationMessage)) {
        sendSmsNotification(
            $contactNumber, 
            trim($notificationMessage), 
            $talksasaApiKey, 
            $talksasaSenderId, 
            $talksasaUrl
        );
        $alertsSent++;
    }
}

error_log("Daily Alert Check Complete. {$alertsSent} alerts sent.");
?>