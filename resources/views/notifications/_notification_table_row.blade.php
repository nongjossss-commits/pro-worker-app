@php
    $employee = $notification->employee;
    $employer = $employee->employer;
    $daysRemaining = $notification->days_remaining;
    $dueDate = \Carbon\Carbon::parse($notification->due_date);
    \Carbon\Carbon::setLocale('th');

    $rowClass = '';
    if ($daysRemaining < 0) {
        $rowClass = 'table-dark';
    } elseif ($daysRemaining <= $notification->danger_threshold) {
        $rowClass = 'table-danger';
    }
@endphp
<tr class="{{ $rowClass }}">
    <td>{{ $loop->iteration + $notifications->firstItem() - 1 }}</td>
    <td>
        <div>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'N/A' }}</div>
        <div class="small text-muted">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? '' }}</div>
    </td>
    <td>{{ $employer->employerNameTh ?? 'N/A' }}</td>
    <td>{{ $dueDate->translatedFormat('d M Y') }}</td>
    <td>
        <span class="badge {{ $daysRemaining < 0 ? 'bg-dark' : 'bg-secondary' }}">
            {{ $daysRemaining < 0 ? 'เลยกำหนด ' . abs($daysRemaining) . ' วัน' : 'เหลือ ' . $daysRemaining . ' วัน' }}
        </span>
    </td>
    <td class="text-center">
        <div class="btn-group btn-group-sm">
            @if($notification->status == 'cancelled')
                <form action="{{ route('notifications.restore', $notification) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success" title="นำกลับ">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </form>
                <form action="{{ route('notifications.forceDelete', $notification) }}" method="POST" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" title="ลบถาวร">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </form>
            @else
                <a href="{{ route('notifications.viewEmployee', ['notificationId' => $notification->id]) }}" class="btn btn-info" title="ดูข้อมูล">
                    <i class="bi bi-search"></i>
                </a>
                <button type="button" class="btn btn-success renew-btn" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}">
                    <i class="bi bi-calendar-check"></i>
                </button>
                <button type="button" class="btn btn-warning" title="ยกเลิกการต่ออายุ" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}">
                    <i class="bi bi-x-circle"></i>
                </button>
            @endif
        </div>
    </td>
</tr>
