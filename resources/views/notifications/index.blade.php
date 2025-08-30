@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')
@php
    $tabs = [
        'ninety_day_report' => ['id' => '90day', 'name' => 'รายงานตัว 90 วัน'],
        'passport_expiry' => ['id' => 'passport', 'name' => 'Passport'],
        'permits' => ['id' => 'permits', 'name' => 'ใบอนุญาต/วีซ่า'],
    ];
    $workPermitNotifications = $groupedNotifications->get('work_permit_expiry', collect());
    $visaNotifications = $groupedNotifications->get('visa_expiry', collect());
    $permitsTotalCount = $workPermitNotifications->count() + $visaNotifications->count();
    $activeTabFound = false;
@endphp
@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>
    @if($groupedNotifications->isEmpty())
        <div class="alert alert-success text-center">
            <i class="bi bi-check-circle-fill me-2"></i> ไม่มีรายการแจ้งเตือน
        </div>
    @else
        <ul class="nav nav-tabs" id="notificationTab" role="tablist">
            @foreach($tabs as $type => $details)
                @php
                    $count = ($type === 'permits') ? $permitsTotalCount : $groupedNotifications->get($type, collect())->count();
                @endphp
                @if($count > 0)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if(!$activeTabFound) active @endif" id="{{ $details['id'] }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $details['id'] }}-pane" type="button" role="tab">
                            {{ $details['name'] }}
                            <span class="badge bg-danger rounded-pill ms-1">{{ $count }}</span>
                        </button>
                    </li>
                    @php $activeTabFound = true; @endphp
                @endif
            @endforeach
        </ul>
        <div class="tab-content pt-4" id="notificationTabContent">
            @php $activeTabFound = false; @endphp
            @foreach($tabs as $type => $details)
                @php
                    $count = ($type === 'permits') ? $permitsTotalCount : $groupedNotifications->get($type, collect())->count();
                @endphp
                @if($count > 0)
                    <div class="tab-pane fade @if(!$activeTabFound) show active @endif" id="{{ $details['id'] }}-pane" role="tabpanel">
                        @if($type === 'permits')
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <h5 class="mb-3">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $workPermitNotifications->count() }})</h5>
                                    <div class="vstack gap-3">
                                        @forelse($workPermitNotifications->sortBy('due_date') as $notification)
                                            @include('notifications._notification_item', ['notification' => $notification, 'label' => 'ใบอนุญาตทำงาน'])
                                        @empty
                                            <div class="text-muted text-center">ไม่มีรายการ</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <h5 class="mb-3">วีซ่าหมดอายุ ({{ $visaNotifications->count() }})</h5>
                                    <div class="vstack gap-3">
                                        @forelse($visaNotifications->sortBy('due_date') as $notification)
                                            @include('notifications._notification_item', ['notification' => $notification, 'label' => 'วีซ่า'])
                                        @empty
                                            <div class="text-muted text-center">ไม่มีรายการ</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="vstack gap-3">
                                @foreach($groupedNotifications->get($type, collect())->sortBy('due_date') as $notification)
                                    @include('notifications._notification_item', ['notification' => $notification, 'label' => $details['name']])
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @php $activeTabFound = true; @endphp
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
