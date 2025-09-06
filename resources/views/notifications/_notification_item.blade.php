@props(['notification'])

@php
    $dueDate = \Carbon\Carbon::parse($notification->due_date);
    $now = \Carbon\Carbon::now();
    $daysRemaining = $now->diffInDays($dueDate, false); // `false` allows negative numbers

    $alertClass = 'alert-secondary';
    if ($daysRemaining < 0) {
        $alertClass = 'alert-dark'; // Expired
    } elseif ($daysRemaining <= 30) {
        $alertClass = 'alert-danger'; // Danger zone
    } elseif ($daysRemaining <= 60) {
        $alertClass = 'alert-warning'; // Warning zone
    }

    $activeTab = request()->input('tab', '90day');
@endphp

<div class="alert {{ $alertClass }} notification-item">
    <div class="d-flex align-items-center gap-3">
        {{-- Employee Photo Placeholder --}}
        <div class="flex-shrink-0">
             <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #e9ecef; border-radius: 50%;">
                <i class="bi bi-person fs-4 text-muted"></i>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <p class="mb-1"><strong>ลูกจ้าง:</strong> {{ $notification->employee->employeeNameTh ?? 'N/A' }}</p>
                <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</p>
                <div class="mb-0 small">
                    @switch($notification->type)
                        @case('ninety_day_report')
                            <p><strong>แจ้งเตือน:</strong> ครบกำหนดรายงานตัว 90 วัน</p>
                            @break
                        @case('passport_expiry')
                            <p><strong>แจ้งเตือน:</strong> Passport ใกล้หมดอายุ</p>
                            @break
                        @case('work_permit_expiry')
                            <p><strong>แจ้งเตือน:</strong> ใบอนุญาตทำงานใกล้หมดอายุ</p>
                            @break
                        @case('work_permit_expired')
                             <p><strong>แจ้งเตือน:</strong> ขาดต่อใบอนุญาตทำงาน</p>
                            @break
                        @case('visa_expiry')
                            <p><strong>แจ้งเตือน:</strong> วีซ่าใกล้หมดอายุ</p>
                            @break
                         @case('ci_renewal')
                            <p><strong>แจ้งเตือน:</strong> ต่ออายุ CI</p>
                            @break
                        @case('resolution_renewal')
                            <p><strong>แจ้งเตือน:</strong> ต่ออายุมติ</p>
                            @break
                        @default
                            <p><strong>แจ้งเตือน:</strong> {{ $notification->type }}</p>
                    @endswitch
                </div>
                 <p class="mb-0 small"><strong>วันหมดอายุ:</strong> {{ $dueDate->format('d/m/Y') }}</p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                @if($daysRemaining !== null)
                <span class="badge bg-dark mb-2 d-block text-nowrap">
                    @if ($daysRemaining >= 0)
                        เหลือ {{ $daysRemaining }} วัน
                    @else
                        เลยกำหนด {{ abs($daysRemaining) }} วัน
                    @endif
                </span>
                @endif

                <div class="btn-group btn-group-sm">
                    <a href="#" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></a>
                    @if($activeTab === 'resolution_renew')
                        <button type="button" class="btn btn-primary" title="ติดตามงาน"><i class="bi bi-clipboard-check"></i></button>
                    @else
                        <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                    @endif
                    <button type="button" class="btn btn-warning" title="ยกเลิกการต่ออายุ"><i class="bi bi-x-circle"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
