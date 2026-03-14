@props(['notification', 'label'])

@php
    // This helper logic is moved from the main view to make the component self-contained.
    if (!function_exists('getNotificationPresentationInfo')) {
        function getNotificationPresentationInfo($dueDate) {
            if (!$dueDate) {
                return (object)['daysRemainingText' => 'ไม่มีข้อมูล', 'alertClass' => 'alert-secondary'];
            }
            $today = \Carbon\Carbon::today();
            $dueDate = \Carbon\Carbon::parse($dueDate);
            $daysRemaining = $today->diffInDays($dueDate, false);

            $alertClass = 'alert-secondary'; // Default
            if ($daysRemaining < 0) {
                $alertClass = 'alert-dark'; // Expired
            } elseif ($daysRemaining <= 15) {
                $alertClass = 'alert-danger';
            } elseif ($daysRemaining <= 45) {
                $alertClass = 'alert-warning';
            }

            $daysRemainingText = $daysRemaining < 0
                ? 'หมดอายุ ' . abs($daysRemaining) . ' วัน'
                : 'เหลือ ' . $daysRemaining . ' วัน';

            return (object)[
                'daysRemainingText' => $daysRemainingText,
                'alertClass' => $alertClass,
            ];
        }
    }
    $presentation = getNotificationPresentationInfo($notification->due_date);
    $employee = $notification->employee;
@endphp

<div class="alert {{ $presentation->alertClass }} notification-item" role="alert">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ $employee->employeePhoto['data'] ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
        <div class="d-flex justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">{{ $employee->employeeNameTh ?? 'N/A' }}</h5>
                @if(isset($employee->{{ __('employer))') }}<p class="mb-1"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>@endif
                <p class="mb-0 small"><strong>วันหมดอายุ {{ $label }}:</strong> {{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : 'N/A' }}</p>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
                <span class="badge bg-dark mb-2 d-block text-nowrap">{{ $presentation->daysRemainingText }}</span>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></button>
                    <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                    <button type="button" class="btn btn-warning" title="{{ __('ยกเลิก') }}"><i class="bi bi-x-circle"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
