<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$code = trim($body['code'] ?? '');

if (!$email || !$code) {
    echo json_encode(['status' => 'failed', 'message' => 'Email and code are required.']);
    exit;
}

list($success, $message) = verify_code_check($email, $code);
if ($success) {
    echo json_encode(['status' => 'success', 'message' => $message]);
} else {
    echo json_encode(['status' => 'failed', 'message' => $message]);
}
