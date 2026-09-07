<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

if (is_post()) {
    csrf_check_or_die();
    $action = post('action');

    if ($action === 'create') {
        $examDate = post('exam_date');
        $startTime = post('start_time');
        $endTime = post('end_time');

        $examTs = strtotime($examDate);
        if ($examDate === '' || !$examTs) {
            flash_set('danger', 'กรุณาระบุวันสอบให้ถูกต้อง');
        } elseif ($startTime === '' || $endTime === '' || strtotime($endTime) <= strtotime($startTime)) {
            flash_set('danger', 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม');
        } else {
            exam_days_create($pdo, $examDate, $startTime, $endTime, $user['id']);
            flash_set('success', 'เปิดวันสอบเรียบร้อยแล้ว');
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)post('id');
        $day = exam_days_find($pdo, $id);
        if ($day) {
            exam_days_set_status($pdo, $id, $day['status'] === 'open' ? 'closed' : 'open');
            flash_set('success', 'อัปเดตสถานะวันสอบเรียบร้อยแล้ว');
        }
    }
    redirect(base_url('admin/exam_days.php'));
}

$days = exam_days_all($pdo);
$pageTitle = 'จัดการวันสอบ';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">จัดการวันสอบ</h3>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">เปิดวันสอบใหม่</h5>
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <input type="date" name="exam_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-3">
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">เปิดวันสอบ</button>
            </div>
        </form>
    </div>
</div>

<table class="table table-bordered bg-white">
    <thead>
    <tr>
        <th>วันสอบ</th><th>เวลา</th><th>ความจุ (โครงงาน)</th><th>อนุมัติแล้ว</th><th>สถานะ</th><th>ลงทะเบียนได้ถึง</th><th>จัดการ</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($days as $d): ?>
        <?php
        $capacity = rules_exam_day_capacity($d['start_time'], $d['end_time']);
        $approvedCount = projects_count_approved_for_exam_day($pdo, $d['id']);
        $daysLeft = rules_days_until($d['exam_date']);
        ?>
        <tr>
            <td><a href="<?= base_url('admin/exam_day.php?id=' . $d['id']) ?>"><?= h($d['exam_date']) ?></a></td>
            <td><?= h(substr($d['start_time'], 0, 5)) ?> - <?= h(substr($d['end_time'], 0, 5)) ?></td>
            <td><?= $capacity ?></td>
            <td><?= $approvedCount ?></td>
            <td><?= $d['status'] === 'open' ? '<span class="badge bg-success">เปิด</span>' : '<span class="badge bg-secondary">ปิด</span>' ?></td>
            <td><?= $daysLeft >= RULE_REGISTRATION_MIN_DAYS ? 'ลงทะเบียนได้' : 'เลยกำหนดลงทะเบียนแล้ว' ?></td>
            <td>
                <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <?= $d['status'] === 'open' ? 'ปิดวันสอบ' : 'เปิดวันสอบ' ?>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
