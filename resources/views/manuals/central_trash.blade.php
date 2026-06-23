{{-- User Manual: Central Trash --}}

<h4><i class="bi bi-trash-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"ถังขยะกลาง (Central Trash)"</strong> เก็บ<strong>ข้อมูลที่ถูกลบ</strong>ทั้งหมดของระบบ
    ทั้งนายจ้าง, ลูกจ้าง, ตัวแทน, ที่อยู่ ฯลฯ ใน<strong>ที่เดียว</strong>
    เพื่อให้สามารถ<strong>กู้คืน (Restore)</strong> หรือ<strong>ลบถาวร (Force Delete)</strong> ได้ง่าย
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้</li>
    <li>ต้องมีสิทธิ์ <code>view-trash</code> + <code>restore-*</code> หรือ <code>force-delete-*</code> ตามประเภท</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบ Tabs</strong> — แยกตามประเภทข้อมูล (Employers, Employees, Delegates, ฯลฯ)</li>
    <li><strong>ตาราง</strong> — แสดงรายการที่ลบไป + วันที่ลบ + ผู้ลบ</li>
    <li><strong>ปุ่มกู้คืน + ปุ่มลบถาวร</strong> ในแต่ละแถว</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. กู้คืนรายการที่ลบไป</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เลือก tab ของประเภทข้อมูล (เช่น Employees)</li>
        <li>หา record ที่ต้องการกู้</li>
        <li>กดปุ่ม <i class="bi bi-arrow-counterclockwise"></i> "กู้คืน"</li>
        <li>ยืนยัน — record จะกลับไปอยู่ในเมนูเดิม</li>
    </ol>
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        ถ้ากู้คืน Employee อาจเกินโควต้าระบบ — ถ้าเกินจะกู้ไม่ได้ ต้องเพิ่มโควต้าก่อน
    </div>
</div>

<h5>2. ลบถาวร (ห้ามทำพร่ำเพรื่อ!)</h5>
<div class="manual-warn">
    กดปุ่ม <i class="bi bi-x-circle-fill text-danger"></i> "ลบถาวร" → ยืนยัน 2 ครั้ง → record <strong>หายตลอดไป</strong> กู้คืนไม่ได้
    <br><br>
    ใช้เฉพาะกรณี:
    <ul class="mb-0">
        <li>ข้อมูลซ้ำที่ป้อนผิด</li>
        <li>ข้อมูลทดสอบที่ลืมลบ</li>
        <li>ข้อมูลที่ติดในระบบเก่า ต้องการ cleanup จริงๆ</li>
    </ul>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Soft Delete vs Force Delete:</strong> Soft Delete = ย้ายมาถังขยะ (กู้คืนได้), Force Delete = ลบจริง (กู้ไม่ได้)
</div>

<div class="manual-tip">
    <strong>หลังกู้คืน:</strong> ตรวจว่า record ที่กู้กลับมาไม่มีข้อมูลขัดแย้ง (เช่น นายจ้างที่กู้กลับมาแล้ว ลูกจ้างยังอยู่ในถังขยะอยู่)
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ลบถาวรไปแล้ว เอากลับมาได้ไหม?</dt>
    <dd>A: <strong>ไม่ได้</strong> — ต้องไปดู backup ของ database จากผู้ดูแล server</dd>

    <dt>Q: ถังขยะเก็บไว้นานแค่ไหน?</dt>
    <dd>A: ไม่จำกัด — ระบบไม่ลบให้อัตโนมัติ ต้องลบเองถ้าต้องการ cleanup</dd>

    <dt>Q: ลบ Employer แล้วลูกจ้างไปไหน?</dt>
    <dd>A: ลูกจ้างยังอยู่เหมือนเดิม แค่ความสัมพันธ์กับ Employer หาย — ต้อง assign ใหม่</dd>
</dl>
