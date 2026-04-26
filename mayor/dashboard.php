<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mayor');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_renewal'])) {
    $stmt = $db->prepare('UPDATE renewal_settings SET is_open = NOT is_open WHERE id = 1');
    $stmt->execute();
    $stmt->close();
    flash('Renewal settings updated.', 'success');
    redirect('dashboard.php');
}

$stmt = $db->prepare('SELECT COUNT(*) AS total, SUM(status = "approved") AS approved, SUM(status = "pending") AS pending, SUM(status = "rejected") AS rejected FROM application');
$stmt->execute();
$appCounts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) AS total, SUM(status = "Approved") AS approved, SUM(status = "Pending") AS pending, SUM(status = "Rejected") AS rejected FROM renew');
$stmt->execute();
$renewCounts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare('SELECT is_open FROM renewal_settings WHERE id = 1 LIMIT 1');
$stmt->execute();
$renewalRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$isRenewalOpen = !empty($renewalRow['is_open']);

$stmt = $db->prepare('SELECT first_name FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$name = $userRow['first_name'] ?? 'Mayor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mayor Dashboard | Scholar App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f7fafc; font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .navbar { position: fixed; left: 250px; top: 0; right: 0; background: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; z-index: 900; transition: left 0.3s ease; border-bottom: 1px solid #e2e8f0; }
    .sidebar.hide ~ .navbar { left: 0; }
    .navbar-left { display: flex; align-items: center; gap: 15px; }
    .toggle-btn { background: rgba(102, 126, 234, 0.1); border: none; font-size: 24px; color: #667eea; cursor: pointer; padding: 5px 10px; border-radius: 8px; transition: 0.3s; }
    .toggle-btn:hover { background: rgba(102, 126, 234, 0.2); transform: translateY(-2px); }
    .navbar h1 { color: #2d3748; font-size: 24px; font-weight: 600; }
    .navbar a { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; transition: 0.3s; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
    .navbar a:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
    .container { margin-left: 250px; margin-top: 100px; max-width: calc(100% - 250px); padding: 0 40px 40px; transition: margin-left 0.3s ease; }
    .sidebar.hide ~ .container { margin-left: 0; max-width: 100%; }
    .header-card { background: white; padding: 40px; border-radius: 24px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; }
    .header-card h2 { font-size: 32px; margin-bottom: 10px; font-weight: 700; letter-spacing: -0.025em; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .header-card p { color: #718096; font-size: 16px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 40px; }
    .stat-card { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; transition: all 0.3s ease; border: 2px solid #e2e8f0; }
    .stat-card:hover { transform: translateY(-8px); box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15); }
    .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
    .stat-card .number { font-size: 36px; font-weight: 700; margin-bottom: 8px; line-height: 1; }
    .stat-card .label { color: #666; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card.total { border-color: #667eea; }
    .stat-card.total .number { color: #667eea; }
    .stat-card.total:hover { border-color: #667eea; background: rgba(102, 126, 234, 0.05); }
    .stat-card.approved { border-color: #48bb78; }
    .stat-card.approved .number { color: #48bb78; }
    .stat-card.approved:hover { border-color: #48bb78; background: #f0fdf4; }
    .stat-card.pending { border-color: #ffa500; }
    .stat-card.pending .number { color: #ffa500; }
    .stat-card.pending:hover { border-color: #ffa500; background: #fffbeb; }
    .stat-card.rejected { border-color: #f56565; }
    .stat-card.rejected .number { color: #f56565; }
    .stat-card.rejected:hover { border-color: #f56565; background: #fef2f2; }
    .renewal-toggle-btn { padding: 12px 24px; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .renewal-toggle-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
    .btn-close { background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%); }
    .btn-open { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
    .renewal-status { margin-left: 15px; color: #718096; font-size: 14px; }
    .status-open { color: #48bb78; }
    .status-closed { color: #f56565; }
    .summary-card { background: white; padding: 32px; border-radius: 24px; margin-top: 40px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; }
    .summary-card h3 { font-size: 22px; margin-bottom: 25px; text-align: center; font-weight: 700; letter-spacing: -0.025em; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .summary-item { text-align: center; padding: 20px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 16px; border: 2px solid rgba(102, 126, 234, 0.2); transition: all 0.3s ease; }
    .summary-item:hover { transform: translateY(-3px); border-color: rgba(102, 126, 234, 0.4); }
    .summary-item .number { font-size: 36px; font-weight: 700; color: #667eea; margin-bottom: 8px; }
    .summary-item .label { color: #4a5568; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    @media (max-width: 1200px) { .stats-grid, .summary-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .navbar { left: 0; padding: 15px 20px; } .container { margin-left: 0; padding: 0 20px 40px; margin-top: 90px; } .stats-grid, .summary-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <nav class="navbar">
        <div class="navbar-left">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <h1>🏛️ Mayor Dashboard</h1>
        </div>
        <a href="records.php">View Scholar Records</a>
    </nav>
    <div class="container">
        <div class="header-card">
            <h2>Welcome, <?= h($name) ?>!</h2>
            <p>Overview of scholarship applications and renewals.</p>
        </div>
        <?php render_flash(); ?>
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="icon">📊</div>
                <div class="number"><?= h($appCounts['total']) ?></div>
                <div class="label">Total Applications</div>
            </div>
            <div class="stat-card approved">
                <div class="icon">✅</div>
                <div class="number"><?= h($appCounts['approved']) ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card pending">
                <div class="icon">⏳</div>
                <div class="number"><?= h($appCounts['pending']) ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card rejected">
                <div class="icon">❌</div>
                <div class="number"><?= h($appCounts['rejected']) ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>
        <div class="header-card" style="padding: 24px 32px; margin-bottom: 24px;">
            <div style="display:flex;align-items:center;justify-content: space-between;flex-wrap:wrap;gap: 16px;">
                <div>
                    <h2 style="font-size: 22px; margin-bottom: 10px;">Renewal Applications</h2>
                    <p style="color:#718096; margin:0;">Control renewal availability and view current renewal totals.</p>
                </div>
                <form method="POST" action="dashboard.php" style="display:flex;align-items:center;gap:12px;">
                    <button type="submit" name="toggle_renewal" class="renewal-toggle-btn <?= $isRenewalOpen ? 'btn-close' : 'btn-open' ?>">
                        <?= $isRenewalOpen ? '🔒 Close Renewals' : '🔓 Open Renewals' ?>
                    </button>
                    <span class="renewal-status">Status: <strong class="<?= $isRenewalOpen ? 'status-open' : 'status-closed' ?>"><?= $isRenewalOpen ? 'Open' : 'Closed' ?></strong></span>
                </form>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="icon">📋</div>
                <div class="number"><?= h($renewCounts['total']) ?></div>
                <div class="label">Total Renewals</div>
            </div>
            <div class="stat-card approved">
                <div class="icon">✅</div>
                <div class="number"><?= h($renewCounts['approved']) ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card pending">
                <div class="icon">⏳</div>
                <div class="number"><?= h($renewCounts['pending']) ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card rejected">
                <div class="icon">❌</div>
                <div class="number"><?= h($renewCounts['rejected']) ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>
        <div class="summary-card">
            <h3>📈 Overall Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="number"><?= h($appCounts['total'] + $renewCounts['total']) ?></div>
                    <div class="label">Total Applications</div>
                </div>
                <div class="summary-item">
                    <div class="number"><?= h($appCounts['approved'] + $renewCounts['approved']) ?></div>
                    <div class="label">Total Approved</div>
                </div>
                <div class="summary-item">
                    <div class="number"><?= h($appCounts['pending'] + $renewCounts['pending']) ?></div>
                    <div class="label">Total Pending</div>
                </div>
                <div class="summary-item">
                    <div class="number"><?= h($appCounts['rejected'] + $renewCounts['rejected']) ?></div>
                    <div class="label">Total Rejected</div>
                </div>
            </div>
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
