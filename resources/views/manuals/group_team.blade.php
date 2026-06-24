{{-- User Manual: Group & Team (กลุ่มและทีม) --}}

<h4><i class="bi bi-people-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"กลุ่มและทีม (Group &amp; Team)"</strong> ใช้สำหรับ<strong>จัดกลุ่มลูกจ้าง</strong>เป็นทีมย่อยๆ
    เพื่อให้สามารถจัดการเป็นหน่วยได้ เช่น <em>"ทีมโรงงาน A กะเช้า"</em>, <em>"ทีมแม่บ้าน"</em>, <em>"ทีมงานก่อสร้าง"</em>
    ใช้ในการ <strong>สร้าง Production / Workflow แบบกลุ่ม</strong>, <strong>การวางบิลแบบ batch</strong>, และ <strong>จัดเก็บข้อมูลให้เป็นระเบียบ</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Caretaker</span> — จัดการเฉพาะกลุ่มของนายจ้างที่ตัวเองดูแล</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<p>เมื่อเข้าเมนู จะเจอ <strong>2 ทางเลือก</strong>:</p>
<ol>
    <li><strong>Affiliated with Employer (กลุ่มสังกัดนายจ้าง)</strong> — กลุ่มที่ผูกกับนายจ้างใดนายจ้างหนึ่ง ลูกจ้างในกลุ่มต้องอยู่ภายใต้นายจ้างนั้น</li>
    <li><strong>Independent / No Employer (กลุ่มอิสระ)</strong> — กลุ่มไม่ผูกนายจ้างใด ลูกจ้างจากนายจ้างไหนก็ใส่ได้</li>
</ol>
<p>ทั้งสองแบบมีหน้า <strong>Manage</strong> ที่แสดง <strong>Accordion ของแต่ละกลุ่ม</strong> + <strong>ทีมย่อย</strong>ภายในกลุ่ม</p>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. สร้างกลุ่มใหม่</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เข้าเมนู "กลุ่มและทีม" → เลือกประเภทกลุ่ม (Affiliated / Independent)</li>
        <li>ถ้าเป็น Affiliated → เลือกนายจ้าง</li>
        <li>กดปุ่ม "<strong>+ สร้างกลุ่มใหม่</strong>"</li>
        <li>ตั้งชื่อกลุ่ม (เช่น "ทีมโรงงาน A กะเช้า") → ยืนยัน</li>
    </ol>
</div>

<h5>2. เพิ่มลูกจ้างเข้ากลุ่ม</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิด accordion ของกลุ่ม → กด "+ เพิ่มสมาชิก"</li>
        <li>กรอกชื่อค้นหา / passport → เลือกลูกจ้างจากรายการ → ยืนยัน</li>
        <li>Affiliated: เห็นเฉพาะลูกจ้างของนายจ้างนั้น</li>
        <li>Independent: เห็นลูกจ้างทุกคนในระบบ</li>
    </ol>
</div>

<h5>3. แบ่งทีมย่อยภายในกลุ่ม</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดกลุ่ม → กด "<strong>+ สร้างทีมย่อย</strong>"</li>
        <li>ตั้งชื่อทีม (เช่น "ทีม A1", "ทีม A2")</li>
        <li>ลากลูกจ้างเข้าทีม (Drag &amp; Drop) หรือกด "เพิ่ม" ในทีม</li>
    </ol>
</div>

<h5>4. ใช้กลุ่มในการสร้างงาน</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดเมนู Pre-Prod / Workflow / Production</li>
        <li>เมื่อกด "+ เพิ่มงาน" → ระบุ Group Name ที่สร้างไว้</li>
        <li>ระบบจะนำลูกจ้างในกลุ่มมาใช้ร่วมกัน — จัดการได้เป็นหน่วยเดียว</li>
    </ol>
</div>

<h5>5. ย้าย / ลบ / เปลี่ยนชื่อกลุ่ม</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิด accordion ของกลุ่ม → ไอคอน <i class="bi bi-pencil-square"></i> เปลี่ยนชื่อ</li>
        <li>ไอคอน <i class="bi bi-trash"></i> ลบกลุ่ม (ลูกจ้างจะถูกถอดออกจากกลุ่ม แต่ไม่ลบลูกจ้าง)</li>
        <li>ลาก-วางลูกจ้างระหว่างทีมหรือนอกกลุ่มได้</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>เลือกประเภทกลุ่มให้เหมาะ:</strong>
    <ul class="mb-0">
        <li><strong>Affiliated</strong> — เหมาะกับลูกจ้างของนายจ้างเดียวกัน เช่น "ลูกจ้างโรงงาน ABC ทั้งหมด"</li>
        <li><strong>Independent</strong> — เหมาะกับลูกจ้างข้ามนายจ้าง เช่น "ลูกจ้างที่ต้องไปสัมภาษณ์วันเดียวกัน" รวมจากหลายโรงงาน</li>
    </ul>
</div>

<div class="manual-tip">
    <strong>Group Name ในงาน:</strong> ตอนสร้าง Production Order หรือ Workflow item — ระบุ Group Name ที่ตรงกับชื่อกลุ่มที่นี่ ระบบจะ link ให้อัตโนมัติ
</div>

<div class="manual-warn">
    <strong>การลบกลุ่ม:</strong> ลบกลุ่ม = ถอดลูกจ้างออกจากกลุ่มเท่านั้น ไม่ลบลูกจ้างจากระบบ
    แต่ <strong>ลบทีมย่อย</strong> ลูกจ้างในทีมจะกลับไปอยู่ในกลุ่มหลัก (ไม่หาย)
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ลูกจ้างคนหนึ่งอยู่ได้กี่กลุ่ม?</dt>
    <dd>A: <strong>หลายกลุ่ม</strong>ได้พร้อมกัน — เช่นอยู่ทั้งใน "ลูกจ้างโรงงาน A" (Affiliated) และ "ทีมไปสัมภาษณ์ 25/3/26" (Independent)</dd>

    <dt>Q: ลูกจ้างย้ายนายจ้าง — กลุ่ม Affiliated เดิมเป็นยังไง?</dt>
    <dd>A: ลูกจ้างจะถูก <strong>ถอดออกจากกลุ่ม Affiliated</strong>เดิมโดยอัตโนมัติ เพราะกลุ่ม Affiliated ผูกกับนายจ้าง — ส่วน Independent ไม่กระทบ</dd>

    <dt>Q: สร้างกลุ่มซ้ำชื่อกันได้ไหม?</dt>
    <dd>A: ภายในนายจ้างเดียวกันไม่ได้ — ระบบจะเตือน "ชื่อกลุ่มซ้ำ" ให้เปลี่ยน</dd>

    <dt>Q: ลากลูกจ้างระหว่างทีมไม่ได้?</dt>
    <dd>A: ต้องอยู่ในกลุ่มเดียวกัน — ทีมต่างกลุ่มกันใช้ "เพิ่มสมาชิก" → เลือกแทน</dd>
</dl>
