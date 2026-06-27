{{-- Training Edition: Central Trash --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-trash-fill"></i> {{ __('ถังขยะกลาง (Central Trash)') }} — {{ __('กู้คืนข้อมูลที่ลบไป') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ถังขยะกลาง"</strong> รวบรวม<strong>ข้อมูลที่ถูกลบ</strong>จากทั่วระบบ
        (ลูกจ้าง / นายจ้าง / Production / etc.) — <strong>กู้คืนได้</strong>ภายในระยะเวลาที่กำหนด
        หรือลบถาวรถ้าไม่ต้องการ
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดดูรายการที่ถูกลบ</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/01-list',
        'alt' => 'รายการ trash items แยกตามประเภท',
        'caption' => 'Central Trash — แยก tabs ตามประเภท entity',
        'callouts' => [
            '<strong>Tabs:</strong> Employees / Employers / Production / etc.',
            '<strong>Deleted at:</strong> วันที่ลบ',
            '<strong>Deleted by:</strong> ใครเป็นคนลบ',
            '<strong>Days remaining:</strong> นับถอยหลังก่อน auto-purge',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ถังขยะกลาง</strong></li>
            <li>เลือก tab ของประเภทข้อมูลที่ลบ</li>
            <li>ค้นหารายการที่ต้องการกู้</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Restore (กู้คืน) หรือ Delete Forever</h2>

    @include('manuals.training._screenshot', [
        'src' => 'central_trash/02-restore-delete',
        'alt' => 'ปุ่ม Restore + Delete Forever พร้อม confirm',
        'caption' => 'Restore / Delete Forever — มี confirm dialog',
        'callouts' => [
            '<strong>♻️ Restore:</strong> คืนข้อมูลกลับมาใช้งานปกติ',
            '<strong>🗑️ Delete Forever:</strong> ลบถาวร — กู้กลับมาไม่ได้!',
            '<strong>Confirm dialog:</strong> ทั้ง 2 action ต้องยืนยัน',
            '<strong>Bulk action:</strong> เลือกหลายรายการพร้อมกัน',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>หา item ที่ต้องการกู้ → กดปุ่ม <strong>♻️ Restore</strong></li>
            <li>หรือกด <strong>🗑️ Delete Forever</strong> เพื่อลบถาวร</li>
            <li>ยืนยันใน confirm dialog</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ระวัง:</strong> Delete Forever = ลบถาวร กู้กลับมาไม่ได้ ตรวจให้แน่ใจก่อนกด
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: เก็บใน trash นานแค่ไหน?</dt>
        <dd>A: Default 30 วัน → auto-purge อัตโนมัติ (Super Admin ปรับได้)</dd>

        <dt>Q: ลูกจ้างที่ลบ — restore แล้ว employer ยังเหมือนเดิมไหม?</dt>
        <dd>A: ใช่ — ทุกความสัมพันธ์ + เอกสาร + activity log ยังคงเดิม</dd>

        <dt>Q: Staff ลบข้อมูลได้ไหม?</dt>
        <dd>A: ขึ้นกับ permission — บาง action ต้องเป็น Admin หรือสูงกว่า</dd>
    </dl>
</section>
