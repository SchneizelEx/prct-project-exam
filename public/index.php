<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = current_user();
if ($user) {
    redirect(role_home_path($user['role']));
}

$pageTitle = 'ระบบลงทะเบียนสอบนำเสนอโครงงาน';
require __DIR__ . '/../src/partials/header.php';
?>
<div class="text-center mb-4">
    <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-lg">เข้าสู่ระบบ</a>
</div>
<?php require __DIR__ . '/../src/partials/approved_schedule.php'; ?>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
