<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

$stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$userExists = $result->num_rows > 0;
$stmt->close();

if ($userExists) {
    echo json_encode(['status' => 'error', 'message' => 'Email already registered. Please use a different email.']);
    exit;
}

$code = random_int(100000, 999999);
store_verification_code($email, $code);

if (!send_verification_email($email, $code)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email. Please try again later.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Verification code sent to email.']);
