@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')
@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>
    @if($groupedNotifications->isEmpty())
        <div class="alert alert-success text-center">
            <i class="bi bi-check-circle-fill me-2"></i> ไม่มีรายการแจ้งเตือน
        </div>
    @else
        @php
            // Define all possible tabs and their keys
            $tabs = [
                'ninety_day_report' => 'รายงานตัว 90 วัน',
                'passport_expiry' => 'Passport',
                'permits' => 'ใบอนุญาต/วีซ่า',
                'ci_renewal' => 'ต่ออายุ CI',
                'resolution_renewal' => 'ต่ออายุมติ',
            ];
            // Filter only the tabs that have notifications
            $visibleTabs = collect($tabs)->filter(function($name, $key) use ($groupedNotifications) {
                if ($key === 'permits') {
                    return $groupedNotifications->has('work_permit_expiry') || $groupedNotifications->has('visa_expiry');
                }
                return $groupedNotifications->has($key);
            });
        @endphp
        <ul class="nav nav-tabs" id="notificationTab" role="tablist">
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
        <div class="tab-content pt-4" id="notificationTabContent">
            @foreach($visibleTabs as $key => $name)
                <div class="tab-pane fade @if ($loop->first) show active @endif" id="{{ $key }}-pane" role="tabpanel">
                    @if($key === 'permits')
                        <div class="row g-4">
                            <div class="col-lg-6">
                                @php $workPermitNotifications = $groupedNotifications->get('work_permit_expiry', collect()); @endphp
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
                                @php $visaNotifications = $groupedNotifications->get('visa_expiry', collect()); @endphp
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
                            @php
                                $notifications = $groupedNotifications->get($key, collect());
                                $label = $name;
                                if ($key === 'ci_renewal') {
                                    $label = 'CI ใกล้หมดอายุ';
                                } elseif ($key === 'resolution_renewal') {
                                    $label = 'มติใกล้หมดอายุ';
                                }
                            @endphp
                            @foreach($notifications->sortBy('due_date') as $notification)
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
