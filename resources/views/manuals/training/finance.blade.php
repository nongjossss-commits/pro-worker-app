{{-- Training Edition: Finance (Add-on Module) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cash-coin"></i> {{ __('การเงิน (Finance)') }} — {{ __('ระบบบัญชี + ภาษี + Audit Log ของสำนักงาน') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"การเงิน (Finance)"</strong> เป็นโมดูล <strong>add-on</strong> สำหรับสำนักงานที่ต้องการระบบบัญชีในตัว
        ประกอบด้วย Ledger (สมุดบัญชี), Tax Invoices (ใบกำกับภาษี), WHT (หัก ณ ที่จ่าย),
        ภ.พ.30 / ภ.ง.ด.3/53, Bank Reconciliation, Monthly Bundle และ Audit Log
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (ขึ้นกับสิทธิ์ manage-finance)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิดเมนู Finance</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/01-main-dashboard',
        'alt' => 'หน้าหลัก Finance แสดง summary cards + sub-menus',
        'caption' => 'Finance Dashboard — Summary cards + sub-menu links',
        'callouts' => [
            '<strong>Summary cards:</strong> ยอดรวมเดือนนี้, รายรับ, รายจ่าย, VAT, WHT',
            '<strong>Sub-menus:</strong> Ledger / Tax Invoices / WHT / Reports / Bank / Audit Log',
            '<strong>Monthly bundle:</strong> ปุ่ม 1-click สร้าง ZIP เอกสารปิดสิ้นเดือน',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>การเงิน (Finance)</strong></li>
            <li>ดู summary cards เพื่อเข้าใจสถานะรวม</li>
            <li>เลือก sub-menu ตามงานที่ต้องการ</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">บันทึก Ledger (สมุดบัญชี)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/02-ledger-entry',
        'alt' => 'ฟอร์มบันทึกรายรับ-รายจ่าย',
        'caption' => 'Ledger Entry — รายรับ / รายจ่าย พร้อม VAT + WHT',
        'callouts' => [
            '<strong>ประเภท:</strong> รายรับ / รายจ่าย',
            '<strong>วันที่:</strong> วันที่บันทึก (default = วันนี้)',
            '<strong>คู่ค้า:</strong> ลูกค้าหรือ vendor',
            '<strong>VAT:</strong> 7% (default) — Excl. หรือ Incl. VAT',
            '<strong>WHT:</strong> 3% (ค่าบริการ) / 5% (เช่าทรัพย์)',
            '<strong>Slip image:</strong> แนบรูปสลิปได้',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Ledger → "+ บันทึกรายการ"</li>
            <li>เลือกประเภท (รายรับ/รายจ่าย)</li>
            <li>กรอกข้อมูล: วันที่, คู่ค้า, จำนวน, VAT</li>
            <li>แนบสลิป (optional) → กด "บันทึก"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">สร้างใบกำกับภาษี (Tax Invoice)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/03-tax-invoice',
        'alt' => 'ฟอร์มสร้างใบกำกับภาษี',
        'caption' => 'Tax Invoice Form — เลือก profile + กรอกลูกค้า + ระบุช่องทางชำระ',
        'callouts' => [
            '<strong>โปรไฟล์ผู้ออก:</strong> สำนักงานของเรา (จาก Financial Profiles)',
            '<strong>ข้อมูลลูกค้า:</strong> ชื่อ + tax ID + ที่อยู่',
            '<strong>VAT 7%:</strong> default ไทย ปัดเศษ 2 ตำแหน่ง',
            '<strong>ช่องทางชำระ:</strong> เงินสด / โอน / PromptPay',
            '<strong>Bank account:</strong> ถ้าเลือก "โอน" → เลือกบัญชีธนาคาร',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tax Invoices → "+ สร้างใหม่"</li>
            <li>เลือก <strong>Biller Profile</strong></li>
            <li>กรอกข้อมูลลูกค้า + ยอดเงิน + VAT</li>
            <li>ติ๊กช่องทางการชำระ</li>
            <li>กด "Save & Issue" → ระบบ lock เลขที่ + generate PDF</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ระวัง:</strong> ใบกำกับที่ Issued แล้ว แก้ไม่ได้ — ต้อง void แล้วออกใบใหม่
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">รายงานภาษีรายเดือน (ภ.พ.30 / ภ.ง.ด.3/53)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/04-tax-reports',
        'alt' => 'หน้า Tax Reports เลือกเดือน + ดาวน์โหลด',
        'caption' => 'Tax Reports — สรุปประจำเดือนสำหรับยื่นภาษี',
        'callouts' => [
            '<strong>เลือกเดือน:</strong> dropdown ของเดือน',
            '<strong>ภ.พ.30:</strong> VAT เดือนนั้น (รายรับ - รายจ่าย VAT)',
            '<strong>ภ.ง.ด.3:</strong> WHT บุคคลธรรมดา',
            '<strong>ภ.ง.ด.53:</strong> WHT นิติบุคคล',
            '<strong>Export Excel:</strong> สำหรับใช้ยื่นภาษี',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Tax Reports</li>
            <li>เลือกเดือนที่ต้องการ</li>
            <li>กด download ของแต่ละรายงาน</li>
            <li>ใช้ไฟล์ที่ได้ในการยื่นภาษี</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Monthly Bundle + Bank Reconciliation + Audit Log</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/05-monthly-bundle',
        'alt' => 'Monthly Bundle + Bank Reconciliation + Audit Log',
        'caption' => 'ฟีเจอร์ปิดสิ้นเดือนแบบครบครัน',
        'callouts' => [
            '<strong>Monthly Bundle:</strong> ZIP รวมเอกสารทั้งเดือน (รายรับ + รายจ่าย + ใบกำกับ + WHT)',
            '<strong>Bank Reconciliation:</strong> อัพโหลด statement → ระบบ match รายการให้',
            '<strong>Audit Log:</strong> ประวัติทุกการแก้ไขข้อมูลการเงิน — ใครแก้/แก้อะไร/เมื่อไหร่',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> ปลายเดือน → Generate Monthly Bundle → Bank Reconcile → ตรวจ Audit Log = ปิดสิ้นเดือนครบในขั้นตอนเดียว
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: เลขใบกำกับภาษีต่อเนื่องไหม?</dt>
        <dd>A: ต่อเนื่อง — ระบบรัน number ต่อจากใบล่าสุดในปีภาษีเดียวกัน ห้ามขาด</dd>

        <dt>Q: ลบใบกำกับที่ออกผิดได้ไหม?</dt>
        <dd>A: <strong>Void</strong> ได้ ลบจริงๆ ไม่ได้ — เลขใบยังเก็บไว้ในระบบเพื่อรักษาลำดับ</dd>

        <dt>Q: WHT 3% vs 5% ต่างกันยังไง?</dt>
        <dd>A: 3% = ค่าบริการทั่วไป / 5% = ค่าเช่าทรัพย์, ค่าจ้างบุคคลธรรมดา</dd>

        <dt>Q: เห็นเมนู Finance ไม่ได้?</dt>
        <dd>A: ต้องมี role manage-finance หรือสูงกว่า + subscription รวม Finance Module</dd>
    </dl>
</section>
