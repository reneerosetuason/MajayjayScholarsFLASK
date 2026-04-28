<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_role('student');
ensure_upload_folder();

$userId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT first_name, middle_name, last_name, email FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$userInfo = $userResult->fetch_assoc();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) AS count FROM application WHERE user_id = ? AND (scholarship_type = ? OR scholarship_type IS NULL)');
$type = 'new';
$stmt->bind_param('is', $userId, $type);
$stmt->execute();
$countResult = $stmt->get_result();
$countRow = $countResult->fetch_assoc();
$stmt->close();

if ($countRow && intval($countRow['count']) > 0) {
    flash('You have already submitted an application. You can only apply once.', 'error');
    redirect('student.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $municipality = trim($_POST['municipality'] ?? 'Majayjay');
    $barangay = trim($_POST['barangay'] ?? '');
    $school_name = trim($_POST['school_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $gwa = trim($_POST['gwa'] ?? '');
    $year_applied = trim($_POST['year_applied'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $requiredFiles = ['school_id', 'id_picture', 'birth_certificate', 'grades', 'cor'];
    $uploadedFiles = [];
    $error = null;
    foreach ($requiredFiles as $field) {
        if (empty($_FILES[$field]['name'])) {
            $error = 'Please upload all required documents.';
            break;
        }
        if (!allowed_file($_FILES[$field]['name'])) {
            $error = 'Allowed file types are PNG, JPG, JPEG, and PDF.';
            break;
        }
    }

    if ($error) {
        flash($error, 'error');
    } else {
        foreach ($requiredFiles as $field) {
            $file = $_FILES[$field];
            $name = pathinfo($file['name'], PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
            $filename = $safeName . '_' . time() . '.' . $ext;
            $destination = UPLOAD_FOLDER . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $error = 'Unable to save uploaded files. Please try again.';
                break;
            }
            $uploadedFiles[$field] = $filename;
        }
    }

    if (!$error) {
        $stmt = $db->prepare('SELECT COUNT(*) AS count FROM application WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $existingRow = $countResult->fetch_assoc();
        $stmt->close();

        if ($existingRow && intval($existingRow['count']) > 0) {
            flash('You have already submitted an application. You can only apply once.', 'error');
            redirect('student.php');
        }

        $stmt = $db->prepare('INSERT INTO application (user_id, first_name, middle_name, last_name, student_id, contact_number, address, municipality, baranggay, school_name, course, year_level, gwa, year_applied, reason, school_id_path, id_picture_path, birth_certificate_path, grades_path, cor_path, scholarship_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $scholarship_type = 'new';
        $stmt->bind_param('issssssssssssdsssssss', $userId, $userInfo['first_name'], $userInfo['middle_name'], $userInfo['last_name'], $student_id, $contact_number, $address, $municipality, $barangay, $school_name, $course, $year_level, $gwa, $year_applied, $reason, $uploadedFiles['school_id'], $uploadedFiles['id_picture'], $uploadedFiles['birth_certificate'], $uploadedFiles['grades'], $uploadedFiles['cor'], $scholarship_type);
        if ($stmt->execute()) {
            flash('✅ Application submitted successfully!', 'success');
            $stmt->close();
            redirect('student.php');
        }
        $stmt->close();
        flash('❌ Error submitting application. Please try again.', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Apply Scholarship | Scholar App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root{--primary:#667eea;--primary-dark:#764ba2;--muted:#e2e8f0;}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f7fafc;color:#2d3748;min-height:100vh;overflow-x:hidden}
    .navbar{position:fixed;left:250px;top:0;right:0;background:white;padding:16px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);z-index:900;transition:left 0.3s ease;border-bottom:1px solid #e2e8f0;}
    .sidebar.hide ~ .navbar{left:0;}
    .navbar h1{color:#2d3748;font-size:20px;display:flex;align-items:center;gap:10px;font-weight:600}
    .navbar .btn{padding:8px 14px;border-radius:8px;background:var(--muted);color:#222;text-decoration:none;font-weight:600;border:none;cursor:pointer}
    .toggle-btn{background:rgba(102,126,234,0.1);border:none;font-size:24px;color:var(--primary);cursor:pointer;padding:5px 10px;border-radius:8px;transition:0.3s;}
    .toggle-btn:hover{background:rgba(102,126,234,0.2);transform:translateY(-2px);}
    .page{margin-left:250px;margin-top:70px;padding:28px 32px;transition:margin-left 0.3s ease;}
    .sidebar.hide ~ .page{margin-left:0;}
    .header-card{background:white;color:#2d3748;padding:32px;border-radius:24px;text-align:center;margin-bottom:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:850px;margin-left:auto;margin-right:auto;border:1px solid #e2e8f0}
    .header-card h2{font-size:28px;margin-bottom:8px;font-weight:700;letter-spacing:-0.025em;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .header-card p{color:#718096}
    .form-card{background:white;padding:32px;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);max-width:850px;margin:0 auto;border:1px solid #e2e8f0}
    .section-title{display:flex;gap:10px;align-items:center;color:#2d3748;font-weight:700;margin-bottom:14px;font-size:16px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;font-weight:700;margin-bottom:8px}
    input[type="text"], input[type="tel"], input[type="number"], select, textarea, input[type="file"]{width:100%;padding:14px 18px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px;background:#f7fafc;font-family:'Inter',sans-serif;transition:all 0.3s ease;}
    input:focus, select:focus, textarea:focus{outline:none;border-color:#667eea;background:white;box-shadow:0 0 0 3px rgba(102,126,234,0.1)}
    input:read-only{background:#edf2f7;cursor:not-allowed;color:#666;opacity:0.7}
    textarea{min-height:120px;resize:vertical}
    .hint{font-size:12px;color:#777;margin-top:6px}
    .info-box{background:#e8f5e9;border-left:4px solid #48bb78;padding:16px;border-radius:12px;margin-bottom:20px;color:#1f7a3a;border:1px solid #c3e6cb}
    .alert{padding:20px 25px;border-radius:16px;margin-bottom:20px;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,0.1);animation:slideDown 0.3s ease;border:1px solid}
    .alert-success{background:#d4edda;color:#155724;border-color:#48bb78;font-size:15px}
    .alert-error{background:#fee;color:#c62828;border-color:#f56565;font-size:15px}
    @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
    .btn-group{display:flex;gap:14px;margin-top:24px}
    .btn{flex:1;padding:14px;border-radius:12px;font-weight:600;border:none;cursor:pointer;transition:all 0.3s ease;font-size:15px}
    .btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;box-shadow:0 4px 15px rgba(102,126,234,0.3)}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.4)}
    .btn-secondary{background:rgba(255,255,255,0.9);color:#333;text-decoration:none;display:flex;align-items:center;justify-content:center;border:2px solid #e2e8f0}
    .btn-secondary:hover{background:white;border-color:#667eea;color:#667eea}
    .btn-primary:disabled{background:#ccc;cursor:not-allowed;opacity:0.6}
    @media(max-width:900px){.page{margin-left:16px;padding:16px;margin-top:70px}.navbar{left:0}.form-row{grid-template-columns:1fr}.sidebar{position:relative;width:100%;height:auto;box-shadow:none;flex-direction:row;gap:12px;align-items:center;padding:12px;transform:none}.sidebar.hide{transform:none}.sidebar a{display:inline-block;padding:8px;font-size:13px}.logout-btn{margin-top:0}}
</style>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="navbar">
        <h1><button class="toggle-btn" onclick="toggleSidebar()">☰</button>📝 Apply for Scholarship</h1>
    </div>
    <div class="page">
        <div class="header-card">
            <h2>Scholarship Application Form</h2>
            <p>Complete the form honestly. Fields marked with * are required.</p>
        </div>

        <?php render_flash(); ?>

        <div class="form-card">
            <div class="info-box">
                <strong>📋 Before you start</strong>
                Make sure your documents are ready. Max file size 5MB. Supported: JPG, PNG, PDF.
            </div>

            <form id="applicationForm" action="apply.php" method="POST" enctype="multipart/form-data">
                <div class="section-title">👤 Personal Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span style="color:#f56565">*</span></label>
                        <input id="first_name" name="first_name" type="text" readonly value="<?= h($userInfo['first_name'] ?? '') ?>" style="background:#f5f5f5">
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input id="middle_name" name="middle_name" type="text" readonly value="<?= h($userInfo['middle_name'] ?? '') ?>" style="background:#f5f5f5">
                    </div>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span style="color:#f56565">*</span></label>
                    <input id="last_name" name="last_name" type="text" readonly value="<?= h($userInfo['last_name'] ?? '') ?>" style="background:#f5f5f5">
                    <div class="hint">✓ Name automatically filled from your profile. Contact admin to update.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID <span style="color:#f56565">*</span></label>
                        <input id="student_id" name="student_id" type="text" required placeholder="2024-12345">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number <span style="color:#f56565">*</span></label>
                        <input id="contact_number" name="contact_number" type="tel" required placeholder="09171234567" pattern="[0-9]{11}">
                        <div class="hint">11-digit mobile number</div>
                    </div>
                </div>
                <div class="section-title" style="margin-top:14px">📍 Address</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">House No. / Street <span style="color:#f56565">*</span></label>
                        <input id="address" name="address" type="text" required placeholder="Blk 2 Lot 10, Rizal St.">
                        <div class="hint">Street, house no., etc.</div>
                    </div>
                    <div class="form-group">
                        <label for="municipality">Municipality <span style="color:#f56565">*</span></label>
                        <select id="municipality" name="municipality" required>
                            <option value="Majayjay">Majayjay</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay <span style="color:#f56565">*</span></label>
                    <select id="barangay" name="barangay" required>
                        <option value="">Select Barangay</option>
                        <option>Amonoy</option><option>Bakia</option><option>Balanac</option><option>Balayong</option>
                        <option>Banilad</option><option>Banti</option><option>Bitaoy</option><option>Botocan</option>
                        <option>Bukal</option><option>Burgos</option><option>Burol</option><option>Coralao</option>
                        <option>Gagalot</option><option>Ibabang Banga</option><option>Ibabang Bayucain</option><option>Ilayang Banga</option>
                        <option>Ilayang Bayucain</option><option>Isabang</option><option>Malinao</option><option>May-it</option>
                        <option>Munting Kawayan</option><option>Olla</option><option>Oobi</option><option>Origuel (Poblacion)</option>
                        <option>Panalaban</option><option>Pangil</option><option>Panglan</option><option>Piit</option>
                        <option>Pook</option><option>Rizal</option><option>San Francisco (Poblacion)</option><option>San Isidro</option>
                        <option>San Miguel (Poblacion)</option><option>San Roque</option><option>Santa Catalina</option><option>Suba</option>
                        <option>Talortor</option><option>Tanawan</option><option>Taytay</option><option>Villa Nogales</option>
                    </select>
                </div>
                <div class="section-title" style="margin-top:14px">🎓 Academic Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="school_name">School Name <span style="color:#f56565">*</span></label>
                        <input id="school_name" name="school_name" type="text" required placeholder="Enter your school name">
                    </div>
                    <div class="form-group">
                        <label for="course">Course <span style="color:#f56565">*</span></label>
                        <input id="course" name="course" type="text" required placeholder="Enter your course">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="year_level">Year Level <span style="color:#f56565">*</span></label>
                        <input id="year_level" name="year_level" type="text" required placeholder="e.g. 2nd Year">
                    </div>
                    <div class="form-group">
                        <label for="gwa">GWA <span style="color:#f56565">*</span></label>
                        <input id="gwa" name="gwa" type="text" required placeholder="e.g. 1.75">
                    </div>
                </div>
                <div class="form-group">
                    <label for="year_applied">Year Applied <span style="color:#f56565">*</span></label>
                    <input id="year_applied" name="year_applied" type="number" required placeholder="2025">
                </div>
                <div class="form-group">
                    <label for="reason">Why should you be selected?</label>
                    <textarea id="reason" name="reason" placeholder="Tell us why..." required></textarea>
                </div>
                <div class="section-title" style="margin-top:14px">📎 Upload Documents</div>
                <div class="form-group">
                    <label for="school_id">School ID <span style="color:#f56565">*</span></label>
                    <input id="school_id" name="school_id" type="file" accept=".png,.jpg,.jpeg,.pdf" required>
                </div>
                <div class="form-group">
                    <label for="id_picture">ID Picture <span style="color:#f56565">*</span></label>
                    <input id="id_picture" name="id_picture" type="file" accept=".png,.jpg,.jpeg,.pdf" required>
                </div>
                <div class="form-group">
                    <label for="birth_certificate">Birth Certificate <span style="color:#f56565">*</span></label>
                    <input id="birth_certificate" name="birth_certificate" type="file" accept=".png,.jpg,.jpeg,.pdf" required>
                </div>
                <div class="form-group">
                    <label for="grades">Grades <span style="color:#f56565">*</span></label>
                    <input id="grades" name="grades" type="file" accept=".png,.jpg,.jpeg,.pdf" required>
                </div>
                <div class="form-group">
                    <label for="cor">Certificate of Registration (COR) <span style="color:#f56565">*</span></label>
                    <input id="cor" name="cor" type="file" accept=".png,.jpg,.jpeg,.pdf" required>
                </div>
                <div class="btn-group">
                    <a href="student.php" class="btn btn-secondary">Back to Dashboard</a>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
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
