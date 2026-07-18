# Changelog 15-07-2026

## Summary

พัฒนาระบบ CRM Enquiry ตาม spec และปรับปรุง flow หลักของ Laravel CRM ให้ใช้งานจริงได้มากขึ้น ตั้งแต่ permission/role, migration, seed, UI หน้า Enquiry/GIS, workflow assignment/status, ไปจนถึงระบบคัดกรอง spam lead แยกออกจาก Inbox

## Laravel CRM Foundation

- รัน migration พื้นฐานของระบบ CRM แล้ว
- เพิ่ม role/permission seed พื้นฐานสำหรับ CRM
- เพิ่มความสัมพันธ์ `users.primary_role_id` เพื่อเชื่อม user กับ role หลัก
- backfill role ให้ user เดิม และ sync permission ตอน login
- แก้ปัญหาเข้า `/enquiry` แล้วเจอ `403 This action is unauthorized.`
- ตรวจสอบ route/login flow ของ `/crm-login-system` และ permission fallback

## Enquiry Workflow

- เพิ่ม workflow fields ให้ `enquiries` และ `gis_enquiries`
- เพิ่ม assignment flow สำหรับ sale/sale manager
- เพิ่ม status flow เช่น Lead/MQL, SQL, Prospect, Customer
- เพิ่ม soft delete/restore สำหรับ enquiry records
- เพิ่ม activity log สำหรับการสร้าง record, assign, เปลี่ยน status, delete/restore
- ปรับ API/web actions ให้ redirect กลับหน้าเดิมเมื่อเรียกจาก form และตอบ JSON เมื่อเรียกแบบ API

## CRM Enquiry UI

- ปรับหน้า CRM Enquiry ให้เป็น interface แนว CRM dashboard
- เพิ่ม metric cards เช่น Total, Unassigned, Customers, Suspected Spam
- เพิ่ม filter/search/sort สำหรับ record
- เพิ่ม action controls ในตาราง เช่น assign, update status, delete, restore
- เพิ่มหน้า GIS Enquiry ในรูปแบบเดียวกัน
- ปรับ Blade template และแก้ปัญหา compiled view จาก inline `@php`

## Spam Filtering

- เพิ่ม migration สำหรับ spam review columns:
  - `spam_status`
  - `spam_score`
  - `spam_reasons`
  - `spam_checked_at`
  - `spam_reviewed_by`
  - `spam_reviewed_at`
- เพิ่ม service `App\Services\Spam\EnquirySpamScorer`
- เพิ่ม command `php artisan enquiries:score-spam`
- เพิ่ม tab สำหรับแยก record:
  - Inbox
  - Suspected Spam
  - Confirmed Spam
  - Marked Valid
- Inbox ถูกปรับให้แสดงเฉพาะ `spam_status = clean`
- ปิดช่อง `spam=all` ที่อาจทำให้ spam หลุดกลับมาใน Inbox
- เพิ่ม action ให้ user ย้าย record เป็น suspected spam, confirm spam, หรือ mark valid
- record ใหม่จะถูก score อัตโนมัติตอน submit

## Spam Rules Added

- จับชื่อ random mixed-case เช่น `cWgMqSDJybwMKELixZ`
- ไม่ flag ชื่อคนที่อ่านได้ เช่น `Vasuda Leedhirakul` และ `VasudaLerpankul`
- จับ pattern จากข้อมูลจริงที่ยังหลุด Inbox:
  - ชื่อ/บริษัท/ข้อความแบบ mixed-case random เช่น `sEQEXjIClkLAasGgE`
  - email local-part ที่ถูกหั่นด้วยจุดถี่ เช่น `a.we.pu.d.u.qo.t.ad0.4@gmail.com`
  - field หลายส่วนที่เป็น random string พร้อมกัน
- re-score existing records แล้ว ผลล่าสุด:
  - Scanned: 111
  - Suspected: 55
  - Clean: 56

## Tests

- เพิ่ม unit tests สำหรับ `EnquirySpamScorer`
- เพิ่ม feature tests สำหรับ Inbox filter
- เพิ่ม test กัน legacy query `?spam=all` bypass spam filter
- ผลทดสอบล่าสุด:

```bash
php artisan test
```

ผ่านทั้งหมด:

```text
Tests: 11 passed
```

## Commands Run

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan enquiries:score-spam --dry-run
php artisan enquiries:score-spam
php artisan optimize:clear
php artisan test
```

## Important Notes

- ไม่มีการลบ spam records ออกจาก DB
- records ที่น่าสงสัยจะถูกย้ายสถานะไป `suspected`
- records ที่ user ยืนยันว่าไม่ใช่ spam จะอยู่ใน tab `Marked Valid`
- Inbox ตั้งใจให้เป็นพื้นที่ทำงานสะอาดสำหรับ lead ที่ `clean` เท่านั้น
- ถ้ายังมี spam รูปแบบใหม่หลุดมา ควรเก็บตัวอย่างจริงแล้วเพิ่ม rule/test จาก pattern นั้นต่อ
