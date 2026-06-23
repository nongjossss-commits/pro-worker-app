{{--
    User Manual: Employees (ข้อมูลลูกจ้าง)
--}}

<h4><i class="bi bi-people-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"ข้อมูลลูกจ้าง"</strong> คือฐานข้อมูลแรงงานต่างด้าว (พม่า/ลาว/กัมพูชา) ที่อยู่ในการดูแลของสำนักงาน
    ใช้เก็บข้อมูลพื้นฐาน (ชื่อ-นามสกุล, สัญชาติ, เลขพาสปอร์ต, วีซ่า, ใบอนุญาตทำงาน, สัญญา MOU)
    และเชื่อมโยงกับ<strong>นายจ้าง</strong>ที่ลูกจ้างทำงานอยู่
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้ทุกอย่าง รวมถึงลาออก/กู้คืน/ลบถาวร</li>
    <li><span class="manual-role">Staff</span> — ดู / เพิ่ม / แก้ไข ได้ ไม่สามารถลบถาวรได้</li>
    <li><span class="manual-role">Caretaker</span> — ดู / เพิ่ม / แก้ไข + ลาออกลูกจ้างได้</li>
    <li><span class="manual-role">Employer</span> — เห็นเฉพาะลูกจ้างของตัวเอง</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบกรอง</strong> ด้านบน — กรองตามสัญชาติ, สถานะวีซ่า, นายจ้าง, ค้นหาด้วยชื่อ/เลขพาสปอร์ต</li>
    <li><strong>แถบสถานะ</strong> — ทำงานอยู่ / ลาออก / หมดสัญญา / ถังขยะ</li>
    <li><strong>การ์ดลูกจ้าง</strong> — แต่ละการ์ดมีรูปถ่าย, ชื่อ, สัญชาติ, สถานะวีซ่า, นายจ้าง</li>
    <li><strong>ปุ่ม "+ เพิ่มลูกจ้างใหม่"</strong> และ <strong>"นำเข้าจาก Excel"</strong> ที่มุมบนขวา</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งานที่พบบ่อย</h4>

<h5>1. เพิ่มลูกจ้างทีละคน</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กด <strong>"+ เพิ่มลูกจ้างใหม่"</strong> ที่มุมบนขวา</li>
        <li>กรอกข้อมูลพื้นฐาน — ชื่อ-นามสกุล (ไทย/อังกฤษ), เพศ, สัญชาติ, วันเกิด</li>
        <li>กรอกเลข <strong>พาสปอร์ต</strong> + วันหมดอายุ</li>
        <li>เลือก <strong>นายจ้าง</strong> ที่ลูกจ้างทำงานอยู่</li>
        <li>อัพโหลดรูปถ่าย + เอกสาร (สำเนาพาสปอร์ต, วีซ่า, ใบอนุญาตทำงาน) เท่าที่มี</li>
        <li>กด <strong>"บันทึก"</strong></li>
    </ol>
</div>

<h5>2. นำเข้าลูกจ้างเป็นกลุ่ม (Excel Bulk Import)</h5>
<div class="manual-step">
    เหมาะกับนายจ้างที่มีลูกจ้างเข้ามาทีเดียวหลายคน
    <ol class="mb-0 mt-2">
        <li>กดปุ่ม <strong>"นำเข้าจาก Excel"</strong></li>
        <li>ดาวน์โหลด <strong>"แบบฟอร์ม Template"</strong> ก่อน — ไฟล์ตัวอย่างมีหัวคอลัมน์ที่ระบบต้องการ</li>
        <li>กรอกข้อมูลลูกจ้างใน Excel ตามหัวคอลัมน์ (1 คน 1 แถว)</li>
        <li>อัพโหลดไฟล์กลับเข้าระบบ</li>
        <li>ตรวจ <strong>preview</strong> ก่อนยืนยัน — ระบบจะแสดงรายการที่จะเพิ่มและเตือนถ้ามีข้อมูลผิด</li>
        <li>กด <strong>"ยืนยัน"</strong> — ระบบจะเพิ่มทีเดียวทั้งหมด</li>
    </ol>
</div>

<h5>3. ลาออก / หมดสัญญา (Terminate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดการ์ดลูกจ้างที่จะลาออก</li>
        <li>กดปุ่ม <strong>"ลาออก / หมดสัญญา"</strong></li>
        <li>เลือกสาเหตุ + วันที่ลาออก</li>
        <li>กด <strong>"ยืนยัน"</strong> — สถานะจะเปลี่ยนเป็น "ลาออก" และจะไม่นับในโควต้าใช้งาน</li>
    </ol>
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>เคล็ดลับ:</strong>
        ลูกจ้างที่ลาออกไม่ได้หายไป — ยังอยู่ในแถบ "ลาออก" สามารถกู้คืนกลับมาทำงานได้ถ้าจ้างใหม่
    </div>
</div>

<h5>4. กู้คืนลูกจ้างที่ลาออก (Reinstate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>คลิกแถบสถานะ <strong>"ลาออก"</strong> ด้านบน</li>
        <li>หาลูกจ้างที่ต้องการกู้คืน</li>
        <li>กด <strong>"กลับมาทำงาน"</strong></li>
    </ol>
</div>

<h5>5. ลบลูกจ้างถาวร (Force Delete)</h5>
<div class="manual-warn">
    <strong>เฉพาะ Admin/Super Admin เท่านั้น</strong> — ใช้สำหรับลูกจ้างที่ป้อนผิดหรือซ้ำ ลบแล้ว<strong>ย้อนคืนไม่ได้</strong>
    <br>
    ขั้นตอน: ไปที่แถบ "ถังขยะ" → กดไอคอนลบถาวร → ยืนยัน 2 ครั้ง
</div>

<h4><i class="bi bi-lightbulb me-2"></i>ข้อควรรู้ / Tips</h4>

<div class="manual-tip">
    <strong>โควต้าลูกจ้างสูงสุด:</strong> Super Admin สามารถจำกัดจำนวนลูกจ้างที่ active ได้
    (ตั้งค่าใน Super Admin Settings → ตั้งค่าโควต้า) — ถ้าเต็ม จะเพิ่มลูกจ้างใหม่ไม่ได้
</div>

<div class="manual-tip">
    <strong>ลูกจ้าง vs ผู้แทน (Delegate):</strong> "ข้อมูลพนักงาน" ในเมนูซ้ายของบางบทบาท
    หมายถึง <strong>ผู้แทนนายจ้าง (Delegate)</strong> ไม่ใช่ลูกจ้างแรงงาน — สองอันคนละเมนูกัน
</div>

<div class="manual-tip">
    <strong>วีซ่า/พาสปอร์ตใกล้หมด:</strong> ระบบจะแจ้งเตือนใน <strong>Notifications</strong>
    อัตโนมัติเมื่อใกล้หมดอายุ — เช็คทุกวันเช้า
</div>

<div class="manual-warn">
    <strong>ก่อนลบลูกจ้างถาวร:</strong> ตรวจให้แน่ใจว่าไม่มีงาน Production / Workflow / ใบกำกับภาษี
    ที่อ้างถึงลูกจ้างคนนี้อยู่
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>

<dl>
    <dt>Q: เพิ่มลูกจ้างไม่ได้ ขึ้นเตือน "เกินโควต้า"?</dt>
    <dd>A: โควต้าระบบเต็มแล้ว — ติดต่อ Super Admin ให้เพิ่มโควต้า หรือลาออกลูกจ้างเก่าที่ไม่ใช้แล้ว</dd>

    <dt>Q: ทำไม Import Excel ขึ้น error?</dt>
    <dd>A: ตรวจหัวคอลัมน์ในไฟล์ Excel ต้องตรงกับ template ที่ดาวน์โหลด — อย่าเปลี่ยนชื่อคอลัมน์</dd>

    <dt>Q: ลูกจ้างย้ายไปอยู่นายจ้างใหม่?</dt>
    <dd>A: แก้ข้อมูลลูกจ้าง → เปลี่ยน "นายจ้าง" เป็นคนใหม่ → บันทึก</dd>

    <dt>Q: เห็นแต่ลูกจ้างของบริษัทเดียว?</dt>
    <dd>A: ถ้าคุณ login ด้วย role "Employer" จะเห็นเฉพาะลูกจ้างของตัวเอง — ต้องใช้ role Admin/Staff ขึ้นไปเพื่อดูทุกคน</dd>
</dl>
