<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$day = exam_days_find($pdo, $id);
if (!$day) {
    http_response_code(404);
    die('ไม่พบวันสอบ');
}

if (is_post()) {
    csrf_check_or_die();
    if (post('action') === 'set_examiners') {
        $teacherIds = array_map('intval', $_POST['examiner_ids'] ?? []);
        exam_day_examiners_set($pdo, $id, $teacherIds);
        flash_set('success', 'บันทึกคณะกรรมการสอบเรียบร้อยแล้ว');
    }
    redirect(base_url('admin/exam_day.php?id=' . $id));
}

$capacity = rules_exam_day_capacity($day['start_time'], $day['end_time']);
$allProjects = projects_all($pdo, ['exam_day_id' => $id]);
$approved = projects_approved_for_exam_day($pdo, $id);
$queue = rules_compute_queue($approved, $day['start_time']);
$allTeachers = users_all($pdo, 'teacher');
$examinerIds = array_map(fn($t) => (int)$t['id'], exam_day_examiners_get($pdo, $id));

$pageTitle = 'รายละเอียดวันสอบ ' . $day['exam_date'];
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-1">วันสอบ <?= h($day['exam_date']) ?></h3>
<p class="text-muted">เวลา <?= h(substr($day['start_time'], 0, 5)) ?> - <?= h(substr($day['end_time'], 0, 5)) ?>
    | ความจุ <?= $capacity ?> โครงงาน | อนุมัติแล้ว <?= count($approved) ?>/<?= $capacity ?></p>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">คณะกรรมการสอบวันนี้</h5>
        <p class="text-muted small">ครูที่ติ๊กไว้จะเห็นและให้คะแนนได้ทุกโครงงานที่ได้คิวสอบในวันนี้ ไม่ว่าจะเป็นที่ปรึกษาโครงงานนั้นหรือไม่</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="set_examiners">
            <div class="border rounded p-2 mb-2" style="max-height:200px; overflow-y:auto;">
                <?php foreach ($allTeachers as $t): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="examiner_ids[]" value="<?= (int)$t['id'] ?>"
                            id="examiner-<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $examinerIds, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="examiner-<?= (int)$t['id'] ?>">
                            <?= h($t['full_name']) ?><?= $t['code'] ? ' (' . h($t['code']) . ')' : '' ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if (!$allTeachers): ?>
                    <p class="text-muted mb-0">ยังไม่มีบัญชีครูในระบบ</p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">บันทึกคณะกรรมการสอบ</button>
        </form>
    </div>
</div>

<h5 class="mt-4">คิวสอบ (เรียงตามเวลาที่ครูอนุมัติ)</h5>
<table class="table table-bordered bg-white">
    <thead><tr><th>ลำดับ</th><th>เวลา</th><th>ประเภทสอบ</th><th>โครงงาน</th><th>ที่ปรึกษา</th></tr></thead>
    <tbody>
    <?php if (!$queue): ?>
        <tr><td colspan="5" class="text-center text-muted">ยังไม่มีโครงงานที่ได้รับอนุมัติ</td></tr>
    <?php endif; ?>
    <?php foreach ($queue as $q): ?>
        <tr>
            <td><?= $q['queue_no'] ?></td>
            <td><?= h($q['slot_start']) ?> - <?= h($q['slot_end']) ?></td>
            <td><?= h(exam_type_label($q['exam_type'])) ?></td>
            <td><a href="<?= base_url('admin/projects.php?id=' . $q['id']) ?>"><?= h($q['title']) ?></a></td>
            <td><?= h($q['advisor_name']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h5 class="mt-4">โครงงานทั้งหมดในวันสอบนี้</h5>
<table class="table table-bordered bg-white">
    <thead><tr><th>โครงงาน</th><th>ประเภทสอบ</th><th>ที่ปรึกษา</th><th>ลงทะเบียนโดย</th><th>สถานะ</th></tr></thead>
    <tbody>
    <?php foreach ($allProjects as $p): ?>
        <tr>
            <td><?= h($p['title']) ?></td>
            <td><?= h(exam_type_label($p['exam_type'])) ?></td>
            <td><?= h($p['advisor_name']) ?></td>
            <td><?= h($p['registered_by_name']) ?></td>
            <td><span class="badge <?= project_status_badge_class($p['status']) ?>"><?= h(project_status_label($p['status'])) ?></span></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$allProjects): ?>
        <tr><td colspan="5" class="text-center text-muted">ยังไม่มีการลงทะเบียน</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
