<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

if (is_post()) {
    csrf_check_or_die();
    $action = post('action');

    if ($action === 'create') {
        $role = post('role');
        $username = post('username');
        $password = (string)($_POST['password'] ?? '');
        $fullName = post('full_name');
        $email = post('email');
        $code = post('code');

        if (!in_array($role, ['admin', 'teacher', 'student'], true) || $username === '' || $password === '' || $fullName === '') {
            flash_set('danger', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        } elseif (strlen($password) < 6) {
            flash_set('danger', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        } elseif (users_find_by_username($pdo, $username)) {
            flash_set('danger', 'ชื่อผู้ใช้นี้มีอยู่แล้ว');
        } else {
            users_create($pdo, [
                'role' => $role, 'username' => $username, 'password' => $password,
                'full_name' => $fullName, 'email' => $email, 'code' => $code,
            ]);
            flash_set('success', 'สร้างบัญชีผู้ใช้เรียบร้อยแล้ว');
        }
    } elseif ($action === 'toggle_active') {
        $id = (int)post('id');
        $target = users_find($pdo, $id);
        if ($target) {
            users_set_active($pdo, $id, !$target['is_active']);
            flash_set('success', 'อัปเดตสถานะบัญชีเรียบร้อยแล้ว');
        }
    } elseif ($action === 'reset_password') {
        $id = (int)post('id');
        $newPassword = (string)($_POST['new_password'] ?? '');
        if (strlen($newPassword) < 6) {
            flash_set('danger', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        } else {
            users_reset_password($pdo, $id, $newPassword);
            flash_set('success', 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว');
        }
    }
    redirect(base_url('admin/users.php'));
}

$allUsers = users_all($pdo);
$pageTitle = 'จัดการผู้ใช้';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">จัดการผู้ใช้</h3>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">เพิ่มบัญชีผู้ใช้ใหม่</h5>
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-2">
                <select name="role" class="form-select" required>
                    <option value="teacher">ครู</option>
                    <option value="student">นักเรียน</option>
                    <option value="admin">ผู้ดูแลระบบ</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required>
            </div>
            <div class="col-md-2">
                <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="full_name" class="form-control" placeholder="ชื่อ-นามสกุล" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="code" class="form-control" placeholder="รหัสนักเรียน/ครู">
            </div>
            <div class="col-md-2">
                <input type="email" name="email" class="form-control" placeholder="อีเมล (ถ้ามี)">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">เพิ่มบัญชี</button>
            </div>
        </form>
    </div>
</div>

<table class="table table-bordered bg-white">
    <thead>
    <tr>
        <th>บทบาท</th><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>รหัส</th><th>อีเมล</th><th>สถานะ</th><th>จัดการ</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($allUsers as $u): ?>
        <tr>
            <td><?= h($u['role']) ?></td>
            <td><?= h($u['username']) ?></td>
            <td><?= h($u['full_name']) ?></td>
            <td><?= h($u['code']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= $u['is_active'] ? '<span class="badge bg-success">ใช้งาน</span>' : '<span class="badge bg-secondary">ระงับ</span>' ?></td>
            <td class="d-flex gap-1 flex-wrap">
                <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <?= $u['is_active'] ? 'ระงับ' : 'เปิดใช้งาน' ?>
                    </button>
                </form>
                <form method="post" class="d-inline d-flex gap-1" onsubmit="return this.new_password.value.length >= 6;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <input type="password" name="new_password" class="form-control form-control-sm" placeholder="รหัสผ่านใหม่" style="width:140px">
                    <button type="submit" class="btn btn-sm btn-outline-warning">ตั้งรหัสผ่าน</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
