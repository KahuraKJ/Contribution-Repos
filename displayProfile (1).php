<?php
// displayProfile.php

include 'header.php';
session_start();

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- TalkSasa API Configuration (Define these here or include a config file) ---
$talksasaApiKey = "568|EYdgWpoVxXxMAEJxPNV8GznW0GD1fk3PndtCmUBnb6656386";
$talksasaSenderId = "DIGITALBODA";
$talksasaUrl = "https://bulksms.talksasa.com/api/v3/sms/send";


// Define uploads directory for clarity and maintainability
define('UPLOAD_DIR', 'uploads/');

// DB config
define('DB_HOST', 'localhost');
define('DB_USER', 'digita51_enock');
define('DB_PASS', 'digita51_enock');
define('DB_NAME', 'digita51_portal');

// --- Function to clean and format phone number for TalkSasa (expects +254XXXXXXXXX) ---
function formatPhoneNumber($number) {
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

// --- Function to send the SMS notification via TalkSasa (FIXED cURL CALLS) ---
function sendSmsNotification($recipient, $message, $apiKey, $senderId, $url) {
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
    
    // THE FIX: Ensure $ch is the first argument in ALL curl_setopt calls
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
        error_log("SMS Alert SUCCESS to {$recipient}. Message: {$message}");
        return true;
    } else {
        error_log("SMS Alert FAILED to {$recipient}. Code: {$httpCode}, Error: {$error}, Response: " . print_r($decodedResponse, true));
        return false;
    }
}


// Connect via PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Fetch user data (UPDATED to include notification status columns)
$sql = "SELECT
    p.full_name, p.id_no, p.gender, TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
    c.contact_number, c.email_address,
    t.plate_number, t.driving_license_number, t.mode_of_transport,
    t.license_issue_date, t.license_expiry_date, t.dl_notified_on,
    t.policy_number, t.insurance_company, t.policy_issue_date, t.policy_expiry_date, t.policy_notified_on,
    r.county, r.sub_county, r.ward, r.estate,
    r.home_county, r.home_sub_county, r.home_ward, r.home_estate
    
FROM personal p
LEFT JOIN contact_address c ON p.id_no = c.id_no
LEFT JOIN mode_of_travel t ON p.id_no = t.id_no
LEFT JOIN residential r ON p.id_no = r.id_no 
WHERE p.id_no = :id_no";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_no' => $id_no]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("User not found.");
}

// Fetch the profile image path
$image_path = null;
$imageSql = "SELECT image_path FROM products WHERE id_no = :id_no LIMIT 1";
$imageStmt = $pdo->prepare($imageSql);
$imageStmt->execute(['id_no' => $id_no]);
$imageResult = $imageStmt->fetch(PDO::FETCH_ASSOC);

if ($imageResult) {
    $image_path = $imageResult['image_path'];
}

// Functions (unchanged)
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper($w[0]);
        if (strlen($initials) == 2) break;
    }
    return $initials;
}

function getStatus($expiryDate) {
    if (empty($expiryDate)) {
        return ['text' => 'Not Provided', 'class' => 'status-warning', 'is_expired' => false];
    }
    $today = new DateTime();
    try {
        $expiry = new DateTime($expiryDate);
    } catch (\Exception $e) {
        error_log("Invalid date format: " . $expiryDate);
        return ['text' => 'Invalid Date', 'class' => 'status-expired', 'is_expired' => false];
    }

    $interval = $today->diff($expiry);

    if ($today > $expiry) {
        return ['text' => 'Expired', 'class' => 'status-expired', 'is_expired' => true];
    } elseif ($interval->days < 30) {
        return ['text' => 'Expiring Soon', 'class' => 'status-warning', 'is_expired' => false];
    } else {
        return ['text' => 'Active', 'class' => 'status-active', 'is_expired' => false];
    }
}

$licenseStatus = getStatus($data['license_expiry_date'] ?? null);
$policyStatus = getStatus($data['policy_expiry_date'] ?? null);


// =================================================================
// --- DOCUMENT EXPIRY NOTIFICATION LOGIC (Send Once) ---
// =================================================================

$notificationMessage = '';
$fullName = $data['full_name'];
$contactNumber = $data['contact_number'];
$sendDlAlert = false;
$sendPolicyAlert = false;
$today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format

// 1. Check if License is expired AND NOT notified TODAY
if ($licenseStatus['is_expired'] && ($data['dl_notified_on'] != $today)) {
    $licenseNumber = $data['driving_license_number'] ?? 'N/A';
    $notificationMessage .= "Hi {$fullName}, your Driving License (No: {$licenseNumber}) has EXPIRED. Please renew it immediately. ";
    $sendDlAlert = true;
}

// 2. Check if Policy is expired AND NOT notified TODAY
if ($policyStatus['is_expired'] && ($data['policy_notified_on'] != $today)) {
    $policyNumber = $data['policy_number'] ?? 'N/A';
    $insuranceCompany = $data['insurance_company'] ?? 'N/A';
    
    if (!empty($notificationMessage)) {
         $notificationMessage .= "Also, "; 
    } else {
        $notificationMessage .= "Hi {$fullName}, ";
    }
    
    $notificationMessage .= "your Insurance Policy (No: {$policyNumber} with {$insuranceCompany}) has EXPIRED. Please renew it immediately.";
    $sendPolicyAlert = true;
}

// 3. If any document is expired AND needs notification, send the aggregated SMS
if (!empty($notificationMessage)) {
    // Send the SMS
    $smsSent = sendSmsNotification(
        $contactNumber, 
        trim($notificationMessage), // Send the complete, trimmed message
        $talksasaApiKey, 
        $talksasaSenderId, 
        $talksasaUrl
    );

    // 4. Update the database ONLY if the SMS was successfully sent
    if ($smsSent) {
        $updateFields = [];
        if ($sendDlAlert) {
            $updateFields[] = "dl_notified_on = CURDATE()";
        }
        if ($sendPolicyAlert) {
            $updateFields[] = "policy_notified_on = CURDATE()";
        }

        if (!empty($updateFields)) {
            $updateSql = "UPDATE mode_of_travel SET " . implode(', ', $updateFields) . " WHERE id_no = :id_no";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute(['id_no' => $data['id_no']]);
        }
    }
}

// ... (Rest of your profile display HTML code would follow here)
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Boda Boda Rider Profile</title>
<link rel="stylesheet" href="userdashboard.css">
<style>
/* The style block from your original post is large. 
    I'll only include the new/modified CSS for clarity and space.
*/
.flex-group-container {
    display: flex;
    gap: 20px;
    margin-top: 25px;
    flex-wrap: wrap;
}
.flex-group-item {
    flex: 1;
    min-width: 220px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.flex-group-item h4 {
    margin-top: 0;
}
/* Re-include the rest of your original CSS here if this is the only file */
<?php
// Include the rest of your original CSS here to ensure it works
// (or assume it's in userdashboard.css/the style block is complete)
?>
/* YOUR ORIGINAL CSS GOES HERE */
body, html {
  margin: 0; padding: 0;
  font-family: 'Poppins', sans-serif;
  background-image: url('background.jpeg');
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
}
.form-container {
  background-color: #fff;
  width: 100%;
  max-width: 500px;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  animation: fadeIn 0.5s ease-in-out;
}
.profile-card { text-align: center; }
.profile-pic {
 width: 80px; height: 80px;
 border-radius: 50%; object-fit: cover;
 margin-bottom: 15px;
}
.initials {
 width: 80px; height: 80px;
 display: flex; align-items: center; justify-content: center;
 border-radius: 50%;
 background-color: #F25A2C; color: #fff;
 font-size: 28px; font-weight: bold;
}
h1 { margin-bottom: 10px; color: rgb(30, 30, 61); }
h4 { margin: 10px 0 5px; color: #202155; font-weight: 600; }
p { margin: 0 0 15px; color: #555; font-size: 14px; }
.info { text-align: left; margin-top: 20px; }

.btn {
 display: inline-block;
 padding: 10px 20px;
 color: white;
 border-radius: 8px;
 text-decoration: none;
 font-weight: bold; 
}

/* Specific button colors */
.photo-btn {
  background-color: #F25A2C; 
}

.edit-btn {
  background-color: #202155; 
}

/* Hover effect */
.btn:hover {
  opacity: 0.8; 
}

/* Container to add space between buttons */
.button-container {
  margin-top: 20px;
  display: flex;
  gap: 15px; 
}
.center-wrapper {
  flex: 1; display: flex; justify-content: center; align-items: flex-start;
  padding: 40px 20px;
}
.alert {
  padding: 15px; margin-bottom: 20px; border-radius: 8px;
  text-align: center; font-weight: bold; max-width: 500px;
  margin-left: auto; margin-right: auto;
}
.alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.status {
  padding: 2px 8px; border-radius: 12px; color: white;
  font-size: 0.8em; font-weight: 600;
}
.status-active { background-color: #28a745; }
.status-warning { background-color: #ffc107; }
.status-expired { background-color: #dc3545; }
.document-link img {
  border: 1px solid #ccc; border-radius: 8px;
  padding: 5px; max-width: 100%; height: auto;
}
.document-link { display: block; margin-top: 10px; }
</style>
</head>
<body>
<div class="center-wrapper">
    <div class="form-container">
        <div class="profile-card">
            <?php
            $fullName = $data['full_name'] ?? 'User Name';
            ?>
            <?php if (!empty($image_path) && file_exists($image_path)): ?>
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Profile Photo" class="profile-pic">
            <?php else: ?>
                <div class="initials"><?php echo getInitials($fullName); ?></div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($fullName ?? ''); ?></h1>
            <p>ID No: <?php echo htmlspecialchars($data['id_no'] ?? ''); ?> | Age: <?php echo htmlspecialchars($data['age'] ?? 'N/A'); ?></p>
        </div>

        <div class="info">
            <h4>Personal Details & Contact</h4>
            <p>Gender: <?php echo htmlspecialchars($data['gender'] ?? ''); ?></p>
            <p>Contact: <?php echo htmlspecialchars($data['contact_number'] ?? ''); ?></p>
            <p>Email: <?php echo htmlspecialchars($data['email_address'] ?? ''); ?></p>

            <div class="flex-group-container">
                
                <div class="flex-group-item">
                    <h4 style="color:#202155;">Transport Details</h4>
                    <p>Plate No: <?php echo htmlspecialchars($data['plate_number'] ?? ''); ?></p>
                    <p>Mode of Transport: <?php echo htmlspecialchars($data['mode_of_transport'] ?? '') ; ?></p>
                    <p>Driving License: <?php echo htmlspecialchars($data['driving_license_number'] ?? 'N/A'); ?>
                        <span class="status <?php echo $licenseStatus['class']; ?>"><?php echo $licenseStatus['text']; ?></span>
                    </p>
                    <p>License Issue Date: <?php echo htmlspecialchars($data['license_issue_date'] ?? ''); ?></p>
                    <p>License Expiry Date: <?php echo htmlspecialchars($data['license_expiry_date'] ?? ''); ?></p>
                </div>

                <div class="flex-group-item">
                    <h4 style="color:#202155;">Insurance Details</h4>
                    <p>Policy Number: <?php echo htmlspecialchars($data['policy_number']?? ''); ?></p>
                    <p>Insurance Company: <?php echo htmlspecialchars($data['insurance_company'] ?? ''); ?></p>
                    <p>Policy Issue Date: <?php echo htmlspecialchars($data['policy_issue_date'] ?? ''); ?></p>
                    <p>Policy Expiry Date: <?php echo htmlspecialchars($data['policy_expiry_date'] ?? ''); ?>
                        <span class="status <?php echo $policyStatus['class']; ?>"><?php echo $policyStatus['text']; ?></span>
                    </p>
                </div>
            </div>

            <div class="flex-group-container">
                
                <div class="flex-group-item">
                    <h4 style="color:#202155;">Current Residence</h4>
                    <p>County: <?php echo htmlspecialchars($data['county'] ?? ''); ?></p>
                    <p>Sub-County: <?php echo htmlspecialchars($data['sub_county'] ?? ''); ?></p>
                    <p>Ward: <?php echo htmlspecialchars($data['ward'] ?? ''); ?></p>
                    <p>Estate: <?php echo htmlspecialchars($data['estate'] ?? ''); ?></p>
                </div>

                <div class="flex-group-item">
                    <h4 style="color:#202155;">Home Residence</h4>
                    <p>County: <?php echo htmlspecialchars($data['home_county'] ?? ''); ?></p>
                    <p>Sub-County: <?php echo htmlspecialchars($data['home_sub_county'] ?? ''); ?></p>
                    <p>Ward: <?php echo htmlspecialchars($data['home_ward'] ?? ''); ?></p>
                    <p>Estate: <?php echo htmlspecialchars($data['home_estate'] ?? ''); ?></p>
                </div>
            </div>
            
        </div>
        <div class="button-container">
            <a href="photos.php" class="btn photo-btn">Upload Photo</a>
            
            <a href="editprofile.php" class="btn edit-btn">Edit Profile</a>
        </div>
    </div>
</div>
</body>
</html>