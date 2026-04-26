<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection should be established by config.php first.

function allowed_file($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['png','jpg','jpeg','pdf'], true);
}

function ensure_upload_folder() {
    if (!is_dir(UPLOAD_FOLDER)) {
        mkdir(UPLOAD_FOLDER, 0777, true);
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flash($msg, $type = 'success') {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}

function render_flash() {
    foreach (get_flash() as $flash) {
        $type = htmlspecialchars($flash['type'], ENT_QUOTES);
        $msg = htmlspecialchars($flash['msg'], ENT_QUOTES);
        echo "<div class=\"alert alert-{$type}\">{$msg}</div>";
    }
}

function require_role($role) {
    $userType = strtolower($_SESSION['user_type'] ?? '');
    if ($userType !== strtolower($role)) {
        flash('Access denied!', 'error');
        redirect('/MajayjayScholars/login.php');
    }
}

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function generate_verification_key($email) {
    return 'verify_' . md5(strtolower(trim($email)));
}

function store_verification_code($email, $code) {
    $key = generate_verification_key($email);
    $_SESSION['verification_store'][$key] = [
        'email' => $email,
        'code' => strval($code),
        'verified' => false,
        'created_at' => time(),
        'expires_at' => time() + 600,
    ];
}

function get_verification_data($email) {
    $key = generate_verification_key($email);
    $data = $_SESSION['verification_store'][$key] ?? null;
    if (!$data) {
        return null;
    }
    if (time() > $data['expires_at']) {
        unset($_SESSION['verification_store'][$key]);
        return null;
    }
    return $data;
}

function verify_code_check($email, $code) {
    $data = get_verification_data($email);
    if (!$data) {
        return [false, 'No verification code found. Please request a code first.'];
    }
    $storedCode = trim(strval($data['code']));
    $receivedCode = trim(strval($code));
    if ($storedCode !== $receivedCode) {
        return [false, 'Incorrect verification code.'];
    }
    $_SESSION['verification_store'][generate_verification_key($email)]['verified'] = true;
    return [true, 'Email verified successfully.'];
}

function is_email_verified($email) {
    $data = get_verification_data($email);
    return $data && !empty($data['verified']);
}

function cleanup_verification($email) {
    $key = generate_verification_key($email);
    if (isset($_SESSION['verification_store'][$key])) {
        unset($_SESSION['verification_store'][$key]);
    }
}

function smtp_send_command($socket, $command = null) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return [substr($response, 0, 3), $response];
}

function send_email_smtp($to, $subject, $htmlBody) {
    $host = 'ssl://smtp.gmail.com';
    $port = 465;
    $username = SENDER_EMAIL;
    $password = SENDER_APP_PASSWORD;

    $socket = @fsockopen($host, $port, $errno, $errstr, 30);
    if (!$socket) {
        error_log("SMTP connection failed: {$errno} {$errstr}");
        return false;
    }

    list($code, $response) = smtp_send_command($socket);
    if ($code !== '220') {
        fclose($socket);
        error_log('SMTP welcome error: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, 'EHLO localhost');
    if ($code !== '250') {
        fclose($socket);
        error_log('SMTP EHLO failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, 'AUTH LOGIN');
    if ($code !== '334') {
        fclose($socket);
        error_log('SMTP auth start failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, base64_encode($username));
    if ($code !== '334') {
        fclose($socket);
        error_log('SMTP auth user failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, base64_encode($password));
    if ($code !== '235') {
        fclose($socket);
        error_log('SMTP auth pass failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, 'MAIL FROM: <' . $username . '>');
    if ($code !== '250') {
        fclose($socket);
        error_log('SMTP MAIL FROM failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, 'RCPT TO: <' . $to . '>');
    if ($code !== '250' && $code !== '251') {
        fclose($socket);
        error_log('SMTP RCPT TO failed: ' . $response);
        return false;
    }

    list($code, $response) = smtp_send_command($socket, 'DATA');
    if ($code !== '354') {
        fclose($socket);
        error_log('SMTP DATA failed: ' . $response);
        return false;
    }

    $headers = [];
    $headers[] = 'From: Majayjay Scholars <' . $username . '>';
    $headers[] = 'To: ' . $to;
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.\r\n";
    list($code, $response) = smtp_send_command($socket, $message);
    if ($code !== '250') {
        fclose($socket);
        error_log('SMTP send message failed: ' . $response);
        return false;
    }

    smtp_send_command($socket, 'QUIT');
    fclose($socket);
    return true;
}

function send_verification_email($email, $code) {
    $subject = 'Your Verification Code - Majayjay Scholars Registration';
    $html = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body style=\"margin:0;padding:0;background:#f3f4f6;font-family:'Inter',-apple-system,sans-serif;\">"
        . "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f3f4f6;padding:40px 20px;\">"
        . "<tr><td align=\"center\"><table width=\"600\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.1);\">"
        . "<tr><td style=\"background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:50px 40px;text-align:center;\">"
        . "<h1 style=\"margin:0;font-size:28px;font-weight:700;color:#fff;\">Email Verification</h1>"
        . "<p style=\"margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.9);\">Majayjay Scholars Program</p></td></tr>"
        . "<tr><td style=\"padding:40px 50px;text-align:center;\">"
        . "<p style=\"margin:0 0 30px;font-size:16px;color:#4a5568;\">Use the code below to complete your registration:</p>"
        . "<div style=\"background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:3px;border-radius:16px;display:inline-block;\">"
        . "<div style=\"background:#fff;padding:24px 48px;border-radius:14px;\">"
        . "<div style=\"font-size:36px;font-weight:700;color:#667eea;letter-spacing:8px;font-family:'Courier New',monospace;\">{$code}</div>"
        . "</div></div><p style=\"margin:30px 0 0;font-size:14px;color:#718096;\">Expires in <strong style=\"color:#667eea;\">10 minutes</strong></p></td></tr>"
        . "<tr><td style=\"padding:0 50px 40px;text-align:center;\">"
        . "<p style=\"margin:0;font-size:12px;color:#cbd5e0;\">© 2025 Majayjay Scholars Program. All rights reserved.</p></td></tr>"
        . "</table></td></tr></table></body></html>";
    return send_email_smtp($email, $subject, $html);
}

function send_status_email($email, $name, $status) {
    if ($status === 'approved') {
        $subject = '🎉 Congratulations! Your Scholarship Application is Approved';
        $gradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        $icon = '✓';
        $statusText = 'APPROVED';
        $message = "Congratulations <strong>{$name}</strong>! We are thrilled to inform you that your scholarship application has been <strong>approved</strong>. We are excited to welcome you to the Majayjay Scholars family!";
        $action = 'You will receive further instructions via email regarding the next steps.';
    } else {
        $subject = 'Scholarship Application Status Update';
        $gradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        $icon = '✕';
        $statusText = 'NOT APPROVED';
        $message = "Dear <strong>{$name}</strong>, after careful review, we regret to inform you that your scholarship application has not been approved at this time.";
        $action = 'We encourage you to reapply in the future. Keep striving for excellence!';
    }
    $html = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body style=\"margin:0;padding:0;background:#f3f4f6;font-family:'Inter',-apple-system,sans-serif;\">"
        . "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f3f4f6;padding:40px 20px;\">"
        . "<tr><td align=\"center\"><table width=\"600\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.1);\">"
        . "<tr><td style=\"background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:50px 40px;text-align:center;\">"
        . "<h1 style=\"margin:0;font-size:28px;font-weight:700;color:#fff;\">Majayjay Scholars Program</h1></td></tr>"
        . "<tr><td style=\"padding:40px 40px 20px;text-align:center;\">"
        . "<div style=\"background:{$gradient};color:#fff;padding:16px 32px;border-radius:50px;display:inline-block;\">"
        . "<span style=\"font-size:18px;font-weight:700;\">{$icon} {$statusText}</span></div></td></tr>"
        . "<tr><td style=\"padding:20px 50px;text-align:center;\">"
        . "<p style=\"margin:0 0 20px;font-size:17px;color:#374151;line-height:1.7;\">{$message}</p>"
        . "<p style=\"margin:0;font-size:15px;color:#6b7280;\">{$action}</p></td></tr>"
        . "<tr><td style=\"padding:0 50px 40px;text-align:center;\">"
        . "<p style=\"margin:0;font-size:12px;color:#d1d5db;\">© 2025 Majayjay Scholars. All rights reserved.</p></td></tr>"
        . "</table></td></tr></table></body></html>";
    return send_email_smtp($email, $subject, $html);
}
