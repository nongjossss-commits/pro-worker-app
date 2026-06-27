{{-- Training Edition: Notifications --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-bell-fill"></i> {{ __('การแจ้งเตือน (Notifications)') }} — {{ __('ศูนย์รวมแจ้งเตือนทุกประเภท') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"การแจ้งเตือน"</strong> รวบรวม<strong>การแจ้งเตือนทั้งหมด</strong>ในระบบ
        เช่น ลูกจ้างใกล้หมดอายุ, ใบเสนอราคา approved, ticket ใหม่จากลูกค้า
        รองรับ <strong>Web Push</strong> (notification เด้งบน browser) + <strong>In-app bell icon</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">ดู Notifications + Mark as Read</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/01-list',
        'alt' => 'รายการ notifications + filter unread/all',
        'caption' => 'Notifications List — แยก unread/read',
        'callouts' => [
            '<strong>Bell icon:</strong> มุมขวาบน navbar — มี badge นับ unread',
            '<strong>Filter:</strong> Unread / All / By type',
            '<strong>Click notification:</strong> เปิดเรื่องที่เกี่ยวข้องโดยตรง',
            '<strong>Mark all as read:</strong> เคลียร์ badge counter',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ประเภทการแจ้งเตือน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/02-types',
        'alt' => 'ตัวอย่าง notification ประเภทต่างๆ',
        'caption' => 'Notification Types — สีและไอคอนต่างกัน',
        'callouts' => [
            '<strong>🔴 Expiry alerts:</strong> ลูกจ้างใกล้หมดอายุ (passport/visa/WP)',
            '<strong>🔵 Ticket:</strong> ลูกค้าส่งคำขอใหม่',
            '<strong>🟢 Approved:</strong> ใบเสนอราคา / สัญญา approved',
            '<strong>🟡 Workflow:</strong> งานเข้าขั้นตอนใหม่',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">เปิด Web Push Notifications</h2>

    @include('manuals.training._screenshot', [
        'src' => 'notifications/03-web-push',
        'alt' => 'Browser popup ขอ permission web push',
        'caption' => 'Web Push — รับแจ้งเตือนแม้ปิด browser',
        'callouts' => [
            '<strong>Permission popup:</strong> เด้งครั้งแรกที่ login',
            '<strong>"Allow":</strong> รับ notification ผ่าน browser',
            '<strong>Background:</strong> ทำงานแม้ปิดแท็บ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Login → browser จะถาม permission</li>
            <li>กด <strong>"Allow"</strong></li>
            <li>เมื่อมี notification → เด้งบน browser ทันที</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ไม่เห็น web push popup?</dt>
        <dd>A: Browser settings → site permissions → notifications → allow ด้วยตนเอง</dd>

        <dt>Q: ใครได้รับ notification?</dt>
        <dd>A: ขึ้นกับ role — Admin เห็นทุกอย่าง, Caretaker/Employer เห็นเฉพาะของตัวเอง</dd>
    </dl>
</section>
