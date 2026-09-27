{{-- User Manual: P Production --}}

<h4><i class="bi bi-clipboard-data-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"P Production"</strong> คือศูนย์รวมงานทั้งหมดที่กำลังดำเนินการอยู่ในสำนักงาน
    ตั้งแต่ลูกค้าใหม่ที่ปิดการขายจากเมนู Sales แล้ว → เข้าสู่ขั้นตอนเตรียมเอกสาร (Pre-Production)
    → ส่งต่อให้ Workflow ดำเนินการต่อ
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Caretaker</span> — ทำงานประจำวันได้ (ข้อมูลลูกจ้าง, นัดหมาย) แต่สร้างงาน/ตั้งค่าขั้นตอน/แก้ข้อมูลการเงินไม่ได้ และติ๊กขั้นตอนไม่ได้ถ้าไม่ได้รับสิทธิ์</li>
    <li><span class="manual-role">Employer</span> (บัญชีลูกค้า) — เข้าเมนูนี้ไม่ได้</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>การ์ดสรุปสถิติ</strong> ด้านบน — จำนวนงานที่ใกล้ครบกำหนด, กำลังทำ, รอตรวจ</li>
    <li><strong>แถบตัวกรอง</strong> — กรองตามนายจ้าง, เจ้าของงาน, ประเภทงาน (MOU/Visa)</li>
    <li><strong>การ์ดงาน</strong> — แต่ละการ์ดมีรูปเซลล์ + ชื่อนายจ้าง + จำนวนลูกจ้าง + สถานะแต่ละขั้นตอน</li>
    <li><strong>ปุ่ม "Send to Workflow"</strong> — ส่งงานเข้าสู่ขั้นตอน Workflow (เฉพาะ role ที่มีสิทธิ์ approve-production)</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. เปิดงานเพื่อดูรายละเอียด</h5>
<div class="manual-step">
    กดที่การ์ดงาน → เข้าหน้า Edit Job ที่มีหลายแท็บ: ลูกจ้าง, เอกสาร, การเงิน, ระยะเวลา
</div>

<h5>2. แก้ไขข้อมูลทีละลูกจ้าง</h5>
<div class="manual-step">
    ในแท็บ "ลูกจ้าง" — แต่ละการ์ดลูกจ้างมีปุ่มแก้ไข, ดูเอกสาร, อัพโหลดภาพ
    ใช้ฟังก์ชัน <strong>Document Scanner</strong> ถ่ายรูปจากกล้องเข้าระบบได้เลย
</div>

<h5>3. เพิ่ม Custom Field</h5>
<div class="manual-step">
    บางงานมีข้อมูลเพิ่มเติมพิเศษ — กดปุ่ม "Fields" บนการ์ด → เพิ่มฟิลด์ใหม่ตามต้องการ
    (เช่น "เลขที่ใบรับรองแพทย์", "วันที่ปฐมนิเทศ")
</div>

<h5>4. ส่งงานเข้า Workflow</h5>
<div class="manual-step">
    เมื่อเอกสารพร้อมแล้ว → กดปุ่ม <strong>"Send to Workflow"</strong>
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        เฉพาะ role ที่มีสิทธิ์ <code>approve-production</code> (Admin/Super Admin) เท่านั้น
    </div>
</div>

<h5>5. ส่งทั้งใบ (Bulk Send)</h5>
<div class="manual-step">
    ถ้ามีหลายลูกจ้างในใบ MOU เดียวกัน — กดปุ่ม "ส่งทั้งใบ" ที่การ์ด MOU เพื่อส่งครั้งเดียว
</div>

<h5>6. แท็บการเงิน (Financial Tab) ในใบงาน</h5>
<div class="manual-step">
    เมื่อเปิด Edit Job → คลิก tab <strong>"Financial"</strong> หรือกดปุ่ม "การเงิน" ที่การ์ดนายจ้าง
    <ul class="mb-0">
        <li><strong>สร้างแท็บการเงินหลายอัน</strong>ในใบงานเดียวได้ (เช่น "ค่าบริการ MOU พม่า", "งวดเปลี่ยนนายจ้าง")
            กด <strong>"+ เพิ่มแท็บ"</strong> → ตั้งชื่อแท็บ (บังคับ — ห้ามว่าง / ห้ามซ้ำ)</li>
        <li>กดไอคอน <i class="bi bi-pencil-square"></i> หรือ <strong>double-click ที่ชื่อแท็บ</strong> เพื่อเปลี่ยนชื่อ</li>
        <li>กดไอคอน <i class="bi bi-trash"></i> เพื่อลบแท็บ (มี confirm + แสดงผลกระทบ)</li>
    </ul>
</div>

<h5>7. ตั้งราคา per-head + หมายเหตุงวด</h5>
<div class="manual-step">
    ในแท็บการเงิน เลือกโหมด <strong>"ต่อหัว (Per-head)"</strong>:
    <ul class="mb-0">
        <li>เพิ่มงวดราคา (Tier) — แต่ละงวดมี ราคา + จำนวนคน + <strong>หมายเหตุ</strong></li>
        <li>คลิกที่ <strong>กล่องหมายเหตุ</strong>หรือไอคอน <i class="bi bi-pencil-square"></i> เพื่อเปิด popup ใหญ่แก้ไข (มี counter 500 ตัวอักษร + Ctrl+Enter บันทึก)</li>
        <li>หมายเหตุนี้จะแสดงในใบแจ้งหนี้/ใบเสร็จที่ออกตามงวดนี้ด้วย</li>
        <li>กด <i class="bi bi-trash"></i> ลบงวด — มี confirm + เตือนถ้ามีลูกจ้าง assigned อยู่</li>
    </ul>
</div>

<h5>8. จัดทีมใน Pre-Production</h5>
<div class="manual-step">
    กดปุ่ม <strong>จัดทีม</strong> บนการ์ดลูกจ้าง → เลือกหรือสร้างทีม · รายชื่อลูกจ้างจะจัดกลุ่มตามทีมเหมือนใน Workflow ·
    แก้ชื่อ/ลบทีมได้จากป้ายทีม · ปุ่ม "ไม่มีทีม" เอาลูกจ้างออกจากทีม
</div>

<h5>9. จัดการขั้นตอนเตรียมงาน (ปุ่ม Steps)</h5>
<div class="manual-step">
    เพิ่ม / เปลี่ยนชื่อ / ลบ / ลากเรียงลำดับขั้นตอนเตรียมงานได้ในหน้าต่างเดียว ไม่ต้องรีโหลดหน้า
    (ขั้นตอนของ Pre-Production แยกจากขั้นตอนของ Workflow)
</div>

<h5>10. แท็บแบบหลายการ์ด กับการส่งเข้า Workflow</h5>
<div class="manual-step">
    แท็บที่ตั้งเป็น <strong>หลายการ์ด</strong> (เช่น MOU นำเข้า) — การ์ดแต่ละใบถูกส่งเข้า Workflow เป็นงานแยกกัน ไม่ถูกรวมเข้ากับการ์ดอื่นของนายจ้างเดียวกัน
</div>

<h5>11. ตรวจข้อมูลซ้ำ + โหมดเช็คงาน</h5>
<div class="manual-step">
    ตอนเพิ่มลูกจ้างใหม่ ระบบเตือนถ้าเลขพาสปอร์ต / ใบอนุญาตทำงาน / บัตรชมพู / เลขประจำตัว / เลข RA ซ้ำกับลูกจ้างที่มีอยู่ ·
    เมนูนี้เป็น 1 ใน 4 เมนูของ <strong>โหมดเช็คงาน</strong> (ดูรายละเอียดในคู่มือ Workflow)
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>ดูสถานะรวม:</strong> สีของการ์ดบอกความเร่งด่วน — เหลือง = ใกล้ครบกำหนด, แดง = เลยกำหนด
</div>

<div class="manual-tip">
    <strong>Pre-Production vs Workflow:</strong> งานที่อยู่ในเมนูนี้เป็น Pre-Production (เตรียมเอกสาร)
    หลังกด Send to Workflow แล้วงานจะย้ายไปอยู่ในเมนู "Workflow"
</div>
<div class="manual-tip">
    <strong>ส่งข้อมูลไปแชท LINE:</strong> ลากปุ่ม <i class="bi bi-grid-3x2-gap-fill"></i> บนการ์ดไปวางในแชท หรือคลิกเพื่อคัดลอกข้อความ / รูปการ์ด / แชร์ — ดูรายละเอียดที่คู่มือเมนูลูกจ้าง ข้อ 11
</div>

<div class="manual-warn">
    <strong>ระวัง:</strong> งานที่ส่งไป Workflow แล้ว แก้ไขใน Production ไม่ได้ — ต้องไปแก้ใน Workflow
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ทำไมไม่เห็นปุ่ม "Send to Workflow"?</dt>
    <dd>A: role ของคุณไม่มีสิทธิ์ approve-production — ติดต่อ Admin ให้เพิ่มสิทธิ์</dd>

    <dt>Q: งานหายไปไหน หลังกด Send to Workflow?</dt>
    <dd>A: ย้ายไปอยู่ในเมนู "Workflow" — กรองด้วยชื่อนายจ้างก็เจอ</dd>

    <dt>Q: ติ๊กขั้นตอนเตรียมงานไม่ได้?</dt>
    <dd>A: บัญชีนั้นยังไม่มีสิทธิ์ "อัปเดตขั้นตอนความคืบหน้า" — ให้ Admin เปิดสิทธิ์ที่เมนูจัดการผู้ใช้</dd>

    <dt>Q: ลูกจ้างที่ลาออกระหว่างทำงาน Production?</dt>
    <dd>A: เปิดการ์ดลูกจ้าง → กด "ลาออก" — งานจะถูกถอดออกจาก Production อัตโนมัติ</dd>
</dl>
