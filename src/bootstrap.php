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

// path ย่อยที่แอปถูก deploy ไว้ เช่น '/project-exam' ถ้า deploy ไว้ที่ root ให้เว้นว่าง
define('APP_BASE_PATH', rtrim($config['app']['base_path'] ?? '', '/'));

// นักเรียนต้องเลือกสมาชิก/ครูที่ปรึกษา และอัปโหลด 2 ไฟล์ ในฟอร์มลงทะเบียน อาจใช้เวลานานกว่า
// session lifetime ปกติของเซิร์ฟเวอร์ (มักตั้งไว้แค่ ~20-24 นาที) จึงกำหนดเองให้ยาวพอ กัน CSRF token
// หมดอายุกลางคันจากค่า session.gc_maxlifetime เริ่มต้นของเซิร์ฟเวอร์
$sessionLifetime = 4 * 60 * 60; // 4 ชั่วโมง
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => APP_BASE_PATH !== '' ? APP_BASE_PATH . '/' : '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rules.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/csv_import.php';
require_once __DIR__ . '/repositories/users.php';
require_once __DIR__ . '/repositories/exam_days.php';
require_once __DIR__ . '/repositories/projects.php';

$pdo = db_connect($config['db']);
