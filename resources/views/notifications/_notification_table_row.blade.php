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
    <td>{{ $itemNumber }}</td>
    <td>
        <div>{{ $notification->employee->employeeNameEn ?? 'N/A' }}</div>
        <div class="small text-muted">{{ $notification->employee->employeeNameTh ?? 'N/A' }}</div>
    </td>
    <td>{{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</td>
    <td>{{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d M Y') }}</td>
    <td class="{{ $text_class }}">
        @if($is_overdue)
            หมดอายุ {{ abs($days_remaining) }} วัน
        @else
            เหลือ {{ $days_remaining }} วัน
        @endif
    </td>
    <td class="text-center">
<div class="btn-group btn-group-sm">
    @if($notification->status === 'cancelled')
        <form action="{{ route('notifications.restore', $notification->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-info btn-sm" title="กู้คืน"><i class="bi bi-arrow-counterclockwise"></i> กู้คืน</button>
        </form>
         <form action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
        </form>
    @else
        <a href="#" class="btn btn-info" title="สร้างงาน"><i class="bi bi-rocket-takeoff-fill"></i></a>
        <a href="#" class="btn btn-success" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-calendar-check"></i></a>
        <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-primary" title="ดูข้อมูล"><i class="bi bi-search"></i></a>
        <a href="#" class="btn btn-warning" title="ยกเลิก" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>
    @endif
</div>
    </td>
</tr>
