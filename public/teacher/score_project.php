<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role(['teacher', 'admin']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$project = projects_find($pdo, $projectId);
if (!$project) {
    http_response_code(404);
    die('ไม่พบโครงงาน');
}

if ($project['status'] !== 'approved') {
    http_response_code(400);
    die('โครงงานนี้ยังไม่ได้รับอนุมัติให้เข้าสอบ จึงยังให้คะแนนไม่ได้');
}

$isExaminer = $user['role'] === 'admin' || exam_day_is_examiner($pdo, (int)$project['exam_day_id'], $user['id']);
if (!$isExaminer) {
    http_response_code(403);
    die('ท่านไม่ได้เป็นคณะกรรมการสอบของวันสอบนี้');
}

$errors = [];

if (is_post()) {
    csrf_check_or_die();

    $criteria = evaluation_scores_for_project($pdo, $projectId, $project['exam_type']);
    $criterionScores = [];

    foreach ($criteria as $c) {
        $criterionId = (int)$c['criterion_id'];
        $raw = post('scores_' . $criterionId);
        if ($raw === '' || !ctype_digit($raw)) {
            $errors[] = 'กรุณากรอกคะแนนของหัวข้อ "' . $c['name'] . '" เป็นตัวเลข';
            continue;
        }
        $score = (int)$raw;
        if ($score > (int)$c['max_score']) {
            $errors[] = 'คะแนนหัวข้อ "' . $c['name'] . '" ต้องไม่เกิน ' . $c['max_score'];
            continue;
        }
        $criterionScores[$criterionId] = $score;
    }

    if (!$errors) {
        if ($criterionScores) {
            evaluation_scores_save($pdo, $projectId, $criterionScores, $user['id']);
        }
        projects_set_evaluation_notes($pdo, $projectId, post('notes'));
        flash_set('success', 'บันทึกคะแนนและบันทึกเพิ่มเติมเรียบร้อยแล้ว');
        redirect(base_url('teacher/score_project.php?project_id=' . $projectId));
    }
}

$criteria = evaluation_scores_for_project($pdo, $projectId, $project['exam_type']);
$total = evaluation_total_score($pdo, $projectId);

$pageTitle = 'ให้คะแนน: ' . $project['title'];
require __DIR__ . '/../../src/partials/header.php';
?>
<h3><?= h($project['title']) ?></h3>
<p>
    ประเภทการสอบ: <strong><?= h(exam_type_label($project['exam_type'])) ?></strong>
    | วันสอบ: <?= h($project['exam_date']) ?> เวลา <?= h(substr($project['exam_start_time'], 0, 5)) ?>-<?= h(substr($project['exam_end_time'], 0, 5)) ?>
    | ที่ปรึกษา: <?= h($project['advisor_name']) ?>
</p>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

    <table class="table table-bordered bg-white">
        <thead><tr><th>หัวข้อประเมิน</th><th style="width:140px">คะแนนเต็ม</th><th style="width:140px">คะแนนที่ให้</th></tr></thead>
        <tbody>
        <?php foreach ($criteria as $c): ?>
            <tr>
                <td><?= h($c['name']) ?></td>
                <td><?= (int)$c['max_score'] ?></td>
                <td>
                    <input type="number" name="scores_<?= (int)$c['criterion_id'] ?>" class="form-control form-control-sm"
                        min="0" max="<?= (int)$c['max_score'] ?>" value="<?= $c['score'] !== null ? (int)$c['score'] : '' ?>" required>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$criteria): ?>
            <tr><td colspan="3" class="text-center text-muted">ยังไม่มีหัวข้อประเมินสำหรับประเภทสอบนี้ กรุณาแจ้งผู้ดูแลระบบให้ตั้งค่าแบบประเมินก่อน</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>คะแนนรวมปัจจุบัน: <strong><?= $total !== null ? $total : '-' ?>/<?= EVALUATION_TOTAL_SCORE ?></strong></p>

    <div class="mb-3">
        <label class="form-label">บันทึกเพิ่มเติมของคณะกรรมการ (ถ้ามี)</label>
        <textarea name="notes" class="form-control" rows="8" placeholder="ข้อสังเกต ข้อเสนอแนะ หรือประเด็นที่ต้องแก้ไขหลังการนำเสนอ"><?= h($project['evaluation_notes']) ?></textarea>
        <div class="form-text">กรรมการทุกคนที่ได้รับมอบหมายวันนี้เห็นและแก้ไขข้อความนี้ร่วมกันได้ (พิมพ์ยาวได้ไม่จำกัด)</div>
    </div>

    <button type="submit" class="btn btn-primary">บันทึก</button>
    <a href="<?= base_url('teacher/exam_scores.php') ?>" class="btn btn-link">กลับไปหน้ารายการ</a>
</form>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
