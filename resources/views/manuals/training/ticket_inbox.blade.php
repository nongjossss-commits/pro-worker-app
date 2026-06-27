{{-- Training Edition: Ticket Inbox --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-inbox-fill"></i> {{ __('กล่องรับเรื่อง (Ticket Inbox)') }} — {{ __('รับและจัดการคำขอจากนายจ้าง') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"กล่องรับเรื่อง"</strong> เป็นที่<strong>รับ ticket</strong>จากนายจ้างที่ส่งคำขอเข้ามา
        เช่น "ขอต่อ visa พนักงานคนนี้", "ขอเปลี่ยน passport" — Admin/Staff รับเรื่อง + assign + ติดตาม
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-tickets)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">รับ Ticket ใหม่ + Assign</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/01-list-assign',
        'alt' => 'รายการ tickets + assignment dropdown',
        'caption' => 'Ticket Inbox — รายการตามสถานะ',
        'callouts' => [
            '<strong>สถานะ:</strong> Open / In Progress / Resolved / Closed',
            '<strong>Assigned to:</strong> Staff รับผิดชอบ ticket นี้',
            '<strong>Priority:</strong> Normal / High / Urgent',
            '<strong>Type:</strong> visa / wp / passport / others',
            '<strong>Last updated:</strong> เวลาที่มีการตอบกลับล่าสุด',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>กล่องรับเรื่อง</strong></li>
            <li>คลิก ticket เพื่อเปิดดู</li>
            <li>กด "Assign to..." → เลือก Staff รับผิดชอบ</li>
            <li>เปลี่ยนสถานะตามการดำเนินงาน</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ตอบกลับ + แนบเอกสาร</h2>

    @include('manuals.training._screenshot', [
        'src' => 'ticket_inbox/02-chat',
        'alt' => 'หน้าตอบกลับ ticket + chat thread',
        'caption' => 'Ticket Detail — Chat thread + attachments',
        'callouts' => [
            '<strong>Message thread:</strong> ข้อความระหว่าง office ↔ employer',
            '<strong>แนบเอกสาร:</strong> upload PDF/รูป',
            '<strong>แนบลูกจ้าง:</strong> link ลูกจ้างคนใดเข้า ticket',
            '<strong>Mark Resolved:</strong> ปิด ticket เมื่อเสร็จ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: Notify ลูกค้าเมื่อตอบกลับ?</dt>
        <dd>A: ระบบส่ง notification + email อัตโนมัติเมื่อมีการตอบกลับ</dd>

        <dt>Q: Reassign ticket ได้ไหม?</dt>
        <dd>A: ได้ — Admin สามารถเปลี่ยน Staff รับผิดชอบได้ทุกเมื่อ</dd>
    </dl>
</section>
