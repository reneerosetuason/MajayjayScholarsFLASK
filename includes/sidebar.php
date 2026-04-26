<?php
$path = $_SERVER['REQUEST_URI'];
$userType = $_SESSION['user_type'] ?? '';
?>
<div class="sidebar" id="sidebar">
    <img src="/MajayjayScholars/static/assets/majayjay_logo.jpg" alt="Majayjay Logo" class="sidebar-logo">
    <h2>MajayjayScholars</h2>

    <?php if ($userType === 'student'): ?>
        <a href="/MajayjayScholars/student.php" class="<?= strpos($path,'student.php') !== false ? 'active' : '' ?>">🏠 Dashboard</a>
        <a href="/MajayjayScholars/my_applications.php" class="<?= strpos($path,'my_applications') !== false ? 'active' : '' ?>">📄 My Applications</a>
        <a href="/MajayjayScholars/renew.php" class="<?= strpos($path,'renew') !== false ? 'active' : '' ?>">🔄 Renew Scholarship</a>
        <a href="/MajayjayScholars/apply.php" class="<?= strpos($path,'apply') !== false ? 'active' : '' ?>">📝 Apply Scholarship</a>

    <?php elseif ($userType === 'mayor'): ?>
        <a href="/MajayjayScholars/mayor/dashboard.php" class="<?= strpos($path,'mayor/dashboard') !== false ? 'active' : '' ?>">🏛 Mayor Dashboard</a>
        <a href="/MajayjayScholars/mayor/records.php" class="<?= strpos($path,'mayor/records') !== false ? 'active' : '' ?>">📁 Scholar Records</a>

    <?php elseif ($userType === 'admin'): ?>
        <a href="/MajayjayScholars/admin/dashboard.php" class="<?= strpos($path,'admin/dashboard') !== false ? 'active' : '' ?>">🏛 Admin Dashboard</a>
        <a href="/MajayjayScholars/admin/add_admin.php" class="<?= strpos($path,'add_admin') !== false ? 'active' : '' ?>">👥 Add Admin Account</a>
    <?php endif; ?>

    <a href="/MajayjayScholars/logout.php" class="logout-btn">Logout</a>
</div>

<style>
.sidebar {
    width: 250px; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex; flex-direction: column; padding: 20px; position: fixed;
    height: 100vh; left: 0; top: 0; transition: all 0.3s ease; z-index: 1100;
    border-right: 1px solid #e2e8f0;
}
.sidebar.hide { left: -260px; }
.sidebar-logo { width: 80px; height: 80px; object-fit: contain; margin: 0 auto 15px auto; display: block; }
.sidebar h2 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; font-size: 22px; margin-bottom: 30px; text-align: center; font-weight: 600;
}
.sidebar a {
    display: block; padding: 12px 15px; color: #2d3748; text-decoration: none;
    border-radius: 12px; font-weight: 500; transition: all 0.3s ease; margin-bottom: 8px;
}
.sidebar a:hover { background: rgba(102,126,234,0.1); color: #667eea; transform: translateX(5px); }
.sidebar a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff; box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}
.logout-btn {
    margin-top: auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important; text-align: center; padding: 12px; border-radius: 12px;
    transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}
.logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.4); }
</style>
