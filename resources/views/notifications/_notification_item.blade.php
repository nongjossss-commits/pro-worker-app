@php
    $employee = $notification->employee;
    $employer = $employee->employer;
    $daysRemaining = $notification->days_remaining;
    $dueDate = \Carbon\Carbon::parse($notification->due_date);

    // Set locale to Thai for date formatting
    \Carbon\Carbon::setLocale('th');

    $alertClass = 'alert-secondary';
    if ($daysRemaining < 0) {
        $alertClass = 'alert-dark text-white';
    } elseif ($daysRemaining <= $notification->danger_threshold) {
        $alertClass = 'alert-danger';
    }

    // Nationality to Flag code mapping
    $flagCodes = [
        'เมียนมา' => 'mm',
        'ลาว' => 'la',
        'กัมพูชา' => 'kh',
        'เวียดนาม' => 'vn',
    ];
    $nationality = $employee->employeeNationality ?? null;
    $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
@endphp

<div class="alert {{ $alertClass }} notification-item">
    <div class="d-flex align-items-center gap-3">
        @if($employee)
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                 class="employee-photo-thumb"
                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; margin-right: 1rem;"
                 alt="Photo">
        @endif
        <div class="d-flex justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    {{ $loop->iteration }}. {{ $employee->employeeNameEn ?? 'N/A' }}

                    @if($nationality)
                        <span class="text-muted fw-normal small">
                            {{ $nationality }}
                            @if($flagCode)
                                <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                            @endif
                        </span>
                    @endif
                </h5>
                <p class="mb-1"><strong>นายจ้าง:</strong> {{ $employer->employerNameTh ?? 'N/A' }}</p>
                <p class="mb-0 small">
                    <strong>{{ $notification->title }}:</strong> {{ $dueDate->translatedFormat('d F Y') }}
                </p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                <span class="badge bg-dark mb-2 d-block text-nowrap">
                    {{ $daysRemaining < 0 ? 'เลยกำหนด ' . abs($daysRemaining) . ' วัน' : 'เหลือ ' . $daysRemaining . ' วัน' }}
                </span>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('notifications.viewEmployee', ['notificationId' => $notification->id]) }}" class="btn btn-info" title="ดูข้อมูล">
                        <i class="bi bi-search"></i>
                    </a>
                    <button type="button" class="btn btn-success renew-btn" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}">
                        <i class="bi bi-calendar-check"></i>
                    </button>
                    <button type="button" class="btn btn-warning" title="ยกเลิกการต่ออายุ" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
