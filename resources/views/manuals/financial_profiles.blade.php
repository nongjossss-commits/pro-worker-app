{{-- User Manual: Financial Profiles --}}

<h4><i class="bi bi-person-vcard me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"โปรไฟล์การเงิน (Financial Profiles)"</strong> ใช้สำหรับเก็บข้อมูล
    <strong>ผู้ออกบิล (Biller)</strong> และ <strong>ลูกค้า (Customer)</strong> ที่จะปรากฏบนเอกสารการเงิน
    เช่น ใบเสนอราคา, ใบกำกับภาษี, ใบเสร็จ
</p>
<p>
    ออฟฟิศหนึ่งอาจมีโปรไฟล์ผู้ออกบิลหลายอัน (เช่น "ออฟฟิศกรุงเทพ", "ออฟฟิศเชียงใหม่")
    แต่ละโปรไฟล์มีโลโก้, ลายเซ็น, ตราประทับ และ <strong>บัญชีธนาคาร</strong> ของตัวเอง
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้</li>
    <li><span class="manual-role">Staff</span> — เข้าได้ (ขึ้นกับสิทธิ์ <code>manage-finance</code>)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>รายการโปรไฟล์</strong> (ฝั่งซ้าย) — แสดงโปรไฟล์ทั้งหมด แยกประเภท Biller / Customer</li>
    <li><strong>ฟอร์มแก้ไข</strong> (ฝั่งขวา) — แก้ไขข้อมูลโปรไฟล์ที่เลือก</li>
    <li><strong>Panel "บัญชีธนาคาร"</strong> — แสดงหลังบันทึกโปรไฟล์ — เพิ่ม/แก้/ลบบัญชีของโปรไฟล์นั้นได้</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. สร้างโปรไฟล์ผู้ออกบิล (Biller)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กด "+ เพิ่มโปรไฟล์ใหม่"</li>
        <li>เลือกประเภท "Biller (ผู้ออกบิล)"</li>
        <li>กรอกชื่อบริษัท, เลขผู้เสียภาษี, ที่อยู่, เบอร์โทร, อีเมล</li>
        <li>อัพโหลด <strong>โลโก้, ลายเซ็น, ตราประทับ</strong></li>
        <li>วาง<strong>ตำแหน่งลายเซ็น/ตราประทับ</strong>โดยลากบน PDF preview</li>
        <li>กด "บันทึก"</li>
    </ol>
</div>

<h5>2. เพิ่มบัญชีธนาคารให้โปรไฟล์</h5>
<div class="manual-step">
    หลังบันทึกโปรไฟล์แล้ว — Panel "บัญชีธนาคาร" จะปรากฏ:
    <ol class="mb-0 mt-2">
        <li>กด "+ Add Bank"</li>
        <li>เลือกประเภท: <strong>ธนาคารไทย / PromptPay / กำหนดเอง</strong></li>
        <li>ถ้าเลือก "ธนาคารไทย" — เลือกแบงค์จากรายการ (17 ธนาคาร พร้อมโลโก้สี)</li>
        <li>กรอกชื่อบัญชี + เลขที่บัญชี + สาขา</li>
        <li>กด "บันทึก"</li>
    </ol>
</div>

<h5>3. แก้ไขโปรไฟล์</h5>
<div class="manual-step">
    คลิกที่โปรไฟล์ในรายการซ้าย → แก้ในฟอร์มขวา → กด "บันทึก"
</div>

<h5>4. ลบโปรไฟล์</h5>
<div class="manual-step">
    กดไอคอนถังขยะที่โปรไฟล์ — ระบบจะถามยืนยัน
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        ถ้าโปรไฟล์ถูกใช้ในใบกำกับภาษีที่ออกแล้ว — <strong>ห้ามลบ</strong> เพราะจะทำให้ PDF เก่าหา profile ไม่เจอ
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>โปรไฟล์ Biller vs Customer:</strong> Biller = ตัวเรา (ผู้ออกบิล), Customer = ลูกค้าที่ใช้บ่อย
    (เพื่อ auto-fill ในใบกำกับภาษีโดยไม่ต้องพิมพ์ใหม่)
</div>

<div class="manual-tip">
    <strong>หลายโปรไฟล์ Biller:</strong> เหมาะกับสำนักงานที่มีหลายสาขา/หลายนิติบุคคล
</div>

<div class="manual-tip">
    <strong>โลโก้ธนาคาร:</strong> ระบบจะใส่สีแบรนด์ + ตัวอักษรย่อให้อัตโนมัติบน PDF
    (เช่น KBANK = สีเขียว K, SCB = สีม่วง S)
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: เพิ่มบัญชีธนาคารไม่ได้?</dt>
    <dd>A: ต้อง <strong>บันทึกโปรไฟล์ก่อน</strong> — Panel บัญชีธนาคารจะปรากฏหลังบันทึกแล้วเท่านั้น</dd>

    <dt>Q: ลายเซ็นไม่ขึ้นบน PDF?</dt>
    <dd>A: ตรวจว่าอัพโหลดลายเซ็น + วางตำแหน่งบน preview แล้ว — ถ้าไม่ได้วาง ระบบจะไม่รู้ว่าจะใส่ตรงไหน</dd>

    <dt>Q: เลือกธนาคารแล้ว dropdown ค้าง?</dt>
    <dd>A: หลังกดเลือก → ระบบจะยุบลิสต์เป็น chip ทันที กดปุ่ม "เปลี่ยน" เพื่อเลือกใหม่</dd>
</dl>
