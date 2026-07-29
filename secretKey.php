<?php
// Generate 32 cryptographically secure random bytes
$random_bytes = openssl_random_pseudo_bytes(32); 

// Convert the random bytes to a URL-safe Base64 string (43 characters long)
$secret_key_base64 = base64_encode($random_bytes); 

// Display the generated key
echo "Generated 32-Byte Key (Base64 Encoded):\n";
echo $secret_key_base64;
// Example Output: U3d0NUJkY3c2eW44RkR0d1h1WnpvNmxYZEF6SW9iM1Y=
?>