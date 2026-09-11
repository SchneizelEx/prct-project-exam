<?php
require_once __DIR__ . '/../src/bootstrap.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$siteUrl = $scheme . '://' . $host . base_url('/');

$homeHref = base_url('index.php');
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>คู่มือการใช้งานระบบลงทะเบียนสอบนำเสนอโครงงาน</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Sarabun:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap');

  :root {
    --paper: #F4F6F5;
    --paper-raised: #FFFFFF;
    --ink: #1C232B;
    --ink-muted: #5B6672;
    --line: #D8DEE2;

    --navy: #1E3A5F;
    --navy-ink: #12233B;
    --navy-soft: #E7EDF3;

    --student: #96650F;
    --student-ink: #6E4A0B;
    --student-soft: #F5EBD7;

    --teacher: #7A2E3F;
    --teacher-ink: #5B2130;
    --teacher-soft: #F0E0E4;

    --ok: #276245;
    --ok-soft: #E1EFE7;
    --warn: #8A6B0A;
    --warn-soft: #F6EDD3;
    --danger: #8E3232;
    --danger-soft: #F5E1E1;
    --mute: #5F6B76;
    --mute-soft: #E7E9EC;

    --code-bg: #EAEEEA;
    --shadow: 0 1px 2px rgba(28,35,43,0.06), 0 6px 20px -8px rgba(28,35,43,0.12);
  }

  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --paper: #14181C;
      --paper-raised: #1B2126;
      --ink: #E9EDEF;
      --ink-muted: #9CA8AF;
      --line: #2B333A;

      --navy: #86A9D2;
      --navy-ink: #DCE7F2;
      --navy-soft: #20303F;

      --student: #E0B564;
      --student-ink: #F3DFAF;
      --student-soft: #33291A;

      --teacher: #D191A1;
      --teacher-ink: #F0CBD5;
      --teacher-soft: #33212A;

      --ok: #7FC49E;
      --ok-soft: #1C2E24;
      --warn: #E1C167;
      --warn-soft: #332B15;
      --danger: #E29B9B;
      --danger-soft: #3A1F1F;
      --mute: #9DAAB4;
      --mute-soft: #232A30;

      --code-bg: #202722;
      --shadow: 0 1px 2px rgba(0,0,0,0.3), 0 8px 24px -8px rgba(0,0,0,0.5);
    }
  }

  :root[data-theme="dark"] {
    --paper: #14181C;
    --paper-raised: #1B2126;
    --ink: #E9EDEF;
    --ink-muted: #9CA8AF;
    --line: #2B333A;

    --navy: #86A9D2;
    --navy-ink: #DCE7F2;
    --navy-soft: #20303F;

    --student: #E0B564;
    --student-ink: #F3DFAF;
    --student-soft: #33291A;

    --teacher: #D191A1;
    --teacher-ink: #F0CBD5;
    --teacher-soft: #33212A;

    --ok: #7FC49E;
    --ok-soft: #1C2E24;
    --warn: #E1C167;
    --warn-soft: #332B15;
    --danger: #E29B9B;
    --danger-soft: #3A1F1F;
    --mute: #9DAAB4;
    --mute-soft: #232A30;

    --code-bg: #202722;
    --shadow: 0 1px 2px rgba(0,0,0,0.3), 0 8px 24px -8px rgba(0,0,0,0.5);
  }

  * { box-sizing: border-box; }
  html { color-scheme: light dark; }
  body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif;
    font-size: 16px;
    line-height: 1.65;
  }
  h1, h2, h3 { font-family: 'Kanit', 'Noto Sans Thai', system-ui, sans-serif; text-wrap: balance; margin: 0; }
  code, .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }

  .wrap { max-width: 880px; margin: 0 auto; padding: 0 20px 80px; }

  /* ---------- Masthead ---------- */
  .masthead {
    background: var(--navy-soft);
    border-bottom: 1px solid var(--line);
  }
  .masthead-inner {
    max-width: 880px; margin: 0 auto; padding: 24px 20px 28px;
    display: flex; flex-direction: column; gap: 10px;
  }
  .back-link {
    font-size: 13.5px; font-weight: 600; color: var(--navy-ink);
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    width: fit-content;
  }
  .back-link:hover { text-decoration: underline; }
  .eyebrow {
    font-size: 12.5px; font-weight: 600; letter-spacing: 0.08em;
    color: var(--navy-ink); text-transform: uppercase; margin-top: 8px;
  }
  .masthead h1 { font-size: clamp(26px, 4.4vw, 36px); font-weight: 700; color: var(--ink); }
  .masthead p { margin: 2px 0 0; color: var(--ink-muted); max-width: 62ch; }
  .site-box {
    margin-top: 10px; display: inline-flex; align-items: center; gap: 10px;
    background: var(--paper-raised); border: 1px solid var(--line); border-radius: 10px;
    padding: 10px 14px; box-shadow: var(--shadow); width: fit-content; max-width: 100%;
  }
  .site-box .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ok); flex: none; }
  .site-box .url { font-weight: 500; word-break: break-all; }
  .site-box .label { color: var(--ink-muted); font-size: 13px; }

  /* ---------- Role tabs ---------- */
  .tabbar {
    position: sticky; top: 0; z-index: 5;
    background: var(--paper); border-bottom: 1px solid var(--line);
  }
  .tabbar-inner {
    max-width: 880px; margin: 0 auto; padding: 10px 20px;
    display: flex; gap: 8px;
  }
  .tab-btn {
    font-family: 'Kanit', sans-serif; font-size: 15px; font-weight: 600;
    padding: 9px 18px; border-radius: 999px; border: 1px solid var(--line);
    background: var(--paper-raised); color: var(--ink-muted); cursor: pointer;
    display: flex; align-items: center; gap: 8px; transition: border-color .15s, color .15s, background .15s;
  }
  .tab-btn .swatch { width: 9px; height: 9px; border-radius: 50%; }
  .tab-btn[data-role="student"] .swatch { background: var(--student); }
  .tab-btn[data-role="teacher"] .swatch { background: var(--teacher); }
  .tab-btn[aria-selected="true"][data-role="student"] { color: var(--student-ink); border-color: var(--student); background: var(--student-soft); }
  .tab-btn[aria-selected="true"][data-role="teacher"] { color: var(--teacher-ink); border-color: var(--teacher); background: var(--teacher-soft); }
  .tab-btn:focus-visible { outline: 2px solid var(--navy); outline-offset: 2px; }

  /* ---------- Sections ---------- */
  section.role { padding-top: 30px; }
  section.role[hidden] { display: none; }

  .role-head { display: flex; align-items: baseline; gap: 12px; margin-bottom: 4px; }
  .role-head h2 { font-size: 24px; font-weight: 700; }
  .role[data-role="student"] .role-head h2 { color: var(--student-ink); }
  .role[data-role="teacher"] .role-head h2 { color: var(--teacher-ink); }
  .role-sub { color: var(--ink-muted); margin: 4px 0 22px; max-width: 68ch; }

  /* ---------- Credential card ---------- */
  .cred-card {
    display: grid; grid-template-columns: 1fr 1fr; gap: 1px;
    background: var(--line); border: 1px solid var(--line); border-radius: 12px;
    overflow: hidden; box-shadow: var(--shadow); margin-bottom: 26px;
  }
  .cred-cell { background: var(--paper-raised); padding: 16px 18px; }
  .cred-cell .k { font-size: 12.5px; color: var(--ink-muted); font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }
  .cred-cell .v { margin-top: 5px; font-weight: 600; }
  .cred-cell .hint { margin-top: 3px; font-size: 13.5px; color: var(--ink-muted); }
  @media (max-width: 560px) { .cred-card { grid-template-columns: 1fr; } }

  /* ---------- Steps ---------- */
  .steps { display: flex; flex-direction: column; gap: 0; margin: 0 0 28px; padding: 0; list-style: none; counter-reset: step; }
  .steps > li {
    counter-increment: step; position: relative;
    display: grid; grid-template-columns: 34px 1fr; gap: 14px;
    padding: 16px 0; border-top: 1px solid var(--line);
  }
  .steps > li:last-child { padding-bottom: 0; }
  .steps > li::before {
    content: counter(step);
    font-family: 'Kanit', sans-serif; font-weight: 600; font-size: 14px;
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: var(--role-soft, var(--navy-soft)); color: var(--role-ink, var(--navy));
    border: 1px solid var(--line);
  }
  .role[data-role="student"] .steps > li::before { --role-soft: var(--student-soft); --role-ink: var(--student-ink); }
  .role[data-role="teacher"] .steps > li::before { --role-soft: var(--teacher-soft); --role-ink: var(--teacher-ink); }
  .steps .step-title { font-weight: 600; margin: 3px 0 4px; }
  .steps .step-body { color: var(--ink-muted); }
  .steps .step-body p { margin: 4px 0; }
  .steps ul.field-list { margin: 8px 0 0; padding-left: 18px; }
  .steps ul.field-list li { display: list-item; padding: 2px 0; border: none; grid-template-columns: none; }
  .steps ul.field-list li::before { content: none; }

  .field { background: var(--code-bg); padding: 1px 7px; border-radius: 5px; font-size: 13.5px; }

  /* ---------- Flow strip ---------- */
  .flow { display: flex; align-items: stretch; gap: 0; margin: 4px 0 30px; overflow-x: auto; }
  .flow-node {
    flex: 1 1 0; min-width: 130px; background: var(--paper-raised); border: 1px solid var(--line);
    border-radius: 10px; padding: 12px 14px; font-size: 13.5px; font-weight: 600; text-align: center;
    display: flex; align-items: center; justify-content: center;
  }
  .flow-arrow { flex: none; width: 26px; display: flex; align-items: center; justify-content: center; color: var(--ink-muted); }
  .flow-node.pending { background: var(--warn-soft); color: var(--warn); border-color: transparent; }
  .flow-node.ok { background: var(--ok-soft); color: var(--ok); border-color: transparent; }
  .flow-node.danger { background: var(--danger-soft); color: var(--danger); border-color: transparent; }
  .flow-branch { display: flex; flex-direction: column; gap: 6px; flex: 1 1 0; min-width: 130px; }

  /* ---------- Rule table ---------- */
  table.rules { width: 100%; border-collapse: collapse; margin: 0 0 28px; font-size: 14.5px; }
  table.rules th, table.rules td { text-align: left; padding: 10px 12px; border-top: 1px solid var(--line); vertical-align: top; }
  table.rules thead th { border-top: none; color: var(--ink-muted); font-weight: 600; font-size: 12.5px; text-transform: uppercase; letter-spacing: .03em; }
  table.rules td.num { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .table-scroll { overflow-x: auto; }

  /* ---------- Status legend ---------- */
  .legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }
  .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 13.5px; font-weight: 600; }
  .badge.pending { background: var(--warn-soft); color: var(--warn); }
  .badge.ok { background: var(--ok-soft); color: var(--ok); }
  .badge.danger { background: var(--danger-soft); color: var(--danger); }
  .badge.mute { background: var(--mute-soft); color: var(--mute); }

  /* ---------- Callout ---------- */
  .callout {
    border: 1px solid var(--line); border-left: 4px solid var(--role-ink, var(--navy));
    background: var(--paper-raised); border-radius: 0 10px 10px 0;
    padding: 14px 16px; margin: 0 0 28px; box-shadow: var(--shadow);
  }
  .role[data-role="student"] .callout { --role-ink: var(--student-ink); }
  .role[data-role="teacher"] .callout { --role-ink: var(--teacher-ink); }
  .callout .c-title { font-weight: 600; margin-bottom: 4px; }
  .callout p { margin: 3px 0; color: var(--ink-muted); font-size: 14.5px; }

  /* ---------- FAQ ---------- */
  h3.section-h { font-size: 17px; margin: 34px 0 12px; font-weight: 600; }
  .faq { display: flex; flex-direction: column; gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
  .faq-item { background: var(--paper-raised); padding: 13px 16px; }
  .faq-item .q { font-weight: 600; font-size: 14.5px; }
  .faq-item .a { color: var(--ink-muted); font-size: 14px; margin-top: 3px; }

  /* ---------- Footer quick ref ---------- */
  footer { border-top: 1px solid var(--line); margin-top: 10px; padding-top: 30px; }
  footer h3 { font-size: 15px; margin-bottom: 12px; }
  footer p.small { color: var(--ink-muted); font-size: 13px; margin-top: 18px; }

  a { color: var(--navy-ink); }
</style>
</head>
<body>

<header class="masthead">
  <div class="masthead-inner">
    <a class="back-link" href="<?= h($homeHref) ?>">&larr; กลับหน้าแรก</a>
    <div class="eyebrow">คู่มือการใช้งาน</div>
    <h1>ระบบลงทะเบียนสอบนำเสนอโครงงาน</h1>
    <p>คู่มือสำหรับนักเรียน/นักศึกษา และครูที่ปรึกษา ครอบคลุมตั้งแต่การเข้าสู่ระบบ การลงทะเบียนขอสอบ ไปจนถึงการตรวจสอบคิวสอบ</p>
    <div class="site-box">
      <span class="dot" aria-hidden="true"></span>
      <span class="label">เข้าใช้งานที่</span>
      <span class="url mono"><?= h($siteUrl) ?></span>
    </div>
  </div>
</header>

<nav class="tabbar">
  <div class="tabbar-inner" role="tablist" aria-label="เลือกคู่มือตามบทบาท">
    <button class="tab-btn" data-role="student" role="tab" aria-selected="true"><span class="swatch"></span>คู่มือนักเรียน</button>
    <button class="tab-btn" data-role="teacher" role="tab" aria-selected="false"><span class="swatch"></span>คู่มือครูที่ปรึกษา</button>
  </div>
</nav>

<div class="wrap">

  <!-- ================= STUDENT ================= -->
  <section class="role" data-role="student">
    <div class="role-head"><h2>คู่มือนักเรียน</h2></div>
    <p class="role-sub">สำหรับนักเรียน/นักศึกษาที่ต้องการลงทะเบียนขอสอบนำเสนอโครงงาน และติดตามผลการอนุมัติ</p>

    <div class="cred-card">
      <div class="cred-cell">
        <div class="k">ชื่อผู้ใช้ (Username)</div>
        <div class="v">รหัสนักศึกษา</div>
        <div class="hint">ใช้รหัสประจำตัวนักศึกษาของท่าน เช่นเดียวกับที่ใช้ในระบบอื่นของวิทยาลัย</div>
      </div>
      <div class="cred-cell">
        <div class="k">รหัสผ่าน (Password)</div>
        <div class="v">เลขประจำตัวประชาชน 13 หลัก</div>
        <div class="hint">กรอกติดกันไม่ต้องเว้นวรรคหรือขีด</div>
      </div>
    </div>

    <h3 class="section-h">ขั้นตอนการลงทะเบียนขอสอบ</h3>
    <ol class="steps">
      <li>
        <div>
          <div class="step-title">เข้าสู่ระบบ</div>
          <div class="step-body"><p>เปิดเว็บไซต์ระบบ กรอก <span class="field">ชื่อผู้ใช้</span> และ <span class="field">รหัสผ่าน</span> ตามด้านบน แล้วกด "เข้าสู่ระบบ" ระบบจะพาไปหน้า "โครงงานของฉัน"</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ตรวจสอบวันสอบที่เปิดให้ลงทะเบียน</div>
          <div class="step-body"><p>ในหน้าแรกจะมีตาราง "วันสอบที่เปิดให้ลงทะเบียนได้ตอนนี้" — วันสอบจะแสดงในรายการนี้ก็ต่อเมื่อ <b>วันนี้ห่างจากวันสอบอย่างน้อย 7 วัน</b> เท่านั้น หากยังไม่เห็นวันที่ต้องการ แปลว่าใกล้วันสอบเกินไปแล้ว หรือแอดมินยังไม่เปิดวันสอบนั้น</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">กดปุ่ม "ลงทะเบียนสอบโครงงานใหม่"</div>
          <div class="step-body">
            <p>กรอกแบบฟอร์มให้ครบ โดยนักเรียนที่กรอกฟอร์มถือเป็น "ตัวแทนกลุ่ม" และจะถูกเพิ่มเป็นสมาชิกให้อัตโนมัติ:</p>
            <ul class="field-list">
              <li><span class="field">ชื่อโครงงาน</span></li>
              <li><span class="field">ประเภทการสอบ</span> — เลือก 1 ใน 3: "เสนอหัวข้อ", "นำเสนอความก้าวหน้า" หรือ "สอบจบโครงการ" (ดูรายละเอียดด้านล่าง)</li>
              <li><span class="field">วันสอบ</span> — เลือกจากวันที่เปิดให้ลงทะเบียนเท่านั้น</li>
              <li><span class="field">ครูที่ปรึกษาโครงงาน</span> — เลือกครู 1 ท่านที่จะเป็นผู้อนุมัติ</li>
              <li><span class="field">สมาชิกร่วมโครงงาน</span> — ติ๊กเลือกเพื่อนร่วมกลุ่มเพิ่มได้สูงสุด 4 คน (รวมตัวเองแล้วไม่เกิน 5 คน) ระบบแสดงให้เลือกเฉพาะนักเรียน<b>ระดับเดียวกัน</b> (ปวช. หรือ ปวส.) เท่านั้น</li>
              <li><span class="field">ไฟล์รายงานผลดำเนินงาน</span> — ต้องเป็นไฟล์ <code>.docx</code> เท่านั้น</li>
              <li><span class="field">ไฟล์นำเสนอ</span> — ต้องเป็นไฟล์ <code>.pptx</code> เท่านั้น</li>
            </ul>
          </div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ลงทะเบียนใหม่แยกทุกครั้งที่จะสอบแต่ละประเภท</div>
          <div class="step-body">
            <p>1 โครงงานต้องผ่านการสอบหลายรอบตามภาคเรียน แต่ละรอบต้องลงทะเบียนแยกกันใหม่ทุกครั้ง (กรอกฟอร์มใหม่ เลือกวันสอบใหม่ อัปโหลดไฟล์ใหม่) โดยอย่างน้อยต้องมี:</p>
            <ul class="field-list">
              <li>"เสนอหัวข้อ" <b>หรือ</b> "นำเสนอความก้าวหน้า" อย่างน้อย 1 ครั้ง</li>
              <li>"สอบจบโครงการ" 1 ครั้ง</li>
            </ul>
          </div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">รอครูที่ปรึกษาอนุมัติ</div>
          <div class="step-body"><p>หลังกดลงทะเบียน สถานะโครงงานจะเป็น "รอครูอนุมัติ" — ครูที่เลือกไว้จะเป็นผู้กดอนุมัติหรือไม่อนุมัติในระบบของท่านเอง นักเรียนไม่ต้องทำอะไรเพิ่มในขั้นนี้</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ติดตามผลที่หน้า "โครงงานของฉัน"</div>
          <div class="step-body"><p>เข้าสู่ระบบแล้วดูสถานะได้ตลอดเวลา เมื่อครูอนุมัติแล้ว ช่อง "คิว/เวลาสอบ" จะแสดงลำดับคิวและเวลาสอบจริงให้ทันที เช่น "ลำดับที่ 3 เวลา 09:40-10:00"</p></div>
        </div>
      </li>
    </ol>

    <h3 class="section-h">ประเภทการสอบทั้ง 3 แบบ</h3>
    <div class="table-scroll">
      <table class="rules">
        <thead><tr><th>ประเภท</th><th>ความหมาย</th></tr></thead>
        <tbody>
          <tr><td>เสนอหัวข้อ</td><td>สอบรอบแรก เสนอหัวข้อ/แนวคิดโครงงานให้กรรมการพิจารณาความเหมาะสม</td></tr>
          <tr><td>นำเสนอความก้าวหน้า</td><td>รายงานความคืบหน้าการทำโครงงานระหว่างภาคเรียน</td></tr>
          <tr><td>สอบจบโครงการ</td><td>สอบรอบสุดท้าย นำเสนอผลงานฉบับสมบูรณ์</td></tr>
        </tbody>
      </table>
    </div>

    <h3 class="section-h">สถานะโครงงานที่อาจพบ</h3>
    <div class="legend">
      <span class="badge pending">รอครูอนุมัติ</span>
      <span class="badge ok">อนุมัติแล้ว</span>
      <span class="badge danger">ไม่อนุมัติ</span>
      <span class="badge mute">ยกเลิก</span>
    </div>

    <div class="flow">
      <div class="flow-node">ลงทะเบียน</div>
      <div class="flow-arrow">→</div>
      <div class="flow-node pending">รอครูอนุมัติ</div>
      <div class="flow-arrow">→</div>
      <div class="flow-branch">
        <div class="flow-node ok">อนุมัติ → มีคิวสอบ</div>
        <div class="flow-node danger">ไม่อนุมัติ (มีเหตุผลแจ้ง)</div>
      </div>
    </div>

    <div class="callout">
      <div class="c-title">กำหนดเวลาที่ต้องรู้</div>
      <p>ลงทะเบียนล่วงหน้าอย่างน้อย <b>7 วัน</b> ก่อนวันสอบ และครูต้องกดอนุมัติก่อนวันสอบอย่างน้อย <b>2 วัน</b> โครงงานจึงจะมีชื่ออยู่ในคิวสอบ — ควรลงทะเบียนแต่เนิ่นๆ เพื่อให้ครูมีเวลาตรวจและอนุมัติทัน กติกานี้ใช้เหมือนกันทุกประเภทการสอบ</p>
    </div>

    <h3 class="section-h">คำถามที่พบบ่อย</h3>
    <div class="faq">
      <div class="faq-item">
        <div class="q">อัปโหลดไฟล์แล้วระบบไม่ยอมรับ ขึ้นว่า "ไฟล์ต้องเป็นนามสกุล .docx เท่านั้น"</div>
        <div class="a">ระบบตรวจทั้งนามสกุลไฟล์และชนิดไฟล์จริง ต้องเป็นไฟล์ Word (.docx) และ PowerPoint (.pptx) ที่แท้จริงเท่านั้น ไฟล์ PDF, รูปภาพ หรือไฟล์ที่แปลงนามสกุลเองใช้ไม่ได้</div>
      </div>
      <div class="faq-item">
        <div class="q">ขึ้นว่า "มีสมาชิกบางคนลงทะเบียนโครงงานอื่นในวันสอบนี้ไปแล้ว"</div>
        <div class="a">สมาชิกแต่ละคน (รวมตัวเอง) ลงทะเบียนได้เพียง 1 โครงงานต่อ 1 วันสอบ ตรวจสอบว่ามีเพื่อนในกลุ่มไปสมัครโครงงานอื่นไว้ในวันเดียวกันหรือไม่ (ลงทะเบียนคนละประเภทสอบในวันเดียวกันก็ยังถือว่าชนกัน)</div>
      </div>
      <div class="faq-item">
        <div class="q">ไม่เห็นวันสอบที่ต้องการในรายการให้เลือก</div>
        <div class="a">แปลว่าวันนั้นเหลือเวลาน้อยกว่า 7 วันแล้ว หรือแอดมินยังไม่เปิดวันสอบนั้น ให้ติดต่อผู้ดูแลระบบของวิทยาลัย</div>
      </div>
      <div class="faq-item">
        <div class="q">ไม่เห็นชื่อเพื่อนบางคนในรายชื่อสมาชิกร่วมโครงงาน</div>
        <div class="a">ระบบแสดงให้เลือกเฉพาะนักเรียนระดับเดียวกับผู้ลงทะเบียน (ปวช. หรือ ปวส.) เท่านั้น เพื่อนต่างระดับจะไม่ปรากฏในรายชื่อให้เลือก</div>
      </div>
      <div class="faq-item">
        <div class="q">โครงงานถูก "ไม่อนุมัติ" ทำอย่างไรต่อ</div>
        <div class="a">เหตุผลที่ครูระบุจะแสดงอยู่ใต้สถานะในหน้า "โครงงานของฉัน" ให้แก้ไขตามคำแนะนำแล้วลงทะเบียนใหม่ (หากยังอยู่ในกรอบเวลา 7 วันก่อนสอบ)</div>
      </div>
    </div>
  </section>

  <!-- ================= TEACHER ================= -->
  <section class="role" data-role="teacher" hidden>
    <div class="role-head"><h2>คู่มือครูที่ปรึกษา</h2></div>
    <p class="role-sub">สำหรับครู 2 บทบาท: <b>ครูที่ปรึกษา</b> ตรวจสอบและอนุมัติคำขอสอบของนักเรียนในความดูแล และ <b>คณะกรรมการสอบ</b> (ถ้าได้รับมอบหมายจากแอดมิน) ให้คะแนนตามแบบประเมิน — ครู 1 คนอาจมีทั้งสองบทบาทพร้อมกันได้</p>

    <div class="cred-card">
      <div class="cred-cell">
        <div class="k">ชื่อผู้ใช้ (Username)</div>
        <div class="v">อีเมลของวิทยาลัย</div>
        <div class="hint">ใช้อีเมล @ ของวิทยาลัยที่ทางวิทยาลัยออกให้</div>
      </div>
      <div class="cred-cell">
        <div class="k">รหัสผ่าน (Password)</div>
        <div class="v">เลขประจำตัวประชาชน 13 หลัก</div>
        <div class="hint">กรอกติดกันไม่ต้องเว้นวรรคหรือขีด</div>
      </div>
    </div>

    <h3 class="section-h">บทบาทที่ 1: ครูที่ปรึกษา — ตรวจและอนุมัติคำขอสอบ</h3>
    <ol class="steps">
      <li>
        <div>
          <div class="step-title">เข้าสู่ระบบ</div>
          <div class="step-body"><p>กรอก <span class="field">ชื่อผู้ใช้</span> (อีเมลวิทยาลัย) และ <span class="field">รหัสผ่าน</span> (เลขบัตรประชาชน) ระบบจะพาไปหน้า "โครงงานที่ฉันเป็นที่ปรึกษา"</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ดูรายการคำขอที่รอตรวจ</div>
          <div class="step-body"><p>หน้าแดชบอร์ดแสดงโครงงานทุกเรื่องที่มีชื่อท่านเป็นครูที่ปรึกษา พร้อมสถานะปัจจุบัน กดปุ่ม "รายละเอียด" ที่แถวใดก็ได้เพื่อดูข้อมูลเต็ม</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ตรวจรายละเอียดและไฟล์แนบ</div>
          <div class="step-body">
            <p>หน้ารายละเอียดแสดงข้อมูลครบสำหรับพิจารณา:</p>
            <ul class="field-list">
              <li>ประเภทการสอบ (เสนอหัวข้อ / นำเสนอความก้าวหน้า / สอบจบโครงการ)</li>
              <li>วันสอบ เวลา และจำนวนวันที่เหลือก่อนถึงวันสอบ</li>
              <li>รายชื่อสมาชิกทุกคนในกลุ่ม</li>
              <li>ลิงก์ดาวน์โหลด <span class="field">รายงานผลดำเนินงาน (.docx)</span> และ <span class="field">ไฟล์นำเสนอ (.pptx)</span> — กดเพื่อเปิดตรวจก่อนตัดสินใจ</li>
            </ul>
          </div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">กด "อนุมัติ" หรือ "ไม่อนุมัติ"</div>
          <div class="step-body">
            <p>หากข้อมูลและไฟล์ถูกต้อง กด <b>อนุมัติ</b> (ระบบจะถามยืนยันอีกครั้ง) โครงงานจะได้รับคิวสอบทันที</p>
            <p>หากยังไม่พร้อม ให้พิมพ์เหตุผลในช่องข้าง ๆ แล้วกด <b>ไม่อนุมัติ</b> — นักเรียนจะเห็นเหตุผลนี้ในระบบของตนเอง</p>
          </div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">ตรวจสอบคิวสอบที่ได้</div>
          <div class="step-body"><p>หลังอนุมัติ ช่อง "คิว/เวลาสอบ" ในหน้าแดชบอร์ดจะอัปเดตลำดับและเวลาสอบให้อัตโนมัติ (คำนวณตามลำดับเวลาที่กดอนุมัติของทุกโครงงานในวันนั้น)</p></div>
        </div>
      </li>
    </ol>

    <h3 class="section-h">สถานะโครงงานที่อาจพบ</h3>
    <div class="legend">
      <span class="badge pending">รอครูอนุมัติ</span>
      <span class="badge ok">อนุมัติแล้ว</span>
      <span class="badge danger">ไม่อนุมัติ</span>
      <span class="badge mute">ยกเลิก</span>
    </div>

    <div class="table-scroll">
      <table class="rules">
        <thead><tr><th>เงื่อนไข</th><th>ผลที่เกิดขึ้น</th></tr></thead>
        <tbody>
          <tr><td>เหลือเวลาน้อยกว่า 2 วันก่อนวันสอบ</td><td>ปุ่มอนุมัติ/ไม่อนุมัติจะหายไป ระบบขึ้นข้อความ "เลยกำหนดอนุมัติแล้ว" ให้ประสานผู้ดูแลระบบ</td></tr>
          <tr><td>คิวสอบวันนั้นเต็มแล้ว</td><td>กดอนุมัติไม่ได้ ระบบแจ้งให้ประสานผู้ดูแลระบบเพื่อเปิดวันสอบเพิ่ม</td></tr>
          <tr><td>กดอนุมัติเร็ว</td><td>ได้คิวสอบเวลาต้น ๆ ของวัน เพราะคิวเรียงตามลำดับเวลาที่อนุมัติจริง</td></tr>
        </tbody>
      </table>
    </div>

    <div class="callout">
      <div class="c-title">กำหนดเวลาที่ต้องรู้</div>
      <p>ต้องกดอนุมัติ <b>ก่อนวันสอบอย่างน้อย 2 วัน</b> โครงงานจึงจะมีชื่ออยู่ในคิวสอบ — ควรตรวจและอนุมัติทันทีที่นักเรียนส่งคำขอ อย่ารอใกล้วันสอบ เพราะระบบจะปิดกั้นการอนุมัติโดยอัตโนมัติเมื่อเลยกำหนด</p>
    </div>

    <h3 class="section-h">บทบาทที่ 2: คณะกรรมการสอบ — ให้คะแนนตามแบบประเมิน</h3>
    <p class="role-sub">แอดมินเป็นผู้มอบหมายว่าครูคนไหนเป็นกรรมการสอบของ "วันสอบ" ใดบ้าง กรรมการของวันนั้นจะให้คะแนนได้ทุกโครงงานที่ได้คิวสอบในวันนั้น ไม่ว่าจะเป็นที่ปรึกษาโครงงานนั้นเองหรือไม่ก็ตาม</p>
    <ol class="steps">
      <li>
        <div>
          <div class="step-title">เปิดเมนู "ให้คะแนนสอบ (คณะกรรมการ)"</div>
          <div class="step-body"><p>ปุ่มนี้อยู่บนแดชบอร์ดของครู จะเห็นรายชื่อ "วันสอบ" ทุกวันที่ท่านได้รับมอบหมายเป็นกรรมการ พร้อมโครงงานทั้งหมดที่ได้คิวสอบในแต่ละวัน</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">กด "ให้คะแนน" ที่โครงงานที่ต้องการ</div>
          <div class="step-body"><p>ระบบแสดงแบบประเมินตามประเภทการสอบของโครงงานนั้นโดยอัตโนมัติ (แบบประเมินของ "เสนอหัวข้อ", "นำเสนอความก้าวหน้า" และ "สอบจบโครงการ" มีหัวข้อต่างกัน) แต่ละหัวข้อมีคะแนนเต็มกำกับไว้ รวมทั้งหมด 60 คะแนน</p></div>
        </div>
      </li>
      <li>
        <div>
          <div class="step-title">กรอกคะแนนแต่ละหัวข้อแล้วบันทึก</div>
          <div class="step-body"><p>กรอกคะแนนให้ไม่เกินคะแนนเต็มของแต่ละหัวข้อ แล้วกด "บันทึกคะแนน" ระบบคำนวณคะแนนรวมให้อัตโนมัติ สามารถกลับมาแก้ไขคะแนนซ้ำได้ภายหลังหากจำเป็น</p></div>
        </div>
      </li>
    </ol>

    <h3 class="section-h">คำถามที่พบบ่อย</h3>
    <div class="faq">
      <div class="faq-item">
        <div class="q">กดอนุมัติไม่ได้ ปุ่มหายไปเฉย ๆ</div>
        <div class="a">ตรวจวันสอบของโครงงานนั้น หากเหลือเวลาน้อยกว่า 2 วัน ระบบจะปิดการอนุมัติอัตโนมัติเพื่อให้มีเวลาจัดคิวสอบ ต้องแจ้งผู้ดูแลระบบให้ช่วยดำเนินการ</div>
      </div>
      <div class="faq-item">
        <div class="q">ขึ้นว่า "คิวสอบวันนี้เต็มแล้ว"</div>
        <div class="a">ช่วงเวลาที่แอดมินเปิดไว้สำหรับวันสอบนั้นเต็มความจุแล้ว (คำนวณจากความยาวช่วงเวลา ÷ 20 นาทีต่อโครงงาน) แจ้งผู้ดูแลระบบให้เปิดวันสอบเพิ่มหรือขยายช่วงเวลา</div>
      </div>
      <div class="faq-item">
        <div class="q">ไม่อนุมัติไปแล้ว นักเรียนแก้ไขส่งใหม่ได้หรือไม่</div>
        <div class="a">นักเรียนสามารถลงทะเบียนโครงงานใหม่ได้ (นับเป็นรายการใหม่) ตราบใดที่ยังอยู่ในกรอบเวลาลงทะเบียนล่วงหน้า 7 วันก่อนวันสอบ</div>
      </div>
      <div class="faq-item">
        <div class="q">ไม่เห็นเมนู "ให้คะแนนสอบ" หรือเข้าหน้ากรอกคะแนนแล้วขึ้น "ท่านไม่ได้เป็นคณะกรรมการสอบของวันสอบนี้"</div>
        <div class="a">ท่านยังไม่ได้รับมอบหมายจากแอดมินให้เป็นกรรมการของวันสอบนั้น (คนละสิทธิ์กับการเป็นครูที่ปรึกษา) ติดต่อผู้ดูแลระบบให้ตรวจสอบการมอบหมาย</div>
      </div>
      <div class="faq-item">
        <div class="q">โครงงานที่ต้องให้คะแนนยังไม่มีหัวข้อประเมินให้กรอก</div>
        <div class="a">แจ้งผู้ดูแลระบบให้ไปตั้งค่าแบบประเมินของประเภทการสอบนั้นก่อน (หน้าจัดการแบบประเมินโครงการ ฝั่งแอดมิน)</div>
      </div>
    </div>
  </section>

  <footer>
    <h3>สรุปข้อมูลเข้าสู่ระบบโดยย่อ</h3>
    <div class="table-scroll">
      <table class="rules">
        <thead><tr><th>บทบาท</th><th>ชื่อผู้ใช้</th><th>รหัสผ่าน</th></tr></thead>
        <tbody>
          <tr><td>นักเรียน</td><td>รหัสนักศึกษา</td><td>เลขประจำตัวประชาชน 13 หลัก</td></tr>
          <tr><td>ครูที่ปรึกษา</td><td>อีเมลของวิทยาลัย</td><td>เลขประจำตัวประชาชน 13 หลัก</td></tr>
        </tbody>
      </table>
    </div>
    <p class="small">พบปัญหาการเข้าสู่ระบบหรือข้อมูลไม่ถูกต้อง ติดต่อผู้ดูแลระบบของวิทยาลัย</p>
  </footer>

</div>

<script>
  (function () {
    var buttons = document.querySelectorAll('.tab-btn');
    var sections = document.querySelectorAll('section.role');
    function select(role) {
      buttons.forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.role === role)); });
      sections.forEach(function (s) { s.hidden = s.dataset.role !== role; });
    }
    buttons.forEach(function (b) {
      b.addEventListener('click', function () { select(b.dataset.role); window.scrollTo({ top: 0, behavior: 'smooth' }); });
    });
  })();
</script>
</body>
</html>
