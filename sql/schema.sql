-- ระบบลงทะเบียนสอบนำเสนอโครงงาน
-- MySQL 8.4
-- ใช้: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS present_registration
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE present_registration;

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
    advisor_teacher_id INT UNSIGNED NOT NULL,
    exam_day_id INT UNSIGNED NOT NULL,
    registered_by INT UNSIGNED NOT NULL,
    report_docx_path VARCHAR(255) NOT NULL,
    presentation_pptx_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
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
