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
        <span class="role-pill role-readonly">Caretaker (งานประจำวัน)</span>
    </div>
    <p class="training-intro-desc" style="margin-top:10px">
        <strong>สิทธิ์:</strong> การติ๊กขั้นตอนต้องมีสิทธิ์ <em>"อัปเดตขั้นตอนความคืบหน้า"</em> (Admin/Staff มีโดยค่าเริ่มต้น)
        · การสร้างงาน/ตั้งค่าขั้นตอน ต้องมีสิทธิ์ <em>"จัดการ Workflow"</em>
        · ข้อมูลการเงินต้องมีสิทธิ์ <em>"จัดการการเงิน"</em> — Caretaker ทำงานประจำวันได้ (ข้อมูลลูกจ้าง/นัดหมาย) แต่แก้โครงสร้างหรือการเงินไม่ได้
    </p>
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
            <li>เลือก <strong>ลูกจ้าง</strong> (ติ๊กหลายคนได้) หรือกรอกคนใหม่ / นำเข้าจาก Excel</li>
            <li>กด <strong>"Add"</strong> — ลูกจ้างจะปรากฏในการ์ดของ employer ทันที</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>ตรวจข้อมูลซ้ำอัตโนมัติ:</strong> ถ้าเลขพาสปอร์ต / ใบอนุญาตทำงาน / บัตรชมพู / เลขประจำตัว / เลข RA ตรงกับลูกจ้างที่มีอยู่แล้ว ระบบจะเตือนก่อนบันทึก
    </div>
    <div class="slide-tip">
        💡 <strong>แท็บ "แจ้งเข้า / เปลี่ยนนายจ้าง":</strong> ถ้าเพิ่มลูกจ้างที่เคยถูกแจ้งออกไปแล้ว ระบบจะย้ายลูกจ้างมาอยู่กับนายจ้างใหม่ให้ทันที
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
    <div class="slide-number">STEP 6</div>
    <h2 class="slide-title">แท็บแบบ "หลายการ์ด" — ปิดงานทั้งการ์ด</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/07-multi-card',
        'alt' => 'แท็บ MOU นำเข้า พร้อมปุ่ม Completed Jobs และ Create Job',
        'caption' => 'แท็บแบบหลายการ์ด (เช่น MOU นำเข้า) — 1 นายจ้างมีได้หลายการ์ด แต่ละการ์ดคือ 1 งาน',
        'callouts' => [
            '<strong>Create Job:</strong> สร้างการ์ดงานใหม่ทุกครั้ง (ไม่รวมกับการ์ดเดิม)',
            '<strong>Finish Job:</strong> ปิดงานทั้งการ์ด — ลูกจ้างที่ยังค้างในการ์ดจะถูกปิดพร้อมกัน',
            '<strong>Completed Jobs:</strong> ดูการ์ดที่ปิดแล้ว และกด Undo ได้ภายใน 24 ชั่วโมง',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Super Admin เลือกได้ตอนสร้าง/แก้ไขแท็บว่าเป็น <strong>การ์ดเดียว</strong> (ใช้การ์ดเดิมของนายจ้างตลอด เช่น แจ้งเข้า/แจ้งออก) หรือ <strong>หลายการ์ด</strong> (สร้างการ์ดใหม่ทุกงาน เช่น MOU นำเข้า)</li>
            <li>แท็บหลายการ์ด: ทำงานเสร็จแล้วกด <strong>Finish Job</strong> บนการ์ด — ปุ่ม Finish รายคนจะถูกซ่อน</li>
            <li>ปิดผิด → เปิด <strong>Completed Jobs</strong> แล้วกด <strong>Undo</strong> (ภายใน 24 ชม.)</li>
            <li><strong>Cancel</strong> = การ์ดยังอยู่แต่ถูกทำเครื่องหมายยกเลิก · <strong>Delete</strong> = ลบการ์ด (กู้คืนได้จากถังขยะ)</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>ลบแท็บงาน:</strong> Super Admin ลบแท็บที่สร้างเองได้ (แท็บหลักของระบบลบไม่ได้ เปลี่ยนชื่อได้อย่างเดียว) — การลบเป็นการซ่อนแท็บ งานและข้อมูลการเงินเดิมยังอยู่ครบ
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 7</div>
    <h2 class="slide-title">จัดทีม + จัดการขั้นตอน (Steps)</h2>

    <div class="slide-instructions">
        <strong>จัดทีม (Manage Team)</strong> — ใช้ได้ทั้ง Workflow และ Pre-Production
        <ol>
            <li>กดปุ่ม <strong>จัดทีม</strong> บนการ์ดลูกจ้าง → เลือกทีมที่มีอยู่ หรือพิมพ์ชื่อทีมใหม่</li>
            <li>แก้ชื่อทีม / ลบทีม ได้จากไอคอนบนป้ายชื่อทีม (ลบทีม = ล้างชื่อทีม ลูกจ้างไม่หาย)</li>
            <li>กด <strong>"ไม่มีทีม"</strong> เพื่อเอาลูกจ้างออกจากทีม</li>
        </ol>
        <strong>จัดการขั้นตอน (ปุ่ม Steps)</strong>
        <ol>
            <li>เพิ่ม / เปลี่ยนชื่อ / ลบ / ลากเรียงลำดับขั้นตอนได้ในหน้าต่างเดียว ไม่ต้องรีโหลดหน้า</li>
            <li>หน้าต่างนี้เหมือนกันทั้ง 4 เมนู (Pre-Production, Workflow, มติลงทะเบียน, มติต่ออายุ)</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 8</div>
    <h2 class="slide-title">โหมดเช็คงาน (Job Check Mode)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/06-job-check-mode',
        'alt' => 'หน้าต่างเริ่มโหมดเช็คงาน',
        'caption' => 'โหมดเช็คงาน — บันทึกสถานะตอนเริ่ม แล้วสรุปว่าลูกจ้างคนไหนมีความเคลื่อนไหวเมื่อจบ',
        'callouts' => [
            '<strong>ปุ่ม "โหมดเช็คงาน"</strong> บนแถบด้านบน → เริ่มโหมด',
            '<strong>ประวัติการเช็คงาน:</strong> ย้อนดูรายงานได้ 7 วัน',
            '<strong>สรุปรายงานเชิงลึก:</strong> อัปโหลดไฟล์รายงานเพื่อรวมสรุปรายนายจ้าง',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>กด <strong>โหมดเช็คงาน</strong> → <strong>เริ่มโหมดเช็คงาน</strong> — ระบบบันทึกสถานะลูกจ้างทุกคนใน 4 เมนูไว้</li>
            <li>ทำงานตามปกติใน Pre-Production / Workflow / มติลงทะเบียน / มติต่ออายุ (แท็บที่อยู่ในโหมดจะถูกจำกัดให้อยู่ใน 4 เมนูนี้)</li>
            <li>พักกลางวัน → กด <strong>Pause</strong> ไปทำงานเมนูอื่นได้ แล้วกด <strong>Resume</strong> เมื่อกลับมา</li>
            <li>เสร็จแล้วกด <strong>Finish</strong> → ดาวน์โหลด Excel 2 ไฟล์: <strong>มีความเคลื่อนไหว</strong> และ <strong>ไม่มีความเคลื่อนไหว</strong> (มีรูปลูกจ้าง + แท็บงานที่มาจาก)</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 เปิดหลายแท็บได้ — เฉพาะแท็บที่เริ่ม/เข้าร่วมโหมดเท่านั้นที่ถูกจำกัด · ถ้าลืมปิด ระบบปิดให้อัตโนมัติตอน 05:00 ของวันถัดไป
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

        <dt>Q: ติ๊กขั้นตอนไม่ได้ ปุ่มกดไม่ได้?</dt>
        <dd>A: บัญชีนั้นยังไม่มีสิทธิ์ "อัปเดตขั้นตอนความคืบหน้า" — ให้ Admin เปิดสิทธิ์ที่เมนูจัดการผู้ใช้ (Caretaker ไม่มีสิทธิ์นี้โดยค่าเริ่มต้น)</dd>

        <dt>Q: Select All ของนายจ้างเลือกได้ครบไหม ถ้ายังไม่ได้กางการ์ด?</dt>
        <dd>A: ครบ — ระบบดึงรายชื่อลูกจ้างทั้งหมดของนายจ้างนั้นตามตัวกรองที่ใช้อยู่ แม้การ์ดยังไม่ได้เปิดหรือมีหลายหน้า</dd>
    </dl>
</section>
