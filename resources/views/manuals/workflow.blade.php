{{-- User Manual: Workflow --}}

<h4><i class="bi bi-diagram-3-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"Workflow"</strong> คือศูนย์รวมงานที่กำลังเดินตามขั้นตอนต่างๆ ของสำนักงาน
    เช่น ยื่นเอกสารกรมการจัดหางาน, ทำพาสปอร์ต, ขอวีซ่า, ออกใบอนุญาตทำงาน ฯลฯ
    โดยแต่ละงานจะวิ่งผ่าน "Steps" ที่กำหนดไว้
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้</li>
    <li><span class="manual-role">Caretaker</span> — ดูได้ ไม่ได้แก้</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบขั้นตอน</strong> ด้านบน — แสดงแต่ละ Step ที่งานต้องผ่าน + จำนวนงานในแต่ละ Step</li>
    <li><strong>แถบตัวกรอง</strong> — กรองตามขั้นตอน, นายจ้าง, ประเภทงาน</li>
    <li><strong>การ์ดงาน</strong> — แสดงงานทั้งหมดที่อยู่ในขั้นตอนที่เลือก</li>
    <li><strong>ปุ่ม Auto-apply MOU</strong> — สำหรับงานต่ออายุ MOU ที่ระบบทำให้อัตโนมัติ</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. ดูงานในแต่ละขั้นตอน</h5>
<div class="manual-step">
    กดที่ Step ในแถบขั้นตอน → แสดงเฉพาะงานที่อยู่ใน Step นั้น
</div>

<h5>2. ย้ายงานไปขั้นตอนถัดไป</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดการ์ดงาน</li>
        <li>กดปุ่ม <strong>"ดำเนินการต่อ / Next Step"</strong></li>
        <li>กรอกข้อมูลที่ Step ใหม่ต้องการ (เช่น เลขใบรับ, วันที่)</li>
        <li>กด "ยืนยัน" — งานจะย้าย Step</li>
    </ol>
</div>

<h5>3. ย้อนกลับขั้นตอน</h5>
<div class="manual-step">
    ถ้ามีข้อผิดพลาด ใช้ปุ่ม <strong>"Send Back"</strong> ส่งงานกลับ Step ก่อนหน้า
    หรือ <strong>"Send Back to Pre-Production"</strong> ส่งกลับเมนู Production
</div>

<h5>4. ตั้งฟิลด์เพิ่มเติม (Custom Fields)</h5>
<div class="manual-step">
    กดปุ่ม "Fields" บนการ์ด MOU → เพิ่มข้อมูลตาม Step ที่ต้องการ
    (เช่น "เลขที่บัตรชมพู", "วันที่นัดสัมภาษณ์")
</div>

<h5>5. Auto-apply ต่ออายุ MOU</h5>
<div class="manual-step">
    ระบบจะ <strong>auto-apply</strong> งานต่ออายุ MOU อัตโนมัติทุก 24 ชั่วโมง
    Admin สามารถตั้งค่าใน Super Admin Settings → ส่วน Workflow
</div>

<h5>6. MOU นำเข้า — สร้าง Demand Card</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิด Tab <strong>"MOU นำเข้า"</strong> → กดปุ่ม <strong>"Create Job"</strong></li>
        <li>เลือก Work Type = MOU นำเข้า</li>
        <li><strong>เลือกนายจ้าง</strong> — พิมพ์ค้นหาได้ (ชื่อไทย/EN/รหัส) ไม่ต้อง scroll หา</li>
        <li><strong>เลือกประเภท MOU นำเข้า</strong>:
            <ul>
                <li><span class="badge bg-success">Return</span> = ลูกจ้างอยู่ในไทยแล้ว → บันทึกข้อมูลลูกจ้างได้ทันที</li>
                <li><span class="badge bg-primary">New from Origin</span> = คนใหม่จากต้นทาง → ยังไม่มีข้อมูลลูกจ้าง รอ Demand → Name list</li>
                <li>ถ้ายังไม่แน่ใจ → ปล่อยว่างได้ ระบบจะแสดงเป็น <span class="badge bg-warning text-dark">Pending Classification</span></li>
            </ul>
        </li>
        <li>กรอกสัญชาติ + จำนวนชาย/หญิงที่ต้องการนำเข้า</li>
        <li>กด "Create Demand Card"</li>
    </ol>
</div>

<h5>7. เปลี่ยนประเภท MOU นำเข้าทีหลัง</h5>
<div class="manual-step">
    บนหน้า Workflow tab "MOU นำเข้า" → คลิกที่ <strong>badge สี (Return/New/Pending)</strong> บน card → เลือกประเภทใหม่ → กด Save
</div>

<h5>8. แท็บ "แจ้งออก" (Notify Out) — ระบุวันและเหตุผลก่อนปิด</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิด Tab <strong>"แจ้งออก"</strong> → กดปุ่ม <strong>"+ Add Employee"</strong></li>
        <li>ค้นหาลูกจ้างได้<strong>ทุกคนในระบบ</strong> (Global search) — ไม่ติด employer scope</li>
        <li>ลูกจ้างที่อยู่ในงาน <strong>renewal / เปลี่ยนนายจ้าง</strong> อยู่ก็เพิ่มเข้า notify_out ได้ (ทำคู่กันได้)</li>
        <li>ลูกจ้างคนเดิมที่อยู่ใน notify_out อยู่แล้ว — <strong>เพิ่มซ้ำไม่ได้</strong> จนกว่าจะเสร็จสิ้น</li>
        <li>ระบบจะ <strong>auto-group ตามนายจ้างปัจจุบัน</strong> ของลูกจ้าง (1 employer = 1 order)</li>
    </ol>
</div>

<div class="manual-step">
    <strong>ก่อนกดเสร็จสิ้นในแถบ notify_out:</strong>
    <ol class="mb-0">
        <li>การ์ดลูกจ้างจะมี<strong>แถบสีเหลือง</strong>ด้านล่าง</li>
        <li>กรอก <strong>วันแจ้งออก</strong> (date picker — required)</li>
        <li>เลือก <strong>เหตุผล</strong> (dropdown: ลาออก / เลิกจ้าง / ครบสัญญา / เปลี่ยนนายจ้าง / หนีกลับประเทศ / เสียชีวิต) — หรือพิมพ์เองได้</li>
        <li>ระบบ autosave ทุกการเปลี่ยน → badge เปลี่ยนเป็น <strong>"พร้อมเสร็จสิ้น"</strong> สีเขียว</li>
        <li>กด "Finish" → ระบบจะ <strong>auto-update employee.terminated_at + termination_reason + status='resigned'</strong> ทันที</li>
        <li><strong>ถ้ายังไม่กรอกวันแจ้งออก</strong> → กด Finish ไม่ได้ จะเด้งเตือน "ต้องระบุวันแจ้งออกก่อน"</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>ใช้ตัวกรองหลายชุด:</strong> เลือกหลาย Step พร้อมกันได้เพื่อดูภาพรวม
</div>

<div class="manual-tip">
    <strong>เจ้าของงาน:</strong> ใช้ตัวกรอง "เจ้าของงาน" เพื่อดูเฉพาะงานของพี่เลี้ยงตัวเอง
</div>

<div class="manual-warn">
    <strong>ระวัง:</strong> การย้าย Step มีผลต่อ Notification ที่ส่งให้ลูกค้า — ตรวจให้ดีก่อนกด
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: งานหายไป — ไม่พบใน Workflow?</dt>
    <dd>A: เช็คตัวกรอง — อาจอยู่ใน Step ที่ไม่ได้เลือก ลองเลือก "ทั้งหมด" หรือกรองด้วยชื่อนายจ้าง</dd>

    <dt>Q: เพิ่ม Step ใหม่ได้ไหม?</dt>
    <dd>A: ได้ — ติดต่อ Admin เพิ่มผ่านเมนูตั้งค่า Step</dd>

    <dt>Q: ลบ Step ที่กำลังใช้งานอยู่?</dt>
    <dd>A: ห้าม — งานในนั้นจะค้าง ต้องย้ายไป Step อื่นก่อน</dd>

    <dt>Q: notify_out ของลูกจ้างคนนั้นหายไปจากแถบเอง?</dt>
    <dd>A: ระบบ <strong>auto-cancel</strong> notify_out pending เมื่อลูกจ้างย้ายนายจ้าง — เพราะ notify_out คือการ "ออกจากนายจ้างเก่า" ที่ไม่เกี่ยวข้องอีกแล้ว สามารถดูประวัติได้ที่เมนู "ประวัติการกระทำ" หรือสร้าง notify_out ใหม่ในนายจ้างใหม่หากต้องการ</dd>

    <dt>Q: ทำ notify_out แบบ manual จากเมนูพนักงานได้ไหม?</dt>
    <dd>A: ได้ — เมนูพนักงาน → ปุ่ม "แจ้งออก" → ระบุวัน+เหตุผล (workflow ไม่บังคับใช้ ถ้าต้องการเร็วๆ ก็ทำตรงนั้นได้)</dd>
</dl>
