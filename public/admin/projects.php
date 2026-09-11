<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

if (is_post()) {
    csrf_check_or_die();
    if (post('action') === 'cancel') {
        $id = (int)post('id');
        projects_cancel($pdo, $id);
        flash_set('success', 'ยกเลิกโครงงานเรียบร้อยแล้ว');
        redirect(base_url('admin/projects.php'));
    }
}

$detailId = (int)($_GET['id'] ?? 0);
if ($detailId > 0) {
    $project = projects_find($pdo, $detailId);
    if (!$project) {
        http_response_code(404);
        die('ไม่พบโครงงาน');
    }
    $members = projects_members($pdo, $detailId);
    $totalScore = evaluation_total_score($pdo, $detailId);
    $pageTitle = $project['title'];
    require __DIR__ . '/../../src/partials/header.php';
    ?>
    <h3><?= h($project['title']) ?></h3>
    <p>
        <span class="badge <?= project_status_badge_class($project['status']) ?>"><?= h(project_status_label($project['status'])) ?></span>
        <?php if ($project['status'] === 'rejected' && $project['rejected_reason']): ?>
            <span class="text-danger">เหตุผล: <?= h($project['rejected_reason']) ?></span>
        <?php endif; ?>
    </p>
    <ul class="list-group mb-3">
        <li class="list-group-item">ประเภทการสอบ: <?= h(exam_type_label($project['exam_type'])) ?></li>
        <li class="list-group-item">ครูที่ปรึกษา: <?= h($project['advisor_name']) ?></li>
        <li class="list-group-item">วันสอบ: <?= h($project['exam_date']) ?> เวลา <?= h(substr($project['exam_start_time'],0,5)) ?>-<?= h(substr($project['exam_end_time'],0,5)) ?></li>
        <li class="list-group-item">ลงทะเบียนโดย: <?= h($project['registered_by_name']) ?> เมื่อ <?= h($project['created_at']) ?></li>
        <li class="list-group-item">สมาชิก:
            <?= h(implode(', ', array_map(fn($m) => $m['full_name'], $members))) ?>
        </li>
        <li class="list-group-item">
            ไฟล์แนบ:
            <a href="<?= h(base_url('download.php?project_id=' . $project['id'] . '&type=report')) ?>">รายงานผลดำเนินงาน (.docx)</a>
            |
            <a href="<?= h(base_url('download.php?project_id=' . $project['id'] . '&type=presentation')) ?>">ไฟล์นำเสนอ (.pptx)</a>
        </li>
        <?php if ($project['status'] === 'approved'): ?>
            <li class="list-group-item">
                คะแนนประเมิน:
                <?php if ($totalScore !== null): ?>
                    <strong><?= $totalScore ?>/<?= EVALUATION_TOTAL_SCORE ?></strong>
                <?php else: ?>
                    <span class="text-muted">ยังไม่ให้คะแนน</span>
                <?php endif; ?>
                <a href="<?= base_url('teacher/score_project.php?project_id=' . $project['id']) ?>" class="ms-2">ดู/แก้ไขคะแนน</a>
            </li>
            <?php if (!empty($project['evaluation_notes'])): ?>
                <li class="list-group-item">
                    บันทึกเพิ่มเติมของคณะกรรมการ:
                    <div class="mt-1" style="white-space: pre-wrap;"><?= h($project['evaluation_notes']) ?></div>
                </li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>
    <?php if ($project['status'] !== 'cancelled'): ?>
        <form method="post" onsubmit="return confirm('ยืนยันยกเลิกโครงงานนี้?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
            <button type="submit" class="btn btn-outline-danger">ยกเลิกโครงงาน</button>
        </form>
    <?php endif; ?>
    <a href="<?= base_url('admin/projects.php') ?>" class="btn btn-link">กลับไปรายการทั้งหมด</a>
    <?php
    require __DIR__ . '/../../src/partials/footer.php';
    exit;
}

$statusFilter = (string)($_GET['status'] ?? '');
$filters = $statusFilter !== '' ? ['status' => $statusFilter] : [];
$allProjects = projects_all($pdo, $filters);

$pageTitle = 'รายการโครงงานทั้งหมด';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">รายการโครงงานทั้งหมด</h3>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">ทุกสถานะ</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>รอครูอนุมัติ</option>
            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>ไม่อนุมัติ</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
        </select>
    </div>
</form>

<table class="table table-bordered bg-white">
    <thead><tr><th>โครงงาน</th><th>ประเภทสอบ</th><th>ที่ปรึกษา</th><th>วันสอบ</th><th>สถานะ</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($allProjects as $p): ?>
        <tr>
            <td><?= h($p['title']) ?></td>
            <td><?= h(exam_type_label($p['exam_type'])) ?></td>
            <td><?= h($p['advisor_name']) ?></td>
            <td><?= h($p['exam_date']) ?></td>
            <td><span class="badge <?= project_status_badge_class($p['status']) ?>"><?= h(project_status_label($p['status'])) ?></span></td>
            <td><a href="<?= base_url('admin/projects.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">รายละเอียด</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$allProjects): ?>
        <tr><td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
