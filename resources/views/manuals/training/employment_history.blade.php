{{-- Training Edition: Employment History --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-badge"></i> {{ __('ประวัติการจ้างงาน (Employment History)') }} — {{ __('รายชื่อลูกจ้างทุกคน รวมที่ลาออก/ครบสัญญา') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"ประวัติการจ้างงาน"</strong> แสดง<strong>ลูกจ้างทุกคน</strong>ที่เคยอยู่ในระบบ
        ไม่ว่าจะ active, ลาออกแล้ว, ครบสัญญา, หรือเปลี่ยนนายจ้างไปแล้ว
        ใช้สำหรับดูประวัติย้อนหลัง, หาลูกจ้างเก่า, และ <strong>ย้ายนายจ้าง</strong>ลูกจ้างที่ออกแล้ว
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
    <h2 class="slide-title">ค้นหาลูกจ้างย้อนหลัง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/01-search-filter',
        'alt' => 'หน้าประวัติการจ้างงาน + filter bar',
        'caption' => 'Employment History — แสดงลูกจ้างทั้งหมด รวมที่ไม่ active',
        'callouts' => [
            '<strong>ค้นหา:</strong> พิมพ์ชื่อ / passport',
            '<strong>กรองสัญชาติ:</strong> เมียนมา / ลาว / กัมพูชา / เวียดนาม',
            '<strong>กรองประเภท MOU:</strong> เลือกได้ทุก group',
            '<strong>กรองพาสปอร์ต:</strong> CI / PJ / TD / International',
            '<strong>กรองบัตรชมพู:</strong> มี / ไม่มี',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>ประวัติการจ้างงาน</strong></li>
            <li>พิมพ์ค้นหาหรือใช้ filter ที่ด้านบน</li>
            <li>กด "กรอง" — ผลลัพธ์รวมทั้ง active + ไม่ active</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ย้ายลูกจ้างเก่าให้นายจ้างใหม่ (Bulk Transfer)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/02-bulk-transfer',
        'alt' => 'Bulk Action bar + Modal ย้ายนายจ้าง',
        'caption' => 'Bulk Transfer — ย้ายลูกจ้างหลายคนไปนายจ้างใหม่',
        'callouts' => [
            '<strong>Tick checkbox:</strong> เลือกลูกจ้างหลายคน',
            '<strong>Bulk bar:</strong> ลอยขึ้นด้านล่าง',
            '<strong>ย้ายนายจ้าง:</strong> เลือกนายจ้างปลายทาง',
            '<strong>ผลกระทบ:</strong> notify_out ของลูกจ้างเหล่านี้ auto-cancel',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tick checkbox ลูกจ้างที่ต้องการย้าย</li>
            <li>Bulk bar → "Actions" → <strong>"ย้ายนายจ้าง"</strong></li>
            <li>เลือกนายจ้างปลายทาง → ยืนยัน</li>
            <li>ระบบย้าย + auto-cancel notify_out ของคนเหล่านี้</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Export + PDF Batch</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employment_history/03-export-pdf',
        'alt' => 'ปุ่ม Export CSV + Bulk PDF',
        'caption' => 'Export + PDF — ใช้ Bulk Actions',
        'callouts' => [
            '<strong>Export CSV:</strong> ดาวน์โหลดทันที (ตาม filter)',
            '<strong>Advanced Export:</strong> เลือกคอลัมน์เอง',
            '<strong>Automated PDF:</strong> สร้าง PDF จาก template สำหรับหลายคนพร้อมกัน',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กรองข้อมูลที่ต้องการ</li>
            <li>กด "Export CSV" (ด้านขวาบน) — ดาวน์โหลดทันที</li>
            <li>หรือ Bulk Action → "Advanced Export" / "Automated PDF"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ต่างจากเมนู "ข้อมูลลูกจ้าง" ยังไง?</dt>
        <dd>A: ลูกจ้าง = active เท่านั้น, ประวัติ = ทุกคน รวมลาออก/ครบสัญญา/แจ้งออก</dd>

        <dt>Q: ลูกจ้างที่อยู่ถังขยะ เห็นที่นี่ไหม?</dt>
        <dd>A: ไม่ — ต้องไป "ถังขยะกลาง" (Central Trash) — กู้คืนได้</dd>
    </dl>
</section>
