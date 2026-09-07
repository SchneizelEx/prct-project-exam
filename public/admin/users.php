<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

$importResults = null;

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
    } elseif ($action === 'import_csv') {
        $file = $_FILES['csv_file'] ?? null;

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            flash_set('danger', 'กรุณาเลือกไฟล์ CSV');
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            flash_set('danger', 'อัปโหลดไฟล์ไม่สำเร็จ (รหัสข้อผิดพลาด ' . $file['error'] . ')');
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            flash_set('danger', 'ไฟล์ต้องเป็นนามสกุล .csv เท่านั้น');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            flash_set('danger', 'ไฟล์ต้องมีขนาดไม่เกิน 2MB');
        } else {
            $parsed = csv_parse_users($file['tmp_name']);
            if ($parsed['error']) {
                flash_set('danger', $parsed['error']);
            } elseif (!$parsed['rows']) {
                flash_set('danger', 'ไม่พบข้อมูลผู้ใช้ในไฟล์ CSV');
            } else {
                $importResults = [];
                foreach ($parsed['rows'] as $row) {
                    $role = csv_normalize_role($row['role']);
                    $username = $row['username'];
                    $fullName = $row['full_name'];
                    $password = $row['password'];
                    $generatedPassword = null;

                    if ($role === null) {
                        $importResults[] = ['line' => $row['line'], 'username' => $username, 'status' => 'error', 'message' => 'บทบาทไม่ถูกต้อง (ต้องเป็น admin, teacher หรือ student)'];
                        continue;
                    }
                    if ($username === '' || $fullName === '') {
                        $importResults[] = ['line' => $row['line'], 'username' => $username, 'status' => 'error', 'message' => 'ข้อมูลไม่ครบ (ต้องมี username และ full_name)'];
                        continue;
                    }
                    if ($password !== '' && strlen($password) < 6) {
                        $importResults[] = ['line' => $row['line'], 'username' => $username, 'status' => 'error', 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];
                        continue;
                    }
                    if (users_find_by_username($pdo, $username)) {
                        $importResults[] = ['line' => $row['line'], 'username' => $username, 'status' => 'error', 'message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว'];
                        continue;
                    }

                    if ($password === '') {
                        $password = generate_random_password();
                        $generatedPassword = $password;
                    }

                    users_create($pdo, [
                        'role' => $role, 'username' => $username, 'password' => $password,
                        'full_name' => $fullName, 'email' => $row['email'], 'code' => $row['code'],
                    ]);
                    $importResults[] = [
                        'line' => $row['line'],
                        'username' => $username,
                        'status' => 'success',
                        'message' => $generatedPassword !== null ? 'สร้างสำเร็จ (รหัสผ่านที่สุ่มให้: ' . $generatedPassword . ')' : 'สร้างสำเร็จ',
                    ];
                }
            }
        }
    }

    if ($action !== 'import_csv') {
        redirect(base_url('admin/users.php'));
    }
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

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">นำเข้าผู้ใช้จาก CSV</h5>
        <p class="text-muted small mb-2">
            ไฟล์ .csv แถวหัวตารางต้องมีคอลัมน์ <code>role,username,password,full_name,code,email</code>
            (จำเป็น: <code>role</code>, <code>username</code>, <code>full_name</code> — ส่วน <code>password</code> เว้นว่างได้ ระบบจะสุ่มให้อัตโนมัติ)
            ค่า role รับได้ทั้ง <code>admin</code>/<code>teacher</code>/<code>student</code> หรือ ผู้ดูแลระบบ/ครู/นักเรียน<br>
            ตัวอย่าง: <code>student,student10,,นักเรียนสิบ ตัวอย่าง,S010,</code>
        </p>
        <p class="mb-2">
            <a href="<?= base_url('admin/csv_template.php') ?>" class="btn btn-sm btn-outline-secondary">ดาวน์โหลดไฟล์ตัวอย่าง (CSV)</a>
        </p>
        <form method="post" enctype="multipart/form-data" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_csv">
            <div class="col-md-6">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">นำเข้า</button>
            </div>
        </form>
    </div>
</div>

<?php if ($importResults !== null): ?>
    <?php
    $successCount = count(array_filter($importResults, fn($r) => $r['status'] === 'success'));
    $errorCount = count($importResults) - $successCount;
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">ผลการนำเข้า: สำเร็จ <?= $successCount ?> รายการ, ผิดพลาด <?= $errorCount ?> รายการ</h5>
            <p class="small text-danger">รหัสผ่านที่สุ่มให้จะแสดงเพียงครั้งเดียวในตารางนี้ กรุณาบันทึกไว้ก่อนออกจากหน้านี้</p>
            <table class="table table-sm table-bordered">
                <thead><tr><th>แถวที่</th><th>ชื่อผู้ใช้</th><th>ผลลัพธ์</th><th>รายละเอียด</th></tr></thead>
                <tbody>
                <?php foreach ($importResults as $r): ?>
                    <tr class="<?= $r['status'] === 'success' ? 'table-success' : 'table-danger' ?>">
                        <td><?= (int)$r['line'] ?></td>
                        <td><?= h($r['username']) ?></td>
                        <td><?= $r['status'] === 'success' ? 'สำเร็จ' : 'ผิดพลาด' ?></td>
                        <td><?= h($r['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

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
