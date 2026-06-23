{{-- User Manual: Delegates --}}

<h4><i class="bi bi-people me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"ผู้แทนนายจ้าง (Delegates)"</strong> ใช้เก็บข้อมูลของ
    <strong>บุคคลที่นายจ้างมอบหมาย</strong>ให้ติดต่อกับสำนักงานเรา
    เช่น HR ของบริษัทลูกค้า, เลขาฯ, หรือพี่เลี้ยงที่ดูแลแรงงานหน้างาน
</p>
<p>
    <strong>หมายเหตุสำคัญ:</strong> ในเมนูซ้ายของบาง role อาจเห็นชื่อว่า "ข้อมูลพนักงาน"
    แต่จริงๆ คือ Delegates นี้ — <strong>ไม่ใช่ลูกจ้างต่างด้าว</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้</li>
    <li><span class="manual-role">Caretaker</span> — ดูได้</li>
    <li><span class="manual-role">Employer</span> — ดูได้เฉพาะของตัวเอง</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>ตารางผู้แทน</strong> — ชื่อ, นายจ้างที่ผูก, ตำแหน่ง, เบอร์โทร, อีเมล</li>
    <li><strong>ปุ่ม "+ เพิ่มผู้แทนใหม่"</strong> — สร้าง delegate</li>
    <li><strong>ตัวกรอง</strong> — กรองตามนายจ้าง, ค้นหาด้วยชื่อ/เบอร์</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. เพิ่มผู้แทนใหม่</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กด "+ เพิ่มผู้แทนใหม่"</li>
        <li>เลือกนายจ้าง</li>
        <li>กรอกชื่อ-นามสกุล, ตำแหน่ง, เบอร์โทร, อีเมล, ที่อยู่</li>
        <li>อัพโหลดสำเนาบัตรประชาชน (ถ้ามี)</li>
        <li>กด "บันทึก"</li>
    </ol>
</div>

<h5>2. แก้ไขข้อมูล</h5>
<div class="manual-step">
    คลิกที่ชื่อผู้แทนในตาราง → แก้ → บันทึก
</div>

<h5>3. ลบผู้แทน</h5>
<div class="manual-step">
    กดไอคอนถังขยะที่แถวของผู้แทน — ระบบจะถามยืนยัน
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Delegate ≠ Employee:</strong> Delegate = บุคคลที่ติดต่อแทนนายจ้าง (HR, เลขาฯ),
    Employee = ลูกจ้างต่างด้าวที่ทำงานจริงในไซต์งาน
</div>

<div class="manual-tip">
    <strong>1 นายจ้าง = หลาย delegate:</strong> นายจ้างคนหนึ่งอาจมี HR หลายคน, ทีมจัดซื้อ, ฯลฯ
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ทำไมเรียก Delegate ว่า "ข้อมูลพนักงาน" ในเมนู?</dt>
    <dd>A: เพราะใน role "Employer" ลูกค้าจะเห็นเมนูชื่อนี้แทน — เพื่อความเข้าใจง่ายในมุมของลูกค้า</dd>

    <dt>Q: ผู้แทน 1 คนผูกกับหลายนายจ้างได้ไหม?</dt>
    <dd>A: ไม่ได้ — ผู้แทน 1 record = 1 นายจ้าง ถ้าผู้แทนคนเดียวทำงานให้หลายบริษัท ต้องสร้างหลาย record</dd>
</dl>
