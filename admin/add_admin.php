<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$first_name || !$last_name || !$password) {
        flash('Please complete all required fields.', 'error');
    } else {
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            flash('That email is already registered.', 'error');
        } else {
            $stmt = $db->prepare('INSERT INTO users (email, first_name, middle_name, last_name, password, user_type) VALUES (?, ?, ?, ?, ?, ?)');
            $user_type = 'admin';
            $stmt->bind_param('ssssss', $email, $first_name, $middle_name, $last_name, $password, $user_type);
            if ($stmt->execute()) {
                flash('✅ Admin account created successfully.', 'success');
            } else {
                flash('❌ Unable to create admin account.', 'error');
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Admin Account</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f7fafc;color:#2d3748;min-height:100vh;overflow-x:hidden}
    .navbar{position:fixed;left:250px;top:0;right:0;background:white;padding:16px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);z-index:900;transition:left 0.3s ease;border-bottom:1px solid #e2e8f0}
    .page{margin-left:250px;margin-top:70px;padding:28px 32px;}
    .form-card{background:white;padding:32px;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;max-width:900px;margin:0 auto}
    .form-group{margin-bottom:18px}
    label{display:block;font-weight:700;margin-bottom:8px}
    input{width:100%;padding:14px 18px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px}
    .btn{display:inline-flex;padding:14px 28px;border-radius:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-decoration:none;font-weight:600;border:none;cursor:pointer}
    .alert{padding:20px 25px;border-radius:16px;margin-bottom:20px;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,0.1);animation:slideDown 0.3s ease;border:1px solid}
    .alert-success{background:#d4edda;color:#155724;border-color:#48bb78}
    .alert-error{background:#fee;color:#c62828;border-color:#f56565}
    @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="navbar"><h1>Add Admin Account</h1></div>
    <div class="page">
        <div class="form-card">
            <?php render_flash(); ?>
            <h2 style="margin-bottom:20px">Create a new admin user</h2>
            <form method="POST" action="add_admin.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" type="text" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input id="middle_name" name="middle_name" type="text">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input id="last_name" name="last_name" type="text" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button class="btn" type="submit">Create Admin</button>
            </form>
        </div>
    </div>
</body>
</html>
