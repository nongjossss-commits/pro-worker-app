{{-- Training Edition: Sales --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cart-fill"></i> {{ __('การขายและใบเสนอราคา (Sales)') }} — {{ __('Pipeline จาก Lead ถึงปิดการขาย') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"Sales"</strong> ใช้จัดการ<strong>กระบวนการขาย</strong>ตั้งแต่ลูกค้าใหม่ (Lead)
        → ปิดการขาย → ส่งต่อเข้า Production
        ใช้ <strong>Kanban board</strong> ลาก-วางเปลี่ยนสถานะได้สะดวก
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู Sales — Kanban Board</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/01-kanban-board',
        'alt' => 'Kanban board แสดง columns ของ sales stages',
        'caption' => 'Sales Kanban — แต่ละ column เป็น stage (New / Contacted / Quoted / Closed)',
        'callouts' => [
            '<strong>Columns:</strong> stage ต่างๆ ของ lead',
            '<strong>Cards:</strong> ลูกค้าแต่ละราย พร้อมข้อมูลย่อ',
            '<strong>Drag & Drop:</strong> ลากการ์ดไปคอลัมน์อื่น = เปลี่ยน stage',
            '<strong>+ New Lead:</strong> เพิ่มลูกค้าใหม่',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>การขาย (Sales)</strong></li>
            <li>ดู Kanban board — แต่ละคอลัมน์คือ stage ของลูกค้า</li>
            <li>กรองด้วย Owner หรือ Search ที่ด้านบน</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">สร้าง Lead ใหม่</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/02-new-lead',
        'alt' => 'ฟอร์มสร้าง Lead ใหม่',
        'caption' => 'New Lead Form — ข้อมูลลูกค้าและช่องทางติดต่อ',
        'callouts' => [
            '<strong>Customer info:</strong> ชื่อ, บริษัท, contact',
            '<strong>Source:</strong> มาจากช่องทางไหน (referral / FB / website)',
            '<strong>Owner:</strong> Sales รับผิดชอบใคร',
            '<strong>Initial stage:</strong> ปกติเริ่มที่ "New" หรือ "Contacted"',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กดปุ่ม <strong>"+ New Lead"</strong></li>
            <li>กรอกข้อมูลลูกค้า + Sales owner</li>
            <li>กด Save → การ์ดปรากฏใน Kanban</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">เพิ่มลูกจ้าง + สร้างใบเสนอราคา</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/03-quotation-modal',
        'alt' => 'Modal สร้างใบเสนอราคา + manage employees',
        'caption' => 'Quotation Modal — เพิ่มลูกจ้าง + ตั้งราคา + Generate PDF',
        'callouts' => [
            '<strong>Manage Employees:</strong> เพิ่มลูกจ้างชั่วคราว (ยังไม่ต้องเป็น real employee)',
            '<strong>Pricing Tiers:</strong> ตั้งราคาต่อหัวเหมือนใน Production',
            '<strong>Generate PDF:</strong> ออกใบเสนอราคา PDF ส่งลูกค้า',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิดการ์ด Lead → กด <strong>"Manage Employees"</strong></li>
            <li>เพิ่มลูกจ้างชั่วคราว (ระบบสร้างเป็น Temp ก่อน)</li>
            <li>เปิด Financial tab → ตั้งราคา</li>
            <li>กด <strong>"Quotation"</strong> → ออก PDF ใบเสนอราคา</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">ปิดการขาย → ส่งเข้า Production (Transition)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/04-transition-to-production',
        'alt' => 'Modal Transition Lead → Production',
        'caption' => 'Transition Modal — แปลง Lead เป็น Production Order',
        'callouts' => [
            '<strong>เลือก Work Type:</strong> งานที่จะส่งให้ Production ทำ',
            '<strong>Confirm transition:</strong> ระบบสร้าง Employer + Employees + Production Order ให้อัตโนมัติ',
            '<strong>Temp employees → real employees:</strong> ลูกจ้างชั่วคราวจะกลายเป็น real ตอนนี้',
            '<strong>Auto-archive lead:</strong> Lead เดิมจะ archive เพราะปิดการขายแล้ว',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ลูกค้าตกลงซื้อ → ลากการ์ดไปคอลัมน์ <strong>"Closed Won"</strong></li>
            <li>กด <strong>"Transition to Production"</strong></li>
            <li>เลือก Work Type → ยืนยัน</li>
            <li>ระบบสร้าง Employer/Employees/Production Order ครบ → ปรากฏในเมนู Pre-Prod ทันที</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> ลูกจ้างชั่วคราวที่ใส่ใน Lead → จะถูกแปลงเป็น real employee อัตโนมัติเมื่อ transition
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">การมองเห็นและสิทธิ์ Sales</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/05-visibility-permissions',
        'alt' => 'Settings Sales menu visibility ใน Super Admin',
        'caption' => 'Sales Menu Visibility — Super Admin เปิด/ปิดเมนูได้',
        'callouts' => [
            '<strong>Default visibility:</strong> Sales เปิด/ปิดได้ใน Super Admin Settings',
            '<strong>Per-role:</strong> Caretaker/Employer ไม่เห็นเมนู Sales',
            '<strong>Owner-scoped:</strong> Staff เห็นเฉพาะ lead ที่ตัวเองเป็น owner (ถ้าตั้งค่า)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Super Admin Settings → Menu Visibility</li>
            <li>เปิด/ปิดเมนู Sales ได้</li>
            <li>ตั้ง role ที่เข้าถึงได้</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: Lead ที่ Closed Lost — ลบทิ้งได้ไหม?</dt>
        <dd>A: ลบได้ — แต่แนะนำให้ archive แทน เพื่อ track ประวัติการขายและ analytics ในอนาคต</dd>

        <dt>Q: หาก lead กลับมาใหม่ในภายหลัง — สร้างใหม่หรือ revive อันเดิม?</dt>
        <dd>A: เปิด Lead เดิมแล้วเปลี่ยน stage กลับมา New หรือ Contacted</dd>

        <dt>Q: Temp employees ใน Lead ต่างจาก Real Employees ยังไง?</dt>
        <dd>A: Temp = ยังไม่มี record ในตาราง employees จริง (เก็บใน JSON), Real = ถูกสร้างเป็น record ในระบบจริง — ตอน transition จะแปลงให้</dd>

        <dt>Q: ใบเสนอราคาที่ออกแล้ว แก้ได้ไหม?</dt>
        <dd>A: ได้ — แต่จะแสดงเลขใบใหม่/เวอร์ชั่นใหม่ในการ generate รอบถัดไป</dd>
    </dl>
</section>
