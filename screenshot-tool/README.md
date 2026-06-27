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
