<?php
declare(strict_types=1);

const CSV_IMPORT_MAX_ROWS = 500;

function csv_normalize_role(string $value): ?string
{
    $value = mb_strtolower(trim($value));
    return match ($value) {
        'admin', 'ผู้ดูแลระบบ' => 'admin',
        'teacher', 'ครู' => 'teacher',
        'student', 'นักเรียน' => 'student',
        default => null,
    };
}

/**
 * อ่านไฟล์ CSV บัญชีผู้ใช้ ต้องมีคอลัมน์อย่างน้อย: role, username, full_name
 * คอลัมน์ทางเลือก: password, code, email
 *
 * @return array{rows: array<int, array<string, string|int>>, error: ?string}
 */
function csv_parse_users(string $filePath): array
{
    $handle = @fopen($filePath, 'r');
    if (!$handle) {
        return ['rows' => [], 'error' => 'ไม่สามารถเปิดไฟล์ CSV ได้'];
    }

    $header = fgetcsv($handle);
    if ($header === false || $header === null) {
        fclose($handle);
        return ['rows' => [], 'error' => 'ไฟล์ CSV ว่างเปล่า'];
    }

    // ตัด UTF-8 BOM ที่มักติดมาจากการ export ด้วย Excel
    if (isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }
    $header = array_map(fn($h) => mb_strtolower(trim((string)$h)), $header);

    foreach (['role', 'username', 'full_name'] as $required) {
        if (!in_array($required, $header, true)) {
            fclose($handle);
            return ['rows' => [], 'error' => "ไฟล์ CSV ต้องมีคอลัมน์ '$required' (แถวหัวตาราง)"];
        }
    }

    $colIndex = array_flip($header);
    $get = function (array $data, string $col) use ($colIndex): string {
        return isset($colIndex[$col], $data[$colIndex[$col]]) ? trim((string)$data[$colIndex[$col]]) : '';
    };

    $rows = [];
    $line = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if (count($data) === 1 && trim((string)$data[0]) === '') {
            continue; // ข้ามบรรทัดว่าง
        }
        if (count($rows) >= CSV_IMPORT_MAX_ROWS) {
            fclose($handle);
            return ['rows' => $rows, 'error' => 'ไฟล์มีข้อมูลเกิน ' . CSV_IMPORT_MAX_ROWS . ' แถว กรุณาแบ่งไฟล์'];
        }
        $rows[] = [
            'line' => $line,
            'role' => $get($data, 'role'),
            'username' => $get($data, 'username'),
            'password' => $get($data, 'password'),
            'full_name' => $get($data, 'full_name'),
            'code' => $get($data, 'code'),
            'email' => $get($data, 'email'),
        ];
    }
    fclose($handle);

    return ['rows' => $rows, 'error' => null];
}
