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

// Segregate "Permits/Visa" notifications
$workPermitNotifications = collect($groupedNotifications['work_permit_expiry'] ?? []);
$visaNotifications = collect($groupedNotifications['visa_expiry'] ?? [])->sortBy('due_date');

$workPermitNearingExpiry = $workPermitNotifications->filter(function($item) {
    return $item->due_date && \Carbon\Carbon::parse($item->due_date)->isFuture();
})->sortBy('due_date');

$workPermitExpired = $workPermitNotifications->filter(function($item) {
    return $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast();
})->sortByDesc('due_date');

// Helper function to get the count for each tab's badge
function getTabNotificationCount($tabType, $groupedNotifications, $specialCounts) {
    if ($tabType === 'permits') {
        return $specialCounts['permits'];
    }
    return count($groupedNotifications[$tabType] ?? []);
}

$permitsTotalCount = $workPermitNearingExpiry->count() + $workPermitExpired->count() + $visaNotifications->count();

// This flag is used to set the 'active' class on the first visible tab.
$isFirstActiveTab = true;

@endphp

@section('content')
<div class="content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    @if(empty($groupedNotifications))
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
                        <button class="nav-link @if($isFirstActiveTab) active @endif" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}-pane" type="button" role="tab" aria-controls="{{ $tabId }}-pane" aria-selected="{{ $isFirstActiveTab ? 'true' : 'false' }}">
                            {{ $tabDetails['name'] }}
                            <span class="badge bg-{{ $tabDetails['color'] }} rounded-pill ms-1">{{ $count }}</span>
                        </button>
                    </li>
                    @php $isFirstActiveTab = false; @endphp
                @endif
            @endforeach
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content pt-4" id="notificationTabContent">
            @php $isFirstActiveTab = true; @endphp

            @if(!empty($groupedNotifications['ninety_day_report']))
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="90day-pane" role="tabpanel" aria-labelledby="90day-tab">
                <div class="vstack gap-3">
                    @foreach(collect($groupedNotifications['ninety_day_report'])->sortBy('due_date') as $notification)
                        <x-notification-item :notification="$notification" label="รายงานตัว 90 วัน" />
                    @endforeach
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif

            @if(!empty($groupedNotifications['passport_expiry']))
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="passport-pane" role="tabpanel" aria-labelledby="passport-tab">
                <div class="vstack gap-3">
                    @foreach(collect($groupedNotifications['passport_expiry'])->sortBy('due_date') as $notification)
                        <x-notification-item :notification="$notification" label="Passport" />
                    @endforeach
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif

            @if($permitsTotalCount > 0)
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="permits-pane" role="tabpanel" aria-labelledby="permits-tab">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <h5 class="mb-3">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $workPermitNearingExpiry->count() }})</h5>
                        <div class="vstack gap-3">
                            @forelse($workPermitNearingExpiry as $notification)
                                <x-notification-item :notification="$notification" label="ใบอนุญาตทำงาน" />
                            @empty
                                <div class="alert alert-light text-center">ไม่มีรายการ</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h5 class="mb-3">ขาดต่อขอรับใหม่ ({{ $workPermitExpired->count() }})</h5>
                        <div class="vstack gap-3">
                            @forelse($workPermitExpired as $notification)
                                <x-notification-item :notification="$notification" label="ใบอนุญาตทำงาน" />
                            @empty
                                <div class="alert alert-light text-center">ไม่มีรายการ</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h5 class="mb-3">วีซ่าหมดอายุ ({{ $visaNotifications->count() }})</h5>
                        <div class="vstack gap-3">
                            @forelse($visaNotifications as $notification)
                                <x-notification-item :notification="$notification" label="วีซ่า" />
                            @empty
                                <div class="alert alert-light text-center">ไม่มีรายการ</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif

            @if(!empty($groupedNotifications['ci_renewal']))
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="ci-renew-pane" role="tabpanel" aria-labelledby="ci-renew-tab">
                <div class="vstack gap-3">
                    @foreach(collect($groupedNotifications['ci_renewal'])->sortBy('due_date') as $notification)
                        <x-notification-item :notification="$notification" label="ต่ออายุ CI" />
                    @endforeach
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif

            @if(!empty($groupedNotifications['resolution_renewal']))
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="resolution-renew-pane" role="tabpanel" aria-labelledby="resolution-renew-tab">
                <div class="vstack gap-3">
                    @foreach(collect($groupedNotifications['resolution_renewal'])->sortBy('due_date') as $notification)
                        <x-notification-item :notification="$notification" label="ต่ออายุมติ" />
                    @endforeach
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif

            @if(!empty($groupedNotifications['cancelled_renewal']))
            <div class="tab-pane fade @if($isFirstActiveTab) show active @endif" id="cancelled-renew-pane" role="tabpanel" aria-labelledby="cancelled-renew-tab">
                <div class="vstack gap-3">
                    @foreach(collect($groupedNotifications['cancelled_renewal'])->sortBy('due_date') as $notification)
                        <x-notification-item :notification="$notification" label="รายการที่ยกเลิก" />
                    @endforeach
                </div>
            </div>
            @php $isFirstActiveTab = false; @endphp
            @endif
        </div>
    @endif
</div>
@endsection
