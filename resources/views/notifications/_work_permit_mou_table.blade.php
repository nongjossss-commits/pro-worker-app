@php
    $forRenewal = $notifications->filter(fn($n) => $n->days_remaining >= 0 && $n->days_remaining <= 50);
    $forNewApplication = $notifications->filter(fn($n) => $n->days_remaining < 0);
@endphp

<div class="table-responsive">
    <table class="table table-hover table-sm">
        {{-- Section 1: For Renewal --}}
        <thead class="table-light">
            <tr><th colspan="6">ขอต่อ ({{ $forRenewal->count() }} รายการ)</th></tr>
            <tr><th>#</th><th>ชื่อลูกจ้าง</th><th>นายจ้าง</th><th>วันที่ครบกำหนด</th><th>สถานะ</th><th class="text-center">จัดการ</th></tr>
        </thead>
        <tbody>
            @forelse($forRenewal as $notification)
                @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $loop->iteration])
            @empty
                <tr><td colspan="6" class="text-center text-muted">ไม่มีรายการที่ต้องดำเนินการ</td></tr>
            @endforelse
        </tbody>

        {{-- Section 2: For New Application --}}
        <thead class="table-light mt-4">
            <tr><th colspan="6">ขอรับใหม่ ({{ $forNewApplication->count() }} รายการ)</th></tr>
             <tr><th>#</th><th>ชื่อลูกจ้าง</th><th>นายจ้าง</th><th>วันที่ครบกำหนด</th><th>สถานะ</th><th class="text-center">จัดการ</th></tr>
        </thead>
        <tbody>
            @forelse($forNewApplication as $notification)
                @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $loop->iteration])
            @empty
                <tr><td colspan="6" class="text-center text-muted">ไม่มีรายการที่หมดอายุ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
