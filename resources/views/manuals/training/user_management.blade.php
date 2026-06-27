{{-- Training Edition: User Management --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('จัดการผู้ใช้งาน (User Management)') }} — {{ __('Create / edit users + assign roles + permissions') }}
    </h3>
    <p class="training-intro-desc">
        เมนู <strong>"จัดการผู้ใช้งาน"</strong> ใช้สร้าง/แก้ไข <strong>user account</strong>
        ของพนักงานในออฟฟิศ + ลูกค้า (employer role) + assign <strong>role</strong> + ตั้ง <strong>permissions</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin (จำกัด)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เพิ่ม User ใหม่</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/01-create-user',
        'alt' => 'ฟอร์มสร้าง user ใหม่ + role selector',
        'caption' => 'New User Form — กรอกข้อมูล + เลือก role',
        'callouts' => [
            '<strong>ชื่อ + email:</strong> ใช้ login',
            '<strong>Password:</strong> default หรือกำหนดเอง',
            '<strong>Role:</strong> super-admin / admin / staff / caretaker / employer',
            '<strong>Linked Employer:</strong> เฉพาะ role = employer (ผูกกับนายจ้างใด)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>จัดการผู้ใช้งาน</strong></li>
            <li>กด "+ เพิ่มผู้ใช้"</li>
            <li>กรอกชื่อ + email + password</li>
            <li>เลือก Role + Linked Employer (ถ้าเป็น employer)</li>
            <li>กด Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">5 Roles หลัก + Permissions</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/02-roles-permissions',
        'alt' => 'Role list + permission matrix',
        'caption' => 'Roles & Permissions — 5 roles + 27+ permissions',
        'callouts' => [
            '<strong>super-admin:</strong> ทุกอย่าง (root)',
            '<strong>admin:</strong> manage ทุกข้อมูล แต่ไม่แก้ permission/role',
            '<strong>staff:</strong> ทำงานทั่วไป — กรอก/แก้ข้อมูล',
            '<strong>caretaker:</strong> ดูเฉพาะนายจ้างที่ assigned',
            '<strong>employer:</strong> ลูกค้า — เห็นเฉพาะข้อมูลของตัวเอง',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Assign Caretaker ให้ Employer</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/03-caretaker-assign',
        'alt' => 'หน้า Employer Edit → Tab Caretakers',
        'caption' => 'Caretaker Assignment — เลือก caretaker users สำหรับ employer แต่ละราย',
        'callouts' => [
            '<strong>Tab Caretakers:</strong> ที่หน้า Employer Edit',
            '<strong>Multi-select:</strong> หนึ่งนายจ้างมี caretaker หลายคนได้',
            '<strong>Caretaker เห็นเฉพาะที่ assigned:</strong> ป้องกันข้อมูลรั่ว',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>เมนู Employers → เลือกนายจ้าง → tab Caretakers</li>
            <li>เลือก caretaker users (multi-select)</li>
            <li>กด Save</li>
            <li>Caretaker เหล่านั้นจะเห็นนายจ้างนี้ใน Sidebar</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: เพิ่ม role ใหม่ได้ไหม?</dt>
        <dd>A: ทำได้ผ่าน Spatie Permission package — Super Admin เท่านั้น</dd>

        <dt>Q: Reset password ให้ user ได้ไหม?</dt>
        <dd>A: ได้ — Edit user → ใส่ password ใหม่ → Save</dd>

        <dt>Q: ลบ user — ข้อมูลที่ user สร้างเป็นยังไง?</dt>
        <dd>A: ข้อมูลยังอยู่ — เพียง user account ถูกลบ (audit log ยังเห็น)</dd>
    </dl>
</section>
