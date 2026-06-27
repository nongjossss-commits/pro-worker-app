{{-- Training Edition: Registration Resolution --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-card-checklist"></i> {{ __('มติลงทะเบียน (Registration Resolution)') }} — {{ __('จัดการมติ ครม. สำหรับขึ้นทะเบียนแรงงาน') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"มติลงทะเบียน"</strong> ใช้จัดการ<strong>มติ ครม.</strong> เกี่ยวกับการขึ้นทะเบียนแรงงานต่างด้าวใหม่
        เช่น มติ ครม. 16 กันยายน, มติพิเศษช่วงโควิด — ระบบรองรับ <strong>หลายมติพร้อมกัน</strong>เป็นแถบ tab
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ดูได้)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เลือกแถบมติ (Resolution Tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/01-tab-bar',
        'alt' => 'แถบเลือก Resolution tab พร้อมปุ่มสร้างใหม่',
        'caption' => 'Resolution Tab Bar — แต่ละ tab คือมติ ครม. รอบหนึ่ง',
        'callouts' => [
            '<strong>Tab Bar:</strong> แต่ละแถบเป็นมติ 1 รอบ (เช่น "มติ ครม 16 ก.ย. 67")',
            '<strong>+ Add Tab:</strong> สร้างมติใหม่ (เฉพาะ Super Admin)',
            '<strong>⚙️ Edit Tab:</strong> เปลี่ยนชื่อ / ลบมติ',
            '<strong>⭐ Default:</strong> แถบ default ที่ระบบเข้ามาตอนแรก',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>มติลงทะเบียน</strong></li>
            <li>คลิกที่แถบ tab ของมติที่ต้องการ</li>
            <li>หน้าจะ refresh แสดงข้อมูลของมตินั้น</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ระบบสีลูกจ้างตามความคืบหน้า</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/02-color-legend',
        'alt' => 'Legend แสดง 5 สีของความคืบหน้า',
        'caption' => 'Color Legend — 5 สีบอกความคืบหน้าของลูกจ้างแต่ละคน',
        'callouts' => [
            '<strong>⚪ Not renewed yet:</strong> ยังไม่เริ่ม',
            '<strong>🟣 อัพเดทวีซ่าแล้ว:</strong> ต่อ visa แล้ว รอ WP',
            '<strong>🟡 อัพเดทใบอนุญาตทำงานแล้ว:</strong> ต่อ WP แล้ว รอ visa',
            '<strong>🔵 Both renewed:</strong> ต่อครบ พร้อมปิดงาน',
            '<strong>🟢 Finalized:</strong> ปิดงานแล้ว',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>สีเปลี่ยนอัตโนมัติ:</strong> เมื่ออัพเดทวันหมดอายุของลูกจ้างให้เลย target ของ Auto Settings → สีเปลี่ยนทันที
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ตั้งค่า Auto Settings (per-tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/03-auto-settings',
        'alt' => 'Auto Settings popup แสดงชื่อ tab และฟิลด์ visa/wp/mou',
        'caption' => 'Auto Settings — ตั้งค่าแยกแต่ละแถบมติ',
        'callouts' => [
            '<strong>หัว popup:</strong> ระบุชื่อแถบเลย ว่าใช้กับแถบนี้เท่านั้น',
            '<strong>Auto WP Expiry:</strong> วันหมดอายุ work permit เป้าหมาย',
            '<strong>Auto Visa Expiry:</strong> วันหมดอายุ visa เป้าหมาย',
            '<strong>Auto MOU Group:</strong> ประเภท MOU ที่ต้องการ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิดแถบมติที่ต้องการ → กดปุ่ม <strong>"Auto Settings"</strong></li>
            <li>กรอกวันหมดอายุ WP + Visa + MOU Group</li>
            <li>กด Save → ใช้กับ <strong>tab นี้เท่านั้น</strong></li>
            <li>ลูกจ้างที่มีวัน match → auto-pull เข้าเมนูทันที</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Add-only:</strong> ลูกจ้างที่อยู่ในเมนูแล้ว <strong>ไม่ถูกดีดออก</strong>เมื่อแก้วันที่ — เฉพาะกด Complete/Cancel เท่านั้นที่จะออก
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ติ๊กขั้นตอน + ติดตามความคืบหน้า</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/04-progress',
        'alt' => 'การ์ดนายจ้างพร้อมลูกจ้างและ steps',
        'caption' => 'Employer Card — ลูกจ้างพร้อม checkbox แต่ละ step',
        'callouts' => [
            '<strong>การ์ดนายจ้าง:</strong> รวมลูกจ้างทั้งหมดของนายจ้างนั้น',
            '<strong>Checkbox steps:</strong> ติ๊กเพื่อบันทึกว่าทำเสร็จ',
            '<strong>การ์ดล่าสุดเลื่อนขึ้นบน:</strong> ติ๊กแล้ว refresh → การ์ดขึ้นบนสุด',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิดการ์ดนายจ้าง → ดูรายชื่อลูกจ้าง</li>
            <li>ติ๊ก checkbox ของแต่ละขั้นตอนที่ทำเสร็จ</li>
            <li>ระบบบันทึก timestamp อัตโนมัติ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: มติลงทะเบียน vs มติต่ออายุ ต่างกันยังไง?</dt>
        <dd>A: ลงทะเบียน = ลูกจ้างใหม่เข้าระบบครั้งแรก, ต่ออายุ = ลูกจ้างเก่าที่ใกล้หมดอายุ</dd>

        <dt>Q: Auto Settings แถบนึงทับซ้อนกับแถบอื่นไหม?</dt>
        <dd>A: ไม่ — แต่ละแถบมี Auto Settings ของตัวเอง (per-tab keys)</dd>

        <dt>Q: ลูกจ้างหายไปจากเมนู เพราะอะไร?</dt>
        <dd>A: ระบบ <strong>ไม่ดีดออกอัตโนมัติ</strong> — เฉพาะกด "เสร็จสิ้น"/"ยกเลิก" manual หรือถ้าลูกจ้างย้ายนายจ้าง</dd>
    </dl>
</section>
