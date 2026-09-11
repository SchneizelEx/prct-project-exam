-- ระบบลงทะเบียนสอบนำเสนอโครงงาน
-- MySQL 8.4
-- ใช้: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS present_registration
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE present_registration;

-- บังคับให้ client ตีความไฟล์นี้เป็น UTF-8 เสมอ ไม่งั้นบางเครื่อง (โดยเฉพาะ Windows) mysql client
-- จะเดา charset อื่นแทน ทำให้ข้อความภาษาไทยใน INSERT (เกณฑ์ประเมินท้ายไฟล์) ถูกเก็บเพี้ยน (double-encoded)
SET NAMES utf8mb4;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin','teacher','student') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    code VARCHAR(50) NULL, -- รหัสนักเรียน / รหัสครู
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exam_days (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exam_days_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT chk_exam_days_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    exam_type ENUM('topic','progress','final') NOT NULL,
    advisor_teacher_id INT UNSIGNED NOT NULL,
    exam_day_id INT UNSIGNED NOT NULL,
    registered_by INT UNSIGNED NOT NULL,
    report_docx_path VARCHAR(255) NOT NULL,
    presentation_pptx_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    evaluation_notes TEXT NULL,
    rejected_reason VARCHAR(500) NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_advisor FOREIGN KEY (advisor_teacher_id) REFERENCES users(id),
    CONSTRAINT fk_projects_exam_day FOREIGN KEY (exam_day_id) REFERENCES exam_days(id),
    CONSTRAINT fk_projects_registered_by FOREIGN KEY (registered_by) REFERENCES users(id),
    KEY idx_projects_exam_day_status (exam_day_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_members_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_members_student FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE KEY uq_project_student (project_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
