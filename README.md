# ระบบลงทะเบียนสอบนำเสนอโครงงาน

ระบบลงทะเบียนสอบนำเสนอโครงงานสำหรับ 3 บทบาท: ผู้ดูแลระบบ (admin), ครูที่ปรึกษา, นักเรียน
เขียนด้วย PHP 8.3 (plain, ไม่มี framework) + MySQL 8.4 ไม่ใช้ Docker

## กติกาของระบบ

- นักเรียนลงทะเบียนสอบล่วงหน้าอย่างน้อย **7 วัน** ก่อนวันสอบ
- ครูที่ปรึกษาต้องอนุมัติก่อนวันสอบอย่างน้อย **2 วัน** จึงจะมีชื่อในลำดับคิวสอบ
- 1 โครงงาน: นักเรียนไม่เกิน **5 คน** + ครูที่ปรึกษา 1 คน
- เวลาสอบ **20 นาที/โครงงาน** คิวสอบคำนวณอัตโนมัติตามลำดับเวลาที่ครูอนุมัติ ภายในช่วงเวลาที่ admin กำหนดต่อวันสอบ
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
6. **ตั้งค่า `upload_max_filesize` และ `post_max_size` ใน `php.ini` ให้รองรับไฟล์ที่ระบบอนุญาต** (ค่า default ของ PHP มักต่ำแค่ 2-8MB) ระบบอนุญาตไฟล์ได้สูงสุด 25MB/ไฟล์ (ปรับได้ที่ `config/config.php` → `upload.max_size_bytes`) แนะนำตั้ง `php.ini`:
   ```ini
   upload_max_filesize = 30M
   post_max_size = 65M
   ```
   (`post_max_size` ต้องมากกว่าผลรวมไฟล์ทั้งสองที่อัปโหลดพร้อมกัน ไม่ใช่แค่ไฟล์เดียว) แล้ว reload Apache/PHP-FPM
   > ถ้าค่านี้ต่ำเกินไป PHP จะเคลียร์ `$_POST`/`$_FILES` ทิ้งทั้งหมดโดยไม่แจ้ง error ตรงๆ ทำให้ระบบมองว่าเป็น CSRF token ไม่ตรงกัน (ข้อความ "เซสชันหมดอายุ...") ทั้งที่จริงคือไฟล์ใหญ่เกินไป — ตั้งแต่เวอร์ชันล่าสุดระบบจะแยกแจ้งเป็น "ไฟล์ที่แนบมีขนาดใหญ่เกินกว่าเซิร์ฟเวอร์จะรับได้" แทนแล้ว แต่ควรตั้ง `php.ini` ให้ถูกต้องไว้ตั้งแต่แรกเพื่อไม่ให้ผู้ใช้เจอ error นี้เลย

### กรณี deploy ไว้ที่ sub-path ของโดเมนเดิม (ไม่ใช่ root)

ตัวอย่าง: เว็บอยู่ที่ `https://www.prcs.ac.th/project-exam/` (โดเมน `www.prcs.ac.th` มี VirtualHost อยู่แล้วสำหรับแอปอื่นๆ) และเก็บไฟล์ไว้ที่ `/var/www/html/project-exam`

**1. เพิ่ม `config/config.php`** ให้ระบุ `base_path` ตามชื่อ path และ `canonical_host` เป็นโดเมนที่ถูกต้องเพียงหนึ่งเดียว:
```php
'app' => [
    'timezone' => 'Asia/Bangkok',
    'base_path' => '/project-exam',
    'canonical_host' => 'www.prcs.ac.th',
],
```
`base_path` ทำให้ลิงก์/redirect ทั้งหมดในระบบ (สร้างผ่านฟังก์ชัน `base_url()` ทุกจุด) ใส่ prefix `/project-exam` ให้อัตโนมัติ

`canonical_host` **สำคัญมาก** — ถ้าเซิร์ฟเวอร์ตอบเว็บเดียวกันได้จากหลายโดเมน/สคีม (เช่นเข้าได้ทั้ง `prcs.ac.th`, `www.prcs.ac.th`, หรือ `http://` โดยไม่ redirect ไปหาโดเมนเดียว) แต่ละโดเมนจะมี session cookie เป็นคนละก้อนกัน (คนละ origin) ถ้าผู้ใช้เข้าคนละโดเมนกันระหว่างโหลดฟอร์มกับตอนกดส่ง จะเจอ error "CSRF token ไม่ตรงกัน" ทุกครั้งแบบสุ่มๆ ตั้งค่านี้ไว้ ระบบจะ redirect ไปโดเมนที่กำหนด (เป็น https เสมอ) ให้อัตโนมัติก่อนเริ่ม session ทุกครั้ง แก้ปัญหานี้ได้เด็ดขาดโดยไม่ต้องพึ่งการตั้งค่า Apache/DNS ให้ถูกต้อง 100%
เว้นว่างไว้ตอนพัฒนา/ทดสอบในเครื่อง (localhost)

**2. แก้ไฟล์ VirtualHost ของ `www.prcs.ac.th`** (ไฟล์ config เดิมของโดเมนนี้ เช่น `/etc/apache2/sites-available/www.prcs.ac.th.conf`) โดย**ไม่ต้องสร้าง VirtualHost ใหม่** — ให้เพิ่ม `Alias` เข้าไปในบล็อก `<VirtualHost>` เดิม ให้ path `/project-exam` ชี้ไปที่โฟลเดอร์ `public/` ของโปรเจกต์ (สำคัญ: ต้องชี้ที่ `public/` เท่านั้น ไม่ใช่ `/var/www/html/project-exam` ตรงๆ เพื่อไม่ให้เข้าถึง `src/`, `config/`, `storage/` จากเว็บได้):

```apache
<VirtualHost *:443>
    ServerName www.prcs.ac.th
    # ... directive เดิมของ vhost นี้ (SSL, DocumentRoot ของแอปอื่น ฯลฯ) ...

    Alias /project-exam /var/www/html/project-exam/public

    <Directory /var/www/html/project-exam/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**3. ตรวจ syntax แล้ว reload Apache**:
```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

จากนั้นเข้า `https://www.prcs.ac.th/project-exam/` ควรเจอหน้า login/หน้าแรกตามปกติ ลิงก์ภายในทั้งหมด (login, dashboard แต่ละ role, download.php) จะพา path `/project-exam` ไปด้วยเองทุกจุด ไม่ต้องแก้โค้ดเพิ่ม

> หมายเหตุ: ระบบไม่ได้ใช้ `.htaccess`/`mod_rewrite` (ไม่มี pretty URL) จึงไม่ต้องกังวลเรื่อง `RewriteBase`

## Deploy ขึ้นเซิร์ฟเวอร์

Deploy ด้วยมือ (ไม่มี CI/CD อัตโนมัติ) — เช่น `git pull` บนเซิร์ฟเวอร์ หรือ rsync/scp โค้ดขึ้นไปเอง
สิ่งที่ต้องระวังทุกครั้งที่ deploy:

- อย่าทับ `config/config.php` และ `storage/uploads/` บนเซิร์ฟเวอร์ (ข้อมูลเฉพาะของเครื่องนั้น ไม่อยู่ใน git)
- หาก `sql/schema.sql` เปลี่ยน (ติดตั้งใหม่) หรือมีไฟล์ใหม่ใน `sql/migrations/` (อัปเดตของเดิม) ต้องรันเองบนเซิร์ฟเวอร์ ไม่มีการรันอัตโนมัติ — ไฟล์ migration เขียนแบบ `ALTER`/`CREATE` เพิ่ม ไม่ลบข้อมูลเดิม รันได้ปลอดภัย เช่น:
  ```bash
  mysql -u root -p present_registration < sql/migrations/002_exam_type_evaluation.sql
  ```

## ความปลอดภัย

- รหัสผ่านเก็บด้วย `password_hash` (bcrypt)
- ทุกฟอร์ม POST มี CSRF token
- Query ฐานข้อมูลทั้งหมดใช้ PDO prepared statements
- ไฟล์อัปโหลดตรวจทั้งนามสกุลและ MIME จริง เก็บนอก document root และเปลี่ยนชื่อเป็นค่าสุ่มก่อนบันทึก การดาวน์โหลดต้องผ่าน `download.php` ที่ตรวจสิทธิ์ก่อนเสมอ
