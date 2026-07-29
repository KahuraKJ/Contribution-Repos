<?php

// === 1. Decryption Constants ===
// These MUST match the constants used on your referral dashboard page.
define('SECRET_KEY', base64_decode('oRqf6i1+DZVSP0LTjB0NlLyhp2xni7faAMP2rX3Q7vM='));
define('CIPHER_METHOD', 'AES-256-CBC');

/**
 * Decrypts the referral token to get the original member ID.
 * @param string $token The URL-encoded and Base64-encoded token.
 * @return string|false The original member ID (plaintext) or false on failure.
 */
function decryptReferralToken($token) {
    if (empty($token)) {
        return false;
    }
    
    // 1. URL Decode the token (reverses urlencode())
    $token_decoded = urldecode($token);

    // 2. Base64 Decode the token (reverses base64_encode())
    $base64_decoded = base64_decode($token_decoded);

    // 3. Split the raw binary string into Encrypted Data and IV using '::'
    $parts = explode('::', $base64_decoded, 2);

    if (count($parts) !== 2) {
        return false; // Invalid token format
    }

    $encrypted_raw = $parts[0];
    $iv          = $parts[1];

    // 4. Verify IV length
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
    if (strlen($iv) !== $iv_length) {
        return false; // Invalid IV length
    }

    // 5. Perform the decryption
    $original_id = openssl_decrypt(
        $encrypted_raw,
        CIPHER_METHOD,
        SECRET_KEY,
        OPENSSL_RAW_DATA, 
        $iv
    );
    
    // Check for decryption failure (returns false if key/data is wrong)
    if ($original_id === false) {
        return false;
    }

    // Successfully decrypted ID
    return $original_id;
}

// --- Logic to handle form submission ---
$decrypted_id = null;
$status_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_token = isset($_POST['referral_token']) ? trim($_POST['referral_token']) : '';
    
    if (empty($input_token)) {
        $status_message = "🔴 Please enter a referral token.";
    } else {
        $decrypted_id = decryptReferralToken($input_token);
        
        if ($decrypted_id === false) {
            $status_message = "❌ Decryption Failed. The token is invalid, corrupted, or the key/IV is wrong.";
        } else {
            $status_message = "✅ Successfully Decrypted ID!";
        }
    }
}

// --- Logic to grab token from URL (for real use) ---
$url_token = isset($_GET['ref']) ? $_GET['ref'] : '';
if (!empty($url_token) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If a token is provided in the URL, decrypt it automatically
    $decrypted_id = decryptReferralToken($url_token);
    if ($decrypted_id !== false) {
        $status_message = "✅ Referrer ID automatically detected and decrypted from URL.";
    } else {
        $status_message = "❌ Automatic Decryption from URL failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Referral Decryption Tool</title>
</head>
<body>
    <h1>Referral ID Decryption</h1>

    <?php if (!empty($status_message)): ?>
        <p style="font-weight: bold; color: green;"><?php echo $status_message; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="referral_token">Enter Encrypted Token (ref= value):</label><br>
        <input type="text" id="referral_token" name="referral_token" size="80" 
               placeholder="Paste the full Base64-encoded token here..." required><br><br>
        <button type="submit">Decrypt Token</button>
    </form>

    <hr>

    <h2>Decrypted Referrer ID</h2>
    <?php if ($decrypted_id !== null && $decrypted_id !== false): ?>
        <p style="font-size: 1.2em; color: darkblue;">
            The original Referrer ID is: <b><?php echo htmlspecialchars($decrypted_id); ?></b>
        </p>
    <?php elseif ($decrypted_id === false): ?>
        <p style="color: red;">Decryption failed. Could not retrieve the ID.</p>
    <?php else: ?>
        <p>Awaiting token input or URL parameter.</p>
    <?php endif; ?>
</body>
</html>