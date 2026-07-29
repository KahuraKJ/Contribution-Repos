<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$phone = $data['phone'] ?? '';
$amount = $data['amount'] ?? 1;

// Safaricom Credentials
$consumerKey = 'AyF0WM0Uzk8V4EbnA8avuSoLTDo0HVgQDdexJoAfooyGsoiX';
$consumerSecret = 'pQwrbEIvJKAQ4lgQHgVjhN8vglhUdbcDkNLtQZTre0O8oVTQ3F63GaeLmNtwGeTI';
$shortcode = '4149511';
$passkey = 'ed9a99eb44061eac83c1db58f3e7d0d080500e0b4e096a5bcaf12ff781e5522c';
$callback_url = "https://portal.digitalboda.co.ke/donationcallback.php";

// Validate phone number
if (!preg_match('/^0(1\d{8}|7\d{8})$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}
$phone = '254' . substr($phone, 1);

// Get access token
$credentials = base64_encode("$consumerKey:$consumerSecret");
$ch = curl_init('https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
file_put_contents("access_token_log.txt", $response);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$access_token = json_decode($response)->access_token ?? null;

if (!$access_token) {
    echo json_encode(['success' => false, 'message' => 'Failed to get access token','debug' =>$response]);
    exit;
}

// Build STK Push payload
$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);

$payload = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => (int)$amount,
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callback_url,
    'AccountReference' => 'Uplift',
    'TransactionDesc' => 'Membership Payment'
];

// Make STK Push request
// Make STK Push request
$ch = curl_init('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$response = json_decode($result, true);

// Return result
if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
    echo json_encode(['success' => true, 'message' => 'STK Push Sent', 'response' => $response]);
} else {
    echo json_encode(['success' => false, 'message' => $response['errorMessage'] ?? 'Unknown error', 'raw' => $response]);
}
