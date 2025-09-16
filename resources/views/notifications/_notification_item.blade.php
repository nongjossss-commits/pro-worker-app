@php
    $days_remaining = $notification->days_remaining;
    $is_overdue = $days_remaining < 0;

    $card_class = '';
    $badge_class = 'bg-info text-dark';
    if ($is_overdue) {
        $card_class = 'alert-dark';
        $badge_class = 'bg-dark';
    } elseif ($days_remaining <= 30) {
        $card_class = 'alert-danger';
        $badge_class = 'bg-danger';
    } elseif ($days_remaining <= 60) {
        $card_class = 'alert-warning';
        $badge_class = 'bg-warning text-dark';
    }

    $flagMap = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
    $nationality = $notification->employee->employeeNationality ?? '';
    $flagCode = $flagMap[$nationality] ?? null;
@endphp

<div class="alert {{ $card_class }} notification-item">
    <div class="d-flex align-items-center gap-3">
        {{-- FINAL FIX: Use Tailwind classes for robust styling --}}
        <img src="{{ $notification->employee->employeePhoto ? asset('storage/' . $notification->employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo"
             class="w-12 h-12 object-cover rounded-full bg-light flex-shrink-0"
             style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">

        <div class="d-flex justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    {{ $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1)) }}. {{ $notification->employee->employeeNameEn ?? 'N/A' }}
                    @if($flagCode)
                        <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" title="{{ $nationality }}">
                    @endif
                </h5>
                <p class="mb-1 small"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</p>
                <p class="mb-0 small"><strong>วันครบกำหนด:</strong> {{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d F Y') }}</p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                <span class="badge {{ $badge_class }} mb-2 d-block text-nowrap fs-6">
                    @if($is_overdue)
                        หมดอายุ {{ abs($days_remaining) }} วัน
                    @else
                        เหลือ {{ $days_remaining }} วัน
                    @endif
                </span>
                <div class="btn-group btn-group-sm">
                    <a href="#" class="btn btn-success" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-calendar-check"></i></a>
                    <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-primary" title="ดูข้อมูล"><i class="bi bi-search"></i></a>
                    <a href="#" class="btn btn-danger" title="ยกเลิก" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
