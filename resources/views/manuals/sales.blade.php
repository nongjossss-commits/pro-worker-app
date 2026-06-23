{{--
    User Manual: Sales (การขายและใบเสนอราคา / Read and Sale)
--}}

<h4><i class="bi bi-megaphone-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"การขายและใบเสนอราคา"</strong> คือพื้นที่จัดการลูกค้าที่ยัง<strong>ไม่ได้</strong>เป็นนายจ้างเต็มตัว
    ใช้บันทึกการติดต่อสอบถาม (Lead) สร้างใบเสนอราคา (Quotation) และเมื่อปิดการขายได้
    ระบบจะสร้าง <strong>นายจ้าง + ลูกจ้าง + งาน Production</strong> ให้อัตโนมัติ
</p>
<p>
    เป็นจุดเริ่มต้นของ flow งานทั้งหมด — เริ่มจากเซลล์รับลูกค้าใหม่ → ปิดการขาย → เข้าสู่การดูแลรายวัน
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> — เข้าไม่ได้</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<p>หน้านี้เป็นรูปแบบ <strong>Kanban Board</strong> — มีคอลัมน์ตามสถานะของลูกค้า:</p>
<ol>
    <li><strong>ลูกค้าใหม่ (Lead)</strong> — เพิ่งติดต่อมา ยังไม่คุย</li>
    <li><strong>กำลังคุย (In Progress)</strong> — กำลังเจรจาเงื่อนไข</li>
    <li><strong>ส่งใบเสนอราคาแล้ว (Quoted)</strong> — เสนอราคาให้ลูกค้าแล้ว รอตอบ</li>
    <li><strong>ปิดการขาย (Won)</strong> — ตกลง! กำลังจะเข้าระบบจริง</li>
    <li><strong>ยกเลิก (Lost)</strong> — ลูกค้าไม่เอา</li>
</ol>
<p>ลากการ์ดข้ามคอลัมน์เพื่อเปลี่ยนสถานะได้เลย</p>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งานที่พบบ่อย</h4>

<h5>1. เพิ่มลูกค้าใหม่ (Lead)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กด <strong>"+ เพิ่ม Lead"</strong> ที่ส่วนบนของหน้า</li>
        <li>กรอกข้อมูลพื้นฐาน — ชื่อบริษัท/บุคคล, เบอร์โทร, ที่มา (เช่น โทรเข้า, แนะนำ, Facebook)</li>
        <li>เลือก <strong>ประเภทงาน</strong> (MOU, Visa, อื่นๆ)</li>
        <li>กด <strong>"บันทึก"</strong> — Lead จะอยู่ในคอลัมน์ "ลูกค้าใหม่"</li>
    </ol>
</div>

<h5>2. สร้างใบเสนอราคา (Quotation)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดการ์ดของ Lead</li>
        <li>กดปุ่ม <strong>"สร้างใบเสนอราคา"</strong></li>
        <li>กรอกรายการสินค้า/บริการ + ราคา + จำนวน</li>
        <li>เลือก <strong>"โปรไฟล์การเงิน"</strong> (ผู้ออกบิล) — โปรไฟล์ของออฟฟิศที่จะใช้ขึ้นบนใบ</li>
        <li>กด <strong>"พรีวิว"</strong> เพื่อดูตัวอย่าง PDF</li>
        <li>กด <strong>"ส่ง"</strong> — Lead จะเลื่อนไปคอลัมน์ "ส่งใบเสนอราคาแล้ว" อัตโนมัติ</li>
    </ol>
</div>

<h5>3. ปิดการขาย → เข้าระบบจริง</h5>
<div class="manual-step">
    เมื่อลูกค้าตกลง:
    <ol class="mb-0 mt-2">
        <li>เปิดการ์ดที่อยู่ใน "ส่งใบเสนอราคาแล้ว"</li>
        <li>กดปุ่ม <strong>"ปิดการขาย / เปลี่ยนเป็นลูกค้าจริง"</strong></li>
        <li>ระบบจะถามให้กรอกข้อมูล <strong>นายจ้างเต็มรูปแบบ</strong> (เลขผู้เสียภาษี, ที่อยู่จดทะเบียน, ฯลฯ)</li>
        <li>กรอก<strong>รายชื่อลูกจ้าง</strong>ที่จะดูแล (กรอกทีละคนหรือ Import Excel ก็ได้)</li>
        <li>กด <strong>"ยืนยันสร้าง"</strong> — ระบบจะสร้างพร้อมกัน:
            <ul>
                <li>นายจ้างใหม่ในเมนู "ข้อมูลนายจ้าง"</li>
                <li>ลูกจ้างทุกคนในเมนู "ข้อมูลลูกจ้าง"</li>
                <li>งาน Production ใหม่ในเมนู "P Production"</li>
            </ul>
        </li>
    </ol>
</div>

<h5>4. ยกเลิก Lead</h5>
<div class="manual-step">
    ลากการ์ดไปคอลัมน์ <strong>"ยกเลิก (Lost)"</strong> หรือกดปุ่ม "ยกเลิก" ในการ์ด
    ระบุเหตุผล (เช่น "ราคาแพงไป", "ติดต่อไม่ได้")
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>Tip:</strong>
        Lead ที่ยกเลิกแล้วยังเก็บไว้ในประวัติ — กลับมาดูเหตุผลและสถิติได้
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>ข้อควรรู้ / Tips</h4>

<div class="manual-tip">
    <strong>ทำไมเริ่มจาก Lead ก่อนเข้าระบบจริง?</strong>
    เพราะ Lead 70-80% ปกติไม่ปิดการขาย — ถ้าสร้างเป็นนายจ้างเลย จะมีข้อมูลขยะเต็มระบบ
    Lead/Sales แยกออกมาเพื่อให้รายชื่อนายจ้างจริงสะอาดและน่าเชื่อถือ
</div>

<div class="manual-tip">
    <strong>ใบเสนอราคา ≠ ใบกำกับภาษี:</strong>
    ใบเสนอราคา (Quotation) ไม่มีผลทางภาษี ใช้เพื่อแจ้งราคาเฉยๆ
    ใบกำกับภาษี (Tax Invoice) ออกหลังลูกค้าจ่ายเงินแล้ว ในเมนู Finance
</div>

<div class="manual-tip">
    <strong>ลากการ์ดเปลี่ยนสถานะ:</strong> ไม่ต้องเปิดการ์ด — แค่ลากด้วยเมาส์เลื่อนข้ามคอลัมน์ก็พอ
    ระบบจะบันทึกอัตโนมัติ
</div>

<div class="manual-warn">
    <strong>หลังปิดการขายแล้ว แก้ไม่ได้:</strong>
    เมื่อกด "ปิดการขาย" และระบบสร้างนายจ้าง/ลูกจ้างแล้ว
    Lead เดิมจะ "lock" ไม่ให้แก้อีก — แก้ต้องไปแก้ที่นายจ้าง/ลูกจ้างโดยตรง
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>

<dl>
    <dt>Q: ทำไมไม่เห็นเมนูนี้?</dt>
    <dd>A: เมนูนี้เห็นเฉพาะ role <strong>Super Admin / Admin / Staff</strong> — Caretaker และ Employer ไม่มีสิทธิ์เห็น</dd>

    <dt>Q: ใบเสนอราคาที่ส่งไปแล้ว แก้ได้ไหม?</dt>
    <dd>A: ได้ จนกว่าลูกค้าจะปิดการขาย — หลังจากนั้น lock</dd>

    <dt>Q: ลูกค้าเก่ากลับมาอีก ต้องเริ่มจาก Lead ใหม่ไหม?</dt>
    <dd>A: ไม่ต้อง — ถ้าเขาเป็นนายจ้างในระบบอยู่แล้ว เพิ่มงาน Production ใหม่ในเมนู Production ได้เลย</dd>

    <dt>Q: ใครเป็น "เจ้าของ Lead"?</dt>
    <dd>A: คนที่สร้าง Lead จะเป็น<strong>เซลล์เจ้าของ</strong> ใช้สำหรับคิดค่าคอมมิชชั่นในอนาคต</dd>
</dl>
