<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Secure access check — only allow if called from correct JS
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'AdeelSecureFetch') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// JazzTV API setup
$user_id = '10855401';
$url = 'https://jazztv.pk/alpha/api_gateway/index.php/media/live-tv';

// Send POST request to JazzTV
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "user_id=$user_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data from JazzTV API']);
    exit;
}

$data = json_decode($response, true);
if (!isset($data['eData'])) {
    http_response_code(500);
    echo json_encode(['error' => 'No encrypted data found']);
    exit;
}

// Decrypt
$iv = 'fpmjlrbhpljoennm';
$key = 'pplfe775xvye8j81elpo9b14d9c09098';
$ciphertext = hex2bin($data['eData']);
$decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

if ($decrypted === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Decryption failed']);
    exit;
}

$decryptedData = json_decode($decrypted, true);
if (!$decryptedData || !isset($decryptedData['data']['channels'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid decrypted data']);
    exit;
}

echo json_encode($decryptedData['data']['channels']);
