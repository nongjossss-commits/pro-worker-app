@if($notification->employee)
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
            <input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $notification->employee->id }}" id="notification_checkbox_{{ $notification->id }}">
            {{-- FINAL FIX: Use Tailwind classes for robust styling --}}
            <img src="{{ $notification->employee->employeePhoto ? asset('storage/' . $notification->employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                 alt="Photo"
                 class="w-12 h-12 object-cover rounded-full bg-light flex-shrink-0"
                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">

            <div class="d-flex justify-content-between align-items-start w-100">
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">
                        {{ $itemNumber }}. {{ $notification->employee->employeeTitleEn ?? '' }} {{ $notification->employee->employeeNameEn ?? 'N/A' }}
                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $notification->employee->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                        @if($flagCode)
                            <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                                <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" title="{{ $nationality }}" style="width: 16px; height: 12px; margin-right: 5px;">
                                <span>{{ $nationality }}</span>
                            </span>
                        @endif
                    </h5>
                    <p class="mb-1 small">{{ $notification->employee->employeeTitleTh ?? '' }} {{ $notification->employee->employeeNameTh ?? 'N/A' }}</p>
                    <p class="mb-1 small">
                        <strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}
                        @if($notification->employee->employer)
                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $notification->employee->employer->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                        @endif
                    </p>
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
                    <div class="d-flex flex-column flex-md-row gap-2" role="group">
        @if($notification->status === 'cancelled')
            <form action="{{ route('notifications.restore', $notification->id) }}" method="POST" class="d-grid d-md-inline">
                @csrf
                <button type="submit" class="btn btn-info" title="กู้คืน"><i class="bi bi-arrow-counterclockwise"></i> กู้คืน</button>
            </form>
            <form action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" class="d-grid d-md-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
            </form>
        @else
            <a href="#" class="btn btn-info" title="สร้างงาน"><i class="bi bi-rocket-takeoff-fill"></i></a>
            <a href="#" class="btn btn-success" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-calendar-check"></i></a>
            <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-primary" title="ค้นหาตำแหน่ง"><i class="bi bi-geo-alt-fill"></i></a>
            <a href="#" class="btn btn-warning" title="ยกเลิก" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>
        @endif
    </div>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Fallback for deleted employee --}}
    <div class="alert alert-secondary notification-item-deleted" role="alert">
        การแจ้งเตือนนี้เกี่ยวข้องกับข้อมูลลูกจ้างที่ถูกลบออกจากระบบไปแล้ว
    </div>
@endif
