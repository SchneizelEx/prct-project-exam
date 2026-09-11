<?php
declare(strict_types=1);

function exam_days_all(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM exam_days ORDER BY exam_date, start_time');
    return $stmt->fetchAll();
}

function exam_days_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM exam_days WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * วันสอบที่ยังลงทะเบียนได้ (status=open และเหลือเวลา >= 7 วัน) เรียงตามวันที่
 */
function exam_days_open_for_registration(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT * FROM exam_days WHERE status = 'open' AND exam_date >= CURDATE() ORDER BY exam_date, start_time"
    );
    $rows = $stmt->fetchAll();
    return array_values(array_filter($rows, fn($d) => rules_can_register($d['exam_date'])));
}

function exam_days_create(PDO $pdo, string $examDate, string $startTime, string $endTime, int $createdBy): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO exam_days (exam_date, start_time, end_time, created_by) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$examDate, $startTime, $endTime, $createdBy]);
    return (int)$pdo->lastInsertId();
}

function exam_days_set_status(PDO $pdo, int $id, string $status): void
{
    $stmt = $pdo->prepare('UPDATE exam_days SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
}

/**
 * รายชื่อครูที่เป็นคณะกรรมการสอบของวันนี้ (ครบทุก field จาก users)
 */
function exam_day_examiners_get(PDO $pdo, int $examDayId): array
{
    $stmt = $pdo->prepare(
        'SELECT u.* FROM exam_day_examiners ex JOIN users u ON u.id = ex.teacher_id
         WHERE ex.exam_day_id = ? ORDER BY u.full_name'
    );
    $stmt->execute([$examDayId]);
    return $stmt->fetchAll();
}

/**
 * แทนที่รายชื่อคณะกรรมการสอบของวันนี้ทั้งหมดด้วย $teacherIds ที่ให้มา
 * @param int[] $teacherIds
 */
function exam_day_examiners_set(PDO $pdo, int $examDayId, array $teacherIds): void
{
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM exam_day_examiners WHERE exam_day_id = ?');
        $del->execute([$examDayId]);

        $ins = $pdo->prepare('INSERT INTO exam_day_examiners (exam_day_id, teacher_id) VALUES (?, ?)');
        foreach (array_unique($teacherIds) as $teacherId) {
            $ins->execute([$examDayId, $teacherId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function exam_day_is_examiner(PDO $pdo, int $examDayId, int $teacherId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM exam_day_examiners WHERE exam_day_id = ? AND teacher_id = ?');
    $stmt->execute([$examDayId, $teacherId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * วันสอบทั้งหมดที่ครูคนนี้เป็นคณะกรรมการ เรียงตามวันที่
 */
function exam_days_for_examiner(PDO $pdo, int $teacherId): array
{
    $stmt = $pdo->prepare(
        'SELECT e.* FROM exam_day_examiners ex JOIN exam_days e ON e.id = ex.exam_day_id
         WHERE ex.teacher_id = ? ORDER BY e.exam_date, e.start_time'
    );
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll();
}
