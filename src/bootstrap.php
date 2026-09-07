<?php
declare(strict_types=1);

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('ไม่พบไฟล์ config/config.php กรุณาคัดลอกจาก config/config.php.example แล้วตั้งค่าฐานข้อมูล');
}

/** @var array $config */
$config = require $configFile;

date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rules.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/repositories/users.php';
require_once __DIR__ . '/repositories/exam_days.php';
require_once __DIR__ . '/repositories/projects.php';

$pdo = db_connect($config['db']);
