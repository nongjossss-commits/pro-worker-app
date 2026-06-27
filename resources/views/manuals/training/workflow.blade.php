{{-- Training Edition: Workflow — slide-friendly with annotated screenshots --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-diagram-3-fill"></i> {{ __('Workflow') }} — {{ __('ศูนย์รวมงานที่กำลังดำเนินการ') }}
    </h3>
    <p class="training-intro-desc">
        เมนูนี้คือ <strong>ศูนย์รวมงานทั้งหมด</strong>ที่กำลังเดินตามขั้นตอนต่างๆ
        เช่น ยื่นเอกสารกรมการจัดหางาน, ทำพาสปอร์ต, ขอวีซ่า, ออกใบอนุญาตทำงาน
        ผู้ใช้สามารถ<strong>ติ๊กขั้นตอน</strong>ของแต่ละลูกจ้างได้ และระบบจะติดตามความคืบหน้าให้
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (ดูได้)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เข้าหน้า Workflow + เลือก Tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/01-main-view',
        'alt' => 'หน้าหลัก Workflow แสดง tabs ของ Work Type ต่างๆ',
        'caption' => 'หน้าหลัก Workflow — แถบด้านบนเป็น Tab แต่ละ Work Type',
        'callouts' => [
            '<strong>Tab Bar:</strong> เลือกประเภทงาน (Notify In / Visa Renewal / MOU นำเข้า / Notify Out)',
            '<strong>ปุ่ม + Add Employee:</strong> เพิ่มลูกจ้างเข้างาน',
            '<strong>Filter:</strong> กรอง Operator, สถานะ, ค้นหาด้วยชื่อ',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกที่ <strong>Sidebar → Workflow</strong></li>
            <li>เลือก <strong>Tab</strong> ของ Work Type ที่ต้องการทำงาน</li>
            <li>การ์ดของแต่ละนายจ้างจะแสดงพร้อมรายชื่อลูกจ้าง</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> การ์ดที่ <strong>มีกิจกรรมล่าสุด</strong> จะเลื่อนขึ้นบนสุดทุกครั้งที่ refresh
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">ติ๊กขั้นตอนของลูกจ้าง</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/02-tick-step',
        'alt' => 'การ์ดลูกจ้างพร้อม checkbox ของแต่ละ step',
        'caption' => 'การ์ดลูกจ้างพร้อม checkbox ของแต่ละขั้นตอน',
        'callouts' => [
            '<strong>Checkbox:</strong> ติ๊กเพื่อบันทึกว่าทำขั้นตอนนั้นเสร็จแล้ว',
            '<strong>Step name:</strong> ชื่อขั้นตอน (เช่น "ยื่นคำขอ", "ชำระค่าธรรมเนียม")',
            '<strong>Progress bar:</strong> เปอร์เซ็นต์ความคืบหน้ารวม',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>คลิกที่ <strong>checkbox</strong> ของขั้นตอนที่ทำเสร็จ</li>
            <li>ระบบบันทึก <strong>timestamp + ผู้ทำ</strong> อัตโนมัติ</li>
            <li>Progress bar อัพเดททันที</li>
            <li>เมื่อทุกขั้นตอนเสร็จ → กดปุ่ม <strong>Finish</strong> เพื่อปิดงาน</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ระวัง:</strong> ติ๊กผิดสามารถ click ซ้ำเพื่อยกเลิกได้ แต่จะมี Activity Log บันทึกการเปลี่ยนแปลง
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">เพิ่มลูกจ้างเข้างาน Workflow</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/03-add-employee-modal',
        'alt' => 'Modal สำหรับเพิ่มลูกจ้างเข้า Workflow',
        'caption' => 'Add Employee Modal — เลือกประเภทงาน + employer + ลูกจ้าง',
        'callouts' => [
            '<strong>Searchable employer dropdown:</strong> พิมพ์ชื่อ/รหัสค้นหาได้',
            '<strong>Employee list:</strong> รายชื่อลูกจ้างของ employer นั้น',
            '<strong>Bulk select:</strong> เลือกหลายคนพร้อมกัน',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กดปุ่ม <strong>"+ Add Employee"</strong> ที่ด้านบนของ tab</li>
            <li>เลือก <strong>Employer</strong> (พิมพ์ค้นหาได้)</li>
            <li>เลือก <strong>ลูกจ้าง</strong> (ติ๊กหลายคนได้)</li>
            <li>กด <strong>"Add"</strong> — ลูกจ้างจะปรากฏในการ์ดของ employer ทันที</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">แท็บ "แจ้งออก" (Notify Out)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/04-notify-out',
        'alt' => 'การ์ดลูกจ้างในแท็บ Notify Out พร้อม date + reason field',
        'caption' => 'Notify Out tab — มีแถบสีเหลืองสำหรับกรอกวันและเหตุผลแจ้งออก',
        'callouts' => [
            '<strong>วันแจ้งออก (จำเป็น):</strong> date picker บังคับกรอกก่อนกด Finish',
            '<strong>เหตุผล:</strong> ลาออก / เลิกจ้าง / ครบสัญญา / เปลี่ยนนายจ้าง / อื่นๆ',
            '<strong>Badge สี:</strong> เหลือง = ต้องกรอก, เขียว = พร้อมเสร็จสิ้น',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิด Tab <strong>"แจ้งออก"</strong></li>
            <li>เพิ่มลูกจ้าง (ค้นหาได้ทุกคนในระบบ — Global search)</li>
            <li>กรอก <strong>วันแจ้งออก</strong> + <strong>เหตุผล</strong> ในแถบสีเหลือง</li>
            <li>กด <strong>Finish</strong> — ระบบ auto-update employee status เป็น "resigned"</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>เคล็ดลับ:</strong> ถ้าลูกจ้างเปลี่ยนนายจ้าง (ไม่ได้ลาออกจริง) → notify_out จะ auto-cancel
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">MOU นำเข้า — สร้าง Demand Card</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/05-mou-import',
        'alt' => 'การ์ด MOU นำเข้าพร้อม subtype color badge',
        'caption' => 'MOU Import card — แสดง subtype (Return/New/Pending) ด้วยสีและ badge',
        'callouts' => [
            '<strong>Border color:</strong> 🟢 Return | 🔵 New from Origin | 🟠 Pending',
            '<strong>Badge:</strong> คลิกเพื่อเปลี่ยนประเภทได้ทีหลัง',
            '<strong>Searchable employer:</strong> พิมพ์ค้นหาแทน scroll',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เปิด Tab <strong>"MOU นำเข้า"</strong> → กด <strong>"Create Job"</strong></li>
            <li>เลือกนายจ้าง (พิมพ์ค้นหาได้) + ระบุประเภท:
                <ul>
                    <li>🟢 <strong>Return</strong> — ลูกจ้างอยู่ในไทยแล้ว</li>
                    <li>🔵 <strong>New from Origin</strong> — คนใหม่จากต้นทาง</li>
                    <li>🟠 <strong>ยังไม่ระบุ</strong> — กลับมาเลือกทีหลัง</li>
                </ul>
            </li>
            <li>กรอกสัญชาติ + จำนวนชาย/หญิง</li>
            <li>กด <strong>Create Demand Card</strong></li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ทำไมการ์ดของฉันไม่เลื่อนขึ้นบน?</dt>
        <dd>A: ระบบเลื่อนเฉพาะตอน <strong>refresh</strong> หรือกลับมาจากเมนูอื่น ระหว่างทำงานต่อเนื่อง UI ไม่กระโดด (ป้องกันรบกวน)</dd>

        <dt>Q: ลูกจ้างหายไปจาก Notify Out tab?</dt>
        <dd>A: Auto-cancel เมื่อย้ายนายจ้าง — notify_out คือ "ออกจากนายจ้างเก่า" ที่ไม่เกี่ยวข้องแล้ว</dd>

        <dt>Q: ผู้ใช้ Caretaker เห็นการ์ดบ้าง ไม่เห็นบ้าง?</dt>
        <dd>A: Caretaker เห็นเฉพาะนายจ้างที่ตัวเองดูแล (assigned)</dd>
    </dl>
</section>
