<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('teacher');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$project = projects_find($pdo, $id);

if (!$project || (int)$project['advisor_teacher_id'] !== $user['id']) {
    http_response_code(404);
    die('ไม่พบโครงงาน หรือท่านไม่ใช่ที่ปรึกษาโครงงานนี้');
}

if (is_post()) {
    csrf_check_or_die();
    $action = post('action');

    if ($project['status'] !== 'pending') {
        flash_set('danger', 'โครงงานนี้ถูกดำเนินการไปแล้ว');
    } elseif (!rules_can_approve($project['exam_date'])) {
        flash_set('danger', 'เลยกำหนดอนุมัติแล้ว (ต้องอนุมัติก่อนวันสอบอย่างน้อย ' . RULE_APPROVAL_MIN_DAYS . ' วัน)');
    } elseif ($action === 'approve') {
        $capacity = rules_exam_day_capacity($project['exam_start_time'], $project['exam_end_time']);
        $approvedCount = projects_count_approved_for_exam_day($pdo, $project['exam_day_id']);
        if ($approvedCount >= $capacity) {
            flash_set('danger', 'คิวสอบวันนี้เต็มแล้ว กรุณาประสานงานผู้ดูแลระบบเพื่อเปิดวันสอบเพิ่ม');
        } else {
            projects_approve($pdo, $id);
            flash_set('success', 'อนุมัติโครงงานเรียบร้อยแล้ว');
        }
    } elseif ($action === 'reject') {
        $reason = post('reason');
        if ($reason === '') {
            flash_set('danger', 'กรุณาระบุเหตุผลที่ไม่อนุมัติ');
        } else {
            projects_reject($pdo, $id, $reason);
            flash_set('success', 'บันทึกผลไม่อนุมัติเรียบร้อยแล้ว');
        }
    }
    redirect(base_url('teacher/review.php?id=' . $id));
}

$members = projects_members($pdo, $id);
$canApprove = $project['status'] === 'pending' && rules_can_approve($project['exam_date']);
$isExaminer = $project['status'] === 'approved' && exam_day_is_examiner($pdo, (int)$project['exam_day_id'], $user['id']);

$pageTitle = $project['title'];
require __DIR__ . '/../../src/partials/header.php';
?>
<h3><?= h($project['title']) ?></h3>
<p><span class="badge <?= project_status_badge_class($project['status']) ?>"><?= h(project_status_label($project['status'])) ?></span></p>

<ul class="list-group mb-3">
    <li class="list-group-item">ประเภทการสอบ: <?= h(exam_type_label($project['exam_type'])) ?></li>
    <li class="list-group-item">วันสอบ: <?= h($project['exam_date']) ?> เวลา <?= h(substr($project['exam_start_time'],0,5)) ?>-<?= h(substr($project['exam_end_time'],0,5)) ?>
        (เหลือ <?= rules_days_until($project['exam_date']) ?> วัน)</li>
    <li class="list-group-item">ลงทะเบียนโดย: <?= h($project['registered_by_name']) ?> เมื่อ <?= h($project['created_at']) ?></li>
    <li class="list-group-item">สมาชิก: <?= h(implode(', ', array_map(fn($m) => $m['full_name'], $members))) ?></li>
    <li class="list-group-item">
        ไฟล์แนบ:
        <a href="<?= h(base_url('download.php?project_id=' . $project['id'] . '&type=report')) ?>">รายงานผลดำเนินงาน (.docx)</a>
        |
        <a href="<?= h(base_url('download.php?project_id=' . $project['id'] . '&type=presentation')) ?>">ไฟล์นำเสนอ (.pptx)</a>
    </li>
    <?php if ($project['status'] === 'rejected' && $project['rejected_reason']): ?>
        <li class="list-group-item text-danger">เหตุผลที่ไม่อนุมัติ: <?= h($project['rejected_reason']) ?></li>
    <?php endif; ?>
</ul>

<?php if ($canApprove): ?>
    <div class="d-flex gap-3">
        <form method="post" onsubmit="return confirm('ยืนยันอนุมัติโครงงานนี้?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="btn btn-success">อนุมัติ</button>
        </form>
        <form method="post" class="d-flex gap-2" onsubmit="return this.reason.value.trim() !== '';">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <input type="hidden" name="action" value="reject">
            <input type="text" name="reason" class="form-control" placeholder="เหตุผลที่ไม่อนุมัติ" required>
            <button type="submit" class="btn btn-outline-danger text-nowrap">ไม่อนุมัติ</button>
        </form>
    </div>
<?php elseif ($project['status'] === 'pending'): ?>
    <div class="alert alert-warning">เลยกำหนดอนุมัติแล้ว (ต้องอนุมัติก่อนวันสอบอย่างน้อย <?= RULE_APPROVAL_MIN_DAYS ?> วัน) กรุณาติดต่อผู้ดูแลระบบ</div>
<?php endif; ?>

<?php if ($isExaminer): ?>
    <a href="<?= base_url('teacher/score_project.php?project_id=' . $project['id']) ?>" class="btn btn-outline-primary mt-2">ให้คะแนนสอบ (คณะกรรมการ)</a>
<?php endif; ?>

<a href="<?= base_url('teacher/dashboard.php') ?>" class="btn btn-link mt-3">กลับไปหน้ารายการ</a>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
