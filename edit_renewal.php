<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_role('student');
ensure_upload_folder();

$userId = $_SESSION['user_id'];
$renewId = intval($_GET['id'] ?? 0);

if ($renewId <= 0) {
    flash('Invalid renewal selected.', 'error');
    redirect('my_applications.php');
}

$stmt = $db->prepare('SELECT * FROM renew WHERE renewal_id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $renewId, $userId);
$stmt->execute();
$renewData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$renewData) {
    flash('Renewal not found.', 'error');
    redirect('my_applications.php');
}

if (strtolower($renewData['status']) !== 'pending') {
    flash('Only pending renewals can be edited.', 'error');
    redirect('my_applications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $municipality = trim($_POST['municipality'] ?? 'Majayjay');
    $barangay = trim($_POST['barangay'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $gwa = trim($_POST['gwa'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $fields = [
        'student_id' => $student_id,
        'contact_number' => $contact_number,
        'address' => $address,
        'municipality' => $municipality,
        'baranggay' => $barangay,
        'course' => $course,
        'year_level' => $year_level,
        'gwa' => $gwa,
        'reason' => $reason,
    ];

    $uploadedFiles = [];
    foreach (['school_id' => 'school_id_path', 'id_picture' => 'id_picture_path', 'birth_certificate' => 'birth_certificate_path', 'grades' => 'grades_path', 'cor' => 'cor_path'] as $field => $column) {
        if (!empty($_FILES[$field]['name'])) {
            if (!allowed_file($_FILES[$field]['name'])) {
                flash('Allowed file types are PNG, JPG, JPEG, and PDF.', 'error');
                break;
            }
            $file = $_FILES[$field];
            $name = pathinfo($file['name'], PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
            $filename = $safeName . '_' . time() . '.' . $ext;
            $destination = UPLOAD_FOLDER . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                flash('Unable to save uploaded file. Please try again.', 'error');
                break;
            }
            $uploadedFiles[$column] = $filename;
        }
    }

    $updateColumns = 'student_id=?, contact_number=?, address=?, municipality=?, baranggay=?, course=?, year_level=?, gwa=?, reason=?';
    $params = [$student_id, $contact_number, $address, $municipality, $barangay, $course, $year_level, $gwa, $reason];

    foreach ($uploadedFiles as $column => $filename) {
        $updateColumns .= ", {$column}=?";
        $params[] = $filename;
    }

    $params[] = $renewId;
    $params[] = $userId;

    $types = 'ssssssssds';
    foreach ($uploadedFiles as $_) {
        $types .= 's';
    }
    $types .= 'ii';

    $stmt = $db->prepare("UPDATE renew SET {$updateColumns} WHERE renewal_id = ? AND user_id = ?");
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        flash('✅ Renewal updated successfully.', 'success');
        $stmt->close();
        redirect('my_applications.php');
    }
    $stmt->close();
    flash('❌ Unable to update your renewal. Please try again.', 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Edit Renewal | Scholar App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root{--primary:#667eea;--primary-dark:#764ba2;--muted:#e2e8f0}
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
    .form-card{background:white;padding:32px;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:850px;margin:0 auto;border:1px solid #e2e8f0}
    .section-title{display:flex;gap:10px;align-items:center;color:#2d3748;font-weight:700;margin-bottom:14px;font-size:16px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;font-weight:700;margin-bottom:8px}
    input[type="text"], input[type="tel"], input[type="number"], select, textarea, input[type="file"]{width:100%;padding:14px 18px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px;background:#f7fafc;font-family:'Inter',sans-serif;transition:all 0.3s ease}
    input:focus, select:focus, textarea:focus{outline:none;border-color:#667eea;background:white;box-shadow:0 0 0 3px rgba(102,126,234,0.1)}
    textarea{min-height:120px;resize:vertical}
    .hint{font-size:12px;color:#777;margin-top:6px}
    .info-box{background:#e8f5e9;border-left:4px solid #48bb78;padding:16px;border-radius:12px;margin-bottom:20px;color:#1f7a3a;border:1px solid #c3e6cb}
    .alert{padding:20px 25px;border-radius:16px;margin-bottom:20px;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,0.1);animation:slideDown 0.3s ease;border:1px solid}
    .alert-success{background:#d4edda;color:#155724;border-color:#48bb78}
    .alert-error{background:#fee;color:#c62828;border-color:#f56565}
    @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
    .btn-group{display:flex;gap:14px;margin-top:24px}
    .btn{flex:1;padding:14px;border-radius:12px;font-weight:600;border:none;cursor:pointer;transition:all 0.3s ease;font-size:15px}
    .btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;box-shadow:0 4px 15px rgba(102,126,234,0.3)}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.4)}
    .btn-secondary{background:rgba(255,255,255,0.9);color:#333;text-decoration:none;display:flex;align-items:center;justify-content:center;border:2px solid #e2e8f0}
    .btn-secondary:hover{background:white;border-color:#667eea;color:#667eea}
    @media(max-width:900px){.page{margin-left:16px;padding:16px}.navbar{left:0}.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="navbar">
        <h1><button class="toggle-btn" onclick="toggleSidebar()">☰</button>✏️ Edit Renewal Application</h1>
    </div>
    <div class="page">
        <div class="header-card">
            <h2>Edit Renewal</h2>
            <p>Update your pending renewal details and upload any revised documents.</p>
        </div>

        <?php render_flash(); ?>

        <div class="form-card">
            <div class="info-box">
                Only pending renewals can be edited. Uploaded files are optional unless you want to replace them.
            </div>
            <form action="edit_renewal.php?id=<?= h($renewId) ?>" method="POST" enctype="multipart/form-data">
                <div class="section-title">👤 Personal Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input id="student_id" name="student_id" type="text" value="<?= h($renewData['student_id']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="tel" value="<?= h($renewData['contact_number']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input id="address" name="address" type="text" value="<?= h($renewData['address']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="municipality">Municipality</label>
                        <input id="municipality" name="municipality" type="text" value="<?= h($renewData['municipality']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="barangay">Barangay</label>
                        <input id="barangay" name="barangay" type="text" value="<?= h($renewData['baranggay']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input id="course" name="course" type="text" value="<?= h($renewData['course']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <input id="year_level" name="year_level" type="text" value="<?= h($renewData['year_level']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="gwa">GWA</label>
                    <input id="gwa" name="gwa" type="text" value="<?= h($renewData['gwa']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="reason">Reason</label>
                    <textarea id="reason" name="reason" required><?= h($renewData['reason']) ?></textarea>
                </div>
                <div class="section-title" style="margin-top:14px">📎 Replace Documents</div>
                <div class="form-group">
                    <label for="school_id">School ID</label>
                    <input id="school_id" name="school_id" type="file" accept=".png,.jpg,.jpeg,.pdf">
                    <div class="hint">Leave blank to keep existing linked file.</div>
                </div>
                <div class="form-group">
                    <label for="id_picture">ID Picture</label>
                    <input id="id_picture" name="id_picture" type="file" accept=".png,.jpg,.jpeg,.pdf">
                </div>
                <div class="form-group">
                    <label for="birth_certificate">Birth Certificate</label>
                    <input id="birth_certificate" name="birth_certificate" type="file" accept=".png,.jpg,.jpeg,.pdf">
                </div>
                <div class="form-group">
                    <label for="grades">Grades</label>
                    <input id="grades" name="grades" type="file" accept=".png,.jpg,.jpeg,.pdf">
                </div>
                <div class="form-group">
                    <label for="cor">COR</label>
                    <input id="cor" name="cor" type="file" accept=".png,.jpg,.jpeg,.pdf">
                </div>
                <div class="btn-group">
                    <a href="my_applications.php" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
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