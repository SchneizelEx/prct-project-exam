<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function base_url(string $path = ''): string
{
    $basePath = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
    return $basePath . '/' . ltrim($path, '/');
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * เมื่อไฟล์ที่อัปโหลดรวมกันเกิน post_max_size ของ PHP บนเซิร์ฟเวอร์ PHP จะเคลียร์ $_POST และ
 * $_FILES ทิ้งทั้งหมดโดยไม่แจ้ง error ตรงๆ (ต่างจากไฟล์เดี่ยวเกิน upload_max_filesize ที่ยังมี
 * $_FILES พร้อม error code) ทำให้ตรวจ CSRF แล้วดูเหมือน token ไม่ตรงกันทั้งที่ session ปกติดี
 * ฟังก์ชันนี้เช็คแยกกรณีนี้ออกมาก่อน เพื่อแจ้งข้อความที่ตรงกับปัญหาจริง
 */
function is_post_size_exceeded(): bool
{
    return is_post()
        && empty($_POST)
        && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function generate_random_password(int $length = 8): string
{
    // ตัดตัวอักษร/ตัวเลขที่สับสนง่ายออก (0/O, 1/l/I)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}
