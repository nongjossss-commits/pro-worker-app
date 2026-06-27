{{-- Training Edition: Employees — slide-friendly with annotated screenshots --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('ข้อมูลลูกจ้าง') }} — {{ __('จัดการข้อมูลแรงงานต่างด้าวทั้งหมด') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ข้อมูลลูกจ้าง"</strong> ใช้สำหรับ<strong>เพิ่ม / แก้ไข / ดู</strong> ข้อมูลลูกจ้างทุกคน
        — ข้อมูลส่วนตัว, passport, visa, ใบอนุญาตทำงาน, รูปถ่าย, เอกสารแนบ
        เป็นจุดเริ่มต้นสำหรับงานทุกประเภท (Production, Workflow, มติลงทะเบียน, มติต่ออายุ)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (เฉพาะลูกจ้างที่ดูแล)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดหน้ารายชื่อลูกจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/01-list-view',
        'alt' => 'หน้ารายชื่อลูกจ้าง (card view) พร้อม filter bar',
        'caption' => 'หน้ารายชื่อลูกจ้าง — เปลี่ยนระหว่าง Card view และ Table view ได้',
        'callouts' => [
            '<strong>Filter bar:</strong> ค้นหา, กรองสัญชาติ, MOU group, พาสปอร์ต',
            '<strong>+ เพิ่มลูกจ้าง:</strong> ปุ่มสร้างลูกจ้างใหม่',
            '<strong>มุมมอง Card/Table:</strong> สลับได้ตามต้องการ',
            '<strong>Bulk Action:</strong> ติ๊กหลายคนเพื่อ Export, ย้ายนายจ้าง, สร้าง PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกที่ <strong>Sidebar → ข้อมูลลูกจ้าง</strong></li>
            <li>เลือกประเภทมุมมอง (<strong>Card</strong> หรือ <strong>Table</strong>)</li>
            <li>ใช้ filter ด้านบนเพื่อค้นหาลูกจ้างที่ต้องการ</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> เมนู "ประวัติการจ้างงาน" แสดงทุกคนรวมที่ลาออก — แตกต่างจากเมนูนี้ที่แสดงเฉพาะ active
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">เพิ่มลูกจ้างใหม่</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/02-add-employee',
        'alt' => 'หน้าฟอร์มสร้างลูกจ้างใหม่',
        'caption' => 'ฟอร์มสร้างลูกจ้างใหม่ — หลายแท็บสำหรับข้อมูลแต่ละหมวด',
        'callouts' => [
            '<strong>เลือก Employer:</strong> ลูกจ้างต้องผูกกับนายจ้างเสมอ',
            '<strong>Required fields:</strong> ชื่อ, สัญชาติ, passport',
            '<strong>Tabs:</strong> ข้อมูลทั่วไป → Passport/Visa → เอกสาร → รูปถ่าย',
            '<strong>Document Scanner:</strong> ถ่ายรูปจากกล้องเข้าระบบได้เลย',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กดปุ่ม <strong>"+ เพิ่มลูกจ้าง"</strong> (มุมขวาบน)</li>
            <li>เลือก <strong>นายจ้าง</strong> (พิมพ์ค้นหาได้)</li>
            <li>กรอกข้อมูล <strong>ชื่อ + สัญชาติ + passport</strong> (บังคับ)</li>
            <li>กรอกข้อมูลเพิ่มเติมในแต่ละแท็บ (ไม่บังคับ — แก้ภายหลังได้)</li>
            <li>กด <strong>"บันทึก"</strong></li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ระวัง:</strong> Employee Cap — ระบบจำกัดจำนวนลูกจ้างรวมตาม subscription tier
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">แก้ไขข้อมูลลูกจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/03-edit-employee',
        'alt' => 'หน้าแก้ไขลูกจ้างพร้อมแท็บข้อมูลและเอกสาร',
        'caption' => 'หน้าแก้ไขลูกจ้าง — แท็บ Personal, Documents, History',
        'callouts' => [
            '<strong>Personal:</strong> ชื่อ, ที่อยู่, สัญชาติ, วันเกิด',
            '<strong>Documents:</strong> passport, visa, work permit + upload PDF/รูป',
            '<strong>Other Documents:</strong> 10 slots สำหรับเอกสารเสริม (ตั้งชื่อ default ใน Super Admin)',
            '<strong>History tab:</strong> ดูประวัติการเปลี่ยนแปลง + activity log',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกที่ <strong>การ์ดลูกจ้าง</strong> หรือปุ่มดินสอ ✏️</li>
            <li>แท็บแต่ละหมวดมีข้อมูลให้กรอก</li>
            <li>อัพโหลดไฟล์ผ่านปุ่ม <strong>Upload</strong> หรือใช้ <strong>Document Scanner</strong></li>
            <li>กด <strong>"บันทึก"</strong> — ระบบ log การเปลี่ยนแปลงใน Activity Log</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> แก้ไขข้อมูลลูกจ้าง → การ์ดงานนายจ้างของคนนี้จะเลื่อนขึ้นบนใน Workflow/Production
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ปุ่ม Preview ลูกจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/04-preview-popup',
        'alt' => 'Preview popup แสดงข้อมูลลูกจ้างแบบ read-only',
        'caption' => 'Preview Popup — ดูข้อมูลลูกจ้างได้รวดเร็วโดยไม่ต้องเปิดหน้าแก้ไข',
        'callouts' => [
            '<strong>ปุ่ม Preview 🔍:</strong> อยู่ที่การ์ดลูกจ้างทุกหน้า',
            '<strong>Read-only:</strong> ดูได้แต่แก้ไม่ได้',
            '<strong>ครอบคลุม:</strong> Personal, passport, visa, เอกสาร, รูป',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>มองหาปุ่มไอคอน <strong>แว่นขยาย 🔍</strong> ที่การ์ดลูกจ้าง</li>
            <li>คลิก → Modal เด้งแสดงข้อมูลทั้งหมด</li>
            <li>ปิด modal หรือคลิกพื้นที่ว่างเพื่อกลับ</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Caretaker:</strong> ใช้ Preview ได้กับลูกจ้างของนายจ้างที่ดูแล (assigned) เท่านั้น
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Bulk Actions — ทำพร้อมกันหลายคน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/05-bulk-actions',
        'alt' => 'Bulk action bar ลอยด้านล่างเมื่อ tick หลายคน',
        'caption' => 'Bulk Action Bar — ลอยขึ้นมาเมื่อ tick checkbox ลูกจ้างหลายคน',
        'callouts' => [
            '<strong>Tick checkbox:</strong> ทุกการ์ดมี checkbox มุมซ้ายบน',
            '<strong>Action menu:</strong> Export, ย้ายนายจ้าง, สร้าง PDF, ส่งเข้า Production',
            '<strong>Counter:</strong> แสดงจำนวนที่เลือก',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tick <strong>checkbox</strong> ของลูกจ้างที่ต้องการ (หลายคนได้)</li>
            <li>Bulk Action Bar จะลอยขึ้นที่ด้านล่าง</li>
            <li>เลือก action จาก dropdown:
                <ul>
                    <li><strong>Export CSV / Advanced Export</strong></li>
                    <li><strong>ย้ายนายจ้าง</strong> (Bulk Transfer)</li>
                    <li><strong>Automated PDF</strong> (สร้าง PDF จาก template)</li>
                    <li><strong>Send to Production</strong></li>
                </ul>
            </li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: เพิ่มลูกจ้างใหม่ไม่ได้ — error?</dt>
        <dd>A: ตรวจ Employee Cap — ระบบจำกัดจำนวนตาม subscription, ติดต่อ Super Admin หากต้องเพิ่ม</dd>

        <dt>Q: ลูกจ้างหายไปจากรายการ?</dt>
        <dd>A: ไปดูที่เมนู "ประวัติการจ้างงาน" — อาจถูกแจ้งออก/ครบสัญญาแล้ว, หรือถูกลบไปอยู่ใน "ถังขยะกลาง"</dd>

        <dt>Q: Caretaker เห็นลูกจ้างน้อยกว่าที่ควร?</dt>
        <dd>A: Caretaker เห็นเฉพาะลูกจ้างของนายจ้างที่ตัวเอง assigned</dd>

        <dt>Q: ปุ่ม Preview ใช้ไม่ได้ — Error 500?</dt>
        <dd>A: เคยมี bug — ตอนนี้แก้แล้ว Caretaker preview ได้ปกติ (เห็นเฉพาะลูกจ้างที่ตัวเองดูแล)</dd>
    </dl>
</section>
