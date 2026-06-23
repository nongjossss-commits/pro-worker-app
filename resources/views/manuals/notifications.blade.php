{{-- User Manual: Notifications --}}

<h4><i class="bi bi-bell-fill me-2"></i>เมนูนี้คืออะไร?</h4>
<p>
    เมนู <strong>"การแจ้งเตือน (Notifications)"</strong> เก็บการแจ้งเตือนต่างๆ ที่ระบบสร้างขึ้นอัตโนมัติ
    เช่น พาสปอร์ตใกล้หมดอายุ, วีซ่าใกล้หมด, งานที่กำลังจะครบกำหนด, ข้อความใหม่จากนายจ้าง
</p>

<h4><i class="bi bi-person-check me-2"></i>ใครเข้าได้?</h4>
<p>ทุกคนที่มีสิทธิ์ <code>view-notifications</code></p>

<h4><i class="bi bi-list-check me-2"></i>ขั้นตอนใช้งาน</h4>

<h5>1. ดูการแจ้งเตือน</h5>
<div class="manual-step">
    คลิกที่ icon กระดิ่งบน navbar หรือเปิดเมนู Notifications — แสดงรายการตามลำดับใหม่สุดบนสุด
</div>

<h5>2. ทำเครื่องหมายอ่านแล้ว</h5>
<div class="manual-step">
    คลิกที่การแจ้งเตือน → ระบบจะ mark as read อัตโนมัติ
</div>

<h5>3. ยกเลิก/ลบการแจ้งเตือน</h5>
<div class="manual-step">
    กดไอคอน X ที่การแจ้งเตือนแต่ละชิ้น — เฉพาะคนที่มีสิทธิ์ <code>cancel-notifications</code>
</div>

<h5>4. ต่ออายุการแจ้งเตือน</h5>
<div class="manual-step">
    บางการแจ้งเตือนต่ออายุได้ (เช่น ต่อเวลาเตือนวีซ่าใกล้หมด) — กดปุ่ม "ต่ออายุ"
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Web Push:</strong> เปิด notification permission ใน browser เพื่อรับแจ้งเตือน real-time
</div>

<div class="manual-tip">
    <strong>Expiry Scanner:</strong> ระบบสแกนวันหมดอายุทุกวันเช้า (cron CheckExpiries) — สร้าง notification ใหม่อัตโนมัติ
</div>
