# Screenshot Tool — Training Manual

อัตโนมัติถ่าย screenshot ทุก step ของคู่มือ Training Edition → save เข้า `public/images/manuals/`

## เริ่มต้น (ครั้งแรก)

```bash
# 1. ติดตั้ง Chromium ให้ Playwright (มีอยู่แล้วใน package.json)
npx playwright install chromium

# 2. รัน Laravel app
php artisan serve
# default: http://127.0.0.1:8000
```

## วิธีใช้

```bash
# Run capture (default — ใช้ credentials ตามที่ตั้งไว้ใน script)
node screenshot-tool/capture.mjs
```

ใช้เวลา ~3-5 นาที (70 รูป) — รูปจะ save ที่ `public/images/manuals/{menu}/step.png` อัตโนมัติ

เปิด Training Bundle ที่ Super Admin → "Open Training Bundle" → รูปจะแสดงแทน placeholder ทันที

## ตัวเลือก (env vars)

```bash
# ใช้ URL อื่น (ถ้ารัน php artisan serve ที่ port อื่น)
APP_URL=http://127.0.0.1:9000 node screenshot-tool/capture.mjs

# Login ด้วย user อื่น
ADMIN_EMAIL=admin@local ADMIN_PASSWORD=secret node screenshot-tool/capture.mjs

# ถ่ายเฉพาะบาง screenshot (debug)
ONLY=dashboard/01-overview,workflow/01-main-view node screenshot-tool/capture.mjs

# เปิด browser ให้เห็น (debug — default headless)
HEADED=1 node screenshot-tool/capture.mjs
```

## แก้ไข Manifest

[`manifest.json`](manifest.json) — รายการ screenshot ทั้งหมด แก้ได้:

```json
{
  "key": "workflow/02-tick-step",         // path สำหรับ save (มี / ได้)
  "url": "/workflow",                     // URL ที่จะไป
  "actions": [                            // (optional) actions ก่อนถ่าย
    { "type": "click", "selector": "[data-bs-toggle='collapse']" },
    { "type": "wait",  "ms": 1000 }
  ]
}
```

**Action types:**
- `{ "type": "click", "selector": "..." }` — click element (ignored if not found)
- `{ "type": "wait",  "ms": 1000 }` — wait N milliseconds
- `{ "type": "waitFor", "selector": "..." }` — wait for element to appear
- `{ "type": "scrollTo", "selector": "..." }` — scroll element into view

**Placeholder URLs:**
- `{REGISTRATION_TAB_ID}` / `{RENEWAL_TAB_ID}` — auto-resolved ด้วย `needsTabId: "registration"` หรือ `"renewal"` flag

## หากบางรูปไม่ออก

1. **เมนูยังไม่มีข้อมูล:** หน้าจะ blank — ใส่ข้อมูล demo ก่อน แล้วรันใหม่
2. **Modal/popup ไม่เปิด:** ปรับ `selector` ใน manifest ให้ตรง element ที่ใช้ได้
3. **Selector ไม่เจอ:** ดู error log — ใช้ `HEADED=1` เพื่อเห็น browser
4. **Login fail:** ตรวจ credentials + แอพรันอยู่จริง

## หลังจาก capture เสร็จ

ถ้าอยากแก้รูป (crop, annotate, ใส่ลูกศร) — แก้ไฟล์ใน `public/images/manuals/` โดยตรง ระบบจะใช้ไฟล์ที่อัพเดต

ถ้า UI เปลี่ยน → รัน script ใหม่ → รูปอัพเดทตาม

---

## อัปเดต 2026-09-26 — สิ่งที่ต้องรู้ก่อนถ่ายภาพ

### 1. เตรียมเครื่องที่ใช้ถ่าย (เครื่อง dev เท่านั้น ห้ามใช้ production)
- **ทุกเมนูต้องเปิดอยู่และไม่มีรหัสผ่านเมนู:** ที่ Super Admin → Menu Visibility & Access ไม่อย่างนั้นจะได้แต่หน้า 403 / หน้า "กรอกรหัสผ่านเมนู"
  - ถ้าแก้ในฐานข้อมูลโดยตรง ให้รัน `php artisan cache:clear` ด้วย เพราะค่าเมนูถูก cache ไว้ 1 ชม.
  - ถ่ายเสร็จแล้วตั้งค่ากลับเหมือนเดิม
- **ต้องมีข้อมูลตัวอย่าง** ไม่อย่างนั้นหลายหน้าจะว่าง (Workflow, Pre-Production, การขาย, ผู้แทน/ตัวแทน/บริษัทนำเข้า, ลูกจ้างที่ถูกแจ้งออก, โปรไฟล์การเงิน)
  - ใช้ข้อมูลปลอมเท่านั้น (เช่นจาก Factory) **ห้ามใช้ข้อมูลลูกค้าจริง** เพราะคู่มือถูกแชร์ให้บุคคลภายนอกผ่านลิงก์ได้
  - สคริปต์สร้างข้อมูลตัวอย่างให้เก็บ**นอก repo** และตรวจว่าเป็น `APP_ENV=local` + SQLite ก่อนรัน (ดู `CLAUDE.md`)
- **บัญชี Super Admin ไม่ต้องตั้งภาษา** (ระบบใช้ภาษาไทยหลังล็อกอิน) หน้าที่ไม่ต้องล็อกอินจะถูกสลับเป็นภาษาไทยให้อัตโนมัติ

### 2. Browser
- ถ้ายังไม่ได้รัน `npx playwright install chromium` สคริปต์จะใช้ Google Chrome ที่ติดตั้งในเครื่องให้อัตโนมัติ
- หรือกำหนดเองด้วย `BROWSER_CHANNEL=chrome`

### 3. สคริปต์ตรวจหน้าก่อนบันทึก
สคริปต์จะไม่บันทึกภาพและจะรายงาน `✗` ถ้าหน้าที่ได้เป็นแบบใดแบบหนึ่งต่อไปนี้ (รูปเดิมจะไม่ถูกเขียนทับ):
- HTTP ≥ 400
- ถูกพาไปหน้า login
- ถูกพาไปหน้าปลดล็อกเมนู
- เป็นหน้า error ของระบบ

→ ถ้าเห็น `✗` ให้แก้การตั้งค่าเครื่องตามข้อ 1 แล้วรันเฉพาะรายการนั้นด้วย `ONLY=...`

### 4. รายการพิเศษใน manifest
- `"guest": true` = ถ่ายแบบยังไม่ล็อกอิน เช่น หน้า login, ลืมรหัสผ่าน
- `"actions"` ที่หา element ไม่เจอจะถูกข้าม (`↷`) → ภาพอาจเป็นหน้ารายการแทนหน้าต่าง ให้ตรวจภาพทุกครั้งหลังถ่าย

### 5. หลังถ่ายภาพ
- **ตรวจภาพด้วยตาทุกรูป:** หน้าว่าง / ภาพซ้ำ / ภาพที่ไม่ตรงคำบรรยาย
- **รูปที่เพิ่มใหม่ต้องมีที่ใช้ในคู่มือ:** เพิ่ม `@include('manuals.training._screenshot', ['src' => '{menu}/{file}', ...])` ในคู่มือฝึกอบรมทั้ง 4 ภาษา
