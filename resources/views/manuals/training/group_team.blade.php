{{-- Training Edition: Group & Team --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('กลุ่มและทีม (Group & Team)') }} — {{ __('จัดกลุ่มลูกจ้างเป็นทีมย่อยๆ') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"กลุ่มและทีม"</strong> ใช้<strong>จัดกลุ่มลูกจ้าง</strong>เป็นทีมย่อยๆ
        เช่น "ทีมโรงงาน A กะเช้า", "ทีมแม่บ้าน" — เพื่อจัดการเป็นหน่วยได้
        แบ่ง 2 ประเภท: <strong>Affiliated</strong> (ผูกนายจ้าง) + <strong>Independent</strong> (อิสระ)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (เฉพาะกลุ่มที่ดูแล)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เลือกประเภทกลุ่ม — Affiliated vs Independent</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/01-type-selection',
        'alt' => 'หน้าเลือกประเภทกลุ่ม 2 cards',
        'caption' => 'Group Type Selection — เลือก Affiliated หรือ Independent',
        'callouts' => [
            '<strong>Affiliated:</strong> ผูกกับนายจ้าง 1 ราย — ลูกจ้างในกลุ่มต้องอยู่นายจ้างนั้น',
            '<strong>Independent:</strong> ไม่ผูกนายจ้าง — ลูกจ้างจากนายจ้างไหนก็ใส่ได้',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>กลุ่มและทีม</strong></li>
            <li>เลือก Affiliated หรือ Independent</li>
            <li>ถ้า Affiliated → เลือกนายจ้างก่อน</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">สร้างกลุ่ม + เพิ่มสมาชิก + ทีมย่อย</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/02-manage',
        'alt' => 'หน้า Manage Groups พร้อม accordion ทีมย่อย',
        'caption' => 'Manage Groups — Accordion ของแต่ละกลุ่ม + ทีมย่อย',
        'callouts' => [
            '<strong>+ สร้างกลุ่มใหม่:</strong> ตั้งชื่อ → ยืนยัน',
            '<strong>+ เพิ่มสมาชิก:</strong> ค้นหาลูกจ้าง → ติ๊ก → ยืนยัน',
            '<strong>+ สร้างทีมย่อย:</strong> แบ่ง sub-team ภายในกลุ่ม',
            '<strong>Drag & Drop:</strong> ลากลูกจ้างระหว่างทีม',
            '<strong>Highlight pulse:</strong> ทีมที่เพิ่มล่าสุดจะกระพริบสีส้ม',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด "+ สร้างกลุ่มใหม่" → ตั้งชื่อ</li>
            <li>กด "+ เพิ่มสมาชิก" → ค้นหา → ติ๊ก → ยืนยัน</li>
            <li>กด "+ สร้างทีมย่อย" ถ้าต้องแบ่งกลุ่มย่อย</li>
            <li>ลากลูกจ้างระหว่างทีมได้</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ใช้กลุ่มในการสร้างงาน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/03-use-in-workflow',
        'alt' => 'การใช้ Group Name ในการสร้าง Production / Workflow item',
        'caption' => 'Group Name — ใช้ระบุใน Production/Workflow item',
        'callouts' => [
            '<strong>Field "Group Name":</strong> มีอยู่ทุกฟอร์มสร้างงาน',
            '<strong>Auto-link:</strong> ระบบนำลูกจ้างในกลุ่มมาใช้ร่วมกัน',
            '<strong>จัดการเป็นหน่วยเดียว:</strong> วางบิลแบบ batch + สร้าง PDF batch',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิดฟอร์มสร้างงานใน Production / Workflow</li>
            <li>ระบุ Group Name ตรงกับชื่อกลุ่มที่สร้างไว้</li>
            <li>ระบบ link อัตโนมัติ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ลูกจ้างคนหนึ่งอยู่ได้กี่กลุ่ม?</dt>
        <dd>A: หลายกลุ่มได้พร้อมกัน — เช่นอยู่ Affiliated ของนายจ้าง A + Independent "ไปสัมภาษณ์ 25/3" พร้อมกัน</dd>

        <dt>Q: ลูกจ้างย้ายนายจ้าง — กลุ่ม Affiliated เดิมเป็นยังไง?</dt>
        <dd>A: <strong>ถอดออกอัตโนมัติ</strong>จากกลุ่ม Affiliated เดิม เพราะกลุ่มผูกกับนายจ้าง — Independent ไม่กระทบ</dd>

        <dt>Q: สร้างชื่อกลุ่มซ้ำได้ไหม?</dt>
        <dd>A: ภายในนายจ้างเดียวกันไม่ได้ — ระบบจะเตือน</dd>
    </dl>
</section>
