<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('teacher');

$projects = projects_for_teacher($pdo, $user['id']);

$pageTitle = 'แดชบอร์ดครูที่ปรึกษา';
require __DIR__ . '/../../src/partials/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">โครงงานที่ฉันเป็นที่ปรึกษา</h3>
    <a href="<?= base_url('teacher/exam_scores.php') ?>" class="btn btn-outline-primary">ให้คะแนนสอบ (คณะกรรมการ)</a>
</div>

<table class="table table-bordered bg-white">
    <thead><tr><th>โครงงาน</th><th>ประเภทสอบ</th><th>วันสอบ</th><th>สถานะ</th><th>คิว/เวลาสอบ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($projects as $p): ?>
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
            <td><span class="badge <?= project_status_badge_class($p['status']) ?>"><?= h(project_status_label($p['status'])) ?></span></td>
            <td><?= h($queueInfo) ?></td>
            <td><a href="<?= base_url('teacher/review.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">รายละเอียด</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$projects): ?>
        <tr><td colspan="6" class="text-center text-muted">ยังไม่มีโครงงานที่มอบหมายให้ท่านเป็นที่ปรึกษา</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
