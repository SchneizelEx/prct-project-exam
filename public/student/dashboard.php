<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('student');

$myProjects = projects_for_student($pdo, $user['id']);
$openDays = exam_days_open_for_registration($pdo);

$pageTitle = 'แดชบอร์ดนักเรียน';
require __DIR__ . '/../../src/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">โครงงานของฉัน</h3>
    <a href="<?= base_url('student/register.php') ?>" class="btn btn-primary">ลงทะเบียนสอบโครงงานใหม่</a>
</div>

<table class="table table-bordered bg-white mb-5">
    <thead><tr><th>โครงงาน</th><th>ประเภทสอบ</th><th>วันสอบ</th><th>ที่ปรึกษา</th><th>สถานะ</th><th>คิว/เวลาสอบ</th></tr></thead>
    <tbody>
    <?php foreach ($myProjects as $p): ?>
        <?php
        $queueInfo = '-';
        if ($p['status'] === 'approved') {
            $approved = projects_approved_for_exam_day($pdo, $p['exam_day_id']);
            $queue = rules_compute_queue($approved, $p['exam_start_time']);
            foreach ($queue as $q) {
                if ((int)$q['id'] === (int)$p['id']) {
                    $queueInfo = 'ลำดับที่ ' . $q['queue_no'] . ' เวลา ' . $q['slot_start'] . '-' . $q['slot_end'];
                }
            }
        }
        ?>
        <tr>
            <td><?= h($p['title']) ?></td>
            <td><?= h(exam_type_label($p['exam_type'])) ?></td>
            <td><?= h($p['exam_date']) ?></td>
            <td><?= h($p['advisor_name']) ?></td>
            <td>
                <span class="badge <?= project_status_badge_class($p['status']) ?>"><?= h(project_status_label($p['status'])) ?></span>
                <?php if ($p['status'] === 'rejected' && $p['rejected_reason']): ?>
                    <div class="small text-danger"><?= h($p['rejected_reason']) ?></div>
                <?php endif; ?>
            </td>
            <td><?= h($queueInfo) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$myProjects): ?>
        <tr><td colspan="6" class="text-center text-muted">ยังไม่มีการลงทะเบียน</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<h5>วันสอบที่เปิดให้ลงทะเบียนได้ตอนนี้</h5>
<table class="table table-bordered bg-white">
    <thead><tr><th>วันสอบ</th><th>เวลา</th></tr></thead>
    <tbody>
    <?php foreach ($openDays as $d): ?>
        <tr><td><?= h($d['exam_date']) ?></td><td><?= h(substr($d['start_time'],0,5)) ?>-<?= h(substr($d['end_time'],0,5)) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$openDays): ?>
        <tr><td colspan="2" class="text-center text-muted">ขณะนี้ยังไม่มีวันสอบที่เปิดให้ลงทะเบียน</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
