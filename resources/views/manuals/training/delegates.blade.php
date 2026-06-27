{{-- Training Edition: Delegates --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard"></i> {{ __('ผู้แทน (Delegates)') }} — {{ __('บุคคลผู้รับมอบอำนาจลงนามแทนนายจ้าง') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ผู้แทน (Delegates)"</strong> เก็บข้อมูลของบุคคลที่<strong>ได้รับมอบอำนาจ</strong>ลงนามแทนนายจ้าง
        ในเอกสารต่างๆ (เช่น หนังสือมอบอำนาจ, ใบคำขอ) — มีลายเซ็น + ตราประทับ + ที่อยู่ครบเหมือนนายจ้าง
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู Delegates</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/01-list',
        'alt' => 'รายการผู้แทน + filter',
        'caption' => 'Delegates List',
        'callouts' => [
            '<strong>ชื่อ TH/EN + ตำแหน่ง:</strong> ผู้รับมอบอำนาจ',
            '<strong>Linked Employer:</strong> ผูกกับนายจ้างใดบ้าง (optional)',
            '<strong>+ เพิ่มผู้แทน:</strong> สร้างใหม่',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">เพิ่ม + แก้ไขข้อมูลผู้แทน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'delegates/02-form',
        'alt' => 'ฟอร์มสร้าง/แก้ไข Delegate',
        'caption' => 'Delegate Form — ข้อมูลพร้อมลายเซ็น',
        'callouts' => [
            '<strong>ข้อมูลบุคคล:</strong> ชื่อ + ตำแหน่ง + tax ID + ที่อยู่',
            '<strong>ลายเซ็น:</strong> อัพโหลด PNG (พื้นโปร่ง)',
            '<strong>หนังสือมอบอำนาจ:</strong> แนบ PDF อ้างอิงได้',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด "+ เพิ่มผู้แทน"</li>
            <li>กรอกข้อมูล + อัพโหลดลายเซ็น</li>
            <li>กด Save</li>
            <li>ใช้ตอนสร้าง PDF: ระบุ Delegate field — ระบบดึงชื่อ/ลายเซ็นมาใส่อัตโนมัติ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ต่างจากเมนู "ข้อมูลพนักงาน" ในไซด์บาร์ของ employer role ยังไง?</dt>
        <dd>A: ในไซด์บาร์ของ employer role "ข้อมูลพนักงาน" = Delegates (พนักงานบริษัทผู้รับมอบอำนาจ), ส่วนเมนู "ข้อมูลลูกจ้าง" = แรงงานต่างด้าวจริงๆ</dd>

        <dt>Q: Delegate ลงนามแทนได้กี่นายจ้าง?</dt>
        <dd>A: หลายนายจ้างได้ — ตอนสร้าง PDF เลือก Delegate ที่ต้องการได้</dd>
    </dl>
</section>
