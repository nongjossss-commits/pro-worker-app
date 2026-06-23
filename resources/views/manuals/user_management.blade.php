{{-- User Manual: User Management --}}

<h4><i class="bi bi-person-fill-gear me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"จัดการผู้ใช้งาน (User Management)"</strong> ใช้สำหรับสร้าง/แก้/ลบ
    <strong>บัญชีผู้ใช้</strong>ของระบบ พร้อมกำหนด<strong>บทบาท (Role)</strong> ให้แต่ละคน
    + ดู<strong>สิทธิ์ (Permissions)</strong>ของแต่ละ Role ที่ด้านล่างหน้า
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้</li>
    <li>(Super Admin เห็น super-admin users ทุกคน; Admin เห็นทุกคนยกเว้น super-admin)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>หน้าตาของหน้านี้</h4>
<ol>
    <li><strong>แถบค้นหา</strong> + ตัวกรองตาม role</li>
    <li><strong>แถบ Tabs ตาม role</strong> — Super Admin, Admin, Caretaker, Staff, Employer</li>
    <li><strong>ตารางผู้ใช้</strong> — ชื่อ, อีเมล, สถานะ, การกระทำ</li>
    <li><strong>Section "บทบาท & สิทธิ์"</strong> ที่ด้านล่าง (เฉพาะคนที่มีสิทธิ์ manage-roles) — ดู role ทั้งหมด + สิทธิ์ที่ผูก</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. สร้างผู้ใช้ใหม่</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>กด "+ เพิ่มผู้ใช้ใหม่"</li>
        <li>กรอกชื่อ, อีเมล, รหัสผ่าน</li>
        <li>เลือก Role (เช่น Staff, Caretaker)</li>
        <li>กด "บันทึก" — ผู้ใช้พร้อม login</li>
    </ol>
</div>

<h5>2. เปลี่ยน Role ของผู้ใช้</h5>
<div class="manual-step">
    คลิกที่ชื่อผู้ใช้ → เลือก Role ใหม่ → บันทึก
</div>

<h5>3. ปิด/เปิดบัญชี (Status)</h5>
<div class="manual-step">
    กดสวิตช์ Status ที่แถวผู้ใช้ — Active = login ได้, Inactive = login ไม่ได้
</div>

<h5>4. รีเซ็ตรหัสผ่าน</h5>
<div class="manual-step">
    คลิกที่ชื่อ → กดปุ่ม "เปลี่ยนรหัสผ่าน" → กรอกใหม่ → บันทึก
</div>

<h5>5. ดูสิทธิ์ของแต่ละ Role</h5>
<div class="manual-step">
    เลื่อนลงไปด้านล่างของหน้า — Section "บทบาท & สิทธิ์" แสดง role ทั้งหมด + permissions ที่ผูกอยู่
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>5 Roles ใน Pro-Worker:</strong>
    <ul class="mb-0 mt-1">
        <li><span class="manual-role">Super Admin</span> — สิทธิ์ทุกอย่าง</li>
        <li><span class="manual-role">Admin</span> — สิทธิ์ทุกอย่างยกเว้น Super Admin Settings</li>
        <li><span class="manual-role">Staff</span> — งานหลัก (ลูกจ้าง, นายจ้าง, การเงิน)</li>
        <li><span class="manual-role">Caretaker</span> — ดูแลลูกจ้าง (ไม่ลบ)</li>
        <li><span class="manual-role">Employer</span> — ลูกค้าที่ login ดูข้อมูลตัวเอง</li>
    </ul>
</div>

<div class="manual-warn">
    <strong>ระวัง:</strong> อย่าให้ role <strong>Admin</strong> กับใครที่ไม่ใช่หัวหน้า — Admin มีสิทธิ์ลบและแก้ทุกอย่าง
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ลบผู้ใช้ผิดคน เอากลับมาได้ไหม?</dt>
    <dd>A: ไปที่ Central Trash → กดกู้คืน (เฉพาะ Super Admin)</dd>

    <dt>Q: เปลี่ยนสิทธิ์ของ Role ได้ไหม?</dt>
    <dd>A: ผ่าน UI ไม่ได้ — ต้องผ่าน command line (Tinker) หรือแก้ Seeder</dd>

    <dt>Q: ทำไม Staff ของผมเข้าเมนูบางอย่างไม่ได้?</dt>
    <dd>A: เช็คใน Section "บทบาท & สิทธิ์" — ดูว่า Staff มีสิทธิ์อะไรบ้าง ถ้าขาด ติดต่อ Super Admin เพิ่ม</dd>
</dl>
