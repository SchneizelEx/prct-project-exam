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
