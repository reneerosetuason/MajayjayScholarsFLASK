<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_role('student');

$userId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT application_id, student_id, school_name, course, year_level, gwa, year_applied, status, scholarship_type, submission_date FROM application WHERE user_id = ? ORDER BY submission_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare('SELECT renewal_id, application_id, submission_date, status FROM renew WHERE user_id = ? ORDER BY submission_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$renewals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Applications | Scholar App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f7fafc;color:#2d3748;min-height:100vh;overflow-x:hidden}
    .navbar{position:fixed;left:250px;top:0;right:0;background:white;padding:16px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);z-index:900;transition:left 0.3s ease;border-bottom:1px solid #e2e8f0}
    .sidebar.hide ~ .navbar{left:0}
    .navbar h1{color:#2d3748;font-size:20px;display:flex;align-items:center;gap:10px;font-weight:600}
    .navbar .btn{padding:8px 14px;border-radius:8px;background:#e2e8f0;color:#222;text-decoration:none;font-weight:600;border:none;cursor:pointer}
    .page{margin-left:250px;margin-top:70px;padding:28px 32px;transition:margin-left 0.3s ease}
    .sidebar.hide ~ .page{margin-left:0}
    .header-card{background:white;color:#2d3748;padding:32px;border-radius:24px;text-align:center;margin-bottom:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:850px;margin-left:auto;margin-right:auto;border:1px solid #e2e8f0}
    .header-card h2{font-size:28px;margin-bottom:8px;font-weight:700;letter-spacing:-0.025em;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .header-card p{color:#718096}
    .table-card{background:white;padding:24px;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);border:1px solid #e2e8f0;margin-bottom:24px;overflow-x:auto}
    table{width:100%;border-collapse:collapse;font-size:14px}
    th,td{padding:14px 16px;text-align:left;border-bottom:1px solid #edf2f7}
    th{color:#4a5568;font-weight:700;background:#f7fafc}
    tr:hover{background:#f9fafb}
    .status-badge{display:inline-flex;padding:8px 12px;border-radius:999px;font-weight:600;font-size:13px}
    .status-pending{background:#fef3c7;color:#b45309}
    .status-approved{background:#d1fae5;color:#047857}
    .status-rejected{background:#fee2e2;color:#b91c1c}
    .status-renewal{background:#e0f2fe;color:#0369a1}
    .action-btn{display:inline-flex;padding:10px 16px;border-radius:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-decoration:none;font-weight:600;transition:all 0.3s ease}
    .action-btn:hover{transform:translateY(-2px)}
    .info-box{background:#e8f5e9;border-left:4px solid #48bb78;padding:16px;border-radius:12px;margin-bottom:20px;color:#1f7a3a;border:1px solid #c3e6cb}
    .alert{padding:20px 25px;border-radius:16px;margin-bottom:20px;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,0.1);animation:slideDown 0.3s ease;border:1px solid}
    .alert-success{background:#d4edda;color:#155724;border-color:#48bb78}
    .alert-error{background:#fee;color:#c62828;border-color:#f56565}
    @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
    @media(max-width:900px){.page{margin-left:16px;padding:16px}.navbar{left:0}}    
</style>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="navbar">
        <h1><button class="toggle-btn" onclick="toggleSidebar()">☰</button>📄 My Applications</h1>
    </div>
    <div class="page">
        <div class="header-card">
            <h2>My Applications</h2>
            <p>Review your scholarship and renewal submissions.</p>
        </div>

        <?php render_flash(); ?>

        <div class="info-box">
            You can edit pending scholarship applications and renewals from the lists below. Once approved or rejected, editing is disabled.
        </div>

        <div class="table-card">
            <h3 style="margin-bottom:16px">Scholarship Applications</h3>
            <?php if (empty($applications)): ?>
                <p>No scholarship applications have been submitted yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>School</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>GWA</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= h($app['student_id']) ?></td>
                                <td><?= h($app['school_name']) ?></td>
                                <td><?= h($app['course']) ?></td>
                                <td><?= h($app['year_level']) ?></td>
                                <td><?= h($app['gwa']) ?></td>
                                <td><span class="status-badge status-<?= strtolower($app['status']) ?>"><?= h($app['status']) ?></span></td>
                                <td><?= h($app['submission_date']) ?></td>
                                <td>
                                    <?php if (strtolower($app['status']) === 'pending'): ?>
                                        <a class="action-btn" href="edit_application.php?id=<?= h($app['application_id']) ?>">Edit</a>
                                    <?php else: ?>
                                        <span style="color:#718096;font-weight:600;">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <h3 style="margin-bottom:16px">Renewal Applications</h3>
            <?php if (empty($renewals)): ?>
                <p>No renewals have been submitted yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Renewal ID</th>
                            <th>Application ID</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renewals as $renew): ?>
                            <tr>
                                <td><?= h($renew['renewal_id']) ?></td>
                                <td><?= h($renew['application_id']) ?></td>
                                <td><span class="status-badge status-<?= strtolower($renew['status']) ?>"><?= h($renew['status']) ?></span></td>
                                <td><?= h($renew['submission_date']) ?></td>
                                <td>
                                    <?php if (strtolower($renew['status']) === 'pending'): ?>
                                        <a class="action-btn" href="edit_renewal.php?id=<?= h($renew['renewal_id']) ?>">Edit</a>
                                    <?php else: ?>
                                        <span style="color:#718096;font-weight:600;">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hide');
        }
    </script>
</body>
</html>
