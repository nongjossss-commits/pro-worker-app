{{-- User Manual: PDF Templates --}}

<h4><i class="bi bi-file-earmark-pdf-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"PDF Templates"</strong> ใช้สำหรับสร้าง<strong>แม่แบบ PDF</strong>สำหรับเอกสารต่างๆ
    เช่น สัญญาจ้าง, ใบรับรองการทำงาน, ฯลฯ
    โดย<strong>ลาก-วาง (drag and drop)</strong> ฟิลด์ข้อมูลลงบน PDF template ที่อัพโหลด
    ทำให้ระบบเติมข้อมูลให้อัตโนมัติเมื่อสร้างเอกสารจริง
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Staff</span> — ดูได้ + ใช้ template ได้ (สิทธิ์ <code>view-pdf-templates</code>)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>รายการ Template</strong> — แสดงทั้งหมดที่สร้างไว้</li>
    <li><strong>ปุ่ม "+ Template ใหม่"</strong></li>
    <li><strong>หน้า Editor</strong> (เมื่อเปิด template) — PDF preview + รายการฟิลด์ลากได้</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. สร้าง Template ใหม่ — มี 2 วิธี</h5>
<div class="manual-step">
    <strong>วิธีที่ 1: อัพโหลด PDF ใหม่</strong>
    <ol class="mb-2">
        <li>กด "+ Template ใหม่"</li>
        <li>เลือก <strong>"Upload new PDF"</strong></li>
        <li>ตั้งชื่อ template (เช่น "สัญญาจ้าง MOU พม่า")</li>
        <li>อัพโหลด PDF ต้นแบบ (เช่นสัญญาเปล่าที่มีช่องว่าง)</li>
        <li>กด "Upload &amp; Go to Builder" → เข้าหน้า Editor</li>
    </ol>
    <strong>วิธีที่ 2: คัดลอกจาก Template เดิม (Clone)</strong>
    <ol class="mb-0">
        <li>กด "+ Template ใหม่"</li>
        <li>เลือก <strong>"Copy from existing template"</strong></li>
        <li>เลือก template ต้นฉบับจากรายการ (ค้นหาได้)</li>
        <li>ตั้งชื่อใหม่ (เช่นเพิ่ม "(Copy)" หรือ "v2") + เลือกประเภท/นายจ้าง</li>
        <li>กด "Clone &amp; Go to Builder" → ระบบ copy ไฟล์ PDF + ตำแหน่งฟิลด์ทั้งหมดให้ → ปรับเฉพาะที่ต้องการแล้วบันทึก</li>
    </ol>
</div>

<h5>2. ลาก-วาง ฟิลด์</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>ในหน้า Editor — รายการฟิลด์อยู่ฝั่งซ้าย (ชื่อนายจ้าง, เลขผู้เสียภาษี, ฯลฯ)</li>
        <li>ลากฟิลด์ที่ต้องการไปวางบน PDF preview ตรงตำแหน่งที่ต้องการ</li>
        <li>ปรับขนาด/ฟอนต์ของแต่ละฟิลด์ได้</li>
        <li>กด "บันทึก"</li>
    </ol>
</div>

<h5>3. ใช้ Template สร้างเอกสาร</h5>
<div class="manual-step">
    ในเมนูลูกจ้าง/นายจ้าง — กด "สร้างเอกสาร" → เลือก template → ระบบเติมข้อมูลให้อัตโนมัติ
</div>

<h5>4. พิมพ์เอกสารแบบ Quick Print (สำหรับ template ที่ไม่ต้องใช้ข้อมูลลูกจ้าง)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กดปุ่ม <strong>"พิมพ์เอกสาร"</strong> (สีเขียว) ที่ด้านบนของหน้า PDF Templates</li>
        <li>เลือก template — ระบบจะ<strong>วิเคราะห์ฟิลด์</strong>ให้ดูว่าต้องใช้ข้อมูลของใครบ้าง:
            <ul>
                <li><span class="badge bg-warning text-dark">ลูกจ้าง</span> = ต้องเลือกลูกจ้างก่อน — ระบบจะเตือนและพิมพ์เปล่าไม่ได้</li>
                <li><span class="badge bg-primary">นายจ้าง</span> / <span class="badge bg-info">ผู้รับมอบอำนาจ</span> / <span class="badge bg-success">บริษัทนำเข้า</span> = เลือก target จาก dropdown แล้วพิมพ์ได้</li>
            </ul>
        </li>
        <li>ถ้า template มีช่องลูกจ้าง → กด <strong>"ไปเลือกลูกจ้าง"</strong> → ในหน้าจัดการลูกจ้างเลือกคนที่ต้องการ → กด "สร้าง PDF อัตโนมัติ"</li>
        <li>ถ้าไม่มีช่องลูกจ้าง → เลือก Target Employer/Delegate/Importer (ตามที่ template ใช้) → กด <strong>"ดาวน์โหลด PDF"</strong> หรือ <strong>"พิมพ์ / Preview"</strong></li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>ฟอนต์ภาษาไทย:</strong> ระบบใช้ THSarabunNew + CP874 encoding — รองรับภาษาไทยครบ
</div>

<div class="manual-tip">
    <strong>ลายเซ็น + ตราประทับ:</strong> สามารถใส่ลายเซ็น/ตราจาก Financial Profile หรือใส่แบบ procedural (วาดบน PDF)
</div>

<div class="manual-tip">
    <strong>Clone ประหยัดเวลาเมื่อ:</strong> ต้องการทำ template ที่ใช้ฟอร์มเดียวกันแต่เปลี่ยนข้อมูลบางช่อง — เช่นใช้สัญญาเดิม แต่เปลี่ยนชื่อบริษัท หรือเปลี่ยนเงื่อนไขเล็กน้อย
</div>

<div class="manual-warn">
    <strong>Quick Print กับช่องลูกจ้าง:</strong> ถ้า template มีช่องข้อมูลลูกจ้าง (ชื่อลูกจ้าง, passport ฯลฯ) <strong>จะพิมพ์เปล่าไม่ได้</strong> — ต้องไปเลือกลูกจ้างก่อน เพราะถ้าพิมพ์เปล่าออกมาช่องเหล่านี้จะว่างเปล่า ใช้งานจริงไม่ได้
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: PDF ที่อัพโหลดมีข้อความเป็นภาพ (Scanned)?</dt>
    <dd>A: ระบบจะใช้เป็น background ได้ — แต่ลาก-วาง field ทับลงไปเองตรงที่เป็นช่องว่าง</dd>

    <dt>Q: ใส่ฟิลด์ตำแหน่งผิด?</dt>
    <dd>A: เปิด template → ลากฟิลด์ใหม่ในตำแหน่งที่ถูก → บันทึก — ระบบจะใช้ตำแหน่งใหม่ทันที</dd>

    <dt>Q: Clone template แล้วลบต้นฉบับ จะกระทบ template ที่ clone หรือไม่?</dt>
    <dd>A: ไม่กระทบ — ระบบ copy ไฟล์ PDF ไปยัง path ใหม่ทั้งหมด ทั้ง 2 template เป็นอิสระจากกันโดยสิ้นเชิง</dd>

    <dt>Q: กด "พิมพ์เอกสาร" แล้วทำไมปุ่ม "ดาวน์โหลด" ปิดอยู่?</dt>
    <dd>A: เพราะ template ที่เลือกมีช่องข้อมูลลูกจ้าง — Quick Print ไม่รองรับ ต้องไปเลือกลูกจ้างจากหน้า "จัดการลูกจ้าง" แล้วใช้ "สร้าง PDF อัตโนมัติ" แทน</dd>
</dl>
