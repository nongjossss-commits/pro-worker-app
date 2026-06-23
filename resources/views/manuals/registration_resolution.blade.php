{{-- User Manual: Registration Resolution (มติลงทะเบียน) --}}

<h4><i class="bi bi-file-earmark-text-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"มติลงทะเบียน (Registration Resolution)"</strong> ใช้สำหรับจัดการ
    <strong>มติ ครม.</strong> เกี่ยวกับการลงทะเบียนแรงงานต่างด้าวรอบใหม่ที่รัฐบาลออกมาเป็นระยะๆ
    ระบบจะจัดเก็บแบบฟอร์ม, ระยะเวลา, และลูกจ้างที่จะเข้าระบบมตินั้น
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Caretaker</span> — ดูได้</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบมติ (Tabs)</strong> — แต่ละแถบคือ 1 มติ ครม. (เช่น มติ พ.ย. 2566, มติ มี.ค. 2567)</li>
    <li><strong>การ์ดนายจ้าง</strong> — แสดงนายจ้างที่มีลูกจ้างอยู่ในมตินี้</li>
    <li><strong>ตัวกรองสถานะ</strong> — กรองตามขั้นตอน (รอ, กำลังทำ, เสร็จ)</li>
    <li><strong>ตัวกรองความคืบหน้า</strong> — กรองตาม visa-only / both / renewal</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. เลือกมติที่ต้องการ</h5>
<div class="manual-step">
    คลิกที่แถบ tab ของมตินั้น → จะแสดงนายจ้าง + ลูกจ้างทั้งหมดที่อยู่ในมตินั้น
</div>

<h5>2. ลงทะเบียนลูกจ้างเข้ามติ</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เปิดการ์ดนายจ้าง</li>
        <li>กดปุ่ม "เพิ่มลูกจ้างเข้ามติ"</li>
        <li>เลือกลูกจ้างที่จะเข้ามตินี้</li>
        <li>กด "ยืนยัน"</li>
    </ol>
</div>

<h5>3. ติดตามสถานะลูกจ้างแต่ละคน</h5>
<div class="manual-step">
    การ์ดลูกจ้างแสดง:
    <ul class="mb-0">
        <li>สีฟ้า = visa only (ทำเฉพาะ visa)</li>
        <li>สีฟ้าเข้ม = both (ทำทั้ง visa + work permit)</li>
        <li>กรอบทึบ = ขั้นตอนสูงสุดที่ทำแล้ว</li>
    </ul>
</div>

<h5>4. กรองด้วยตัวกรองหลายชุด</h5>
<div class="manual-step">
    เลือกหลายสถานะพร้อมกันด้วยการกด Ctrl/Cmd ค้าง — กรองหลายความคืบหน้าได้ในครั้งเดียว
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>สีของป้ายความคืบหน้า:</strong> ระบบใช้สี 4 ระดับเพื่อให้เห็นง่ายว่าลูกจ้างคนไหนอยู่ขั้นตอนไหน
</div>

<div class="manual-tip">
    <strong>ตัวกรองสถานะแสดงเฉพาะที่ตรง:</strong> ถ้ากรอง "Visa only" จะเห็นเฉพาะนายจ้างที่มีลูกจ้างทำ visa เท่านั้น
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: เพิ่มมติใหม่ได้ไหม?</dt>
    <dd>A: Super Admin เพิ่มได้ผ่านเมนู Resolution Tabs Settings</dd>

    <dt>Q: ลูกจ้างคนหนึ่งอยู่ได้หลายมติไหม?</dt>
    <dd>A: ได้ — ลูกจ้าง 1 คนสามารถเข้าได้หลายมติ แสดงในการ์ดของทุกมติที่เข้าร่วม</dd>

    <dt>Q: ทำไมการ์ดนายจ้างหายไป?</dt>
    <dd>A: ถ้านายจ้างคนนั้นไม่มีลูกจ้างใน scope ตัวกรองปัจจุบัน การ์ดจะไม่แสดง</dd>
</dl>
