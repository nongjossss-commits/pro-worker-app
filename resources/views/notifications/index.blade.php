@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหาชื่อ..." value="{{ request('search') }}" style="width: 200px;">
                <select name="nationality" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">-- ทุกสัญชาติ --</option>
                    <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>เมียนมา</option>
                    <option value="ลาว" @selected(request('nationality') == 'ลาว')>ลาว</option>
                    <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>กัมพูชา</option>
                    <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>เวียดนาม</option>
                </select>
                <select name="mou_type" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">-- ทุกประเภท มติ. --</option>
                    <option value="MOU" @selected(request('mou_type') == 'MOU')>MOU</option>
                    <option value="มติต่ออายุในประเทศ" @selected(request('mou_type') == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" @selected(request('mou_type') == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" @selected(request('mou_type') == 'อื่นๆ')>อื่นๆ</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm">ล้างค่า</a>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs" id="notificationTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="90day-tab" data-bs-toggle="tab" data-bs-target="#ninety_day_report-pane" type="button">
                รายงานตัว 90 วัน <span class="badge bg-danger rounded-pill ms-1">{{ $counts['ninety_day_report'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="passport-tab" data-bs-toggle="tab" data-bs-target="#passport_expiry-pane" type="button">
                Passport <span class="badge bg-danger rounded-pill ms-1">{{ $counts['passport_expiry'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="work-permit-expiry-tab" data-bs-toggle="tab" data-bs-target="#work_permit_expiry-pane" type="button">
                ใบอนุญาตทำงาน <span class="badge bg-danger rounded-pill ms-1">{{ $counts['work_permit_expiry'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="visa-expiry-tab" data-bs-toggle="tab" data-bs-target="#visa_expiry-pane" type="button">
                วีซ่า <span class="badge bg-danger rounded-pill ms-1">{{ $counts['visa_expiry'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ci-renewal-tab" data-bs-toggle="tab" data-bs-target="#ci_renewal-pane" type="button">
                ต่ออายุ CI <span class="badge bg-danger rounded-pill ms-1">{{ $counts['ci_renewal'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resolution-renewal-tab" data-bs-toggle="tab" data-bs-target="#resolution_renewal-pane" type="button">
                ต่ออายุมติ <span class="badge bg-danger rounded-pill ms-1">{{ $counts['resolution_renewal'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-pane" type="button">
                รายการที่ยกเลิก <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['cancelled'] ?? 0 }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content pt-4" id="notificationTabContent">
        @foreach($notificationsData as $type => $paginatedNotifications)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $type }}-pane" role="tabpanel">
                @forelse($paginatedNotifications as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @empty
                    <p class="text-center text-muted">ไม่พบการแจ้งเตือนในหมวดนี้</p>
                @endforelse
                <div class="mt-4">
                    {{ $paginatedNotifications->links() }}
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
