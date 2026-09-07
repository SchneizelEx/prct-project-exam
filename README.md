# ระบบลงทะเบียนสอบนำเสนอโครงงาน

ระบบลงทะเบียนสอบนำเสนอโครงงานสำหรับ 3 บทบาท: ผู้ดูแลระบบ (admin), ครูที่ปรึกษา, นักเรียน
เขียนด้วย PHP 8.3 (plain, ไม่มี framework) + MySQL 8.4 ไม่ใช้ Docker

## กติกาของระบบ

- นักเรียนลงทะเบียนสอบล่วงหน้าอย่างน้อย **7 วัน** ก่อนวันสอบ
- ครูที่ปรึกษาต้องอนุมัติก่อนวันสอบอย่างน้อย **2 วัน** จึงจะมีชื่อในลำดับคิวสอบ
- 1 โครงงาน: นักเรียนไม่เกิน **5 คน** + ครูที่ปรึกษา 1 คน
- เวลาสอบ **30 นาที/โครงงาน** คิวสอบคำนวณอัตโนมัติตามลำดับเวลาที่ครูอนุมัติ ภายในช่วงเวลาที่ admin กำหนดต่อวันสอบ
- ไฟล์แนบบังคับ: รายงานผลดำเนินงาน (`.docx`) และไฟล์นำเสนอ (`.pptx`) เท่านั้น
- Admin เป็นผู้สร้างบัญชีผู้ใช้ทั้งหมด (ครู/นักเรียน) ไม่มีหน้าสมัครสมาชิกเอง

## โครงสร้างโปรเจกต์

```
public/       Apache DocumentRoot (หน้าเว็บทั้งหมด)
src/          โค้ดหลัก (bootstrap, db, auth, csrf, rules, repositories)
sql/          schema.sql
bin/          สคริปต์ command line (สร้าง admin คนแรก)
config/       ไฟล์ตั้งค่า (config.php ไม่ commit ขึ้น git)
storage/      ไฟล์อัปโหลด (นอก webroot, ไม่ commit ขึ้น git)
```

## ติดตั้งสำหรับพัฒนา/ทดสอบ

1. ติดตั้ง PHP 8.3 (พร้อม extension `pdo_mysql`, `fileinfo`) และ MySQL 8.4
2. สร้างฐานข้อมูล:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
3. คัดลอกไฟล์ตั้งค่า แล้วใส่ค่า DB ให้ตรงกับเครื่อง:
   ```bash
   cp config/config.php.example config/config.php
   ```
4. สร้างบัญชี admin คนแรก:
   ```bash
   php bin/create_admin.php admin "รหัสผ่านของคุณ" "ชื่อผู้ดูแลระบบ"
   ```
5. รันเซิร์ฟเวอร์ทดสอบในตัว PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
   แล้วเปิด http://localhost:8000

## ติดตั้งบน Apache (production)

1. ตั้งค่า VirtualHost ให้ `DocumentRoot` ชี้ไปที่โฟลเดอร์ `public/` ของโปรเจกต์ (ห้ามชี้ไปที่ root ของโปรเจกต์ เพื่อไม่ให้เข้าถึง `src/`, `config/`, `storage/` ได้โดยตรง) เช่น:
   ```apache
   <VirtualHost *:80>
       ServerName exam.example.ac.th
       DocumentRoot /var/www/present-registration/public
       <Directory /var/www/present-registration/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
2. Import `sql/schema.sql` เข้า MySQL 8.4 บนเซิร์ฟเวอร์
3. สร้าง `config/config.php` บนเซิร์ฟเวอร์จริง (จาก `.example`) ใส่ credentials ของ MySQL จริง — ไฟล์นี้ต้องไม่อยู่ใน git
4. ตรวจสอบว่า `storage/uploads/` เขียนได้โดย user ของ Apache (เช่น `www-data`)
5. รัน `php bin/create_admin.php ...` บนเซิร์ฟเวอร์เพื่อสร้างบัญชี admin คนแรก

## Deploy อัตโนมัติผ่าน GitHub Actions

`.github/workflows/deploy.yml` จะ rsync โค้ดไปยังเซิร์ฟเวอร์ Apache ผ่าน SSH ทุกครั้งที่ push เข้า `main`
ต้องตั้งค่า GitHub Secrets ก่อนใช้งาน (Settings > Secrets and variables > Actions):

| Secret | คำอธิบาย |
|---|---|
| `SSH_HOST` | โฮสต์/IP ของเซิร์ฟเวอร์ |
| `SSH_USER` | ผู้ใช้สำหรับ SSH |
| `SSH_KEY` | private key สำหรับ SSH |
| `SSH_PORT` | พอร์ต SSH (ปกติ 22) |
| `DEPLOY_PATH` | path ปลายทางบนเซิร์ฟเวอร์ เช่น `/var/www/present-registration` |

Workflow จะไม่ deploy ทับ `config/config.php` และ `storage/uploads/` (ข้อมูลเฉพาะของเซิร์ฟเวอร์) และไม่รัน migration ฐานข้อมูลให้อัตโนมัติ — การเปลี่ยนแปลง schema ต้องรันเองบนเซิร์ฟเวอร์

## ความปลอดภัย

- รหัสผ่านเก็บด้วย `password_hash` (bcrypt)
- ทุกฟอร์ม POST มี CSRF token
- Query ฐานข้อมูลทั้งหมดใช้ PDO prepared statements
- ไฟล์อัปโหลดตรวจทั้งนามสกุลและ MIME จริง เก็บนอก document root และเปลี่ยนชื่อเป็นค่าสุ่มก่อนบันทึก การดาวน์โหลดต้องผ่าน `download.php` ที่ตรวจสิทธิ์ก่อนเสมอ
