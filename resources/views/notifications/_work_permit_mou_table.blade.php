@php
    $forRenewal = $notifications->filter(fn($n) => $n->days_remaining >= 0 && $n->days_remaining <= 50);
    $forNewApplication = $notifications->filter(fn($n) => $n->days_remaining < 0);
@endphp

<div class="table-responsive">
    <table class="table table-hover table-sm">
        {{-- Section 1: For Renewal --}}
        <thead class="table-light">
            <tr><th colspan="8">ขอต่อ ({{ $forRenewal->count() }} รายการ)</th></tr>
            <tr>
                <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-mou1"></th>
                <th style="width: 1%;">#</th>
                <th>{{ __('ชื่อลูกจ้าง') }}</th>
                <th>{{ __('สัญชาติ') }}</th>
                <th>{{ __('นายจ้าง') }}</th>
                <th>{{ __('วันที่ครบกำหนด') }}</th>
                <th>{{ __('สถานะ / วันคงเหลือ') }}</th>
                <th class="text-center">{{ __('จัดการ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($forRenewal as $notification)
                @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $loop->iteration])
            @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('ไม่มีรายการที่ต้องดำเนินการ') }}</td></tr>
            @endforelse
        </tbody>

        {{-- Section 2: For New Application --}}
        <thead class="table-light mt-4">
            <tr><th colspan="8">ขอรับใหม่ ({{ $forNewApplication->count() }} รายการ)</th></tr>
             <tr>
                <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-mou2"></th>
                <th style="width: 1%;">#</th>
                <th>{{ __('ชื่อลูกจ้าง') }}</th>
                <th>{{ __('สัญชาติ') }}</th>
                <th>{{ __('นายจ้าง') }}</th>
                <th>{{ __('วันที่ครบกำหนด') }}</th>
                <th>{{ __('สถานะ / วันคงเหลือ') }}</th>
                <th class="text-center">{{ __('จัดการ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($forNewApplication as $notification)
                @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $loop->iteration])
            @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('ไม่มีรายการที่หมดอายุ') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
