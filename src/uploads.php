<?php
declare(strict_types=1);

const UPLOAD_MIME_MAP = [
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];

/**
 * ตรวจสอบและย้ายไฟล์ที่อัปโหลดไปเก็บใน storage dir ด้วยชื่อ random
 * @return array{ok:bool, path?:string, error?:string}
 */
function handle_upload(array $file, string $expectedExt, string $storageDir, int $maxBytes): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'กรุณาเลือกไฟล์'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'อัปโหลดไฟล์ไม่สำเร็จ (รหัสข้อผิดพลาด ' . $file['error'] . ')'];
    }
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'ขนาดไฟล์ต้องไม่เกิน ' . round($maxBytes / 1024 / 1024) . 'MB'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'ไฟล์อัปโหลดไม่ถูกต้อง'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== $expectedExt) {
        return ['ok' => false, 'error' => 'ไฟล์ต้องเป็นนามสกุล .' . $expectedExt . ' เท่านั้น'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // ไฟล์ .docx/.pptx เป็น zip archive ภายใน เครื่องตรวจ mime บางระบบอาจรายงานเป็น application/zip
    $validMimes = [UPLOAD_MIME_MAP[$expectedExt], 'application/zip', 'application/octet-stream'];
    if (!in_array($mime, $validMimes, true)) {
        return ['ok' => false, 'error' => 'ชนิดไฟล์ไม่ถูกต้อง (ตรวจพบ ' . $mime . ')'];
    }

    if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true) && !is_dir($storageDir)) {
        return ['ok' => false, 'error' => 'ไม่สามารถสร้างโฟลเดอร์เก็บไฟล์ได้'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $expectedExt;
    $destination = rtrim($storageDir, '/\\') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'ไม่สามารถบันทึกไฟล์ได้'];
    }

    return ['ok' => true, 'path' => $filename];
}
