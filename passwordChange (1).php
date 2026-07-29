<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "digita51_enock";
$password = "digita51_enock";
$dbname = "digita51_portal";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed.");
}

// --- TalkSasa API Configuration ---
$apiKey = "568|EYdgWpoVxXxMAEJxPNV8GznW0GD1fk3PndtCmUBnb6656386";
$senderId = "DIGITALBODA";
$url = "https://bulksms.talksasa.com/api/v3/sms/send";

// Function to clean and format phone number for TalkSasa (expects +254XXXXXXXXX)
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

// Function to generate a single unique user code
function generateUniqueUserCode($con) {
    do {
        $code = rand(10000, 99999);
        $stmt = $con->prepare("SELECT 1 FROM login WHERE user_code = ?");
        if ($stmt === false) {
            error_log("Failed to prepare user code check: " . $con->error);
            return false;
        }
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
        $codeExists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($codeExists);
    return $code;
}

// Function to generate three unique user codes
function generateThreeUniqueUserCodes($con) {
    $suggestions = [];
    $count = 0;
    while ($count < 3) {
        $newCode = generateUniqueUserCode($con);
        if ($newCode !== false) {
            $suggestions[] = $newCode;
            $count++;
        }
    }
    return $suggestions;
}

$message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'request_otp':
            $memberNo = $conn->real_escape_string($_POST['member_no']);
            if (empty($memberNo)) {
                $message = 'Member number cannot be empty.';
            } else {
                $sql = "SELECT t1.id_no, t2.contact_number FROM login AS t1 INNER JOIN contact_address AS t2 ON t1.id_no = t2.id_no WHERE t1.user_code = '$memberNo'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $id_no = $row['id_no'];
                    $contactNumber = formatPhoneNumber($row['contact_number']);

                    if (empty($contactNumber)) {
                        $message = 'Invalid phone number format.';
                    } else {
                        $otp = rand(1000, 9999); // 4-digit OTP
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

                        $conn->query("DELETE FROM otp_data WHERE member_no = '$memberNo'");
                        $sql_insert_otp = "INSERT INTO otp_data (member_no, otp, expires_at) VALUES ('$memberNo', '$otp', '$expiresAt')";

                        if ($conn->query($sql_insert_otp) === TRUE) {
                            $message_text = "Your OTP for member number change is: " . $otp . ". It is valid for 24 hours.";
                            $postData = json_encode(['recipient' => $contactNumber, 'message' => $message_text, 'sender_id' => $senderId]);
                            $headers = ["Content-Type: application/json", "Authorization: Bearer $apiKey"];

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            $response = curl_exec($ch);
                            curl_close($ch);
                            
                            $decodedResponse = json_decode($response, true);
                            
                            if (isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
                                $_SESSION['current_member_no'] = $memberNo;
                                $message = 'An OTP has been sent. Please check your registered phone or email.';
                                $step = 2; // Move to step 2 to enter OTP
                            } else {
                                $message = 'Failed to send OTP. Please try again later.';
                            }
                        } else {
                            $message = "Failed to generate OTP. Please try again.";
                        }
                    }
                } else {
                    $message = "Member number not found.";
                }
            }
            break;

        case 'verify_otp':
            $memberNo = $_SESSION['current_member_no'] ?? '';
            $otp = $conn->real_escape_string($_POST['otp']);

            if (empty($memberNo) || empty($otp)) {
                $message = "All fields are required.";
                $step = 2;
                break;
            }

            $sql_otp = "SELECT * FROM otp_data WHERE member_no = '$memberNo' AND otp = '$otp' AND expires_at >= NOW()";
            $result_otp = $conn->query($sql_otp);

            if ($result_otp->num_rows > 0) {
                // OTP is valid, generate and display new codes
                $suggestions = generateThreeUniqueUserCodes($conn);
                if (!empty($suggestions)) {
                    $_SESSION['suggestions'] = $suggestions;
                    $message = "OTP verified. Please select a new user code.";
                    $step = 3;
                } else {
                    $message = "Could not generate unique user codes. Please try again.";
                    $step = 2;
                }
            } else {
                $message = "Invalid or expired OTP.";
                $step = 2;
            }
            break;

        case 'change_user_code':
            $memberNo = $_SESSION['current_member_no'] ?? '';
            $newMemberNo = $conn->real_escape_string($_POST['new_member_no']);
            $suggestions = $_SESSION['suggestions'] ?? [];

            if (empty($memberNo) || empty($newMemberNo) || !in_array($newMemberNo, $suggestions)) {
                $message = "Invalid selection or session expired.";
                $step = 1;
                break;
            }

            // --- Update member number ---
            $sql_update = "UPDATE login SET user_code = '$newMemberNo' WHERE user_code = '$memberNo'";
            if ($conn->query($sql_update) === TRUE) {
                // Get contact number for the SMS confirmation
                $sql_contact = "SELECT t2.contact_number FROM login AS t1 INNER JOIN contact_address AS t2 ON t1.id_no = t2.id_no WHERE t1.user_code = '$newMemberNo'";
                $result_contact = $conn->query($sql_contact);
                $row_contact = $result_contact->fetch_assoc();
                $contactNumber = formatPhoneNumber($row_contact['contact_number']);

                if (!empty($contactNumber)) {
                    // Send SMS with new user code
                    $message_text = "Your Member Number has been successfully updated to: " . $newMemberNo . ".";
                    $postData = json_encode(['recipient' => $contactNumber, 'message' => $message_text, 'sender_id' => $senderId]);
                    $headers = ["Content-Type: application/json", "Authorization: Bearer $apiKey"];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_exec($ch);
                    curl_close($ch);
                }

                session_destroy();
                // Redirect to Login1.php
                header("Location: Login1.php");
                exit();
            } else {
                $message = "Failed to update user code. Please try again.";
                $step = 3;
            }
            break;
    }
}

// Check for existing suggestions in session to maintain state on page reload
if (isset($_SESSION['suggestions']) && $step == 3) {
    $suggestions = $_SESSION['suggestions'];
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Member Number</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #0f0c32;
            --primary-light: #F25A2C;
            --light-gray: #f4f4f9;
            --dark-gray: #333;
            --border-color: #e0e0e0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-container {
            width: 100%;
            max-width: 450px;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .form-container:hover {
            transform: translateY(-5px);
        }
        h2 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        input[type="text"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 5px rgba(242, 90, 44, 0.5);
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-light);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        button:hover {
            background-color: #e64e20;
            transform: translateY(-2px);
        }
        .hidden {
            display: none;
        }
        #message-step1, #message-step2 {
            margin-top: 15px;
            font-weight: 600;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .suggestions-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .suggestion-item {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
        }
        .suggestion-item:hover, .suggestion-item.selected {
            border-color: var(--primary-light);
            background-color: rgba(242, 90, 44, 0.1);
            color: var(--primary-light);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 style="text-transform: uppercase;">Change Member Number 🔑</h2>
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo ($step == 3) ? 'success' : 'error'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <div id="step1-form">
                <p>Enter your current member number to receive a one-time password (OTP).</p>
                <form id="request-otp-form" method="post">
                    <input type="hidden" name="action" value="request_otp">
                    <input type="text" name="member_no" id="member_no" placeholder="Current Member No" required>
                    <button type="submit">Send OTP</button>
                </form>
            </div>
        <?php elseif ($step == 2): ?>
            <div id="step2-form">
                <p>An OTP has been sent. Please check your registered phone. Enter the OTP to proceed.</p>
                <form id="verify-otp-form" method="post">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="text" name="otp" id="otp" placeholder="Enter OTP" required>
                    <button type="submit">Verify OTP</button>
                </form>
            </div>
        <?php elseif ($step == 3): ?>
            <div id="step3-form">
                <p>OTP Verified. Select a new user code from the options below and click to change.</p>
                <form id="change-code-form" method="post">
                    <input type="hidden" name="action" value="change_user_code">
                    <div id="suggestions-container" class="suggestions-container">
                        <?php foreach ($suggestions as $suggestion): ?>
                            <div class="suggestion-item" data-value="<?php echo $suggestion; ?>">
                                <?php echo $suggestion; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="new_member_no" id="new_member_no" value="">
                    <button type="submit">Change Member No</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.suggestion-item').on('click', function() {
                $('.suggestion-item').removeClass('selected');
                $(this).addClass('selected');
                var newValue = $(this).data('value');
                $('#new_member_no').val(newValue);
            });
        });
    </script>
</body>
</html>