@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@php
// Define all possible sub-tabs and their keys
$subTabs = [
    'ninety_day_report' => 'รายงานตัว 90 วัน',
    'passport_expiry' => 'Passport',
    'permits' => 'ใบอนุญาต/วีซ่า',
    'ci_renewal' => 'ต่ออายุ CI',
    'resolution_renewal' => 'ต่ออายุมติ',
];
// Filter only the tabs that have notifications for the current status
$visibleTabs = collect($subTabs)->filter(function($name, $key) use ($groupedNotifications) {
    if ($key === 'permits') {
        return $groupedNotifications->has('work_permit_expiry') || $groupedNotifications->has('visa_expiry');
    }
    return $groupedNotifications->has($key);
});

$nationalities = ['Myanmar', 'Laos', 'Cambodia', 'Vietnam'];
$mouTypes = ['MOU', 'NON-MOU'];
@endphp

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    <!-- Main Status Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['tab' => 'unread'] + ($filters ?? [])) }}">รายการที่ต้องดำเนินการ</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'read' ? 'active' : '' }}" href="{{ route('notifications.index', ['tab' => 'read'] + ($filters ?? [])) }}">รายการที่เสร็จสิ้น</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'cancelled' ? 'active' : '' }}" href="{{ route('notifications.index', ['tab' => 'cancelled'] + ($filters ?? [])) }}">รายการที่ยกเลิก</a>
        </li>
    </ul>

    <!-- Filter and Export Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('notifications.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="col-md-4">
                    <label for="search" class="form-label">ค้นหา</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ, รหัสพนักงาน...">
                </div>
                <div class="col-md-2">
                    <label for="nationality" class="form-label">สัญชาติ</label>
                    <select class="form-select" id="nationality" name="nationality">
                        <option value="">ทั้งหมด</option>
                        @foreach($nationalities as $nationality)
                        <option value="{{ $nationality }}" @selected(($filters['nationality'] ?? '') === $nationality)>{{ $nationality }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="mou_type" class="form-label">ประเภท MOU</label>
                    <select class="form-select" id="mou_type" name="mou_type">
                        <option value="">ทั้งหมด</option>
                         @foreach($mouTypes as $mouType)
                        <option value="{{ $mouType }}" @selected(($filters['mou_type'] ?? '') === $mouType)>{{ $mouType }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="col-md-2">
                    <label for="month" class="form-label">เดือนที่ครบกำหนด</label>
                    <input type="month" class="form-control" id="month" name="month" value="{{ $filters['month'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">กรอง</button>
                </div>
            </form>
        </div>
    </div>

    @if($groupedNotifications->isEmpty())
        <div class="alert alert-secondary text-center">
            <i class="bi bi-search me-2"></i>
            @if(array_filter($filters ?? []))
                ไม่พบรายการที่ตรงกับเงื่อนไขการค้นหา
                <a href="{{ route('notifications.index', ['tab' => $activeTab]) }}" class="d-block mt-2">ล้างตัวกรองทั้งหมด</a>
            @else
                ไม่มีรายการแจ้งเตือนในหมวดหมู่นี้
            @endif
        </div>
    @else
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('notifications.export', ['tab' => $activeTab] + $filters) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export to CSV
        </a>
    </div>
        <ul class="nav nav-tabs" id="notificationSubTab" role="tablist">
            @foreach($visibleTabs as $key => $name)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($loop->first) active @endif" id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}-pane" type="button" role="tab">
                        {{ $name }}
                        @php
                            $count = ($key === 'permits')
                                ? $groupedNotifications->get('work_permit_expiry', collect())->count() + $groupedNotifications->get('visa_expiry', collect())->count()
                                : $groupedNotifications->get($key, collect())->count();
                        @endphp
                        <span class="badge bg-danger rounded-pill ms-1">{{ $count }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content pt-4" id="notificationSubTabContent">
            @foreach($visibleTabs as $key => $name)
                <div class="tab-pane fade @if ($loop->first) show active @endif" id="{{ $key }}-pane" role="tabpanel">
                    @if($key === 'permits')
                        <div class="row g-4">
                            <div class="col-lg-6">
                                @php $workPermitNotifications = $groupedNotifications->get('work_permit_expiry', collect()); @endphp
                                @if($workPermitNotifications->isNotEmpty())
                                <h5 class="mb-3">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $workPermitNotifications->count() }})</h5>
                                <div class="vstack gap-3">
                                    @foreach($workPermitNotifications as $notification)
                                        @include('notifications._notification_item', ['notification' => $notification, 'label' => 'ใบอนุญาตทำงาน'])
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <div class="col-lg-6">
                                @php $visaNotifications = $groupedNotifications->get('visa_expiry', collect()); @endphp
                                 @if($visaNotifications->isNotEmpty())
                                <h5 class="mb-3">วีซ่าหมดอายุ ({{ $visaNotifications->count() }})</h5>
                                <div class="vstack gap-3">
                                    @foreach($visaNotifications as $notification)
                                        @include('notifications._notification_item', ['notification' => $notification, 'label' => 'วีซ่า'])
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="vstack gap-3">
                            @php
                                $notifications = $groupedNotifications->get($key, collect());
                                $label = $name;
                                if ($key === 'ci_renewal') {
                                    $label = 'CI ใกล้หมดอายุ';
                                } elseif ($key === 'resolution_renewal') {
                                    $label = 'มติใกล้หมดอายุ';
                                }
                            @endphp
                            @foreach($notifications as $notification)
                                @include('notifications._notification_item', ['notification' => $notification, 'label' => $label])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
