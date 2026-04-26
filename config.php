<?php
session_start();

// Database
$db = new mysqli('localhost', 'root', 'ren123', 'majayjay_scholars');
if ($db->connect_error) {
    die('DB connection failed: ' . $db->connect_error);
}

// Email
define('SENDER_EMAIL', 'majayjayscholars@gmail.com');
define('SENDER_APP_PASSWORD', 'zsxp iqvn klmd xqfw');

// Upload
define('UPLOAD_FOLDER', __DIR__ . '/static/uploads/');
define('ALLOWED_EXT', ['png','jpg','jpeg','pdf']);
