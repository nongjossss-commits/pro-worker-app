{{-- Training Edition: Financial Profiles --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard-fill"></i> {{ __('โปรไฟล์การเงิน (Financial Profiles)') }} — {{ __('Master template ของผู้ออกใบกำกับ + ลูกค้า') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"โปรไฟล์การเงิน"</strong> เป็นที่<strong>เก็บข้อมูล master</strong>
        สำหรับ Biller (ผู้ออก = สำนักงานของเรา) + Customer (ลูกค้าประจำ)
        ทุกครั้งที่ออกใบกำกับ/ใบเสร็จ ระบบจะให้เลือกจาก profile เหล่านี้ — ไม่ต้องกรอกใหม่ทุกครั้ง
        รวมถึง <strong>บัญชีธนาคาร</strong>, <strong>โลโก้</strong>, และ <strong>ลายเซ็น</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-finance)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู Financial Profiles</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/01-list',
        'alt' => 'รายชื่อ profiles แยกประเภท Biller / Customer',
        'caption' => 'Financial Profiles List — สองประเภท: Biller + Customer',
        'callouts' => [
            '<strong>Biller profiles:</strong> ออฟฟิศของเรา (อาจมีหลาย profile ถ้าออกบิลในชื่อต่างกัน)',
            '<strong>Customer profiles:</strong> ลูกค้าประจำที่ออกบิลบ่อย',
            '<strong>+ สร้างใหม่:</strong> เพิ่ม profile ใหม่',
            '<strong>Edit / Delete:</strong> ปุ่มจัดการ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → Finance → <strong>โปรไฟล์การเงิน (Financial Profiles)</strong></li>
            <li>เลือกประเภท Biller หรือ Customer</li>
            <li>ดูรายการ profile ที่มีอยู่</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">สร้าง Biller Profile (ผู้ออก)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/02-biller-builder',
        'alt' => 'หน้า Biller Builder พร้อมฟิลด์ครบ',
        'caption' => 'Biller Profile Builder — ข้อมูลผู้ออกใบกำกับ',
        'callouts' => [
            '<strong>ชื่อบริษัท + tax ID:</strong> สำคัญที่สุด',
            '<strong>ที่อยู่:</strong> ที่อยู่จดทะเบียน',
            '<strong>โลโก้:</strong> อัพโหลด PNG/JPG (พิมพ์บนใบกำกับ)',
            '<strong>ลายเซ็น:</strong> ลายเซ็นผู้มีอำนาจ + ตราประทับ',
            '<strong>Bank accounts:</strong> เพิ่มได้หลายบัญชี (KBank, SCB, BBL ฯลฯ)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด "+ สร้างใหม่" → เลือก type = Biller</li>
            <li>กรอกข้อมูลบริษัท + tax ID + ที่อยู่</li>
            <li>อัพโหลดโลโก้ + ลายเซ็น + ตราประทับ</li>
            <li>เพิ่มบัญชีธนาคาร (สามารถเพิ่มได้หลายบัญชี)</li>
            <li>กด "Save Profile"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">เพิ่ม Bank Accounts ในโปรไฟล์</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/03-bank-accounts',
        'alt' => 'รายการบัญชีธนาคารพร้อม brand badge',
        'caption' => 'Bank Accounts — เพิ่ม/แก้/ลบ บัญชีในโปรไฟล์',
        'callouts' => [
            '<strong>ธนาคาร:</strong> เลือกจาก dropdown (KBank/SCB/BBL/Krungsri/TTB ฯลฯ)',
            '<strong>เลขบัญชี:</strong> เลขบัญชี',
            '<strong>ชื่อบัญชี:</strong> ชื่อผู้ถือบัญชี',
            '<strong>Brand badge:</strong> โลโก้ธนาคารแสดงบนใบเสร็จอัตโนมัติ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิด Biller Profile → tab "Bank Accounts"</li>
            <li>กด "+ เพิ่มบัญชี"</li>
            <li>เลือกธนาคาร + กรอกเลขบัญชี + ชื่อบัญชี</li>
            <li>กด Save</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> ตอนออกใบกำกับเลือก "ช่องทางชำระ = โอน" → ระบบให้เลือกบัญชีจาก profile นี้ พิมพ์บน PDF อัตโนมัติ
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Customer Profiles (ลูกค้าประจำ)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/04-customer-profiles',
        'alt' => 'รายการ Customer Profiles',
        'caption' => 'Customer Profiles — ลูกค้าประจำที่ออกบิลบ่อย',
        'callouts' => [
            '<strong>ชื่อลูกค้า + tax ID:</strong> ข้อมูลที่จะพิมพ์บนใบกำกับ',
            '<strong>ที่อยู่:</strong> ที่อยู่จัดส่งเอกสาร',
            '<strong>Quick fill:</strong> ตอนออกใบกำกับ → เลือก profile → กรอกข้อมูลครบทันที',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด "+ สร้างใหม่" → เลือก type = Customer</li>
            <li>กรอกชื่อ + tax ID + ที่อยู่ลูกค้า</li>
            <li>กด Save</li>
            <li>ตอนออกบิลครั้งถัดไป → เลือก profile นี้ → ข้อมูลเติมเองอัตโนมัติ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ทำไมตอนออกใบกำกับไม่เห็นบัญชีธนาคาร?</dt>
        <dd>A: ต้องสร้างบัญชีในโปรไฟล์การเงิน (Biller Profile) ก่อน — Finance → Tax Invoice จึงจะมีให้เลือก</dd>

        <dt>Q: ลบ profile ที่ใช้กับบิลเก่าได้ไหม?</dt>
        <dd>A: <strong>ไม่ควร</strong> — บิลเก่าจะอ้างอิงไม่เจอ ใช้ archive แทน</dd>

        <dt>Q: มี profile หลาย Biller ได้ไหม?</dt>
        <dd>A: ได้ — เช่นถ้าออกบิลในชื่อบริษัทที่ต่างกัน (เช่น "ABC จำกัด" และ "ABC Service")</dd>

        <dt>Q: ลายเซ็น+ตราประทับใส่ที่ไหน?</dt>
        <dd>A: ใน Biller Profile → tab "ลายเซ็น/ตรา" → อัพโหลดเป็นไฟล์ PNG (พื้นโปร่งใส แนะนำ)</dd>
    </dl>
</section>
