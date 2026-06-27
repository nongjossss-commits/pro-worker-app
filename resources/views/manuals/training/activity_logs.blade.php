{{-- Training Edition: Activity Logs --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clock-history"></i> {{ __('ประวัติการกระทำ (Activity Logs)') }} — {{ __('Audit trail ของทุกการเปลี่ยนแปลงในระบบ') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ประวัติการกระทำ"</strong> บันทึก<strong>ทุกการเปลี่ยนแปลงข้อมูล</strong>ในระบบ
        ใครแก้, แก้อะไร, เมื่อไหร่ — สำหรับ <strong>audit + ตรวจสอบย้อนหลัง</strong>
        เพื่อความโปร่งใส + ป้องกัน fraud
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดดูประวัติ + กรอง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'activity_logs/01-list-filter',
        'alt' => 'รายการ activity log + filter bar',
        'caption' => 'Activity Logs — sortable table + filters',
        'callouts' => [
            '<strong>กรองตามผู้ใช้:</strong> เลือก user เฉพาะ',
            '<strong>กรองตามวันที่:</strong> ระบุช่วงวัน',
            '<strong>กรองตามประเภท:</strong> create / update / delete',
            '<strong>กรองตาม entity:</strong> Employee / Employer / etc.',
            '<strong>Details:</strong> เห็นข้อมูลก่อน-หลังการเปลี่ยนแปลง',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ประวัติการกระทำ</strong></li>
            <li>ใช้ filter เพื่อค้นหา</li>
            <li>คลิกแถวเพื่อดู before/after detail</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ใช้สำหรับ Audit + Investigation</h2>

    <div class="slide-instructions">
        <strong>Use case ที่พบบ่อย:</strong>
        <ol>
            <li>ลูกจ้างหาย — ตรวจว่าใครลบ + เมื่อไหร่</li>
            <li>ข้อมูล passport เปลี่ยน — ตรวจว่าใครแก้</li>
            <li>เลขใบกำกับถูก void — ตรวจว่าใคร + เหตุผล</li>
            <li>ทบทวนการทำงานของ staff รายเดือน</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Pro tip:</strong> Activity Log ป้องกันลบได้ — แม้ Super Admin ก็แก้ไม่ได้ (immutable)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: เก็บประวัติย้อนหลังกี่วัน?</dt>
        <dd>A: ไม่มี auto-delete — เก็บถาวรเพื่อ audit (Super Admin สามารถ purge เก่าได้ถ้าต้อง)</dd>

        <dt>Q: Staff/Caretaker เห็น log ของตัวเองได้ไหม?</dt>
        <dd>A: ไม่ — เฉพาะ Super Admin/Admin ดู Activity Log ได้</dd>
    </dl>
</section>
