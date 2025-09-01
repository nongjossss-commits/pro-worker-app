@props(['notification'])

@php
    $daysRemaining = $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->diffInDays(now(), false) : null;
    $daysRemaining = $daysRemaining !== null ? $daysRemaining * -1 : null;

    $alertClass = 'alert-secondary';
    if ($daysRemaining !== null) {
        if ($daysRemaining < 0) {
            $alertClass = 'alert-dark'; // Expired
        } elseif ($daysRemaining <= 30) {
            $alertClass = 'alert-danger'; // Danger zone
        } elseif ($daysRemaining <= 60) {
            $alertClass = 'alert-warning'; // Warning zone
        }
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
                <h5 class="alert-heading mb-1">{{ $notification->employee->name_en ?? 'N/A' }}</h5>
                <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->name ?? 'N/A' }}</p>
                <p class="mb-0 small"><strong>วันหมดอายุ {{ $notification->type }}:</strong> {{ \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') }}</p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                @if($daysRemaining !== null)
                <span class="badge bg-dark mb-2 d-block text-nowrap">
                    @if($daysRemaining >= 0)
                        เหลือ {{ $daysRemaining }} วัน
                    @else
                        หมดอายุแล้ว {{ abs($daysRemaining) }} วัน
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
