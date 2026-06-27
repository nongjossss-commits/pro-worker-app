# Training Manual Screenshots

วางไฟล์ภาพประกอบของ Training Edition Manual ที่นี่

## โครงสร้างโฟลเดอร์

```
public/images/manuals/
├── workflow/
│   ├── 01-main-view.png
│   ├── 02-tick-step.png
│   ├── 03-add-employee-modal.png
│   ├── 04-notify-out.png
│   └── 05-mou-import.png
├── employees/
│   ├── 01-list-view.png
│   ├── 02-add-employee.png
│   ├── 03-edit-employee.png
│   ├── 04-preview-popup.png
│   └── 05-bulk-actions.png
└── (เพิ่มเมนูอื่นเมื่อขยาย Phase ต่อไป)
```

## รูปแบบไฟล์ที่รองรับ

- `.png` (แนะนำ — คุณภาพดี)
- `.jpg` / `.jpeg`
- `.webp`
- `.gif` / `.svg`

ระบบจะหาไฟล์ตามลำดับ extension นี้

## การถ่าย Screenshot

1. ใช้ resolution อย่างน้อย **1920x1080** (Full HD) เพื่อภาพคมชัดบน slide projector
2. **Crop** เฉพาะส่วนที่เกี่ยวข้อง (ไม่ต้องใส่ taskbar/browser tabs)
3. ใช้ **ลูกศร / กรอบสีแดง** highlight จุดที่ต้องการเน้น (ใช้ Snipping Tool / Greenshot / Lightshot)
4. บีบขนาดไฟล์ให้เล็ก (ผ่าน TinyPNG / Squoosh) — ขนาดไม่ควรเกิน **500 KB ต่อรูป**

## ถ้ายังไม่ใส่รูป

ระบบจะแสดง **placeholder กล่อง** สวยๆ พร้อมคำอธิบาย — คู่มือยังใช้งานได้ปกติ แค่จะมีตัวอย่างเช่นกล่อง:

```
┌────────────────────────────┐
│   📷 [ ภาพประกอบ ]          │
│   ยังไม่ได้ใส่ภาพประกอบ      │
│   public/images/manuals/...│
└────────────────────────────┘
```

## หลังใส่รูปแล้ว

- รีโหลดหน้า Training Bundle (`Super Admin → Branding → Open Training Bundle`)
- รูปจะแสดงโดยอัตโนมัติแทนที่ placeholder
- ไม่ต้องแก้โค้ดเลย
