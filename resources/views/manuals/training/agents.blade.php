{{-- Training Edition: Agents --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('ตัวแทน (Agents)') }} — {{ __('นายหน้าที่ส่งลูกค้าหรือลูกจ้างมาให้สำนักงาน') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ตัวแทน (Agents)"</strong> เก็บข้อมูล<strong>นายหน้า / โบรกเกอร์</strong>
        ที่ส่งลูกค้าหรือลูกจ้างมาให้สำนักงาน — สำหรับติดตามค่านายหน้า + commission
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู Agents + เพิ่มตัวแทน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'agents/01-list-add',
        'alt' => 'รายการตัวแทน + ปุ่มเพิ่มใหม่',
        'caption' => 'Agents List + Add Modal',
        'callouts' => [
            '<strong>ชื่อตัวแทน:</strong> ชื่อบุคคลหรือบริษัทนายหน้า',
            '<strong>ติดต่อ:</strong> เบอร์โทร / Email / Line',
            '<strong>Commission:</strong> เปอร์เซ็นต์ค่านายหน้า',
            '<strong>หมายเหตุ:</strong> เพิ่มข้อมูลเฉพาะ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ตัวแทน (Agents)</strong></li>
            <li>กด "+ เพิ่มตัวแทน"</li>
            <li>กรอกข้อมูล → กด Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: Agent ต่างจาก Importer ยังไง?</dt>
        <dd>A: Agent = นายหน้าที่ส่งลูกค้ามาให้ (Thai broker), Importer = บริษัทนำเข้าแรงงาน (มีลายเซ็น/ตราใช้ในเอกสาร)</dd>

        <dt>Q: ผูก Agent กับ Employer ได้ไหม?</dt>
        <dd>A: ได้ — Employer มี field "ตัวแทนที่ส่งมา" สำหรับเลือก Agent</dd>
    </dl>
</section>
