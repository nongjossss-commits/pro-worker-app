@extends('layouts.app')

@section('title', 'รายการแจ้งเตือน')

@php
// 1. Define the tabs based on the design reference
$tabs = [
    '90day' => ['name' => 'รายงานตัว 90 วัน', 'color' => 'danger'],
    'passport' => ['name' => 'Passport', 'color' => 'danger'],
    'permits' => ['name' => 'ใบอนุญาต/วีซ่า', 'color' => 'danger'],
    'ci-renew' => ['name' => 'ต่ออายุ CI', 'color' => 'danger'],
    'resolution-renew' => ['name' => 'ต่ออายุมติ', 'color' => 'danger'],
    'cancelled-renew' => ['name' => 'รายการที่ยกเลิก', 'color' => 'secondary'],
];

// 2. Map the database notification types to the tab they belong to
$typeToTabMapping = [
    'ninety_day_report' => '90day',
    'passport_expiry' => 'passport',
    'visa_expiry' => 'permits',
    'work_permit_expiry' => 'permits',
    'ci_renewal' => 'ci-renew',
    'resolution_renewal' => 'resolution-renew',
    'cancelled_renewal' => 'cancelled-renew',
];

// 3. Helper to get all notifications for a given tab ID
function getNotificationsForTab($tabId, $groupedNotifications, $typeToTabMapping) {
    $notificationsForTab = collect();
    foreach ($typeToTabMapping as $type => $id) {
        if ($id === $tabId && $groupedNotifications->has($type)) {
            $notificationsForTab = $notificationsForTab->merge($groupedNotifications->get($type));
        }
    }
    return $notificationsForTab;
}
@endphp

@section('content')
<div class="content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    @if($groupedNotifications->isEmpty())
        <div class="alert alert-success text-center">
            <i class="bi bi-check-circle-fill me-2"></i> ไม่มีรายการแจ้งเตือนค้าง
        </div>
    @else
        <ul class="nav nav-tabs" id="notificationTab" role="tablist">
            @foreach($tabs as $tabId => $tabDetails)
                @php
                    $notificationsForTab = getNotificationsForTab($tabId, $groupedNotifications, $typeToTabMapping);
                @endphp
                @if($notificationsForTab->isNotEmpty())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}-pane" type="button" role="tab" aria-controls="{{ $tabId }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $tabDetails['name'] }}
                            <span class="badge bg-{{ $tabDetails['color'] }} rounded-pill ms-1">{{ $notificationsForTab->count() }}</span>
                        </button>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="tab-content pt-4" id="notificationTabContent">
            @foreach($tabs as $tabId => $tabDetails)
                 @php
                    $notificationsForTab = getNotificationsForTab($tabId, $groupedNotifications, $typeToTabMapping)->sortBy('due_date');
                @endphp
                @if($notificationsForTab->isNotEmpty())
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}-pane" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                        <div class="vstack gap-3">
                            @foreach($notificationsForTab as $notification)
                                <div class="alert alert-warning notification-item" role="alert">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="alert-heading mb-1">{{ $notification->employee->employeeNameTh ?? 'N/A' }}</h5>
                                        @if($notification->due_date)
                                        <small class="text-muted">ครบกำหนด: {{ \Carbon\Carbon::parse($notification->due_date)->thaidate('j M Y') }}</small>
                                        @endif
                                    </div>
                                    @if(isset($notification->employee->employer))
                                    <p class="mb-1">
                                        <strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}
                                    </p>
                                    @endif
                                    <p class="mb-0">{{ $notification->message }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
