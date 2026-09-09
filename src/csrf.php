<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_check_or_die(): void
{
    if (!csrf_verify()) {
        // เกิดได้บ่อยจากเซสชันหมดอายุระหว่างกรอกฟอร์มนาน (เช่นฟอร์มลงทะเบียนที่ต้องแนบไฟล์)
        // พากลับไปหน้าเดิมพร้อมข้อความแจ้ง แทนที่จะปล่อยให้ค้างอยู่หน้า error เฉยๆ
        flash_set('danger', 'เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณาเข้าสู่ระบบใหม่และลองอีกครั้ง');
        redirect($_SERVER['REQUEST_URI'] ?? base_url('login.php'));
    }
}
