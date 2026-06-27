{{-- Training Edition: Pre-Production --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clipboard-data-fill"></i> {{ __('P Production (Pre-Production)') }} — {{ __('ศูนย์เตรียมเอกสารก่อนเข้า Workflow') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"Pre-Production"</strong> เป็นที่<strong>เตรียมเอกสาร</strong>และข้อมูลของลูกค้าก่อนส่งเข้า Workflow
        ใช้สำหรับลูกค้าใหม่ที่ปิดการขายจาก Sales มาแล้ว → เตรียม Pre-Prod → ส่งเข้า Workflow ดำเนินงานต่อ
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
    <h2 class="slide-title">เข้าหน้า Pre-Production</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/01-main-view',
        'alt' => 'หน้าหลัก Pre-Production แสดงการ์ดงานนายจ้าง',
        'caption' => 'Pre-Production main view — แต่ละการ์ดเป็นงานของนายจ้าง 1 ราย',
        'callouts' => [
            '<strong>การ์ดสรุปสถิติด้านบน:</strong> ใกล้ครบกำหนด / กำลังทำ / รอตรวจ',
            '<strong>ตัวกรอง:</strong> นายจ้าง / เจ้าของงาน / ประเภทงาน (MOU/Visa)',
            '<strong>การ์ดงาน:</strong> รูปเซลล์ + ชื่อนายจ้าง + จำนวนลูกจ้าง + สถานะ',
            '<strong>การ์ดล่าสุดเลื่อนขึ้นบน:</strong> เมื่อมีกิจกรรมล่าสุด (ติ๊กขั้นตอน/แก้ข้อมูล)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Pre-Production</strong></li>
            <li>ดูการ์ดสรุปสถิติด้านบน</li>
            <li>ใช้ filter เพื่อกรองตามนายจ้างหรือ owner</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">เปิดงาน + แก้ไขข้อมูลทีละลูกจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/02-edit-job',
        'alt' => 'หน้า Edit Job มีหลายแท็บ: ลูกจ้าง, เอกสาร, การเงิน',
        'caption' => 'หน้า Edit Job — Tabs: ลูกจ้าง / เอกสาร / การเงิน / ระยะเวลา',
        'callouts' => [
            '<strong>Tab Bar:</strong> สลับระหว่าง Employee / Document / Financial / Timeline',
            '<strong>Employee Card:</strong> แต่ละลูกจ้างมีปุ่มแก้ไข + ดูเอกสาร',
            '<strong>Document Scanner:</strong> ถ่ายรูปจากกล้องเข้าระบบโดยตรง',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกการ์ดงานนายจ้าง → เข้าหน้า Edit Job</li>
            <li>เลือก Tab <strong>"ลูกจ้าง"</strong></li>
            <li>คลิกปุ่มแก้ไข ✏️ เพื่อแก้ข้อมูลลูกจ้างแต่ละคน</li>
            <li>อัพโหลดเอกสารผ่าน Upload หรือ Document Scanner</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">เพิ่ม Custom Field</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/03-custom-fields',
        'alt' => 'Modal เพิ่ม Custom Field สำหรับงานพิเศษ',
        'caption' => 'Custom Fields — เพิ่มฟิลด์เฉพาะสำหรับงานนั้นๆ',
        'callouts' => [
            '<strong>ปุ่ม "Fields":</strong> อยู่บนการ์ด MOU',
            '<strong>เพิ่มฟิลด์ใหม่:</strong> เช่น "เลขใบรับรองแพทย์", "วันที่นัด"',
            '<strong>ระบุประเภท:</strong> text / number / date / dropdown',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กดปุ่ม <strong>"Fields"</strong> บนการ์ดงาน</li>
            <li>กด "+ เพิ่มฟิลด์ใหม่"</li>
            <li>ตั้งชื่อฟิลด์ + เลือกประเภท → บันทึก</li>
            <li>ฟิลด์ใหม่จะอยู่ที่ Tab Custom Fields ของแต่ละลูกจ้าง</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">แท็บการเงิน (Financial Tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/04-financial-tab',
        'alt' => 'Financial tab พร้อม pricing tier cards',
        'caption' => 'Financial Tab — สร้าง pricing tier + งวด + วางบิล',
        'callouts' => [
            '<strong>+ เพิ่มแท็บ:</strong> สร้างแท็บการเงินหลายอันต่องาน (เช่น "ค่าบริการ", "งวดเปลี่ยนนายจ้าง")',
            '<strong>Pricing Tiers:</strong> ตั้งราคาต่อหัวเป็นขั้นๆ + จำนวน + หมายเหตุ',
            '<strong>Note popup:</strong> คลิกที่หมายเหตุ → popup ใหญ่ + counter 500 ตัวอักษร',
            '<strong>ปุ่มดินสอ / ถังขยะ:</strong> แก้/ลบ tier (delete มี confirm)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิด Edit Job → คลิก tab <strong>"Financial"</strong> หรือปุ่ม "การเงิน"</li>
            <li>กด <strong>"+ เพิ่มแท็บ"</strong> → ตั้งชื่อ (ห้ามว่าง/ห้ามซ้ำ)</li>
            <li>เลือกโหมด "ต่อหัว (Per-head)" → เพิ่ม Pricing Tier</li>
            <li>คลิกที่ <strong>กล่องหมายเหตุ</strong> → popup เปิดให้พิมพ์</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>หมายเหตุนี้แสดงในใบแจ้งหนี้/ใบเสร็จด้วย</strong> — ใช้อธิบายให้ลูกค้าเข้าใจว่าเก็บค่าอะไร
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">ส่งงานเข้า Workflow</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/05-send-to-workflow',
        'alt' => 'ปุ่ม Send to Workflow + Bulk Send',
        'caption' => 'Send to Workflow — ส่งทีละคนหรือทั้งใบในครั้งเดียว',
        'callouts' => [
            '<strong>Send to Workflow:</strong> ส่งงานเข้าขั้นตอน Workflow',
            '<strong>Bulk Send:</strong> ส่งทั้งใบ MOU เดียวกันในคลิกเดียว',
            '<strong>สิทธิ์:</strong> เฉพาะ approve-production (Admin/Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ตรวจเอกสาร + ข้อมูลให้พร้อม</li>
            <li>กดปุ่ม <strong>"Send to Workflow"</strong> (ทีละคน) หรือ <strong>"ส่งทั้งใบ"</strong> (Bulk)</li>
            <li>งานย้ายไปอยู่ในเมนู <strong>Workflow</strong></li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ระวัง:</strong> งานที่ส่งไป Workflow แล้ว — กลับมาแก้ใน Pre-Prod ไม่ได้ ต้องแก้ใน Workflow แทน
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ทำไมไม่เห็นปุ่ม "Send to Workflow"?</dt>
        <dd>A: ตรวจ role ของคุณ — ต้องมีสิทธิ์ <code>approve-production</code> (Admin/Super Admin)</dd>

        <dt>Q: ลูกจ้างที่ลาออกระหว่าง Pre-Prod?</dt>
        <dd>A: ลบลูกจ้างคนนั้นจากงาน Pre-Prod หรือ Cancel ทั้งใบหากทุกคนลาออก</dd>

        <dt>Q: ลูกจ้างคนเดียวอยู่ใน Pre-Prod หลายงานได้ไหม?</dt>
        <dd>A: ได้ ถ้าเป็น Work Type ต่างกัน (เช่น MOU + Visa Renewal พร้อมกัน) — ห้ามซ้ำ Work Type เดียวกัน</dd>
    </dl>
</section>
