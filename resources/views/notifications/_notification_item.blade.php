@php
    $days_remaining = $notification->days_remaining;
    $is_overdue = $days_remaining < 0;
    $badge_class = 'bg-dark'; // Default for overdue
    if (!$is_overdue) {
        if ($days_remaining <= 7) $badge_class = 'bg-danger';
        elseif ($days_remaining <= 30) $badge_class = 'bg-warning text-dark';
        else $badge_class = 'bg-info text-dark';
    }
@endphp

<div class="alert alert-secondary notification-item">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ $notification->employee->employeePhoto ? asset('storage/' . $notification->employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
        <div class="d-flex justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">{{ $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1)) }}. {{ $notification->employee->employeeNameEn ?? 'N/A' }} <img src="{{ asset('flags/'.strtolower($notification->employee->employeeNationality ?? '').'.png') }}" class="flag-icon-sm"></h5>
                <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</p>
                <p class="mb-0 small"><strong>วันครบกำหนด:</strong> {{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d F Y') }}</p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                <span class="badge {{ $badge_class }} mb-2 d-block text-nowrap">
                    @if($is_overdue)
                        หมดอายุ {{ abs($days_remaining) }} วัน
                    @else
                        เหลือ {{ $days_remaining }} วัน
                    @endif
                </span>
                <div class="btn-group btn-group-sm">
                    <a href="#" class="btn btn-success renew-btn" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></a>
                    <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></a>
                    <a href="#" class="btn btn-warning cancel-btn" title="ยกเลิก"><i class="bi bi-x-circle"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
