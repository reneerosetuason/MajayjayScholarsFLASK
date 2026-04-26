<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email_verified = $_POST['email_verified'] ?? 'false';

    if (!$email || !$password || !$confirm || !$first_name || !$last_name) {
        flash('Please fill out all required fields.', 'error');
    } elseif ($email_verified !== 'true' || !is_email_verified($email)) {
        flash('Please verify your email before registering.', 'error');
    } elseif ($password !== $confirm) {
        flash('Passwords do not match!', 'error');
    } else {
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $already = $result->num_rows > 0;
        $stmt->close();

        if ($already) {
            flash('Email already exists!', 'error');
        } else {
            $stmt = $db->prepare('INSERT INTO users (email, password, first_name, middle_name, last_name, user_type) VALUES (?, ?, ?, ?, ?, "student")');
            $stmt->bind_param('sssss', $email, $password, $first_name, $middle_name, $last_name);
            if ($stmt->execute()) {
                cleanup_verification($email);
                flash('Registration successful! You can now log in.', 'success');
                $stmt->close();
                redirect('login.php');
            } else {
                flash('Registration failed. Please try again.', 'error');
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MajayjayScholars</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f7fafc;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wrapper {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 700px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .image-side {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            position: relative;
        }

        .logo-container {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .image-side img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .brand-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .form-side {
            flex: 1.2;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .form-header {
            margin-bottom: 32px;
        }

        h2 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .subtitle {
            color: #718096;
            font-size: 1rem;
            font-weight: 400;
        }

        form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f7fafc;
            font-family: 'Inter', sans-serif;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input:disabled {
            background: #edf2f7;
            cursor: not-allowed;
            opacity: 0.6;
        }

        input::placeholder {
            color: #a0aec0;
        }

        .send-code-btn,
        .verify-btn,
        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .send-code-btn:hover,
        .verify-btn:hover,
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .send-code-btn:disabled,
        .verify-btn:disabled,
        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .verification-section {
            display: none;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-radius: 12px;
            border: 2px solid #667eea;
        }

        .verification-section.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .verification-section p {
            margin: 0;
            font-size: 0.875rem;
        }

        .code-input-group {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .code-input {
            flex: 2;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.3em;
            border: 2px solid #667eea !important;
            background: white !important;
        }

        .verify-btn {
            flex: 1;
            padding: 14px 20px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .register-btn {
            width: 100%;
            margin-top: 24px;
        }

        .hint {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
        }

        .alert {
            padding: 14px 18px;
            margin-bottom: 24px;
            border-radius: 12px;
            font-size: 0.9rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #48bb78;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f56565;
        }

        .divider {
            text-align: center;
            margin: 32px 0;
            position: relative;
            color: #a0aec0;
            font-size: 0.875rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            background: white;
            padding: 0 16px;
            position: relative;
        }

        .login-link {
            text-align: center;
            color: #718096;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #5a67d8;
        }

        .status-badge {
            margin-left: 10px;
            color: #48bb78;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .wrapper {
                flex-direction: column;
                min-height: auto;
                margin: 10px;
            }

            .image-side {
                padding: 40px 20px;
                min-height: 200px;
            }

            .image-side img {
                width: 80px;
                height: 80px;
            }

            .brand-text {
                font-size: 1.25rem;
            }

            .form-side {
                padding: 40px 30px;
            }

            h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="image-side">
            <div class="logo-container">
                <img src="static/assets/majayjay_logo.jpg" alt="Majayjay Logo">
                <div class="brand-text">MajayjayScholars</div>
            </div>
        </div>

        <div class="form-side">
            <div class="form-header">
                <h2>Create your account</h2>
                <p class="subtitle">Join MajayjayScholars today</p>
            </div>

            <div id="messageContainer"><?php render_flash(); ?></div>

            <form method="POST" action="register.php" id="registerForm">
                <div class="form-group">
                    <label for="email">Email address <span id="emailStatus"></span></label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                </div>

                <button type="button" class="send-code-btn" id="sendCodeBtn">Send Verification Code</button>

                <div class="verification-section" id="verificationSection">
                    <p style="color: #4a5568; font-weight: 600; margin-bottom: 8px;">Check your email for the verification code</p>
                    <p style="color: #718096; margin-bottom: 12px;">Enter the 6-digit code below:</p>
                    <div class="code-input-group">
                        <input type="text" class="code-input" id="verificationCode" placeholder="000000" maxlength="6" pattern="[0-9]{6}">
                        <button type="button" class="verify-btn" id="verifyBtn">Verify</button>
                    </div>
                    <p style="font-size: 0.8rem; color: #a0aec0; margin-top: 10px;">Didn't receive it? <span class="resend-link" id="resendLink" style="cursor:pointer; color:#667eea;">Resend code</span></p>
                </div>

                <div class="form-group">
                    <label for="firstNameField">First name</label>
                    <input type="text" name="first_name" id="firstNameField" placeholder="Enter your first name" required disabled>
                </div>

                <div class="form-group">
                    <label for="middleNameField">Middle name (optional)</label>
                    <input type="text" name="middle_name" id="middleNameField" placeholder="Enter your middle name" disabled>
                </div>

                <div class="form-group">
                    <label for="lastNameField">Last name</label>
                    <input type="text" name="last_name" id="lastNameField" placeholder="Enter your last name" required disabled>
                </div>

                <div class="form-group">
                    <label for="passwordField">Password (minimum 6 characters)</label>
                    <input type="password" name="password" id="passwordField" placeholder="Create a password" minlength="6" required disabled>
                </div>

                <div class="form-group">
                    <label for="confirmPasswordField">Confirm password</label>
                    <input type="password" name="confirm_password" id="confirmPasswordField" placeholder="Confirm your password" minlength="6" required disabled>
                </div>

                <input type="hidden" name="email_verified" id="emailVerified" value="false">
                <button type="submit" class="register-btn" id="registerBtn" disabled>Create Account</button>
            </form>

            <div class="divider"><span>Already have an account?</span></div>

            <div class="login-link"><a href="login.php">Sign in instead</a></div>
        </div>
    </div>

    <script>
        const sendCodeBtn = document.getElementById('sendCodeBtn');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendLink = document.getElementById('resendLink');
        const emailInput = document.getElementById('email');
        const verificationSection = document.getElementById('verificationSection');
        const verificationCodeInput = document.getElementById('verificationCode');
        const firstNameField = document.getElementById('firstNameField');
        const middleNameField = document.getElementById('middleNameField');
        const lastNameField = document.getElementById('lastNameField');
        const passwordField = document.getElementById('passwordField');
        const confirmPasswordField = document.getElementById('confirmPasswordField');
        const registerBtn = document.getElementById('registerBtn');
        const emailVerifiedInput = document.getElementById('emailVerified');
        const messageContainer = document.getElementById('messageContainer');
        const emailStatus = document.getElementById('emailStatus');

        let isEmailVerified = false;

        function showMessage(message, type) {
            messageContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }

        async function sendVerificationCode() {
            const email = emailInput.value.trim();
            if (!email) {
                showMessage('Please enter your email address', 'error');
                return;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }

            sendCodeBtn.disabled = true;
            sendCodeBtn.textContent = '⏳ Sending...';

            try {
                const response = await fetch('send_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showMessage('✓ Verification code sent to your email!', 'success');
                    verificationSection.classList.add('active');
                    emailInput.readOnly = true;
                    emailInput.style.backgroundColor = '#f5f5f5';
                    sendCodeBtn.textContent = '✓ Code Sent';
                } else {
                    showMessage(data.message || 'Failed to send code', 'error');
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = '📧 Send Verification Code';
                }
            } catch (error) {
                console.error(error);
                showMessage('Network error. Please try again.', 'error');
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '📧 Send Verification Code';
            }
        }

        async function verifyCode() {
            const email = emailInput.value.trim();
            const code = verificationCodeInput.value.trim();
            if (!code) {
                showMessage('Please enter the verification code', 'error');
                return;
            }
            if (code.length !== 6) {
                showMessage('Verification code must be 6 digits', 'error');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            try {
                const response = await fetch('verify_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email, code })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showMessage('✓ Email verified successfully!', 'success');
                    isEmailVerified = true;
                    emailVerifiedInput.value = 'true';
                    firstNameField.disabled = false;
                    middleNameField.disabled = false;
                    lastNameField.disabled = false;
                    passwordField.disabled = false;
                    confirmPasswordField.disabled = false;
                    registerBtn.disabled = false;
                    verificationCodeInput.disabled = true;
                    verifyBtn.disabled = true;
                    verifyBtn.textContent = '✓ Verified';
                    verifyBtn.style.background = '#48bb78';
                    emailStatus.innerHTML = '<span class="status-badge">✓ Verified</span>';
                } else {
                    showMessage(data.message || 'Invalid verification code', 'error');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';
                }
            } catch (error) {
                console.error(error);
                showMessage('Network error. Please try again.', 'error');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify';
            }
        }

        sendCodeBtn.addEventListener('click', sendVerificationCode);
        verifyBtn.addEventListener('click', verifyCode);
        resendLink.addEventListener('click', () => {
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = '📧 Resend Code';
            sendVerificationCode();
        });

        verificationCodeInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyCode();
            }
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            if (!isEmailVerified) {
                e.preventDefault();
                showMessage('Please verify your email before registering', 'error');
                return false;
            }
        });
    </script>
</body>
</html>
