{{-- User Manual: Employment History (ประวัติการจ้างงาน) --}}

<h4><i class="bi bi-person-badge me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"ประวัติการจ้างงาน"</strong> รวบรวม<strong>ลูกจ้างทุกคน</strong>ที่เคยอยู่ในระบบ
    ไม่ว่าจะ <em>ทำงานปกติ</em>, <em>แจ้งออกแล้ว (Resigned)</em>, <em>ครบสัญญา</em>, หรือ <em>เปลี่ยนนายจ้างไปแล้ว</em>
    ใช้สำหรับ <strong>ดูประวัติย้อนหลัง</strong>, <strong>ค้นหาลูกจ้างเก่า</strong>, และ <strong>ย้ายนายจ้าง</strong>ลูกจ้างที่ออกแล้วเข้าใหม่
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Caretaker</span> — เห็นเฉพาะลูกจ้างของนายจ้างที่ตัวเองดูแล</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบกรอง</strong> ด้านบน — ค้นหา, กรองตามสัญชาติ/ประเภท MOU/บัตรชมพู/ประเภทพาสปอร์ต</li>
    <li><strong>มุมขวาบน</strong> — ปุ่ม Export CSV, สลับมุมมอง (การ์ด/ตาราง), เลือกจำนวนรายการต่อหน้า</li>
    <li><strong>รายการลูกจ้าง</strong> — แสดงครบทุกคนทั้งที่ active และไม่ active (รวมที่ลาออก/ครบสัญญาแล้ว)</li>
    <li><strong>Bulk Action Bar</strong> (ลอยล่าง) — เมื่อ tick ลูกจ้างหลายคน → ย้ายนายจ้าง / Export / สร้าง PDF</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. ค้นหาลูกจ้างย้อนหลัง</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>พิมพ์ชื่อ / passport ในช่อง "ค้นหา..."</li>
        <li>เลือกตัวกรองอื่นๆ (สัญชาติ / MOU / พาสปอร์ต) ตามต้องการ</li>
        <li>กด "กรอง" — ผลลัพธ์รวมลูกจ้าง active + ไม่ active</li>
        <li>กด "ล้างค่า" เพื่อรีเซ็ตการกรอง</li>
    </ol>
</div>

<h5>2. ย้ายลูกจ้างเก่าให้นายจ้างใหม่ (Bulk Transfer)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Tick checkbox ลูกจ้างที่ต้องการย้าย (เลือกได้หลายคน)</li>
        <li>Bulk Action Bar เด้งขึ้นด้านล่าง → กด "Actions" → <strong>"ย้ายนายจ้าง"</strong></li>
        <li>เลือกนายจ้างปลายทาง → ยืนยัน</li>
        <li>ระบบจะย้ายลูกจ้างทุกคนไปอยู่นายจ้างใหม่ทันที</li>
    </ol>
</div>

<h5>3. Export ข้อมูลเป็น CSV / Excel</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กรองข้อมูลที่ต้องการก่อน (ถ้าต้องการเฉพาะกลุ่ม)</li>
        <li>กดปุ่ม "Export CSV" ด้านขวาบน — ดาวน์โหลดไฟล์ทันที</li>
        <li>หรือ Bulk Action Bar → "Advanced Export" — เลือกคอลัมน์เองได้</li>
    </ol>
</div>

<h5>4. สร้าง PDF อัตโนมัติแบบ Batch</h5>
<div class="manual-step">
    Tick ลูกจ้างหลายคน → Bulk Action Bar → "Automated PDF" → เลือก template → ระบบสร้าง PDF ครบทุกคน
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>ต่างจากเมนู "ข้อมูลลูกจ้าง" อย่างไร:</strong> เมนูลูกจ้างปกติแสดงเฉพาะลูกจ้างที่ active
    ส่วนเมนูนี้แสดง<strong>ทุกคน</strong>รวมที่ลาออก/ครบสัญญา/แจ้งออกแล้ว — ใช้เมื่อต้องหาคนเก่าหรือดูประวัติย้อนหลัง
</div>

<div class="manual-tip">
    <strong>มุมมองตาราง vs การ์ด:</strong> ตารางเหมาะกับการเปรียบเทียบข้อมูลหลายคนพร้อมกัน,
    การ์ดเหมาะกับการดูรายละเอียดทีละคนพร้อมรูปและบาดจ์
</div>

<div class="manual-warn">
    <strong>การย้ายนายจ้าง:</strong> ลูกจ้างที่ย้ายแล้ว <strong>employer_id จะเปลี่ยน</strong>
    ระบบจะ <strong>auto-cancel</strong> notify_out ที่ยังค้างอยู่ของลูกจ้างคนนั้นทันที (ถ้ามี)
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ทำไมเห็นลูกจ้างน้อยกว่าที่ควร?</dt>
    <dd>A: ตรวจสิทธิ์ของผู้ใช้ — ถ้าเป็น Caretaker จะเห็นเฉพาะลูกจ้างของนายจ้างที่ตัวเองดูแลเท่านั้น</dd>

    <dt>Q: ย้ายนายจ้างแล้วลูกจ้างหายไปจากเมนู notify_out ทันที?</dt>
    <dd>A: ถูกต้อง — เมื่อ employer_id เปลี่ยน ระบบ auto-cancel notify_out pending ของลูกจ้างคนนั้น เพราะ notify_out ใช้สำหรับ "ออกจากนายจ้างเก่า" ที่ไม่เกี่ยวข้องแล้ว</dd>

    <dt>Q: ลูกจ้างที่ลบไป (trash) เห็นในเมนูนี้ไหม?</dt>
    <dd>A: ไม่เห็น — ต้องไปดูที่เมนู "ถังขยะกลาง" (Central Trash) — สามารถกู้คืนกลับมาได้</dd>

    <dt>Q: Export CSV รวมคอลัมน์อะไรบ้าง?</dt>
    <dd>A: ขั้นพื้นฐาน — ใช้ "Advanced Export" เพื่อเลือกคอลัมน์เอง (รวม MOU, วันหมดอายุ, สถานะ ฯลฯ)</dd>
</dl>
