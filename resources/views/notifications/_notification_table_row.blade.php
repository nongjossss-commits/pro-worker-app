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
    <td>{{ $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1)) }}</td>
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
            <a href="#" class="btn btn-success renew-btn" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></a>
            <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></a>
            <a href="#" class="btn btn-warning cancel-btn" title="ยกเลิก"><i class="bi bi-x-circle"></i></a>
        </div>
    </td>
</tr>
