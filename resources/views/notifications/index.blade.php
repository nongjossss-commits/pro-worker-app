@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@php
// Define the main tabs and their corresponding notification types
$mainTabs = [
'90day' => ['name' => 'รายงานตัว 90 วัน', 'color' => 'danger', 'type' => 'ninety_day_report'],
'passport' => ['name' => 'Passport', 'color' => 'danger', 'type' => 'passport_expiry'],
'permits' => ['name' => 'ใบอนุญาต/วีซ่า', 'color' => 'danger', 'type' => 'permits'], // Special case
];

// Prepare collections for the special "permits" tab
$workPermitNearingExpiry = collect($groupedNotifications['work_permit_expiry'] ?? [])->sortBy('due_date');
$visaNotifications = collect($groupedNotifications['visa_expiry'] ?? [])->sortBy('due_date');

// Calculate total for the "permits" tab badge
$permitsTotalCount = $workPermitNearingExpiry->count() + $visaNotifications->count();

// This flag is used to set the 'active' class on the first visible tab.
$isFirstTab = true;
@endphp

@section('content')

@if($groupedNotifications->isEmpty())
    <div class="alert alert-success text-center">
        <i class="bi bi-check-circle-fill me-2"></i> ไม่มีรายการแจ้งเตือน
    </div>
@else
    {{-- Main Tab Navigation --}}
    <ul class="nav nav-tabs" id="notificationTab" role="tablist">
        @foreach($mainTabs as $tabId => $tabDetails)
            @php
                $count = ($tabDetails['type'] === 'permits') ? $permitsTotalCount : $groupedNotifications->get($tabDetails['type'], collect())->count();
            @endphp
            @if($count > 0)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($isFirstTab) active @endif" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}-pane" type="button" role="tab">
                        {{ $tabDetails['name'] }}
                        <span class="badge bg-{{ $tabDetails['color'] }} rounded-pill ms-1">{{ $count }}</span>
                    </button>
                </li>
                @php $isFirstTab = false; @endphp
            @endif
        @endforeach
    </ul>
    {{-- Tab Content --}}
    <div class="tab-content pt-4" id="notificationTabContent">
        @php $isFirstTab = true; @endphp
        {{-- 90 Day Report Pane --}}
        @if($groupedNotifications->has('ninety_day_report') && $groupedNotifications['ninety_day_report']->isNotEmpty())
            <div class="tab-pane fade @if($isFirstTab) show active @endif" id="90day-pane" role="tabpanel">
                <div class="vstack gap-3">
                    @foreach($groupedNotifications['ninety_day_report']->sortBy('due_date') as $notification)
                        {{-- This assumes a Blade component `x-notification-item` exists --}}
                        @include('notifications._notification_item', ['notification' => $notification, 'label' => 'รายงานตัว 90 วัน'])
                    @endforeach
                </div>
            </div>
            @php $isFirstTab = false; @endphp
        @endif
        {{-- Passport Pane --}}
        @if($groupedNotifications->has('passport_expiry') && $groupedNotifications['passport_expiry']->isNotEmpty())
            <div class="tab-pane fade @if($isFirstTab) show active @endif" id="passport-pane" role="tabpanel">
                <div class="vstack gap-3">
                    @foreach($groupedNotifications['passport_expiry']->sortBy('due_date') as $notification)
                        @include('notifications._notification_item', ['notification' => $notification, 'label' => 'Passport'])
                    @endforeach
                </div>
            </div>
            @php $isFirstTab = false; @endphp
        @endif
        {{-- Permits & Visa Pane --}}
        @if($permitsTotalCount > 0)
            <div class="tab-pane fade @if($isFirstTab) show active @endif" id="permits-pane" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h5 class="mb-3">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $workPermitNearingExpiry->count() }})</h5>
                        <div class="vstack gap-3">
                            @forelse($workPermitNearingExpiry as $notification)
                                @include('notifications._notification_item', ['notification' => $notification, 'label' => 'ใบอนุญาตทำงาน'])
                            @empty
                                <div class="text-muted text-center">ไม่มีรายการ</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h5 class="mb-3">วีซ่าหมดอายุ ({{ $visaNotifications->count() }})</h5>
                        <div class="vstack gap-3">
                            @forelse($visaNotifications as $notification)
                                @include('notifications._notification_item', ['notification' => $notification, 'label' => 'วีซ่า'])
                            @empty
                                <div class="text-muted text-center">ไม่มีรายการ</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @php $isFirstTab = false; @endphp
        @endif
    </div>
@endif
{{-- This is a placeholder for the Blade component x-notification-item as it might not exist yet. --}}
{{-- Create a new file at resources/views/notifications/_notification_item.blade.php --}}
