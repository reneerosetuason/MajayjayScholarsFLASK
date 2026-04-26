<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    redirect('admin/dashboard.php');
}
redirect('login.php');
