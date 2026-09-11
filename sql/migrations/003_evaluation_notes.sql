-- Migration 003: บันทึกเพิ่มเติมของคณะกรรมการสอบต่อโครงงาน (ข้อความยาวได้)
-- รันบน production ที่มีข้อมูลอยู่แล้วได้อย่างปลอดภัย (ไม่ลบ/ไม่ทับข้อมูลเดิม)
-- วิธีใช้: mysql -u root -p present_registration < sql/migrations/003_evaluation_notes.sql

USE present_registration;

ALTER TABLE projects
    ADD COLUMN evaluation_notes TEXT NULL AFTER status;
-- บันทึกร่วมกันของคณะกรรมการสอบทุกคนที่ได้รับมอบหมายวันนั้น (แก้ไขทับกันได้เหมือนคะแนน)
-- แก้ไข/บันทึกได้ที่หน้า teacher/score_project.php
