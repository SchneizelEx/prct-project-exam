<?php
declare(strict_types=1);

// สคริปต์สร้างบัญชี admin คนแรก รันจาก command line เท่านั้น
// วิธีใช้: php bin/create_admin.php <username> <password> "<ชื่อ-นามสกุล>"

if (PHP_SAPI !== 'cli') {
    die('สคริปต์นี้ใช้ได้เฉพาะจาก command line เท่านั้น');
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repositories/users.php';

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    fwrite(STDERR, "ไม่พบ config/config.php กรุณาคัดลอกจาก config/config.php.example ก่อน\n");
    exit(1);
}
$config = require $configFile;

[$script, $username, $password, $fullName] = array_pad($argv, 4, null);

if (!$username || !$password || !$fullName) {
    fwrite(STDERR, "วิธีใช้: php bin/create_admin.php <username> <password> \"<ชื่อ-นามสกุล>\"\n");
    exit(1);
}

if (strlen($password) < 6) {
    fwrite(STDERR, "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร\n");
    exit(1);
}

$pdo = db_connect($config['db']);

if (users_find_by_username($pdo, $username)) {
    fwrite(STDERR, "มีชื่อผู้ใช้นี้อยู่แล้ว\n");
    exit(1);
}

$id = users_create($pdo, [
    'role' => 'admin',
    'username' => $username,
    'password' => $password,
    'full_name' => $fullName,
    'email' => '',
    'code' => '',
]);

echo "สร้างบัญชี admin สำเร็จ (id=$id, username=$username)\n";
