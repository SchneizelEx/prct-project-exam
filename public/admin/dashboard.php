<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

$stats = [
    'users' => count(users_all($pdo)),
    'teachers' => count(users_all($pdo, 'teacher')),
    'students' => count(users_all($pdo, 'student')),
    'exam_days' => count(exam_days_all($pdo)),
    'pending' => count(projects_all($pdo, ['status' => 'pending'])),
    'approved' => count(projects_all($pdo, ['status' => 'approved'])),
];

$pageTitle = 'แดชบอร์ดผู้ดูแลระบบ';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">แดชบอร์ดผู้ดูแลระบบ</h3>

<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card text-center p-3"><div class="fs-3"><?= $stats['teachers'] ?></div><div class="text-muted small">ครู</div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card text-center p-3"><div class="fs-3"><?= $stats['students'] ?></div><div class="text-muted small">นักเรียน</div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card text-center p-3"><div class="fs-3"><?= $stats['exam_days'] ?></div><div class="text-muted small">วันสอบทั้งหมด</div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card text-center p-3"><div class="fs-3"><?= $stats['pending'] ?></div><div class="text-muted small">รอครูอนุมัติ</div></div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card text-center p-3"><div class="fs-3"><?= $stats['approved'] ?></div><div class="text-muted small">อนุมัติแล้ว</div></div>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap">
    <a href="<?= base_url('admin/users.php') ?>" class="btn btn-primary">จัดการผู้ใช้</a>
    <a href="<?= base_url('admin/exam_days.php') ?>" class="btn btn-primary">จัดการวันสอบ</a>
    <a href="<?= base_url('admin/projects.php') ?>" class="btn btn-primary">รายการโครงงานทั้งหมด</a>
    <a href="<?= base_url('admin/evaluation_criteria.php') ?>" class="btn btn-primary">จัดการแบบประเมินโครงการ</a>
</div>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
