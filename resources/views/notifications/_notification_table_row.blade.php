@php
    $days_remaining = $notification->days_remaining;
    $is_overdue = $days_remaining < 0;
    $text_class = 'text-dark';
    if ($is_overdue) {
        $text_class = 'text-danger fw-bold';
    } elseif ($days_remaining <= 7) {
        $text_class = 'text-danger';
    } elseif ($days_remaining <= 30) {
        $text_class = 'text-warning';
    }
@endphp

<tr>
    <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $notification->employee->id }}" id="notification_table_checkbox_{{ $notification->id }}"></td>
    <td>{{ $itemNumber }}</td>
    <td class="d-flex align-items-center">
        <img src="{{ $notification->employee->employeePhoto ? asset('storage/' . $notification->employee->employeePhoto) : asset('images/default-profile.png') }}"
             alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">
        <div>
            <div>
                {{ $notification->employee->employeeTitleEn ?? '' }} {{ $notification->employee->employeeNameEn ?? 'N/A' }}
                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $notification->employee->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
            </div>
            <div class="small text-muted">{{ $notification->employee->employeeTitleTh ?? '' }} {{ $notification->employee->employeeNameTh ?? 'N/A' }}</div>
        </div>
    </td>
    <td>
        @if($notification->employee->employeeNationality)
            @php
                $countryCode = \App\Helpers\CountryHelper::getCountryCode($notification->employee->employeeNationality);
            @endphp
            @if($countryCode)
                <span class="d-inline-flex align-items-center">
                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 20px; height: 15px; margin-right: 8px;">
                    <span>{{ $notification->employee->employeeNationality }}</span>
                </span>
            @endif
        @endif
    </td>
    <td>
        {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}
        @if($notification->employee->employer)
        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $notification->employee->employer->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
        @endif
    </td>
    <td>{{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d M Y') }}</td>
    <td class="{{ $text_class }}">
        @if($is_overdue)
            หมดอายุ {{ abs($days_remaining) }} วัน
        @else
            เหลือ {{ $days_remaining }} วัน
        @endif
    </td>
    <td class="text-center">
<div class="d-flex flex-column flex-md-row justify-content-center gap-2">
    @if($notification->status === 'cancelled')
        <form action="{{ route('notifications.restore', $notification->id) }}" method="POST" class="d-grid d-md-inline">
            @csrf
            <button type="submit" class="btn btn-info btn-sm" title="กู้คืน"><i class="bi bi-arrow-counterclockwise"></i> กู้คืน</button>
        </form>
         <form action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" class="d-grid d-md-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
        </form>
    @else
        <a href="#" class="btn btn-info" title="สร้างงาน"><i class="bi bi-rocket-takeoff-fill"></i></a>
        <a href="#" class="btn btn-success" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-calendar-check"></i></a>
        <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-primary" title="ค้นหาตำแหน่ง"><i class="bi bi-geo-alt-fill"></i></a>
        <a href="#" class="btn btn-warning" title="ยกเลิก" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>
    @endif
</div>
    </div>
</td>
</tr>
