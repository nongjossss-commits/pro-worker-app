@php
    $forRenewal = $notifications->filter(function($n) {
        return $n->days_remaining >= 0 && $n->days_remaining <= 50;
    });

    $forNewApplication = $notifications->filter(function($n) {
        return $n->days_remaining < 0;
    });
@endphp

<div class="row">
    {{-- Left Column --}}
    <div class="col-lg-6">
        <h4 class="mb-3 border-bottom pb-2">ขอต่อ ({{ $forRenewal->count() }} รายการ)</h4>
        <div class="vstack gap-3">
            @forelse($forRenewal as $notification)
                @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
            @empty
                <p class="text-muted">ไม่มีรายการที่ต้องดำเนินการ</p>
            @endforelse
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-6">
        <h4 class="mb-3 border-bottom pb-2">ขอรับใหม่ ({{ $forNewApplication->count() }} รายการ)</h4>
        <div class="vstack gap-3">
            @forelse($forNewApplication as $notification)
                 @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
            @empty
                 <p class="text-muted">ไม่มีรายการที่หมดอายุ</p>
            @endforelse
        </div>
    </div>
</div>
