@props(['notification', 'label'])
<div class="alert alert-danger notification-item">
    <div class="d-flex align-items-start gap-3">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 50%; background-color: #e2e8f0; color: #64748b; font-weight: bold;">
            <span>PIC</span>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">{{ $notification->employee->employeeNameTh ?? 'N/A' }}</h5>
            <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</p>
            <p class="mb-0 small"><strong>{{ $label }} หมดอายุ:</strong> {{ \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') }}</p>
        </div>
        <div class="text-end flex-shrink-0 ms-2">
            @php
                $daysRemaining = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($notification->due_date)->startOfDay(), false);
            @endphp
            <span class="badge bg-dark mb-2 d-block text-nowrap">
                @if($daysRemaining >= 0)
                    เหลือ {{ $daysRemaining }} วัน
                @else
                    หมดอายุแล้ว {{ abs($daysRemaining) }} วัน
                @endif
            </span>
        </div>
    </div>
</div>
