{{-- User Manual: Renewal Resolution (มติต่ออายุ) --}}

<h4><i class="bi bi-arrow-clockwise me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"มติต่ออายุ (Renewal Resolution)"</strong> คือพื้นที่จัดการ
    <strong>มติ ครม.</strong> เกี่ยวกับการ<strong>ต่ออายุ</strong>เอกสารแรงงานต่างด้าวที่หมดอายุ
    เช่น ต่อ Work Permit, ต่อ Visa, ต่ออายุ MOU
</p>
<p>
    คล้าย<strong>มติลงทะเบียน</strong> — แต่เน้นที่การต่ออายุของแรงงานที่อยู่ระบบอยู่แล้ว ไม่ใช่ลงทะเบียนใหม่
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้</li>
    <li><span class="manual-role">Caretaker</span> — ดูได้</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบมติต่ออายุ</strong> — แต่ละแถบเป็นรอบของมติต่ออายุ (เช่น "ต่ออายุ 2567 รอบ 1")</li>
    <li><strong>การ์ดนายจ้าง + ลูกจ้าง</strong> — ใช้กลไกเดียวกับมติลงทะเบียน</li>
    <li><strong>ตัวกรองความคืบหน้า</strong> — visa-only, work-permit-only, both</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. เลือกมติต่ออายุ</h5>
<div class="manual-step">
    คลิกที่ tab ของมติต่ออายุที่ต้องการ
</div>

<h5>2. ลงทะเบียนลูกจ้างเข้ามติ</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดการ์ดนายจ้าง</li>
        <li>กดปุ่ม "เพิ่มลูกจ้างเข้ามติ"</li>
        <li>เลือกลูกจ้างที่<strong>กำลังจะหมดอายุ</strong> (ระบบจะ highlight ให้)</li>
        <li>กด "ยืนยัน"</li>
    </ol>
</div>

<h5>3. ดูภาพรวมความคืบหน้า</h5>
<div class="manual-step">
    การ์ดสรุปบนสุดบอก: รวมลูกจ้างทั้งหมดในมติ, ทำเสร็จแล้ว, ยังเหลือ
</div>

<h5>4. Auto-apply</h5>
<div class="manual-step">
    ระบบ <strong>Workflow MOU Auto-apply</strong> ทำงานร่วมกับมตินี้
    — งานต่ออายุที่ทำใน Workflow จะถูก auto-apply กลับมาที่มติทุก 24 ชม.
</div>

<h5>5. Auto Settings — ตั้งค่าแยกแต่ละแถบมติ (Per-tab)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดแถบมติที่ต้องการ → กดปุ่ม <strong>"Auto Settings"</strong> ด้านบนขวา</li>
        <li>หัว popup จะแสดง <strong>ชื่อแถบ</strong> (เช่น "31/03/2026") + เตือนว่าตั้งค่านี้ใช้กับแถบนี้เท่านั้น</li>
        <li>กรอก:
            <ul>
                <li><strong>Auto Work Permit Expiry Date</strong> — วันหมดอายุ WP เป้าหมาย</li>
                <li><strong>Auto Visa Expiry Date</strong> — วันหมดอายุ Visa เป้าหมาย</li>
                <li><strong>Auto MOU Group</strong> — กลุ่ม MOU ที่ต้องการ</li>
            </ul>
        </li>
        <li>กด Save → ใช้กับ <strong>แถบนี้เท่านั้น</strong> ไม่กระทบแถบอื่น</li>
        <li>แต่ละแถบมี Auto Settings ของตัวเอง — ลูกจ้างใน tab 31/03/2026 จะถูกประเมิน color/progress ตาม setting ของ tab นั้น ไม่ใช่ tab อื่น</li>
    </ol>
</div>

<h5>6. ระบบสีลูกจ้าง (Progress Colors)</h5>
<div class="manual-step">
    ลูกจ้างในแต่ละการ์ดจะมีสีบอกความคืบหน้าตาม Auto Settings:
    <ul class="mb-0">
        <li>⚪ <strong>none</strong> = ยังไม่ได้ต่ออายุ</li>
        <li>🟦 <strong>visa_only</strong> = ต่อ visa แล้ว (รอ WP)</li>
        <li>🟧 <strong>work_permit_only</strong> = ต่อ WP แล้ว (รอ visa)</li>
        <li>🟩 <strong>both</strong> = ต่อครบแล้ว พร้อมปิดงาน</li>
        <li>✅ <strong>completed</strong> = ปิดงานแล้ว</li>
    </ul>
</div>

<h5>7. Auto-pull ลูกจ้างเข้าเมนูอัตโนมัติ</h5>
<div class="manual-step">
    ลูกจ้างที่มี WP หรือ Visa หมดอายุตรงกับ Auto Settings ของแถบใด → <strong>auto-pull เข้าแถบนั้น</strong>ทันทีเมื่อมีการอัพเดทวันที่
    <br>
    <strong>การ "add-only":</strong> ลูกจ้างที่อยู่ในเมนูแล้ว <strong>จะไม่ถูกดีดออก</strong>เมื่ออัพเดทวันที่ — สีจะเปลี่ยนตาม progress เท่านั้น (ต้องกดเสร็จสิ้น/ยกเลิก manual)
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>เริ่มต่ออายุก่อนหมด 60 วัน:</strong> ระบบจะ highlight ลูกจ้างที่ใกล้หมดอายุ
    ในเมนู Notifications + เมนู Incomplete Data
</div>

<div class="manual-tip">
    <strong>มติลงทะเบียน vs ต่ออายุ:</strong> ลงทะเบียน = ลูกจ้างใหม่เข้าระบบ, ต่ออายุ = ลูกจ้างเก่าที่จะหมดอายุ
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ลูกจ้างหมดอายุไปแล้ว ต่อได้ไหม?</dt>
    <dd>A: ขึ้นอยู่กับเงื่อนไขของมติ — บางมติอนุญาตให้ต่อย้อนหลังได้ ตรวจกฎกระทรวงก่อน</dd>

    <dt>Q: ทำไมต่ออายุไม่ได้?</dt>
    <dd>A: ตรวจว่าลูกจ้างอยู่ในสถานะ "ทำงานอยู่" (ไม่ใช่ลาออก/หมดสัญญา)</dd>
</dl>
