<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_role('student');

$currentStudentRecord = users_find($pdo, $user['id']);
$currentStudentLevel = student_level_from_code($currentStudentRecord['code'] ?? null);

$openDays = exam_days_open_for_registration($pdo);
$teachers = users_all($pdo, 'teacher');
$otherStudents = array_values(array_filter(
    users_all($pdo, 'student'),
    fn($s) => (int)$s['id'] !== $user['id']
        && ($currentStudentLevel === null || student_level_from_code($s['code']) === $currentStudentLevel)
));

$errors = [];
$old = ['title' => '', 'exam_day_id' => '', 'advisor_teacher_id' => '', 'member_ids' => []];

if (is_post()) {
    csrf_check_or_die();

    $old['title'] = post('title');
    $old['exam_day_id'] = post('exam_day_id');
    $old['advisor_teacher_id'] = post('advisor_teacher_id');
    $old['member_ids'] = array_map('intval', $_POST['member_ids'] ?? []);

    $examDayId = (int)$old['exam_day_id'];
    $advisorId = (int)$old['advisor_teacher_id'];

    // ตรวจ exam day
    $examDay = exam_days_find($pdo, $examDayId);
    if (!$examDay || $examDay['status'] !== 'open' || !rules_can_register($examDay['exam_date'])) {
        $errors[] = 'วันสอบที่เลือกไม่สามารถลงทะเบียนได้ (ต้องลงทะเบียนล่วงหน้าอย่างน้อย ' . RULE_REGISTRATION_MIN_DAYS . ' วัน)';
    }

    // ตรวจครูที่ปรึกษา
    $advisor = users_find($pdo, $advisorId);
    if (!$advisor || $advisor['role'] !== 'teacher' || !$advisor['is_active']) {
        $errors[] = 'กรุณาเลือกครูที่ปรึกษาให้ถูกต้อง';
    }

    // ชื่อโครงงาน
    if ($old['title'] === '') {
        $errors[] = 'กรุณาระบุชื่อโครงงาน';
    }

    // สมาชิก: รวมตัวเอง + ที่เลือก, ต้องเป็นนักเรียนจริงในระบบ
    $validStudentIds = array_map(fn($s) => (int)$s['id'], $otherStudents);
    $selectedMembers = array_values(array_intersect($old['member_ids'], $validStudentIds));
    $memberIds = array_values(array_unique([$user['id'], ...$selectedMembers]));

    $memberError = rules_validate_member_count(count($memberIds));
    if ($memberError) {
        $errors[] = $memberError;
    }

    if (!$errors && $examDay && projects_student_has_active_registration($pdo, $memberIds, $examDayId)) {
        $errors[] = 'มีสมาชิกบางคนลงทะเบียนโครงงานอื่นในวันสอบนี้ไปแล้ว';
    }

    // ไฟล์แนบ
    $reportResult = null;
    $presentationResult = null;
    if (!$errors) {
        $maxBytes = (int)$config['upload']['max_size_bytes'];
        $storageDir = (string)$config['upload']['storage_dir'];

        $reportResult = handle_upload($_FILES['report_docx'] ?? [], 'docx', $storageDir, $maxBytes);
        if (!$reportResult['ok']) {
            $errors[] = 'ไฟล์รายงานผลดำเนินงาน: ' . $reportResult['error'];
        }

        $presentationResult = handle_upload($_FILES['presentation_pptx'] ?? [], 'pptx', $storageDir, $maxBytes);
        if (!$presentationResult['ok']) {
            $errors[] = 'ไฟล์นำเสนอ: ' . $presentationResult['error'];
        }

        if ($errors) {
            // ลบไฟล์ที่อัปโหลดสำเร็จไปแล้วถ้าอีกไฟล์ fail
            foreach ([$reportResult, $presentationResult] as $r) {
                if ($r && $r['ok']) {
                    @unlink(rtrim($storageDir, '/\\') . '/' . $r['path']);
                }
            }
        }
    }

    if (!$errors) {
        try {
            $storageDir = (string)$config['upload']['storage_dir'];
            $projectId = projects_create(
                $pdo,
                $old['title'],
                $advisorId,
                $examDayId,
                $user['id'],
                $reportResult['path'],
                $presentationResult['path'],
                $memberIds
            );
            flash_set('success', 'ลงทะเบียนโครงงานเรียบร้อยแล้ว รอครูที่ปรึกษาอนุมัติ');
            redirect(base_url('student/dashboard.php'));
        } catch (Throwable $e) {
            foreach ([$reportResult, $presentationResult] as $r) {
                if ($r && $r['ok']) {
                    @unlink(rtrim($storageDir, '/\\') . '/' . $r['path']);
                }
            }
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง';
        }
    }
}

$pageTitle = 'ลงทะเบียนสอบโครงงาน';
require __DIR__ . '/../../src/partials/header.php';
?>
<h3 class="mb-4">ลงทะเบียนสอบนำเสนอโครงงาน</h3>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$openDays): ?>
    <div class="alert alert-warning">ขณะนี้ยังไม่มีวันสอบที่เปิดให้ลงทะเบียน (ต้องลงทะเบียนล่วงหน้าอย่างน้อย <?= RULE_REGISTRATION_MIN_DAYS ?> วันก่อนวันสอบ)</div>
<?php else: ?>
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">ชื่อโครงงาน</label>
        <input type="text" name="title" class="form-control" value="<?= h($old['title']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">วันสอบ</label>
        <select name="exam_day_id" class="form-select" required>
            <option value="">-- เลือกวันสอบ --</option>
            <?php foreach ($openDays as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= (string)$d['id'] === $old['exam_day_id'] ? 'selected' : '' ?>>
                    <?= h($d['exam_date']) ?> (<?= h(substr($d['start_time'],0,5)) ?>-<?= h(substr($d['end_time'],0,5)) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">ครูที่ปรึกษาโครงงาน</label>
        <select name="advisor_teacher_id" class="form-select" required>
            <option value="">-- เลือกครูที่ปรึกษา --</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (string)$t['id'] === $old['advisor_teacher_id'] ? 'selected' : '' ?>>
                    <?= h($t['full_name']) ?><?= $t['code'] ? ' (' . h($t['code']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">
            สมาชิกร่วมโครงงาน (นอกเหนือจากตัวเอง สูงสุด <?= RULE_MAX_STUDENTS_PER_PROJECT - 1 ?> คน)
            <?php if ($currentStudentLevel !== null): ?>
                <span class="text-muted small">(แสดงเฉพาะนักเรียนระดับ <?= h($currentStudentLevel) ?>)</span>
            <?php endif; ?>
        </label>
        <div class="border rounded p-2" style="max-height:220px; overflow-y:auto;">
            <?php foreach ($otherStudents as $s): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="member_ids[]" value="<?= (int)$s['id'] ?>"
                        id="member-<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $old['member_ids'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="member-<?= (int)$s['id'] ?>">
                        <?= h($s['full_name']) ?><?= $s['code'] ? ' (' . h($s['code']) . ')' : '' ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">ไฟล์รายงานผลดำเนินงาน (.docx)</label>
        <input type="file" name="report_docx" class="form-control" accept=".docx" required>
    </div>

    <div class="mb-3">
        <label class="form-label">ไฟล์นำเสนอ (.pptx)</label>
        <input type="file" name="presentation_pptx" class="form-control" accept=".pptx" required>
    </div>

    <button type="submit" class="btn btn-primary">ลงทะเบียน</button>
    <a href="<?= base_url('student/dashboard.php') ?>" class="btn btn-link">ยกเลิก</a>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../../src/partials/footer.php'; ?>
