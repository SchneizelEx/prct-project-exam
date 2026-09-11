<?php
declare(strict_types=1);

const PROJECT_DETAIL_SELECT = "
    SELECT p.*,
           t.full_name AS advisor_name,
           t.id AS advisor_id,
           e.exam_date, e.start_time AS exam_start_time, e.end_time AS exam_end_time, e.status AS exam_status,
           r.full_name AS registered_by_name
    FROM projects p
    JOIN users t ON t.id = p.advisor_teacher_id
    JOIN exam_days e ON e.id = p.exam_day_id
    JOIN users r ON r.id = p.registered_by
";

function projects_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(PROJECT_DETAIL_SELECT . ' WHERE p.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function projects_members(PDO $pdo, int $projectId): array
{
    $stmt = $pdo->prepare(
        'SELECT u.* FROM project_members pm JOIN users u ON u.id = pm.student_id
         WHERE pm.project_id = ? ORDER BY u.full_name'
    );
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function projects_for_teacher(PDO $pdo, int $teacherId): array
{
    $stmt = $pdo->prepare(PROJECT_DETAIL_SELECT . ' WHERE p.advisor_teacher_id = ? ORDER BY p.created_at DESC');
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}

function projects_for_student(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare(
        PROJECT_DETAIL_SELECT .
        ' JOIN project_members pm ON pm.project_id = p.id
          WHERE pm.student_id = ? ORDER BY p.created_at DESC'
    );
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

function projects_all(PDO $pdo, array $filters = []): array
{
    $sql = PROJECT_DETAIL_SELECT;
    $where = [];
    $params = [];

    if (!empty($filters['exam_day_id'])) {
        $where[] = 'p.exam_day_id = ?';
        $params[] = $filters['exam_day_id'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'p.status = ?';
        $params[] = $filters['status'];
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function projects_approved_for_exam_day(PDO $pdo, int $examDayId): array
{
    $stmt = $pdo->prepare(
        PROJECT_DETAIL_SELECT . " WHERE p.exam_day_id = ? AND p.status = 'approved' ORDER BY p.approved_at"
    );
    $stmt->execute([$examDayId]);
    return $stmt->fetchAll();
}

function projects_count_approved_for_exam_day(PDO $pdo, int $examDayId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE exam_day_id = ? AND status = 'approved'");
    $stmt->execute([$examDayId]);
    return (int)$stmt->fetchColumn();
}

/**
 * รายชื่อโครงงานที่ได้รับอนุมัติให้เข้าสอบทั้งหมด พร้อมคิว/เวลาสอบ เรียงตามวันเวลาสอบ (ใช้แสดงหน้าแรก/หน้า login)
 */
function projects_public_approved_schedule(PDO $pdo): array
{
    $stmt = $pdo->query(
        PROJECT_DETAIL_SELECT . " WHERE p.status = 'approved' ORDER BY e.exam_date, e.start_time, p.approved_at"
    );
    $approved = $stmt->fetchAll();

    $byDay = [];
    foreach ($approved as $p) {
        $byDay[$p['exam_day_id']]['start'] = $p['exam_start_time'];
        $byDay[$p['exam_day_id']]['projects'][] = $p;
    }

    $schedule = [];
    foreach ($byDay as $day) {
        foreach (rules_compute_queue($day['projects'], $day['start']) as $q) {
            $q['member_names'] = array_map(fn($m) => $m['full_name'], projects_members($pdo, $q['id']));
            $schedule[] = $q;
        }
    }

    usort($schedule, fn($a, $b) => [$a['exam_date'], $a['slot_start']] <=> [$b['exam_date'], $b['slot_start']]);

    return $schedule;
}

/**
 * true ถ้ามีนักเรียนคนใดคนหนึ่งใน $studentIds ที่มีโครงงาน pending/approved อยู่แล้วในวันสอบนี้
 */
function projects_student_has_active_registration(PDO $pdo, array $studentIds, int $examDayId): bool
{
    if (!$studentIds) {
        return false;
    }
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM project_members pm
         JOIN projects p ON p.id = pm.project_id
         WHERE p.exam_day_id = ? AND p.status IN ('pending','approved') AND pm.student_id IN ($placeholders)"
    );
    $stmt->execute([$examDayId, ...$studentIds]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * @param int[] $studentIds รวมตัวแทนแล้ว (unique)
 */
function projects_create(
    PDO $pdo,
    string $title,
    string $examType,
    int $advisorTeacherId,
    int $examDayId,
    int $registeredBy,
    string $reportPath,
    string $presentationPath,
    array $studentIds
): int {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO projects
                (title, exam_type, advisor_teacher_id, exam_day_id, registered_by, report_docx_path, presentation_pptx_path, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\')'
        );
        $stmt->execute([$title, $examType, $advisorTeacherId, $examDayId, $registeredBy, $reportPath, $presentationPath]);
        $projectId = (int)$pdo->lastInsertId();

        $memberStmt = $pdo->prepare('INSERT INTO project_members (project_id, student_id) VALUES (?, ?)');
        foreach ($studentIds as $studentId) {
            $memberStmt->execute([$projectId, $studentId]);
        }

        $pdo->commit();
        return $projectId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function projects_approve(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("UPDATE projects SET status = 'approved', approved_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

function projects_reject(PDO $pdo, int $id, string $reason): void
{
    $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected', rejected_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $id]);
}

function projects_cancel(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("UPDATE projects SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * บันทึกเพิ่มเติมของคณะกรรมการสอบ (ข้อความยาวได้) แก้ไขทับกันได้เหมือนคะแนน
 */
function projects_set_evaluation_notes(PDO $pdo, int $id, string $notes): void
{
    $stmt = $pdo->prepare('UPDATE projects SET evaluation_notes = ? WHERE id = ?');
    $stmt->execute([$notes !== '' ? $notes : null, $id]);
}

function project_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'รอครูอนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ไม่อนุมัติ',
        'cancelled' => 'ยกเลิก',
        default => $status,
    };
}

function project_status_badge_class(string $status): string
{
    return match ($status) {
        'pending' => 'badge-status-pending',
        'approved' => 'badge-status-approved',
        'rejected' => 'badge-status-rejected',
        'cancelled' => 'badge-status-cancelled',
        default => 'bg-secondary',
    };
}
