{{-- Training Edition: Employers --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-building-fill"></i> {{ __('ข้อมูลนายจ้าง (Employers)') }} — {{ __('Master ของบริษัทลูกค้าที่จ้างแรงงานต่างด้าว') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ข้อมูลนายจ้าง"</strong> เก็บข้อมูลของ<strong>นายจ้าง</strong> (บริษัทลูกค้า) ที่จ้างแรงงานต่างด้าว
        ข้อมูลที่นี่ใช้กับลูกจ้าง, การสร้างเอกสาร, ใบกำกับภาษี, สัญญาต่างๆ — เป็นรากฐานหลักของระบบ
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (เฉพาะที่ดูแล)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู + ดูรายชื่อนายจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/01-list',
        'alt' => 'รายการนายจ้างพร้อม filter bar + sequence numbers',
        'caption' => 'Employers List — แสดงเป็น Card + Table view',
        'callouts' => [
            '<strong>+ เพิ่มนายจ้าง:</strong> สร้างนายจ้างใหม่',
            '<strong>Filter:</strong> ค้นหา, กรองตามจังหวัด, กรอง JobOwner',
            '<strong>Sequence number:</strong> เลขลำดับมุมขวาบนการ์ด (CSS counter)',
            '<strong>Card / Table toggle:</strong> สลับมุมมอง',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ข้อมูลนายจ้าง</strong></li>
            <li>กรองหรือค้นหานายจ้างที่ต้องการ</li>
            <li>คลิกการ์ดเพื่อเข้าหน้าแก้ไข</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">เพิ่มนายจ้างใหม่</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/02-create-form',
        'alt' => 'ฟอร์มสร้างนายจ้างใหม่',
        'caption' => 'New Employer Form — กรอกข้อมูลพื้นฐาน + tax ID',
        'callouts' => [
            '<strong>ชื่อบริษัท (TH/EN):</strong> ทั้ง 2 ภาษา',
            '<strong>เลขผู้เสียภาษี:</strong> 13 หลัก',
            '<strong>ที่อยู่:</strong> เพิ่มได้หลายที่อยู่ (จดทะเบียน / ส่งเอกสาร)',
            '<strong>JobOwner:</strong> ผู้ดูแลลูกค้าที่แท้จริง (เช่น พี่กุ้ง)',
            '<strong>Caretakers:</strong> assign Caretaker users ที่ดูแล',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด <strong>"+ เพิ่มนายจ้าง"</strong></li>
            <li>กรอกข้อมูลบริษัท + tax ID + ที่อยู่</li>
            <li>เลือก JobOwner (ผู้ดูแลตัวจริง)</li>
            <li>กด Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">แก้ไขข้อมูลนายจ้าง + เพิ่มผู้แทน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/03-edit-detail',
        'alt' => 'หน้าแก้ไขนายจ้าง — tabs ข้อมูล, ที่อยู่, ลายเซ็น, ผู้แทน',
        'caption' => 'Edit Employer — หลายแท็บ: ข้อมูล / ที่อยู่ / ลายเซ็น / ผู้แทน',
        'callouts' => [
            '<strong>Tab ข้อมูลทั่วไป:</strong> ชื่อ + tax ID + ติดต่อ',
            '<strong>Tab ที่อยู่:</strong> เพิ่มได้หลายที่อยู่',
            '<strong>Tab ลายเซ็น/ตรา:</strong> อัพโหลดลายเซ็น + ตราประทับ',
            '<strong>Tab ผู้แทน:</strong> เพิ่ม Delegates ที่ลงนามแทนนายจ้าง',
            '<strong>Tab เอกสารอื่นๆ:</strong> 3 slots (default text จาก Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกการ์ดนายจ้าง → ปุ่มดินสอ ✏️</li>
            <li>เลือก tab ที่ต้องการแก้</li>
            <li>กด Save เพื่อบันทึก</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Preview + Quick Actions</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/04-preview-modal',
        'alt' => 'Preview popup ของนายจ้าง',
        'caption' => 'Preview Popup — ดูข้อมูลครบเร็วๆ ไม่ต้องเข้าหน้าแก้',
        'callouts' => [
            '<strong>ปุ่ม Preview 🔍:</strong> ดูข้อมูลแบบ read-only',
            '<strong>Stats:</strong> จำนวนลูกจ้าง active + ลาออก + แยกตาม nationality',
            '<strong>รายชื่อลูกจ้าง active:</strong> 10 คนแรก paginated',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>การ์ดนายจ้าง → ปุ่มแว่นขยาย 🔍</li>
            <li>ดูข้อมูล + จำนวนลูกจ้าง</li>
            <li>คลิก "ดูทั้งหมด" เพื่อไปหน้าลูกจ้างของนายจ้างนี้</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ลบนายจ้างที่มีลูกจ้างได้ไหม?</dt>
        <dd>A: ลบได้ — แต่ลูกจ้างจะกลายเป็น orphan ใช้ archive แทน หรือย้ายลูกจ้างไปนายจ้างอื่นก่อน</dd>

        <dt>Q: JobOwner ต่างจาก Caretakers ยังไง?</dt>
        <dd>A: JobOwner = ผู้ดูแลลูกค้าตัวจริง (เช่น พี่กุ้ง ดูแลหลายบริษัท), Caretaker = role ของระบบที่ assign ให้ user เห็นข้อมูล</dd>

        <dt>Q: Caretaker เห็นนายจ้างไหน?</dt>
        <dd>A: เห็นเฉพาะนายจ้างที่ถูก assign ใน tab Caretakers ของนายจ้างนั้น</dd>
    </dl>
</section>
