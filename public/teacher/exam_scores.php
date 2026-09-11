<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('teacher');

$examDays = exam_days_for_examiner($pdo, $user['id']);

$pageTitle = 'ให้คะแนนสอบ (คณะกรรมการ)';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">โครงงานที่ต้องให้คะแนน (วันที่ท่านเป็นคณะกรรมการสอบ)</h3>

<?php if (!$examDays): ?>
    <p class="text-muted">ท่านยังไม่ได้รับมอบหมายให้เป็นคณะกรรมการสอบในวันใด</p>
<?php endif; ?>

<?php foreach ($examDays as $day): ?>
    <?php $projects = projects_approved_for_exam_day($pdo, (int)$day['id']); ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">วันสอบ <?= h($day['exam_date']) ?>
                <span class="text-muted small">(<?= h(substr($day['start_time'], 0, 5)) ?> - <?= h(substr($day['end_time'], 0, 5)) ?>)</span>
            </h5>
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>ลำดับ</th><th>โครงงาน</th><th>ประเภทสอบ</th><th>ที่ปรึกษา</th><th>คะแนน</th><th></th></tr></thead>
                <tbody>
                <?php $queue = rules_compute_queue($projects, $day['start_time']); ?>
                <?php foreach ($queue as $p): ?>
                    <?php $total = evaluation_total_score($pdo, (int)$p['id']); ?>
                    <tr>
                        <td><?= $p['queue_no'] ?></td>
                        <td><?= h($p['title']) ?></td>
                        <td><?= h(exam_type_label($p['exam_type'])) ?></td>
                        <td><?= h($p['advisor_name']) ?></td>
                        <td>
                            <?php if ($total === null): ?>
                                <span class="badge bg-secondary">ยังไม่ให้คะแนน</span>
                            <?php else: ?>
                                <span class="badge bg-success"><?= $total ?>/<?= EVALUATION_TOTAL_SCORE ?></span>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?= base_url('teacher/score_project.php?project_id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">ให้คะแนน</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$queue): ?>
                    <tr><td colspan="6" class="text-center text-muted">ยังไม่มีโครงงานที่ได้รับอนุมัติในวันนี้</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
