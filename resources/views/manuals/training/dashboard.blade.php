{{-- Training Edition: Dashboard --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-speedometer2"></i> {{ __('แดชบอร์ด (Dashboard)') }} — {{ __('ภาพรวมและสรุปข้อมูลของทั้งระบบ') }}
    </h3>
    <p class="training-intro-desc">
        <strong>Dashboard</strong> คือหน้าแรกที่ผู้ใช้เจอตอน login — แสดง<strong>สรุปข้อมูล</strong>
        จำนวนลูกจ้าง, นายจ้าง, งานคงค้าง, การแจ้งเตือนล่าสุด และ <strong>ลิงก์ด่วน</strong>ไปยังเมนูที่ใช้บ่อย
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 0</div>
    <h2 class="slide-title">เข้าสู่ระบบ + ความปลอดภัย</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/03-login',
        'alt' => 'หน้าเข้าสู่ระบบ',
        'caption' => 'หน้าเข้าสู่ระบบ — สีและโลโก้เปลี่ยนตามการตั้งค่าแบรนด์ของระบบ',
        'callouts' => [
            '<strong>รูปตา:</strong> แสดง/ซ่อนรหัสผ่านที่พิมพ์',
            '<strong>จดจำฉัน:</strong> จำอีเมลไว้ในเครื่องนี้ (รหัสผ่านให้เบราว์เซอร์บันทึก)',
            '<strong>ลืมรหัสผ่าน?:</strong> ส่งลิงก์ตั้งรหัสใหม่ทางอีเมล',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>ปิดเบราว์เซอร์ / ปิดแอป → เปิดใหม่ต้องเข้าสู่ระบบใหม่เสมอ</li>
            <li>ไม่มีหน้าโปรแกรมเปิดอยู่เกิน 5 นาที (เช่น พับแอปบนมือถือ) → ระบบออกจากระบบให้อัตโนมัติ</li>
            <li>รีเฟรช หรือเปิดแท็บใหม่ ใช้งานต่อได้ตามปกติ</li>
        </ol>
    </div>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/04-forgot-password',
        'alt' => 'หน้าลืมรหัสผ่าน',
        'caption' => 'ลืมรหัสผ่าน — กรอกอีเมล แล้วเปิดลิงก์ในอีเมลภายใน 60 นาที',
        'callouts' => [
            '<strong>ขอหลายครั้ง:</strong> ใช้ได้เฉพาะลิงก์ในอีเมลฉบับล่าสุด',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">เปิด Dashboard + ดูภาพรวม</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => 'หน้า Dashboard พร้อม summary cards + quick links',
        'caption' => 'Dashboard — Summary cards + Quick links + Recent activity',
        'callouts' => [
            '<strong>Summary cards:</strong> จำนวนนายจ้าง/ลูกจ้าง/งานคงค้าง',
            '<strong>Expiry alerts:</strong> ลูกจ้างใกล้หมดอายุ (60/30/7 วัน)',
            '<strong>Quick links:</strong> ไปยังเมนูที่ใช้บ่อย',
            '<strong>Recent notifications:</strong> 5 รายการล่าสุด',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Login เข้าระบบ → ไป Dashboard อัตโนมัติ</li>
            <li>ดู summary cards ด้านบน</li>
            <li>คลิก card หรือ quick link เพื่อไปยังเมนูที่ต้องการ</li>
            <li>ปุ่มถัดจากปุ่มภาษา = เลือก<strong>โหมดสว่าง / มืด / ตามอุปกรณ์</strong> — เปลี่ยนทันทีไม่ต้องรีเฟรช และจำไว้แยกตามเครื่อง</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">แต่ละ role เห็น Dashboard ต่างกัน</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/02-role-variants',
        'alt' => 'Dashboard variants for Admin / Caretaker / Employer',
        'caption' => 'Dashboard ตาม role — ข้อมูลที่เห็นต่างกัน',
        'callouts' => [
            '<strong>Admin/Staff:</strong> เห็นทุกข้อมูลทั้งระบบ',
            '<strong>Caretaker:</strong> เห็นเฉพาะนายจ้าง+ลูกจ้างที่ดูแล',
            '<strong>Employer:</strong> เห็นเฉพาะลูกจ้างของตัวเอง',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">คำถามที่พบบ่อย</h2>

    <dl class="slide-faq">
        <dt>Q: ตัวเลขใน summary cards ไม่ตรง?</dt>
        <dd>A: cache update ทุก 60 วินาที — กด refresh หรือรอสักครู่</dd>

        <dt>Q: ทำไม Caretaker เห็นข้อมูลน้อย?</dt>
        <dd>A: Caretaker เห็นเฉพาะนายจ้างที่ assigned ผ่าน employer_caretaker pivot</dd>
    </dl>
</section>
