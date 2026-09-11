<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('admin');

if (is_post()) {
    csrf_check_or_die();
    $action = post('action');

    if ($action === 'create') {
        $examType = post('exam_type');
        $name = post('name');
        $maxScore = (int)post('max_score');

        if (!in_array($examType, EXAM_TYPES, true) || $name === '') {
            flash_set('danger', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        } elseif ($maxScore < 1 || $maxScore > EVALUATION_TOTAL_SCORE) {
            flash_set('danger', 'คะแนนเต็มของหัวข้อต้องอยู่ระหว่าง 1 ถึง ' . EVALUATION_TOTAL_SCORE);
        } else {
            evaluation_criteria_create($pdo, $examType, $name, $maxScore);
            flash_set('success', 'เพิ่มหัวข้อประเมินเรียบร้อยแล้ว');
        }
    } elseif ($action === 'delete') {
        evaluation_criteria_delete($pdo, (int)post('id'));
        flash_set('success', 'ลบหัวข้อประเมินเรียบร้อยแล้ว');
    }
    redirect(base_url('admin/evaluation_criteria.php'));
}

$allCriteria = evaluation_criteria_all($pdo);
$byType = [];
foreach (EXAM_TYPES as $type) {
    $byType[$type] = array_values(array_filter($allCriteria, fn($c) => $c['exam_type'] === $type));
}

$pageTitle = 'จัดการแบบประเมินโครงการ';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">จัดการแบบประเมินโครงการ</h3>
<p class="text-muted">กำหนดหัวข้อและคะแนนของแบบประเมินแยกตามประเภทการสอบ รวมคะแนนแต่ละประเภทควรเท่ากับ <?= EVALUATION_TOTAL_SCORE ?> คะแนน</p>

<?php foreach (EXAM_TYPES as $type): ?>
    <?php
    $criteria = $byType[$type];
    $total = array_sum(array_column($criteria, 'max_score'));
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><?= h(exam_type_label($type)) ?></h5>
                <span class="badge <?= $total === EVALUATION_TOTAL_SCORE ? 'bg-success' : 'bg-warning text-dark' ?>">
                    รวม <?= $total ?>/<?= EVALUATION_TOTAL_SCORE ?> คะแนน
                </span>
            </div>

            <table class="table table-sm table-bordered mb-3">
                <thead><tr><th>หัวข้อประเมิน</th><th style="width:120px">คะแนนเต็ม</th><th style="width:80px"></th></tr></thead>
                <tbody>
                <?php foreach ($criteria as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td><?= (int)$c['max_score'] ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('ลบหัวข้อนี้?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$criteria): ?>
                    <tr><td colspan="3" class="text-center text-muted">ยังไม่มีหัวข้อประเมิน</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <form method="post" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="exam_type" value="<?= h($type) ?>">
                <div class="col-md-6">
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="ชื่อหัวข้อประเมิน" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="max_score" class="form-control form-control-sm" placeholder="คะแนนเต็ม" min="1" max="<?= EVALUATION_TOTAL_SCORE ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">เพิ่มหัวข้อ</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
