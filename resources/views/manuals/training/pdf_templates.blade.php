{{-- Training Edition: PDF Templates --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('แม่แบบ PDF (PDF Templates)') }} — {{ __('สร้างแม่แบบเอกสารพร้อม fields auto-fill') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"PDF Templates"</strong> ใช้สร้าง<strong>แม่แบบ PDF</strong>สำหรับเอกสารต่างๆ
        (สัญญาจ้าง, ใบรับรอง, แบบฟอร์มราชการ) โดย <strong>ลาก-วาง</strong> field
        ระบบจะเติมข้อมูลให้อัตโนมัติเมื่อสร้างเอกสารจริง
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">สร้าง Template ใหม่ — 2 วิธี</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/01-create-mode',
        'alt' => 'หน้าสร้าง template มี radio 2 ตัวเลือก',
        'caption' => 'Create Template — Upload PDF ใหม่ หรือ Clone จากของเดิม',
        'callouts' => [
            '<strong>📤 Upload new PDF:</strong> เริ่มจาก PDF เปล่าจากเครื่อง',
            '<strong>📋 Copy from existing:</strong> Clone template เดิม + ปรับเล็กน้อย',
            '<strong>Searchable dropdown:</strong> ค้นหา template ที่จะ clone',
            '<strong>Field count:</strong> แสดงจำนวน fields ที่จะ copy',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>PDF Templates</strong> → "+ Create New Template"</li>
            <li>เลือกโหมด: Upload PDF ใหม่ หรือ Clone จากของเดิม</li>
            <li>ตั้งชื่อ + เลือกประเภท (Global / Employer)</li>
            <li>กด "Upload & Go to Builder"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ลาก-วาง Fields ใน Builder</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/02-builder-drag',
        'alt' => 'PDF Builder — drag fields onto PDF',
        'caption' => 'Template Builder — ลากฟิลด์ลงบน PDF',
        'callouts' => [
            '<strong>Field panel (ซ้าย):</strong> ฟิลด์ที่ใส่ได้ (ชื่อนายจ้าง / passport / signature)',
            '<strong>PDF preview (กลาง):</strong> ลากวาง field ตรงตำแหน่งที่ต้องการ',
            '<strong>Properties (ขวา):</strong> ปรับขนาด / ฟอนต์ / alignment',
            '<strong>Save:</strong> บันทึก field map → ใช้กับลูกจ้างได้',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Smart Quick Print — พิมพ์เปล่า + เติมข้อมูล</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/03-quick-print',
        'alt' => 'Quick Print modal แสดง field analysis',
        'caption' => 'Quick Print — วิเคราะห์ field ก่อนพิมพ์',
        'callouts' => [
            '<strong>เลือก template:</strong> ระบบวิเคราะห์ field ทันที',
            '<strong>Field analysis:</strong> ลูกจ้าง/นายจ้าง/Delegate/Importer/พยาน — กี่ฟิลด์',
            '<strong>ถ้ามีฟิลด์ลูกจ้าง:</strong> Block + แนะนำให้เลือกลูกจ้างก่อน',
            '<strong>ถ้าไม่มี:</strong> เลือก Target Employer/Delegate/Importer แล้วพิมพ์ได้เลย',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: Clone template — ลบต้นฉบับจะกระทบไหม?</dt>
        <dd>A: ไม่ — ระบบ copy ไฟล์ + field map ใหม่ทั้งหมด อิสระจากกัน</dd>

        <dt>Q: PDF ที่อัพโหลดเป็น scan (รูป)?</dt>
        <dd>A: ใช้เป็น background ได้ — ลาก field ทับลงไปตรงที่เป็นช่องว่าง</dd>

        <dt>Q: ฟอนต์ภาษาไทย?</dt>
        <dd>A: ระบบใช้ THSarabunNew + CP874 encoding — รองรับภาษาไทยครบ</dd>
    </dl>
</section>
