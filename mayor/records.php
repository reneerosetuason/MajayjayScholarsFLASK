<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mayor');

$section = $_POST['section'] ?? $_GET['section'] ?? 'applications';
$show_archived = isset($_POST['archived']) ? $_POST['archived'] === 'true' : (isset($_GET['archived']) && $_GET['archived'] === 'true');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $recordType = $_POST['record_type'] ?? '';
    $recordId = intval($_POST['record_id'] ?? 0);
    $redirectUrl = 'records.php?section=' . urlencode($section);
    if ($show_archived) {
        $redirectUrl .= '&archived=true';
    }

    if ($recordId > 0 && in_array($recordType, ['application', 'renewal'], true) && in_array($action, ['approve', 'reject', 'archive'], true)) {
        if ($recordType === 'application') {
            if ($action === 'approve') {
                $stmt = $db->prepare('UPDATE application SET status = "approved" WHERE application_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Application approved.';
            } elseif ($action === 'reject') {
                $stmt = $db->prepare('UPDATE application SET status = "rejected" WHERE application_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Application rejected.';
            } else {
                $stmt = $db->prepare('UPDATE application SET archived = 1 WHERE application_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Application archived.';
            }
        } else {
            if ($action === 'approve') {
                $stmt = $db->prepare('UPDATE renew SET status = "Approved" WHERE renewal_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Renewal approved.';
            } elseif ($action === 'reject') {
                $stmt = $db->prepare('UPDATE renew SET status = "Rejected" WHERE renewal_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Renewal rejected.';
            } else {
                $stmt = $db->prepare('UPDATE renew SET archived = 1 WHERE renewal_id = ?');
                $stmt->bind_param('i', $recordId);
                $message = 'Renewal archived.';
            }
        }

        if (isset($stmt) && $stmt->execute()) {
            flash("✅ {$message}", 'success');
        } else {
            flash('❌ Unable to complete the action. Please try again.', 'error');
        }

        if (isset($stmt)) {
            $stmt->close();
        }
    } else {
        flash('Invalid action or record selected.', 'error');
    }

    redirect($redirectUrl);
}

$appStmt = $db->prepare('SELECT COUNT(*) AS total, SUM(status = "approved") AS approved, SUM(status = "pending") AS pending, SUM(status = "rejected") AS rejected, SUM(archived = 1) AS archived_count FROM application');
$appStmt->execute();
$appStats = $appStmt->get_result()->fetch_assoc();
$appStmt->close();

$renewStmt = $db->prepare('SELECT COUNT(*) AS total, SUM(status = "Approved") AS approved, SUM(status = "Pending") AS pending, SUM(status = "Rejected") AS rejected FROM renew');
$renewStmt->execute();
$renewStats = $renewStmt->get_result()->fetch_assoc();
$renewStmt->close();

$appFilterSql = $show_archived ? 'a.archived = 1' : '(a.archived = 0 OR a.archived IS NULL)';
$appSql = 'SELECT a.application_id, u.user_id, u.email, u.first_name, u.middle_name, u.last_name, a.student_id, a.contact_number, a.address, a.municipality, a.baranggay, a.school_name, a.course, a.year_level, a.gwa, a.status, a.scholarship_type, a.year_applied, a.reason, a.school_id_path, a.id_picture_path, a.birth_certificate_path, a.grades_path, a.cor_path, a.submission_date FROM application a JOIN users u ON a.user_id = u.user_id WHERE ' . $appFilterSql . ' ORDER BY a.submission_date DESC';
$appRecordsStmt = $db->prepare($appSql);
$appRecordsStmt->execute();
$applications = $appRecordsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appRecordsStmt->close();

$renewFilterSql = $show_archived ? 'r.archived = 1' : '(r.archived = 0 OR r.archived IS NULL)';
$renewSql = 'SELECT r.renewal_id, r.application_id, u.user_id, u.email, u.first_name, u.middle_name, u.last_name, r.student_id, r.contact_number, r.address, r.municipality, r.baranggay, r.course, r.year_level, r.gwa, r.reason, r.school_id_path, r.id_picture_path, r.birth_certificate_path, r.grades_path, r.cor_path, r.status, r.submission_date FROM renew r JOIN users u ON r.user_id = u.user_id WHERE ' . $renewFilterSql . ' ORDER BY r.submission_date DESC';
$renewRecordsStmt = $db->prepare($renewSql);
$renewRecordsStmt->execute();
$renewals = $renewRecordsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$renewRecordsStmt->close();

$archivedCount = intval($appStats['archived_count']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scholar Records | Mayor Panel</title>
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
    .header-card { background: white; padding: 32px; border-radius: 24px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
    .header-card h2 { font-weight: 700; letter-spacing: -0.025em; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 10px; }
    .header-card p { color: #718096; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 24px; border-radius: 16px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15); border-color: #667eea; }
    .stat-card .number { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
    .stat-card .label { color: #666; font-size: 13px; }
    .stat-card.total .number { color: #667eea; }
    .stat-card.approved .number { color: #48bb78; }
    .stat-card.pending .number { color: #ffa500; }
    .stat-card.rejected .number { color: #f56565; }
    .records-card { background: white; padding: 32px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow-x: auto; border: 1px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    th { padding: 15px; border-bottom: 1px solid #eee; color: #444; text-align: left; cursor: pointer; position: relative; transition: 0.2s; }
    th:hover { background: #e9ecef; }
    td { padding: 15px; border-bottom: 1px solid #eee; color: #444; }
    tbody tr:hover { background: #f8f9fa; }
    .badge { padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-rejected { background: #f8d7da; color: #721c1c; }
    .badge-new { background: #cfe2ff; color: #084298; }
    .badge-renewal { background: #e7d6f5; color: #6f42c1; }
    .filter-tabs { background: white; padding: 15px 30px; border-radius: 16px; display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
    .filter-btn { padding: 10px 20px; border: 2px solid #e0e0e0; border-radius: 8px; background: white; cursor: pointer; font-weight: 500; color: #666; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; }
    .filter-btn:hover { border-color: #667eea; color: #667eea; }
    .filter-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
    .btn-sm { padding: 8px 14px; border-radius: 10px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.2s; }
    .btn-view { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-view:hover { transform: translateY(-1px); }
    .btn-archive { background: #f6ad55; color: white; }
    .btn-archive:hover { background: #dd6b20; }
    .btn-approve { background: #48bb78; color: white; }
    .btn-approve:hover { background: #38a169; }
    .btn-reject { background: #f56565; color: white; }
    .btn-reject:hover { background: #e53e3e; }
    .action-cell { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); justify-content: center; align-items: center; padding: 20px; z-index: 1200; }
    .modal.active { display: flex; }
    .modal-content { background: white; border-radius: 24px; width: 100%; max-width: 720px; max-height: 90vh; overflow-y: auto; padding: 28px; box-shadow: 0 25px 90px rgba(15, 23, 42, 0.15); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 20px; }
    .modal-header h3 { margin: 0; font-size: 22px; }
    .close-btn { border: none; background: transparent; font-size: 28px; cursor: pointer; color: #718096; }
    .close-btn:hover { color: #2d3748; }
    .detail-row { margin-bottom: 16px; }
    .detail-row strong { display: block; margin-bottom: 6px; font-size: 13px; color: #2d3748; }
    .detail-row p { margin: 0; color: #4a5568; }
    .detail-row a { color: #667eea; text-decoration: none; }
    .detail-row a:hover { text-decoration: underline; }
    .modal-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px; }
    .modal-action-form { margin: 0; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: wrap; }
    .pagination-info { color: #666; font-size: 14px; }
    .pagination-controls { display: flex; gap: 8px; }
    .pagination-controls button { padding: 8px 16px; border: 1px solid #e2e8f0; background: white; color: #667eea; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s; }
    .pagination-controls button:hover:not(:disabled) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
    .pagination-controls button:disabled { opacity: 0.5; cursor: not-allowed; }
    .pagination-controls button.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
    @media (max-width: 768px) { .navbar { left: 0; padding: 15px 20px; } .container { margin-left: 0; padding: 0 20px 40px; margin-top: 90px; } .stats-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <nav class="navbar">
        <div class="navbar-left">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <h1>📁 Scholar Records</h1>
        </div>
        <a href="dashboard.php">Back to Dashboard</a>
    </nav>
    <div class="container">
        <?php render_flash(); ?>
        <div class="header-card">
            <h2>Complete Scholarship Records</h2>
            <p>View and manage all scholarship applications.</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card total"><div class="number"><?= h($appStats['total'] + $renewStats['total']) ?></div><div class="label">Total</div></div>
            <div class="stat-card approved"><div class="number"><?= h($appStats['approved'] + $renewStats['approved']) ?></div><div class="label">Approved</div></div>
            <div class="stat-card pending"><div class="number"><?= h($appStats['pending'] + $renewStats['pending']) ?></div><div class="label">Pending</div></div>
            <div class="stat-card rejected"><div class="number"><?= h($appStats['rejected'] + $renewStats['rejected']) ?></div><div class="label">Rejected</div></div>
        </div>
        <div class="filter-tabs" style="margin-bottom: 15px;">
            <a href="records.php?archived=false&section=applications" class="filter-btn <?= !$show_archived && $section !== 'renewals' ? 'active' : '' ?>">📋 Active Applications</a>
            <a href="records.php?section=renewals" class="filter-btn <?= $section === 'renewals' ? 'active' : '' ?>">🔄 Renewal Applications</a>
            <a href="records.php?archived=true" class="filter-btn <?= $show_archived ? 'active' : '' ?>">🗄️ Archived Applications</a>
        </div>
        <?php if (!$show_archived): ?>
        <div class="filter-tabs">
            <button class="filter-btn active" onclick="filterRows(event, 'all')">All</button>
            <button class="filter-btn" onclick="filterRows(event, 'approved')">Approved</button>
            <button class="filter-btn" onclick="filterRows(event, 'pending')">Pending</button>
            <button class="filter-btn" onclick="filterRows(event, 'rejected')">Rejected</button>
        </div>
        <?php endif; ?>
        <div class="records-card">
            <div class="table-header">
                <h3><?= $section === 'renewals' ? 'Renewal Applications' : 'Scholarship Applications' ?></h3>
                <div class="search-box"><input id="searchInput" type="text" placeholder="Search records..." oninput="searchTable()"></div>
            </div>
            <?php if ($section === 'renewals'): ?>
                <?php if (empty($renewals)): ?>
                    <p style="text-align:center; padding:40px; color:#777;">📭 No renewal applications found</p>
                <?php else: ?>
                    <table id="recordsTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable(0)">Renewal ID</th>
                                <th class="sortable" onclick="sortTable(1)">Name</th>
                                <th class="sortable" onclick="sortTable(2)">Course</th>
                                <th class="sortable" onclick="sortTable(3)">Year Level</th>
                                <th class="sortable" onclick="sortTable(4)">GWA</th>
                                <th class="sortable" onclick="sortTable(5)">Status</th>
                                <th class="sortable" onclick="sortTable(6)">Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($renewals as $row): ?>
                                <tr data-status="<?= strtolower($row['status']) ?>">
                                    <td><?= h($row['renewal_id']) ?></td>
                                    <td><?= h($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= h($row['course']) ?></td>
                                    <td><?= h($row['year_level']) ?></td>
                                    <td><?= h($row['gwa']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= h($row['status']) ?></span></td>
                                    <td><?= h($row['submission_date']) ?></td>
                                    <td class="action-cell">
                                        <button class="btn-sm btn-view" type="button" onclick='openDetailsModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>, "renewal")'>View</button>
                                        <?php if (!$show_archived): ?>
                                            <form method="POST" class="modal-action-form">
                                                <input type="hidden" name="record_type" value="renewal">
                                                <input type="hidden" name="record_id" value="<?= h($row['renewal_id']) ?>">
                                                <input type="hidden" name="section" value="<?= h($section) ?>">
                                                <input type="hidden" name="archived" value="<?= $show_archived ? 'true' : 'false' ?>">
                                                <input type="hidden" name="action" value="archive">
                                                <button class="btn-sm btn-archive" type="submit">Archive</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <?php if (empty($applications)): ?>
                    <p style="text-align:center; padding:40px; color:#777;">📭 No records found</p>
                <?php else: ?>
                    <table id="recordsTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable(0)">App ID</th>
                                <th class="sortable" onclick="sortTable(1)">Name</th>
                                <th class="sortable" onclick="sortTable(2)">School</th>
                                <th class="sortable" onclick="sortTable(3)">Course</th>
                                <th class="sortable" onclick="sortTable(4)">Year Level</th>
                                <th class="sortable" onclick="sortTable(5)">Status</th>
                                <th class="sortable" onclick="sortTable(6)">Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $row): ?>
                                <tr data-status="<?= strtolower($row['status']) ?>" data-type="<?= strtolower($row['scholarship_type']) ?>">
                                    <td><?= h($row['application_id']) ?></td>
                                    <td><?= h($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= h($row['school_name']) ?></td>
                                    <td><?= h($row['course']) ?></td>
                                    <td><?= h($row['year_level']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= h($row['status']) ?></span></td>
                                    <td><?= h($row['submission_date']) ?></td>
                                    <td class="action-cell">
                                        <button class="btn-sm btn-view" type="button" onclick='openDetailsModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>, "application")'>View</button>
                                        <?php if (!$show_archived): ?>
                                            <form method="POST" class="modal-action-form">
                                                <input type="hidden" name="record_type" value="application">
                                                <input type="hidden" name="record_id" value="<?= h($row['application_id']) ?>">
                                                <input type="hidden" name="section" value="<?= h($section) ?>">
                                                <input type="hidden" name="archived" value="<?= $show_archived ? 'true' : 'false' ?>">
                                                <input type="hidden" name="action" value="archive">
                                                <button class="btn-sm btn-archive" type="submit">Archive</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing <span id="startEntry">1</span> to <span id="endEntry">10</span> of <span id="totalEntries">0</span> entries
                </div>
                <div class="pagination-controls">
                    <button type="button" onclick="changePage('prev')" id="prevBtn">Previous</button>
                    <div id="pageNumbers"></div>
                    <button type="button" onclick="changePage('next')" id="nextBtn">Next</button>
                </div>
            </div>
        </div>
        <div id="detailsModal" class="modal" aria-hidden="true">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">Record Details</h3>
                    <button class="close-btn" type="button" onclick="closeModal()">×</button>
                </div>
                <div id="modalBody"></div>
                <div id="modalActions" class="modal-actions"></div>
            </div>
        </div>
    </div>
    <script>
        const currentSection = '<?= h($section) ?>';
        const currentArchived = <?= $show_archived ? 'true' : 'false' ?>;
        const uploadBase = '../static/uploads/';

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hide');
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('recordsTable');
            if (!table) return;
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            const headers = table.getElementsByTagName('th');
            let direction = headers[columnIndex].classList.contains('sort-asc') ? 'desc' : 'asc';
            headers.forEach(header => header.classList.remove('sort-asc', 'sort-desc'));
            headers[columnIndex].classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');

            rows.sort((a, b) => {
                let aValue = a.getElementsByTagName('td')[columnIndex].textContent.trim();
                let bValue = b.getElementsByTagName('td')[columnIndex].textContent.trim();
                if (columnIndex === 0 || columnIndex === 4) {
                    aValue = parseFloat(aValue.replace('#', '')) || 0;
                    bValue = parseFloat(bValue.replace('#', '')) || 0;
                }
                if (aValue < bValue) return direction === 'asc' ? -1 : 1;
                if (aValue > bValue) return direction === 'asc' ? 1 : -1;
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));
            currentPage = 1;
            displayPage(currentPage);
        }

        function openDetailsModal(record, type) {
            const modal = document.getElementById('detailsModal');
            const body = document.getElementById('modalBody');
            const actions = document.getElementById('modalActions');
            const fullName = [record.first_name, record.middle_name, record.last_name].filter(Boolean).join(' ');
            const status = record.status || 'N/A';
            const badgeClass = status.toLowerCase() === 'approved' ? 'badge-approved' : status.toLowerCase() === 'pending' ? 'badge-pending' : 'badge-rejected';
            const typeLabel = type === 'application' ? (record.scholarship_type ? record.scholarship_type.charAt(0).toUpperCase() + record.scholarship_type.slice(1) : 'New') : 'Renewal';
            const idLabel = type === 'application' ? 'Application ID' : 'Renewal ID';
            const recordId = type === 'application' ? record.application_id : record.renewal_id;
            const schoolName = record.school_name ? `<div class="detail-row"><strong>School:</strong><p>${record.school_name}</p></div>` : '';
            const schoolIdLink = record.school_id_path ? `<p>🆔 <a href="${uploadBase}${record.school_id_path}" target="_blank">School ID</a></p>` : '';
            const idPicLink = record.id_picture_path ? `<p>🧾 <a href="${uploadBase}${record.id_picture_path}" target="_blank">ID Picture</a></p>` : '';
            const birthLink = record.birth_certificate_path ? `<p>📜 <a href="${uploadBase}${record.birth_certificate_path}" target="_blank">Birth Certificate</a></p>` : '';
            const gradesLink = record.grades_path ? `<p>📊 <a href="${uploadBase}${record.grades_path}" target="_blank">Grades</a></p>` : '';
            const corLink = record.cor_path ? `<p>🎓 <a href="${uploadBase}${record.cor_path}" target="_blank">Certificate of Registration</a></p>` : '';

            body.innerHTML = `
                <div class="detail-row"><strong>${idLabel}:</strong><p>#${recordId}</p></div>
                <div class="detail-row"><strong>Name:</strong><p>${fullName}</p></div>
                <div class="detail-row"><strong>Student ID:</strong><p>${record.student_id || 'N/A'}</p></div>
                <div class="detail-row"><strong>Course:</strong><p>${record.course || 'N/A'}</p></div>
                <div class="detail-row"><strong>Year Level:</strong><p>${record.year_level || 'N/A'}</p></div>
                ${schoolName}
                <div class="detail-row"><strong>GWA:</strong><p>${record.gwa || 'N/A'}</p></div>
                <div class="detail-row"><strong>Type:</strong><p>${typeLabel}</p></div>
                <div class="detail-row"><strong>Status:</strong><p><span class="badge ${badgeClass}">${status}</span></p></div>
                <div class="detail-row"><strong>Submitted:</strong><p>${record.submission_date || 'N/A'}</p></div>
                <div class="detail-row"><strong>Contact:</strong><p>${record.contact_number || 'N/A'}</p></div>
                <div class="detail-row"><strong>Address:</strong><p>${record.address || 'N/A'}</p></div>
                <div class="detail-row"><strong>Municipality / Barangay:</strong><p>${[record.municipality, record.baranggay].filter(Boolean).join(' / ') || 'N/A'}</p></div>
                <div class="detail-row"><strong>Reason:</strong><p style="background:#f7f7f7; padding:12px; border-radius:8px; margin-top:5px;">${record.reason || 'N/A'}</p></div>
                <div class="detail-row"><strong>Documents:</strong></div>
                <div class="detail-row" style="margin-left:15px;">${schoolIdLink}${idPicLink}${birthLink}${gradesLink}${corLink}</div>
            `;

            actions.innerHTML = '';
            if (status.toLowerCase() === 'pending') {
                actions.innerHTML += `
                    <form method="POST" class="modal-action-form">
                        <input type="hidden" name="record_type" value="${type}">
                        <input type="hidden" name="record_id" value="${recordId}">
                        <input type="hidden" name="section" value="${currentSection}">
                        <input type="hidden" name="archived" value="${currentArchived}">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn-sm btn-approve" type="submit">Approve</button>
                    </form>
                    <form method="POST" class="modal-action-form">
                        <input type="hidden" name="record_type" value="${type}">
                        <input type="hidden" name="record_id" value="${recordId}">
                        <input type="hidden" name="section" value="${currentSection}">
                        <input type="hidden" name="archived" value="${currentArchived}">
                        <input type="hidden" name="action" value="reject">
                        <button class="btn-sm btn-reject" type="submit">Reject</button>
                    </form>
                `;
            }

            if (!currentArchived) {
                actions.innerHTML += `
                    <form method="POST" class="modal-action-form">
                        <input type="hidden" name="record_type" value="${type}">
                        <input type="hidden" name="record_id" value="${recordId}">
                        <input type="hidden" name="section" value="${currentSection}">
                        <input type="hidden" name="archived" value="${currentArchived}">
                        <input type="hidden" name="action" value="archive">
                        <button class="btn-sm btn-archive" type="submit">Archive</button>
                    </form>
                `;
            }

            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        document.getElementById('detailsModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) {
                closeModal();
            }
        });

        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('recordsTable');
            if (!table) return;
            const rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().includes(filter)) {
                        found = true;
                        break;
                    }
                }
                row.style.display = found ? '' : 'none';
            }
            currentPage = 1;
            updatePaginationAfterSearch();
        }

        function filterRows(event, status) {
            const table = document.getElementById('recordsTable');
            if (!table) return;
            const rows = table.getElementsByTagName('tr');
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                event.target.classList.add('active');
            }
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const rowStatus = row.dataset.status || '';
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
            currentPage = 1;
            displayPage(currentPage);
        }

        let currentPage = 1;
        const rowsPerPage = 10;
        let allRows = [];
        let totalPages = 0;

        function displayPage(page) {
            let allRows = [];
            const table = document.getElementById('recordsTable');
            if (table) {
                const tbody = table.getElementsByTagName('tbody')[0];
                allRows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            }
            totalPages = Math.ceil(allRows.length / rowsPerPage);

            if (page > totalPages && totalPages > 0) {
                currentPage = totalPages;
                page = currentPage;
            } else if (totalPages === 0) {
                currentPage = 1;
                page = 1;
            }

            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            allRows.forEach((row, index) => {
                row.style.display = index >= start && index < end ? '' : 'none';
            });

            document.getElementById('startEntry').textContent = allRows.length > 0 ? start + 1 : 0;
            document.getElementById('endEntry').textContent = Math.min(end, allRows.length);
            document.getElementById('totalEntries').textContent = allRows.length;
            document.getElementById('prevBtn').disabled = page === 1;
            document.getElementById('nextBtn').disabled = page === totalPages || totalPages === 0;
            renderPageNumbers();
        }

        function renderPageNumbers() {
            const pageNumbersDiv = document.getElementById('pageNumbers');
            pageNumbersDiv.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.type = 'button';
                btn.className = i === currentPage ? 'active' : '';
                btn.onclick = () => {
                    currentPage = i;
                    displayPage(currentPage);
                };
                pageNumbersDiv.appendChild(btn);
            }
        }

        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < totalPages) {
                currentPage++;
            }
            displayPage(currentPage);
        }

        function updatePaginationAfterSearch() {
            let allRows = [];
            const table = document.getElementById('recordsTable');
            if (table) {
                allRows = Array.from(table.getElementsByTagName('tbody')[0].getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            }
            totalPages = Math.ceil(allRows.length / rowsPerPage);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            } else if (totalPages === 0) {
                currentPage = 1;
            }
            displayPage(currentPage);
        }

        displayPage(currentPage);
    </script>
</body>
</html>
