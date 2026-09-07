<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login();

$projectId = (int)($_GET['project_id'] ?? 0);
$type = (string)($_GET['type'] ?? '');

if ($projectId <= 0 || !in_array($type, ['report', 'presentation'], true)) {
    http_response_code(400);
    die('คำขอไม่ถูกต้อง');
}

$project = projects_find($pdo, $projectId);
if (!$project) {
    http_response_code(404);
    die('ไม่พบโครงงาน');
}

$allowed = false;
if ($user['role'] === 'admin') {
    $allowed = true;
} elseif ($user['role'] === 'teacher' && (int)$project['advisor_teacher_id'] === $user['id']) {
    $allowed = true;
} elseif ($user['role'] === 'student') {
    $memberIds = array_map(fn($m) => (int)$m['id'], projects_members($pdo, $projectId));
    $allowed = in_array($user['id'], $memberIds, true);
}

if (!$allowed) {
    http_response_code(403);
    die('คุณไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้');
}

$path = $type === 'report' ? $project['report_docx_path'] : $project['presentation_pptx_path'];
$fullPath = rtrim($config['upload']['storage_dir'], '/\\') . '/' . $path;

if (!is_file($fullPath)) {
    http_response_code(404);
    die('ไม่พบไฟล์บนเซิร์ฟเวอร์');
}

$extension = $type === 'report' ? 'docx' : 'pptx';
$mime = $type === 'report'
    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    : 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
$downloadName = $type === 'report' ? 'report_' . $projectId . '.' . $extension : 'presentation_' . $projectId . '.' . $extension;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
