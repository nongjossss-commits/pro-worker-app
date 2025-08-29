@extends('layouts.app')

@section('title', 'รายการแจ้งเตือน')

@php
// 1. Define the tabs based on the design reference
$tabs = [
    '90day' => ['name' => 'รายงานตัว 90 วัน', 'color' => 'danger', 'type' => 'ninety_day_report'],
    'passport' => ['name' => 'Passport', 'color' => 'danger', 'type' => 'passport_expiry'],
    'permits' => ['name' => 'ใบอนุญาต/วีซ่า', 'color' => 'danger', 'type' => 'permits'], // Special type
    'ci-renew' => ['name' => 'ต่ออายุ CI', 'color' => 'danger', 'type' => 'ci_renewal'],
    'resolution-renew' => ['name' => 'ต่ออายุมติ', 'color' => 'danger', 'type' => 'resolution_renewal'],
    'cancelled-renew' => ['name' => 'รายการที่ยกเลิก', 'color' => 'secondary', 'type' => 'cancelled_renewal'],
];

// 2. Helper function to calculate days remaining and determine alert class
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

// 3. Segregate "Permits/Visa" notifications
$workPermitNotifications = $groupedNotifications->get('work_permit_expiry', collect());
$visaNotifications = $groupedNotifications->get('visa_expiry', collect())->sortBy('due_date');

$workPermitNearingExpiry = $workPermitNotifications->filter(function($item) {
    return $item->due_date && \Carbon\Carbon::parse($item->due_date)->isFuture();
})->sortBy('due_date');

$workPermitExpired = $workPermitNotifications->filter(function($item) {
    return $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast();
})->sortByDesc('due_date');

// 4. Helper function to get the count for each tab's badge
function getTabNotificationCount($tabType, $groupedNotifications, $specialCounts) {
    if ($tabType === 'permits') {
        return $specialCounts['permits'];
    }
    return $groupedNotifications->get($tabType, collect())->count();
}

$permitsTotalCount = $workPermitNearingExpiry->count() + $workPermitExpired->count() + $visaNotifications->count();

@endphp

@section('content')
<div class="content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    @if($groupedNotifications->isEmpty())
        <div class="alert alert-success text-center">
            <i class="bi bi-check-circle-fill me-2"></i> ไม่มีรายการแจ้งเตือนค้าง
        </div>
    @else
        {{-- Main Tab Navigation --}}
        <ul class="nav nav-tabs" id="notificationTab" role="tablist">
            @foreach($tabs as $tabId => $tabDetails)
                @php
                    $count = getTabNotificationCount($tabDetails['type'], $groupedNotifications, ['permits' => $permitsTotalCount]);
                @endphp
                @if($count > 0)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}-pane" type="button" role="tab" aria-controls="{{ $tabId }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $tabDetails['name'] }}
                            <span class="badge bg-{{ $tabDetails['color'] }} rounded-pill ms-1">{{ $count }}</span>
                        </button>
                    </li>
                @endif
            @endforeach
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content pt-4" id="notificationTabContent">
            @foreach($tabs as $tabId => $tabDetails)
                @php
                    $count = getTabNotificationCount($tabDetails['type'], $groupedNotifications, ['permits' => $permitsTotalCount]);
                @endphp
                @if($count > 0)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}-pane" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">

                        {{-- Special handling for the nested 'Permits/Visa' tab --}}
                        @if($tabId === 'permits')
                            <div class="row g-4">
                                {{-- Column 1: Work Permit Nearing Expiry --}}
                                <div class="col-lg-4">
                                    <h5 class="mb-3">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $workPermitNearingExpiry->count() }})</h5>
                                    <div class="vstack gap-3">
                                        @forelse($workPermitNearingExpiry as $notification)
                                            @php
                                                $presentation = getNotificationPresentationInfo($notification->due_date);
                                                $employee = $notification->employee;
                                            @endphp
                                            <div class="alert {{ $presentation->alertClass }} notification-item" role="alert">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="{{ $employee->employeePhoto['data'] ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
                                                    <div class="d-flex justify-content-between align-items-start w-100">
                                                        <div class="flex-grow-1">
                                                            <h5 class="alert-heading mb-1">{{ $employee->employeeNameTh ?? 'N/A' }}</h5>
                                                            @if(isset($employee->employer))<p class="mb-1"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>@endif
                                                            <p class="mb-0 small"><strong>วันหมดอายุ ใบอนุญาตทำงาน:</strong> {{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : 'N/A' }}</p>
                                                        </div>
                                                        <div class="text-end flex-shrink-0 ms-2">
                                                            <span class="badge bg-dark mb-2 d-block text-nowrap">{{ $presentation->daysRemainingText }}</span>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></button>
                                                                <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                                                                <button type="button" class="btn btn-warning" title="ยกเลิก"><i class="bi bi-x-circle"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="alert alert-light text-center">ไม่มีรายการ</div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Column 2: Expired/New Application --}}
                                <div class="col-lg-4">
                                    <h5 class="mb-3">ขาดต่อขอรับใหม่ ({{ $workPermitExpired->count() }})</h5>
                                    <div class="vstack gap-3">
                                        @forelse($workPermitExpired as $notification)
                                            @php
                                                $presentation = getNotificationPresentationInfo($notification->due_date);
                                                $employee = $notification->employee;
                                            @endphp
                                            <div class="alert {{ $presentation->alertClass }} notification-item" role="alert">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="{{ $employee->employeePhoto['data'] ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
                                                    <div class="d-flex justify-content-between align-items-start w-100">
                                                        <div class="flex-grow-1">
                                                            <h5 class="alert-heading mb-1">{{ $employee->employeeNameTh ?? 'N/A' }}</h5>
                                                            @if(isset($employee->employer))<p class="mb-1"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>@endif
                                                            <p class="mb-0 small"><strong>วันหมดอายุ ใบอนุญาตทำงาน:</strong> {{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : 'N/A' }}</p>
                                                        </div>
                                                        <div class="text-end flex-shrink-0 ms-2">
                                                            <span class="badge bg-dark mb-2 d-block text-nowrap">{{ $presentation->daysRemainingText }}</span>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></button>
                                                                <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                                                                <button type="button" class="btn btn-warning" title="ยกเลิก"><i class="bi bi-x-circle"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="alert alert-light text-center">ไม่มีรายการ</div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Column 3: Visa Expired --}}
                                <div class="col-lg-4">
                                    <h5 class="mb-3">วีซ่าหมดอายุ ({{ $visaNotifications->count() }})</h5>
                                    <div class="vstack gap-3">
                                        @forelse($visaNotifications as $notification)
                                            @php
                                                $presentation = getNotificationPresentationInfo($notification->due_date);
                                                $employee = $notification->employee;
                                            @endphp
                                            <div class="alert {{ $presentation->alertClass }} notification-item" role="alert">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="{{ $employee->employeePhoto['data'] ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
                                                    <div class="d-flex justify-content-between align-items-start w-100">
                                                        <div class="flex-grow-1">
                                                            <h5 class="alert-heading mb-1">{{ $employee->employeeNameTh ?? 'N/A' }}</h5>
                                                            @if(isset($employee->employer))<p class="mb-1"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>@endif
                                                            <p class="mb-0 small"><strong>วันหมดอายุ วีซ่า:</strong> {{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : 'N/A' }}</p>
                                                        </div>
                                                        <div class="text-end flex-shrink-0 ms-2">
                                                            <span class="badge bg-dark mb-2 d-block text-nowrap">{{ $presentation->daysRemainingText }}</span>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></button>
                                                                <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                                                                <button type="button" class="btn btn-warning" title="ยกเลิก"><i class="bi bi-x-circle"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="alert alert-light text-center">ไม่มีรายการ</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @else
                        {{-- Standard handling for other tabs --}}
                            <div class="vstack gap-3">
                                @php
                                    $notificationsForTab = $groupedNotifications->get($tabDetails['type'], collect())->sortBy('due_date');
                                @endphp
                                @foreach($notificationsForTab as $notification)
                                    @php
                                        $presentation = getNotificationPresentationInfo($notification->due_date);
                                        $employee = $notification->employee;
                                    @endphp
                                    <div class="alert {{ $presentation->alertClass }} notification-item" role="alert">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ $employee->employeePhoto['data'] ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}" class="employee-photo-thumb" alt="Photo">
                                            <div class="d-flex justify-content-between align-items-start w-100">
                                                <div class="flex-grow-1">
                                                    <h5 class="alert-heading mb-1">{{ $employee->employeeNameTh ?? 'N/A' }}</h5>
                                                    @if(isset($employee->employer))<p class="mb-1"><strong>นายจ้าง:</strong> {{ $employee->employer->employerNameTh ?? 'N/A' }}</p>@endif
                                                    <p class="mb-0 small"><strong>วันหมดอายุ {{ $tabDetails['name'] }}:</strong> {{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : 'N/A' }}</p>
                                                </div>
                                                <div class="text-end flex-shrink-0 ms-2">
                                                    <span class="badge bg-dark mb-2 d-block text-nowrap">{{ $presentation->daysRemainingText }}</span>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-info" title="ดูข้อมูล"><i class="bi bi-search"></i></button>
                                                        <button type="button" class="btn btn-success" title="ต่ออายุ"><i class="bi bi-calendar-check"></i></button>
                                                        <button type="button" class="btn btn-warning" title="ยกเลิก"><i class="bi bi-x-circle"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
