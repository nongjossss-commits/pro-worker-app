{{-- User Manual: Finance --}}

<h4><i class="bi bi-cash-coin me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"การเงิน (Finance)"</strong> เป็นศูนย์รวมระบบบัญชี/การเงินของสำนักงาน
    รวมทั้ง สมุดบัญชี (Ledger), ใบกำกับภาษี (Tax Invoice), การออกใบหัก ณ ที่จ่าย (WHT),
    รายงานภาษี (ภ.พ.30, ภ.ง.ด.3/53), การปรับยอดธนาคาร (Bank Reconciliation) และ Audit Log
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าเมนูนี้ได้?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — เข้าได้เต็มที่</li>
    <li><span class="manual-role">Staff</span> — เข้าได้บางส่วน (ขึ้นกับสิทธิ์ <code>manage-finance</code>)</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> — ไม่มีสิทธิ์</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>ส่วนต่างๆ ของเมนูการเงิน</h4>
<ol>
    <li><strong>Ledger (สมุดบัญชี)</strong> — บันทึกรายรับ-รายจ่ายทั้งหมด แยกตามวันที่</li>
    <li><strong>Tax Invoices (ใบกำกับภาษี)</strong> — สร้าง/ดู/พิมพ์ใบกำกับภาษี + ช่องทางชำระเงิน</li>
    <li><strong>WHT (หัก ณ ที่จ่าย)</strong> — บันทึก WHT 3%/5% รับมา + ออกเอกสาร</li>
    <li><strong>Tax Reports</strong> — ภ.พ.30 + ภ.ง.ด.3/53 — สรุปประจำเดือนสำหรับยื่นภาษี</li>
    <li><strong>Bank Reconciliation</strong> — ปรับยอดบัญชีธนาคารกับรายการในระบบ</li>
    <li><strong>Audit Log</strong> — ดูประวัติการแก้ไขข้อมูลการเงินทั้งหมด</li>
    <li><strong>Monthly Bundle</strong> — ดาวน์โหลด ZIP รวมเอกสารปิดสิ้นเดือน</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งานที่พบบ่อย</h4>

<h5>1. บันทึกรายรับใหม่</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เข้า Ledger → กด "+ บันทึกรายการ"</li>
        <li>เลือก "รายรับ"</li>
        <li>กรอกวันที่, ลูกค้า, จำนวนเงิน, ประเภท VAT</li>
        <li>แนบ slip ภาพ (ถ้ามี)</li>
        <li>กด "บันทึก"</li>
    </ol>
</div>

<h5>2. สร้างใบกำกับภาษี</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>เข้า Tax Invoices → กด "+ สร้างใหม่"</li>
        <li>เลือก <strong>โปรไฟล์ผู้ออก</strong> (ออฟฟิศของเรา)</li>
        <li>กรอกชื่อลูกค้า + เลขผู้เสียภาษี + ที่อยู่</li>
        <li>กรอกยอดเงิน + อัตรา VAT (ปกติ 7%)</li>
        <li>ติ๊กช่องทางการชำระเงิน (เงินสด, โอน, PromptPay)</li>
        <li>ถ้าเลือก "โอน" — เลือกบัญชีธนาคารจากโปรไฟล์</li>
        <li>กด "Save & Issue" — ระบบจะ lock เลขที่ใบ + generate PDF</li>
    </ol>
</div>

<h5>3. ออกรายงานภาษีรายเดือน</h5>
<div class="manual-step">
    เข้า Tax Reports → เลือกเดือน → ดาวน์โหลด ภ.พ.30 หรือ ภ.ง.ด.3/53
</div>

<h5>4. ปรับยอดธนาคาร</h5>
<div class="manual-step">
    เข้า Bank Reconciliation → upload statement ธนาคาร → ระบบจะ match กับรายการในระบบให้
</div>

<h5>5. ปิดสิ้นเดือน (Monthly Bundle)</h5>
<div class="manual-step">
    เข้า Monthly Bundle → เลือกเดือน → กด "Generate" → ดาวน์โหลด ZIP รวมเอกสารทั้งหมดของเดือน
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>VAT 7%:</strong> เป็น default ของไทย ปัดเศษ 2 ตำแหน่ง
</div>

<div class="manual-tip">
    <strong>WHT 3% vs 5%:</strong> 3% = ค่าบริการทั่วไป, 5% = ค่าเช่าทรัพย์/บุคคล
</div>

<div class="manual-warn">
    <strong>ใบกำกับภาษีที่ Issued แล้ว แก้ไม่ได้:</strong> ตามกฎหมายห้ามแก้ — ต้อง void แล้วออกใบใหม่
</div>

<h4><i class="bi bi-question-circle me-2"></i>คำถามที่พบบ่อย</h4>
<dl>
    <dt>Q: ไม่เห็นช่องเลือกบัญชีธนาคาร?</dt>
    <dd>A: ต้องสร้างบัญชีในโปรไฟล์การเงินก่อน — ไปที่ Financial Profiles → เลือกโปรไฟล์ → เพิ่มบัญชี</dd>

    <dt>Q: เลขใบกำกับภาษีต่อเนื่องไหม?</dt>
    <dd>A: ต่อเนื่อง — ระบบรัน number ต่อจากใบล่าสุดในปีภาษีเดียวกันเสมอ ห้ามขาด</dd>

    <dt>Q: ลบใบกำกับภาษีที่ออกผิดได้ไหม?</dt>
    <dd>A: <strong>Void</strong> ได้ ลบจริงๆ ไม่ได้ — เลขใบยังเก็บไว้ในระบบเพื่อรักษาลำดับ</dd>
</dl>
