<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = current_user();
if ($user) {
    redirect(role_home_path($user['role']));
}

$error = null;

if (is_post()) {
    csrf_check_or_die();
    $username = post('username');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (auth_attempt_login($pdo, $username, $password)) {
        redirect(role_home_path($_SESSION['role']));
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง หรือบัญชีถูกระงับ';
    }
}

$pageTitle = 'เข้าสู่ระบบ';
require __DIR__ . '/../src/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title mb-3 text-center">เข้าสู่ระบบ</h4>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
                </form>
                <p class="text-center mt-3 mb-0 small">
                    ยังไม่เคยใช้งาน? <a href="<?= base_url('guide.php') ?>">อ่านคู่มือการใช้งาน</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../src/partials/approved_schedule.php'; ?>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
