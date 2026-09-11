<?php
declare(strict_types=1);

const RULE_REGISTRATION_MIN_DAYS = 7;
const RULE_APPROVAL_MIN_DAYS = 2;
const RULE_EXAM_SLOT_MINUTES = 20;
const RULE_MAX_STUDENTS_PER_PROJECT = 5;

const EXAM_TYPES = ['topic', 'progress', 'final'];
const EVALUATION_TOTAL_SCORE = 60;

function exam_type_label(string $type): string
{
    return match ($type) {
        'topic' => 'เสนอหัวข้อ',
        'progress' => 'นำเสนอความก้าวหน้า',
        'final' => 'สอบจบโครงการ',
        default => $type,
    };
}

/**
 * จำนวนวันนับจากวันนี้ถึงวันสอบ (ค่าลบ = วันสอบผ่านไปแล้ว)
 */
function rules_days_until(string $examDate): int
{
    $examTs = strtotime($examDate . ' 00:00:00');
    $todayTs = strtotime(date('Y-m-d') . ' 00:00:00');
    return (int) round(($examTs - $todayTs) / 86400);
}

function rules_can_register(string $examDate): bool
{
    return rules_days_until($examDate) >= RULE_REGISTRATION_MIN_DAYS;
}

function rules_can_approve(string $examDate): bool
{
    return rules_days_until($examDate) >= RULE_APPROVAL_MIN_DAYS;
}

/**
 * จำนวนโครงงานสูงสุดที่วันสอบนี้รับได้ (ช่วงเวลา / 30 นาที)
 */
function rules_exam_day_capacity(string $startTime, string $endTime): int
{
    $minutes = (strtotime($endTime) - strtotime($startTime)) / 60;
    return max(0, intdiv((int)$minutes, RULE_EXAM_SLOT_MINUTES));
}

/**
 * คำนวณลำดับคิว + เวลาสอบของโครงงานที่ approved แล้ว เรียงตาม approved_at
 * $approvedProjects: array ของแถวที่มี key 'approved_at' อย่างน้อย
 */
function rules_compute_queue(array $approvedProjects, string $dayStartTime): array
{
    usort($approvedProjects, fn($a, $b) => strcmp($a['approved_at'] ?? '', $b['approved_at'] ?? ''));

    $startTs = strtotime($dayStartTime);
    $result = [];
    foreach ($approvedProjects as $i => $project) {
        $slotStart = $startTs + $i * RULE_EXAM_SLOT_MINUTES * 60;
        $slotEnd = $slotStart + RULE_EXAM_SLOT_MINUTES * 60;
        $project['queue_no'] = $i + 1;
        $project['slot_start'] = date('H:i', $slotStart);
        $project['slot_end'] = date('H:i', $slotEnd);
        $result[] = $project;
    }
    return $result;
}

function rules_validate_member_count(int $count): ?string
{
    if ($count < 1) {
        return 'ต้องมีสมาชิกอย่างน้อย 1 คน';
    }
    if ($count > RULE_MAX_STUDENTS_PER_PROJECT) {
        return 'โครงงานหนึ่งมีผู้ทำได้ไม่เกิน ' . RULE_MAX_STUDENTS_PER_PROJECT . ' คน';
    }
    return null;
}

/**
 * แยกระดับชั้นจากรหัสนักศึกษา: หลักที่ 3 (จากซ้าย) ของรหัสเป็น '2' = ปวช., '3' = ปวส.
 * (2 หลักแรกคือปี พ.ศ. ที่เข้าเรียน 2 ตัวท้าย ก่อนปี 69 รหัสมี 10 หลัก ตั้งแต่ปี 69 มี 11 หลัก
 * แต่ตำแหน่งหลักที่ 3 ที่บอกระดับจะอยู่ตำแหน่งเดิมเสมอไม่ว่ารหัสจะยาวกี่หลัก)
 * เช่น 6722110001 (หลักที่ 3 = 2) เป็น ปวช., 69302010001 (หลักที่ 3 = 3) เป็น ปวส.
 * ตัดช่องว่าง/เครื่องหมายคำพูดที่มักติดมาจากการ import ไฟล์ Excel ออกก่อนตรวจ
 * คืนค่า null ถ้ารหัสไม่มีหรือไม่ตรงรูปแบบที่รู้จัก (จะไม่ถูกนำไปกรองรายชื่อ)
 */
function student_level_from_code(?string $code): ?string
{
    if ($code === null) {
        return null;
    }
    $code = trim($code, " \t\n\r\0\x0B'\"");
    if (strlen($code) < 3 || !ctype_digit($code)) {
        return null;
    }
    return match ($code[2]) {
        '2' => 'ปวช.',
        '3' => 'ปวส.',
        default => null,
    };
}
