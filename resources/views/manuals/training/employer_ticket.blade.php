{{-- Training Edition: Employer Ticket --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-send-fill"></i> {{ __('ส่งคำขอ (Employer Ticket)') }} — {{ __('สำหรับนายจ้าง: ส่งคำขอเข้าออฟฟิศ') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ส่งคำขอ"</strong> สำหรับ <strong>นายจ้าง (Employer role)</strong>
        ส่งคำขอเข้าออฟฟิศโดยตรง เช่น "ขอต่อ Visa", "ขอลาออกพนักงาน" — แทน email/Line
        ออฟฟิศจะรับเรื่องในเมนู <strong>กล่องรับเรื่อง</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">สร้าง Ticket ใหม่</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/01-new-ticket',
        'alt' => 'ฟอร์มสร้าง ticket ใหม่',
        'caption' => 'New Ticket Form — เลือกประเภท + รายละเอียด + แนบเอกสาร',
        'callouts' => [
            '<strong>ประเภท:</strong> Visa / Work Permit / Passport / อื่นๆ',
            '<strong>ลูกจ้างที่เกี่ยวข้อง:</strong> เลือกจากลูกจ้างของตัวเอง',
            '<strong>รายละเอียด:</strong> อธิบายความต้องการ',
            '<strong>แนบไฟล์:</strong> PDF / รูป (optional)',
            '<strong>Priority:</strong> Normal / High / Urgent',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar (Employer) → <strong>ส่งคำขอ</strong> หรือ "+ New Ticket"</li>
            <li>เลือกประเภท + ลูกจ้าง</li>
            <li>กรอกรายละเอียด + แนบเอกสาร</li>
            <li>กด Submit → ออฟฟิศจะได้รับ notification ทันที</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ติดตามสถานะ + ตอบกลับ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employer_ticket/02-status-chat',
        'alt' => 'รายการ tickets + chat thread',
        'caption' => 'My Tickets — ติดตามสถานะ + chat กับออฟฟิศ',
        'callouts' => [
            '<strong>สถานะ:</strong> Open / In Progress / Resolved',
            '<strong>Chat thread:</strong> คุยกับออฟฟิศ',
            '<strong>Notification:</strong> เด้งเมื่อออฟฟิศตอบกลับ',
            '<strong>Mark Resolved:</strong> ปิด ticket เมื่อพอใจ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ส่ง ticket ได้กี่เรื่อง?</dt>
        <dd>A: ไม่จำกัด — แต่ละ ticket แยกเรื่อง</dd>

        <dt>Q: เห็น ticket ของบริษัทอื่นไหม?</dt>
        <dd>A: ไม่ — เห็นเฉพาะของบริษัทตัวเอง</dd>

        <dt>Q: ออฟฟิศปิด ticket ของฉัน — แต่ยังไม่จบ?</dt>
        <dd>A: เปิด ticket ใหม่ + อ้างอิงเลข ticket เก่า</dd>
    </dl>
</section>
