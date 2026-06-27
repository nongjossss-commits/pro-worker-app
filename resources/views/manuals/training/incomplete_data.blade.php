{{-- Training Edition: Incomplete Data --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ __('ข้อมูลไม่ครบ (Incomplete Data)') }} — {{ __('เครื่องมือหาลูกจ้างที่กรอกข้อมูลขาด') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ข้อมูลไม่ครบ"</strong> เป็นเครื่องมือช่วยหา<strong>ลูกจ้างที่มีข้อมูลไม่ครบ</strong>
        เช่น ไม่มี passport, ไม่มี work permit expiry, ไม่มีรูปถ่าย, ไม่มีที่อยู่
        — เพื่อให้ทีมงานเข้าไปแก้ไขก่อนใช้งานจริง
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู + ดูรายการลูกจ้างข้อมูลขาด</h2>

    @include('manuals.training._screenshot', [
        'src' => 'incomplete_data/01-list',
        'alt' => 'รายการลูกจ้างที่มีข้อมูลไม่ครบ',
        'caption' => 'Incomplete Data List — แสดงว่าขาดข้อมูลฟิลด์ไหน',
        'callouts' => [
            '<strong>ลูกจ้าง:</strong> ชื่อ + นายจ้าง',
            '<strong>Missing fields:</strong> badge แดงระบุฟิลด์ที่ขาด',
            '<strong>ปุ่ม Edit ✏️:</strong> คลิกเข้าหน้าแก้ไขลูกจ้างทันที',
            '<strong>Filter:</strong> เลือกตามประเภทฟิลด์ที่ขาด',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ข้อมูลไม่ครบ</strong></li>
            <li>ดูลูกจ้างที่ขาดข้อมูล + ฟิลด์ใดบ้าง</li>
            <li>กดปุ่ม Edit → ไปหน้าลูกจ้าง → กรอกข้อมูล</li>
            <li>กลับมาเช็คอีกครั้ง — รายการจะหายไปเมื่อข้อมูลครบ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ฟิลด์ไหนถือว่า "ข้อมูลไม่ครบ"?</dt>
        <dd>A: ฟิลด์ critical ที่ระบบใช้ออกเอกสาร — passport, สัญชาติ, employer_id, วันเกิด ฯลฯ</dd>

        <dt>Q: ทำไมลูกจ้างยังอยู่ในรายการแม้ครบแล้ว?</dt>
        <dd>A: cache 60 วินาที — รอ refresh หรือกด refresh ด้วยตนเอง</dd>
    </dl>
</section>
