<?php
declare(strict_types=1);

function evaluation_criteria_for_type(PDO $pdo, string $examType): array
{
    $stmt = $pdo->prepare('SELECT * FROM evaluation_criteria WHERE exam_type = ? ORDER BY sort_order, id');
    $stmt->execute([$examType]);
    return $stmt->fetchAll();
}

/**
 * เกณฑ์ทั้งหมดทุกประเภท เรียงตามประเภท+ลำดับ (ใช้ในหน้า admin จัดการแบบประเมิน)
 */
function evaluation_criteria_all(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM evaluation_criteria ORDER BY exam_type, sort_order, id');
    return $stmt->fetchAll();
}

function evaluation_criteria_create(PDO $pdo, string $examType, string $name, int $maxScore): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM evaluation_criteria WHERE exam_type = ?');
    $stmt->execute([$examType]);
    $nextOrder = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'INSERT INTO evaluation_criteria (exam_type, name, max_score, sort_order) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$examType, $name, $maxScore, $nextOrder]);
    return (int)$pdo->lastInsertId();
}

function evaluation_criteria_delete(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM evaluation_criteria WHERE id = ?');
    $stmt->execute([$id]);
}

function evaluation_criteria_find(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM evaluation_criteria WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * เกณฑ์ทุกหัวข้อของ exam_type โครงงานนี้ พร้อมคะแนนที่เคยให้ไว้ (score = null ถ้ายังไม่ได้ให้)
 */
function evaluation_scores_for_project(PDO $pdo, int $projectId, string $examType): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id AS criterion_id, c.name, c.max_score, s.score, s.scored_by, s.updated_at
         FROM evaluation_criteria c
         LEFT JOIN evaluation_scores s ON s.criterion_id = c.id AND s.project_id = ?
         WHERE c.exam_type = ?
         ORDER BY c.sort_order, c.id'
    );
    $stmt->execute([$projectId, $examType]);
    return $stmt->fetchAll();
}

/**
 * บันทึก/แก้ไขคะแนนของโครงงานทีเดียวทุกหัวข้อ (upsert)
 * @param array<int,int> $criterionScores [criterion_id => score]
 */
function evaluation_scores_save(PDO $pdo, int $projectId, array $criterionScores, int $scoredBy): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO evaluation_scores (project_id, criterion_id, score, scored_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE score = VALUES(score), scored_by = VALUES(scored_by)'
        );
        foreach ($criterionScores as $criterionId => $score) {
            $stmt->execute([$projectId, $criterionId, $score, $scoredBy]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * ผลรวมคะแนนของโครงงาน คืนค่า null ถ้ายังไม่เคยให้คะแนนเลยสักหัวข้อ
 */
function evaluation_total_score(PDO $pdo, int $projectId): ?int
{
    $stmt = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(score), 0) FROM evaluation_scores WHERE project_id = ?');
    $stmt->execute([$projectId]);
    [$count, $sum] = $stmt->fetch(PDO::FETCH_NUM);
    return ((int)$count > 0) ? (int)$sum : null;
}
