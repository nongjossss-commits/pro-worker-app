{{-- Training Edition: Importers --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-box-seam-fill"></i> {{ __('บริษัทนำเข้า (Importers)') }} — {{ __('บริษัทที่นำเข้าแรงงาน MOU จากต่างประเทศ') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"บริษัทนำเข้า (Importers)"</strong> เก็บข้อมูลของ<strong>บริษัทนำเข้าแรงงาน</strong> (MOU Importer)
        ที่ดำเนินการนำเข้าแรงงานจากต่างประเทศ — มี<strong>ลายเซ็น + ตราประทับ</strong>ใช้ในเอกสาร MOU
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู + ดูรายการบริษัทนำเข้า</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/01-list',
        'alt' => 'รายการ Importers',
        'caption' => 'Importers List',
        'callouts' => [
            '<strong>ชื่อบริษัท (TH/EN):</strong> ตามทะเบียนพาณิชย์',
            '<strong>เลขทะเบียน:</strong> Importer Registration Number',
            '<strong>ที่อยู่:</strong> ที่อยู่จดทะเบียน',
            '<strong>ลายเซ็น 1/2 + ตรา:</strong> ใช้ใน PDF อัตโนมัติ',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">เพิ่ม + แก้ไขข้อมูล Importer</h2>

    @include('manuals.training._screenshot', [
        'src' => 'importers/02-form',
        'alt' => 'ฟอร์ม Importer',
        'caption' => 'Importer Form — ข้อมูล + ลายเซ็น 2 ตำแหน่ง',
        'callouts' => [
            '<strong>ข้อมูลพื้นฐาน:</strong> ชื่อ + tax ID + ที่อยู่',
            '<strong>ลายเซ็น 1:</strong> ผู้มีอำนาจหลัก',
            '<strong>ลายเซ็น 2:</strong> ผู้มีอำนาจรอง (optional)',
            '<strong>ตราประทับ:</strong> ตราบริษัท',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด "+ เพิ่ม Importer"</li>
            <li>กรอกข้อมูล + อัพโหลดลายเซ็น 1 (ตำแหน่งหลัก)</li>
            <li>อัพโหลดตราประทับ + ลายเซ็น 2 (optional)</li>
            <li>กด Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ใช้ Importer ที่ไหน?</dt>
        <dd>A: ใช้ใน PDF Templates ที่มี Importer fields (เอกสาร MOU import) — ตอน generate ระบบจะดึงข้อมูล + ลายเซ็นมาใส่อัตโนมัติ</dd>

        <dt>Q: Importer ต่างจาก Agent ยังไง?</dt>
        <dd>A: Importer = บริษัทนำเข้าแรงงาน (มีบทบาทใน MOU/เอกสาร), Agent = นายหน้าที่ส่งลูกค้า (ค่านายหน้า)</dd>

        <dt>Q: ทำไมต้องมีลายเซ็น 2 ตำแหน่ง?</dt>
        <dd>A: บางเอกสารต้องมีกรรมการ 2 คนลงนาม — Field "ลายเซ็น 2" สำหรับเคสนั้น (ใส่หรือไม่ใส่ก็ได้)</dd>
    </dl>
</section>
