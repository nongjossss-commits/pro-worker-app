{{--
    User Manual: Employers (ข้อมูลนายจ้าง)
    Audience: New office staff who may have attended training but forgotten steps.
    Tone: Friendly, step-by-step, plain Thai.
--}}

<h4><i class="bi bi-building me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"ข้อมูลนายจ้าง"</strong> คือฐานข้อมูลของบริษัทหรือบุคคลที่ว่าจ้างแรงงานต่างด้าวผ่านสำนักงานของเรา
    ใช้สำหรับเก็บข้อมูลพื้นฐาน เช่น ชื่อบริษัท เลขประจำตัวผู้เสียภาษี ที่อยู่ เบอร์โทร เอกสารต่างๆ
    และเชื่อมโยงกับ <strong>ลูกจ้าง</strong> ที่ทำงานอยู่กับนายจ้างคนนั้น
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้ทุกอย่าง</li>
    <li><span class="manual-role">Staff</span> — ดู / เพิ่ม / แก้ไข ได้</li>
    <li><span class="manual-role">Caretaker</span> — ดู / เพิ่ม / แก้ไข ได้ (ไม่ลบ)</li>
    <li><span class="manual-role">Employer</span> — เห็นเฉพาะข้อมูลของตัวเอง</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบกรอง</strong> ด้านบน — กรองตามสถานะ, มี/ไม่มีลูกจ้าง, ค้นหาด้วยชื่อ/เลขผู้เสียภาษี</li>
    <li><strong>การ์ดนายจ้าง</strong> — แต่ละการ์ดแสดง 1 บริษัท ดูชื่อ จำนวนลูกจ้าง สถานะได้</li>
    <li><strong>ปุ่ม "+ เพิ่มนายจ้างใหม่"</strong> ที่มุมบนขวา — สำหรับสร้างนายจ้างใหม่</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งานที่พบบ่อย</h4>

<h5>1. เพิ่มนายจ้างใหม่</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กดปุ่ม <strong>"+ เพิ่มนายจ้างใหม่"</strong> ที่มุมบนขวา</li>
        <li>กรอกข้อมูลพื้นฐาน — ชื่อบริษัท (ไทย/อังกฤษ), เลขผู้เสียภาษี 13 หลัก, ที่อยู่</li>
        <li>เลือก <strong>"เจ้าของงาน"</strong> ถ้ามี (พี่เลี้ยงที่ดูแลลูกค้ารายนี้)</li>
        <li>อัพโหลดเอกสาร (สำเนา ภพ.20, ทะเบียนพาณิชย์ ฯลฯ) ถ้ามี</li>
        <li>กด <strong>"บันทึก"</strong> — ระบบจะสร้างนายจ้างให้ทันที</li>
    </ol>
</div>

<h5>2. ค้นหานายจ้าง</h5>
<div class="manual-step">
    พิมพ์ชื่อหรือเลขผู้เสียภาษีในช่องค้นหาบนสุด — รายการจะกรองให้อัตโนมัติ
</div>

<h5>3. แก้ไขข้อมูลนายจ้าง</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กดที่การ์ดของนายจ้างที่ต้องการแก้</li>
        <li>กดไอคอน <i class="bi bi-pencil"></i> <strong>"แก้ไข"</strong></li>
        <li>แก้ข้อมูลที่ต้องการ แล้วกด <strong>"บันทึก"</strong></li>
    </ol>
</div>

<h5>4. ดูลูกจ้างของนายจ้างคนนี้</h5>
<div class="manual-step">
    กดที่การ์ดนายจ้าง → จะเห็นรายชื่อลูกจ้างทั้งหมดที่ทำงานอยู่กับนายจ้างคนนี้ พร้อมสถานะแต่ละคน
</div>

<h5>5. ลบนายจ้าง</h5>
<div class="manual-step">
    กดไอคอน <i class="bi bi-trash text-danger"></i> ที่การ์ด — ระบบจะถามยืนยันก่อน
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i> <strong>คำเตือน:</strong>
        ถ้านายจ้างมีลูกจ้างอยู่ ระบบจะ<strong>ไม่ให้ลบ</strong> ต้องโอนหรือลาออกลูกจ้างทั้งหมดก่อน
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>ข้อควรรู้ / Tips</h4>

<div class="manual-tip">
    <strong>ภาษาไทย vs อังกฤษ:</strong> กรอกชื่อให้ครบทั้ง 2 ภาษา —
    ภาษาอังกฤษใช้พิมพ์ในเอกสารราชการ (MOU, สัญญาจ้าง)
</div>

<div class="manual-tip">
    <strong>เลขผู้เสียภาษี:</strong> ต้องเป็น 13 หลัก ถ้ากรอกผิดระบบจะเตือน
</div>

<div class="manual-tip">
    <strong>ที่อยู่หลายแห่ง:</strong> นายจ้าง 1 รายมีได้หลายที่อยู่ — ที่อยู่จดทะเบียน + ที่อยู่สถานที่ทำงาน
</div>

<div class="manual-warn">
    <strong>ระวัง:</strong> ก่อนลบนายจ้าง ตรวจสอบว่าไม่มีงาน <strong>Production</strong> หรือ <strong>Workflow</strong> ที่กำลังดำเนินการอยู่ —
    การลบจะทำให้สถานะงานนั้นๆ ผิดพลาด
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>

<dl>
    <dt>Q: ทำไมเพิ่มนายจ้างไม่ได้?</dt>
    <dd>A: ตรวจว่ากรอกครบทุกช่องที่มีดอกจัน (*) ไหม โดยเฉพาะชื่อ + เลขผู้เสียภาษี</dd>

    <dt>Q: ลูกจ้างหายไปจากการ์ดนายจ้าง?</dt>
    <dd>A: เช็คตัวกรองสถานะ — อาจอยู่ใน "ลาออก" หรือ "หมดสัญญา" ลองเลือก "ทั้งหมด" ดู</dd>

    <dt>Q: ลบนายจ้างผิดคน เอากลับมาได้ไหม?</dt>
    <dd>A: ได้ — ไปที่เมนู <strong>"ถังขยะกลาง"</strong> (Central Trash) แล้วกดกู้คืน (เฉพาะ Admin/Super Admin เท่านั้น)</dd>
</dl>
