-- Migration 004: แก้ไขข้อความภาษาไทยที่เพี้ยนในตาราง evaluation_criteria
--
-- ปัญหา: migration 002 (ก่อนแก้) ถ้ารันด้วย `mysql -u root -p present_registration < ...sql`
-- บนบางเครื่อง (โดยเฉพาะ Windows) โดยไม่ระบุ charset ของ client ชัดเจน จะทำให้ข้อความภาษาไทย
-- ในหัวข้อประเมินที่ seed มาถูกเก็บเพี้ยน (double-encoded) แก้ไฟล์ 002 ให้มี "SET NAMES utf8mb4"
-- แล้ว แต่ถ้าคุณรัน migration 002 (เวอร์ชันเก่าก่อนแก้) ไปแล้วบน production ให้รันไฟล์นี้ต่อ
-- เพื่อซ่อมข้อความให้ถูกต้อง (เขียนทับเฉพาะหัวข้อที่ตรงกับชุดที่ seed มาตั้งแต่แรกเท่านั้น
-- โดยอ้างอิงจาก exam_type + sort_order ไม่ใช้ id จึงไม่กระทบ FK ของ evaluation_scores ที่มีอยู่)
--
-- ถ้ายังไม่เคยรัน migration 002 เลย ให้ใช้ไฟล์ 002 (เวอร์ชันล่าสุดที่แก้แล้ว) โดยตรง
-- ไม่ต้องรันไฟล์นี้ก็ได้ (รันซ้ำก็ไม่มีผลเสีย เพราะ UPDATE ค่าที่ถูกต้องอยู่แล้วให้เป็นค่าเดิม)
--
-- วิธีใช้: mysql --default-character-set=utf8mb4 -u root -p present_registration < sql/migrations/004_fix_evaluation_criteria_thai_text.sql

USE present_registration;
SET NAMES utf8mb4;

UPDATE evaluation_criteria SET name = 'ความเหมาะสมและความเป็นไปได้ของหัวข้อ' WHERE exam_type = 'topic' AND sort_order = 1;
UPDATE evaluation_criteria SET name = 'ความชัดเจนของวัตถุประสงค์และขอบเขตโครงงาน' WHERE exam_type = 'topic' AND sort_order = 2;
UPDATE evaluation_criteria SET name = 'แผนการดำเนินงาน' WHERE exam_type = 'topic' AND sort_order = 3;
UPDATE evaluation_criteria SET name = 'การนำเสนอและตอบข้อซักถาม' WHERE exam_type = 'topic' AND sort_order = 4;

UPDATE evaluation_criteria SET name = 'ความก้าวหน้าของการดำเนินงานเทียบแผน' WHERE exam_type = 'progress' AND sort_order = 1;
UPDATE evaluation_criteria SET name = 'คุณภาพของผลงานที่ทำมาแล้ว' WHERE exam_type = 'progress' AND sort_order = 2;
UPDATE evaluation_criteria SET name = 'การแก้ไขปัญหาและอุปสรรค' WHERE exam_type = 'progress' AND sort_order = 3;
UPDATE evaluation_criteria SET name = 'การนำเสนอและตอบข้อซักถาม' WHERE exam_type = 'progress' AND sort_order = 4;

UPDATE evaluation_criteria SET name = 'ความสมบูรณ์ของโครงงาน' WHERE exam_type = 'final' AND sort_order = 1;
UPDATE evaluation_criteria SET name = 'คุณภาพของผลงาน/นวัตกรรม' WHERE exam_type = 'final' AND sort_order = 2;
UPDATE evaluation_criteria SET name = 'รูปเล่มรายงาน' WHERE exam_type = 'final' AND sort_order = 3;
UPDATE evaluation_criteria SET name = 'การนำเสนอ' WHERE exam_type = 'final' AND sort_order = 4;
UPDATE evaluation_criteria SET name = 'การตอบข้อซักถาม' WHERE exam_type = 'final' AND sort_order = 5;
