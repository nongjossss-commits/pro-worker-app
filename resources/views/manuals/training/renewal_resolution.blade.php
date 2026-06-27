{{-- Training Edition: Renewal Resolution --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-arrow-clockwise"></i> {{ __('มติต่ออายุ (Renewal Resolution)') }} — {{ __('จัดการมติ ครม. สำหรับต่ออายุแรงงาน') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"มติต่ออายุ"</strong> ใช้จัดการ<strong>มติ ครม.</strong> สำหรับ<strong>ต่ออายุ</strong>ลูกจ้างที่กำลังจะหมดอายุ
        ใช้กลไกแบบเดียวกับมติลงทะเบียน — แต่เน้นไปที่ลูกจ้างเก่าที่ใกล้หมดอายุ Work Permit หรือ Visa
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
    <h2 class="slide-title">เปิดเมนู + เลือกแถบมติ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/01-tab-bar',
        'alt' => 'หน้าหลัก มติต่ออายุ + tab bar',
        'caption' => 'มติต่ออายุ — แต่ละ tab เป็นรอบของมติต่ออายุ',
        'callouts' => [
            '<strong>Tab Bar:</strong> เช่น "ต่ออายุ 2568 รอบ 1"',
            '<strong>Stats cards:</strong> รวมลูกจ้าง / ทำเสร็จ / รอ',
            '<strong>Filter pills:</strong> 5 สีตามความคืบหน้า',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>มติต่ออายุ</strong></li>
            <li>คลิกที่ tab ของมติต่ออายุที่ต้องการ</li>
            <li>ดูภาพรวมในการ์ดสรุปด้านบน</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Filter pills — กรองตามความคืบหน้า</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/02-filter-pills',
        'alt' => 'Filter pills 5 สี',
        'caption' => 'Filter pills — เลือกหลายตัวพร้อมกันได้',
        'callouts' => [
            '<strong>⚪ ยังไม่ต่อ:</strong> ลูกจ้างที่ยังไม่เริ่มต่ออายุ',
            '<strong>🟣 อัพเดทวีซ่าแล้ว:</strong> ต่อ visa แล้ว เหลือ WP',
            '<strong>🟡 อัพเดทใบอนุญาตทำงานแล้ว:</strong> ต่อ WP แล้ว เหลือ visa',
            '<strong>🔵 ต่อครบ – พร้อมเสร็จสิ้น:</strong> ต่อครบทั้ง 2 อย่าง พร้อมปิดงาน',
            '<strong>🟢 เสร็จสิ้นแล้ว:</strong> ปิดงานแล้ว',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิก pill ของสถานะที่ต้องการกรอง</li>
            <li>กดหลายอันพร้อมกันได้ (เปิด/ปิด toggle)</li>
            <li>นับจำนวนข้างใน pill บอกจำนวนที่ตรง filter</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">ตั้งค่า Auto Settings (Per-tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/03-auto-settings',
        'alt' => 'Auto Settings popup สำหรับ tab ต่ออายุ',
        'caption' => 'Auto Settings — ตั้งค่าวันหมดอายุเป้าหมายแยกแต่ละ tab',
        'callouts' => [
            '<strong>หัว popup ระบุชื่อ tab:</strong> ป้องกันสับสนกับ tab อื่น',
            '<strong>Auto WP/Visa Expiry:</strong> วันหมดอายุที่ใช้กับ tab นี้',
            '<strong>Auto MOU Group:</strong> ประเภท MOU ของ tab นี้',
            '<strong>Save Settings:</strong> ใช้กับ tab นี้เท่านั้น',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิด tab → กด <strong>"Auto Settings"</strong></li>
            <li>กรอกวันหมดอายุเป้าหมาย</li>
            <li>กด Save</li>
            <li>ลูกจ้างที่มีวันหมดอายุ match → auto-pull เข้า tab</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ติดตามความคืบหน้า + ติ๊กขั้นตอน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/04-progress-tracking',
        'alt' => 'การ์ดนายจ้างพร้อม progress steps',
        'caption' => 'การ์ดนายจ้าง — แต่ละลูกจ้างมี steps ให้ติ๊ก',
        'callouts' => [
            '<strong>การ์ดลูกจ้าง:</strong> สีเปลี่ยนตามความคืบหน้า (5 สี)',
            '<strong>Step checkboxes:</strong> ติ๊กตามขั้นตอน',
            '<strong>การ์ดเลื่อนขึ้นบน:</strong> ทุกครั้งที่ติ๊ก/แก้ข้อมูล → refresh แล้วการ์ดล่าสุดอยู่บน',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิดการ์ดนายจ้าง</li>
            <li>ติ๊ก checkbox ของแต่ละขั้นตอน</li>
            <li>สีของการ์ดลูกจ้างจะเปลี่ยนตามความคืบหน้า</li>
            <li>เมื่อต่อครบ → สีเขียว → กด <strong>"เสร็จสิ้น"</strong> เพื่อปิดงาน</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">การ์ดสรุปสถิติ + ข้อมูลภาพรวม</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/05-stats-cards',
        'alt' => 'การ์ดสรุปสถิติด้านบนของหน้า',
        'caption' => 'การ์ดสรุปสถิติ — บอกภาพรวมของ tab',
        'callouts' => [
            '<strong>พนักงานทั้งหมด:</strong> รวมในมตินี้',
            '<strong>ยกเลิกทั้งหมด:</strong> ที่ถูกยกเลิก',
            '<strong>บันทึกลงฐานข้อมูลแล้ว:</strong> เสร็จสิ้น',
            '<strong>Biometrics Collected:</strong> เก็บไบโอเมตริกแล้ว',
            '<strong>นายจ้างทั้งหมด:</strong> จำนวนนายจ้างใน tab นี้',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>คลิกที่การ์ดสถิติ:</strong> = filter ไปยังหมวดนั้นทันที (เช่น คลิก "เสร็จสิ้น" → กรองเฉพาะที่เสร็จแล้ว)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ลูกจ้างหมดอายุไปแล้ว ต่อได้ไหม?</dt>
        <dd>A: ขึ้นกับเงื่อนไขของมติ — บางมติอนุญาตให้ต่อย้อนหลังได้ ตรวจกฎกระทรวงก่อน</dd>

        <dt>Q: ทำไมต่ออายุไม่ได้?</dt>
        <dd>A: ตรวจว่าลูกจ้างอยู่ในสถานะ "ทำงานอยู่" (ไม่ใช่ลาออก/ครบสัญญา)</dd>

        <dt>Q: ลูกจ้างถูกดีดออกหลังอัพเดทวันหมดอายุ?</dt>
        <dd>A: ไม่ควรเกิด — ระบบเป็น "add-only" ไม่ดีดออกอัตโนมัติ (bug แก้แล้วใน commit ก่อน)</dd>
    </dl>
</section>
