@extends('layouts.app')

@section('title', 'รายการแจ้งเตือน')

@php
$notificationTypes = [
    '90-day-report' => ['name' => 'รายงานตัว 90 วัน', 'color' => 'danger'],
    'passport-expiry' => ['name' => 'Passport', 'color' => 'warning'],
    'visa-expiry' => ['name' => 'วีซ่า', 'color' => 'info'],
];
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
            @foreach($groupedNotifications as $type => $notifications)
                @if(isset($notificationTypes[$type]))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $type }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $type }}-pane" type="button" role="tab" aria-controls="{{ $type }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $notificationTypes[$type]['name'] }}
                            <span class="badge bg-{{ $notificationTypes[$type]['color'] }} rounded-pill ms-1">{{ $notifications->count() }}</span>
                        </button>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="tab-content pt-4" id="notificationTabContent">
            @foreach($groupedNotifications as $type => $notifications)
                @if(isset($notificationTypes[$type]))
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $type }}-pane" role="tabpanel" aria-labelledby="{{ $type }}-tab">
                        <div class="vstack gap-3">
                            @forelse($notifications as $notification)
                                <div class="alert alert-{{ $notificationTypes[$type]['color'] }} notification-item" role="alert">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="alert-heading">{{ $notification->employee->employeeNameTh ?? 'N/A' }}</h5>
                                        <small class="text-muted">ครบกำหนด: {{ \Carbon\Carbon::parse($notification->due_date)->thaidate('j M Y') }}</small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}
                                    </p>
                                    <p class="mb-0">{{ $notification->message }}</p>
                                </div>
                            @empty
                                <p>ไม่มีการแจ้งเตือนสำหรับประเภทนี้</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
