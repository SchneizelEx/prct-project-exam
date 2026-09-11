-- Migration 002: ประเภทการสอบ + คณะกรรมการสอบประจำวัน + แบบประเมินให้คะแนน
-- รันบน production ที่มีข้อมูลอยู่แล้วได้อย่างปลอดภัย (ไม่ลบ/ไม่ทับข้อมูลเดิม)
-- วิธีใช้: mysql -u root -p present_registration < sql/migrations/002_exam_type_evaluation.sql

USE present_registration;

-- บังคับให้ client ตีความไฟล์นี้เป็น UTF-8 เสมอ ไม่งั้นบางเครื่อง (โดยเฉพาะ Windows) mysql client
-- จะเดา charset อื่นแทน ทำให้ข้อความภาษาไทยใน INSERT ด้านล่างถูกเก็บเพี้ยน (double-encoded)
SET NAMES utf8mb4;

ALTER TABLE projects
    ADD COLUMN exam_type ENUM('topic','progress','final') NOT NULL DEFAULT 'final' AFTER title;
-- DEFAULT 'final' ใช้เฉพาะ migrate ข้อมูลเก่าที่ยังไม่มีประเภทระบุ
-- ฟอร์มลงทะเบียนใหม่ (register.php) บังคับให้เลือกเสมอ ไม่มีค่า default ที่ตัว UI

CREATE TABLE exam_day_examiners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_day_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_examiners_exam_day FOREIGN KEY (exam_day_id) REFERENCES exam_days(id) ON DELETE CASCADE,
    CONSTRAINT fk_examiners_teacher FOREIGN KEY (teacher_id) REFERENCES users(id),
    UNIQUE KEY uq_exam_day_teacher (exam_day_id, teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluation_criteria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_type ENUM('topic','progress','final') NOT NULL,
    name VARCHAR(255) NOT NULL,
    max_score INT UNSIGNED NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluation_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    criterion_id INT UNSIGNED NOT NULL,
    score INT UNSIGNED NOT NULL,
    scored_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_scores_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_scores_criterion FOREIGN KEY (criterion_id) REFERENCES evaluation_criteria(id),
    CONSTRAINT fk_scores_scored_by FOREIGN KEY (scored_by) REFERENCES users(id),
    UNIQUE KEY uq_project_criterion (project_id, criterion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เกณฑ์เริ่มต้น (รวม 60 คะแนนต่อประเภท) แอดมินแก้ไข/ลบ/เพิ่มเองได้ภายหลังผ่านหน้า admin/evaluation_criteria.php
INSERT INTO evaluation_criteria (exam_type, name, max_score, sort_order) VALUES
 ('topic','ความเหมาะสมและความเป็นไปได้ของหัวข้อ',20,1),
 ('topic','ความชัดเจนของวัตถุประสงค์และขอบเขตโครงงาน',15,2),
 ('topic','แผนการดำเนินงาน',15,3),
 ('topic','การนำเสนอและตอบข้อซักถาม',10,4),
 ('progress','ความก้าวหน้าของการดำเนินงานเทียบแผน',20,1),
 ('progress','คุณภาพของผลงานที่ทำมาแล้ว',20,2),
 ('progress','การแก้ไขปัญหาและอุปสรรค',10,3),
 ('progress','การนำเสนอและตอบข้อซักถาม',10,4),
 ('final','ความสมบูรณ์ของโครงงาน',20,1),
 ('final','คุณภาพของผลงาน/นวัตกรรม',15,2),
 ('final','รูปเล่มรายงาน',10,3),
 ('final','การนำเสนอ',10,4),
 ('final','การตอบข้อซักถาม',5,5);
