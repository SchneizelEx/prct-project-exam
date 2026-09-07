<?php
require_once __DIR__ . '/../../src/bootstrap.php';

require_role('admin');

$rows = [
    ['role', 'username', 'password', 'full_name', 'code', 'email'],
    ['teacher', 'teacher10', '', 'ครูสิบ ตัวอย่าง', 'T010', 'teacher10@example.com'],
    ['student', 'student10', '', 'นักเรียนสิบ ตัวอย่าง', 'S010', ''],
];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="user_import_template.csv"');

// ใส่ UTF-8 BOM เพื่อให้ Excel เปิดแล้วแสดงภาษาไทยถูกต้อง
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
