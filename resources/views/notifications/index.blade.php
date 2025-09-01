@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@php
$nationalities = ['ลาว', 'กัมพูชา', 'เมียนมา', 'เวียดนาม'];
$mouTypes = ['MOU', 'มติต่ออายุในประเทศ', 'มติขึ้นทะเบียน', 'อื่นๆ'];
$months = [
    '0' => 'มกราคม', '1' => 'กุมภาพันธ์', '2' => 'มีนาคม', '3' => 'เมษายน',
    '4' => 'พฤษภาคม', '5' => 'มิถุนายน', '6' => 'กรกฎาคม', '7' => 'สิงหาคม',
    '8' => 'กันยายน', '9' => 'ตุลาคม', '10' => 'พฤศจิกายน', '11' => 'ธันวาคม'
];
$resolutionSteps = [
    'not_started' => 'ยังไม่เริ่ม', 'step1' => 'ขั้นตอนที่ 1', 'step2' => 'ขั้นตอนที่ 2',
    'step3' => 'ขั้นตอนที่ 3', 'step4' => 'ขั้นตอนที่ 4', 'step5' => 'ขั้นตอนที่ 5',
    'step6' => 'ขั้นตอนที่ 6 (เสร็จสิ้น)'
];
$activeTab = request()->input('tab', '90day');
@endphp

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>
    <ul class="nav nav-tabs" id="notificationTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === '90day' ? 'active' : '' }}" id="90day-tab" href="{{ route('notifications.index', ['tab' => '90day']) }}" role="tab">
                รายงานตัว 90 วัน <span class="badge bg-danger rounded-pill ms-1">{{ $notifications90day->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'passport' ? 'active' : '' }}" id="passport-tab" href="{{ route('notifications.index', ['tab' => 'passport']) }}" role="tab">
                Passport <span class="badge bg-danger rounded-pill ms-1">{{ $notificationsPassport->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'permits' ? 'active' : '' }}" id="permits-tab" href="{{ route('notifications.index', ['tab' => 'permits']) }}" role="tab">
                ใบอนุญาต/วีซ่า <span class="badge bg-danger rounded-pill ms-1">{{ $notificationsWorkPermit->total() + $notificationsWorkPermitExpired->total() + $notificationsVisa->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'ci_renew' ? 'active' : '' }}" id="ci-renew-tab" href="{{ route('notifications.index', ['tab' => 'ci_renew']) }}" role="tab">
                ต่ออายุ CI <span class="badge bg-danger rounded-pill ms-1">{{ $notificationsCiRenew->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'resolution_renew' ? 'active' : '' }}" id="resolution-renew-tab" href="{{ route('notifications.index', ['tab' => 'resolution_renew']) }}" role="tab">
                ต่ออายุมติ <span class="badge bg-danger rounded-pill ms-1">{{ $notificationsResolutionRenew->total() }}</span>
            </a>
        </li>
         <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'cancelled' ? 'active' : '' }}" id="cancelled-renew-tab" href="{{ route('notifications.index', ['tab' => 'cancelled']) }}" role="tab">
                รายการที่ยกเลิก <span class="badge bg-secondary rounded-pill ms-1">{{ $notificationsCancelled->total() }}</span>
            </a>
        </li>
    </ul>
    <div class="tab-content pt-4" id="notificationTabContent">
        {{-- 90 Day Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === '90day' ? 'show active' : '' }}" id="n-90day" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="90day">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_90day" placeholder="ค้นหา..." value="{{ request('search_90day') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_90day">
                        <option value="">-- ทุกสัญชาติ --</option>
                        @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_90day') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_90day">
                        <option value="">-- ทุกประเภท มติ. --</option>
                         @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_90day') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_90day">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_90day') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                    <a href="{{ route('notifications.export', ['export_type' => '90day'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
                </div>
            </form>
            <h5 class="mb-3">รายการรายงานตัว 90 วัน ({{ $notifications90day->total() }})</h5>
            <div id="notification90DayListContainer" class="vstack gap-2">
                @each('notifications._notification_item', $notifications90day, 'notification')
            </div>
            <div class="mt-4">
                {{ $notifications90day->withQueryString()->links() }}
            </div>
        </div>

        {{-- Passport Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'passport' ? 'show active' : '' }}" id="n-passport" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="passport">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_passport" placeholder="ค้นหา..." value="{{ request('search_passport') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_passport">
                        <option value="">-- ทุกสัญชาติ --</option>
                         @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_passport') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_passport">
                        <option value="">-- ทุกประเภท มติ. --</option>
                        @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_passport') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_passport">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_passport') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                    <a href="{{ route('notifications.export', ['export_type' => 'passport'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
                </div>
            </form>
            <h5 class="mb-3">รายการ Passport หมดอายุ ({{ $notificationsPassport->total() }})</h5>
            <div id="notificationPassportListContainer" class="vstack gap-2">
                @each('notifications._notification_item', $notificationsPassport, 'notification')
            </div>
             <div class="mt-4">
                {{ $notificationsPassport->withQueryString()->links() }}
            </div>
        </div>

        {{-- Permits Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'permits' ? 'show active' : '' }}" id="n-permits" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="permits">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_permits" placeholder="ค้นหา..." value="{{ request('search_permits') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_permits">
                        <option value="">-- ทุกสัญชาติ --</option>
                         @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_permits') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_permits">
                        <option value="">-- ทุกประเภท มติ. --</option>
                         @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_permits') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_permits">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_permits') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                </div>
            </form>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $notificationsWorkPermit->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'work_permit'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationWorkPermitListContainer" class="vstack gap-2">
                         @each('notifications._notification_item', $notificationsWorkPermit, 'notification')
                    </div>
                    <div class="mt-4">
                        {{ $notificationsWorkPermit->withQueryString()->links() }}
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">ขาดต่อขอรับใหม่ ({{ $notificationsWorkPermitExpired->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'work_permit_expired'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationWorkPermitExpiredListContainer" class="vstack gap-2">
                        @each('notifications._notification_item', $notificationsWorkPermitExpired, 'notification')
                    </div>
                     <div class="mt-4">
                        {{ $notificationsWorkPermitExpired->withQueryString()->links() }}
                    </div>
                </div>
                <div class="col-lg-4">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">วีซ่าหมดอายุ ({{ $notificationsVisa->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'visa'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationVisaListContainer" class="vstack gap-2">
                        @each('notifications._notification_item', $notificationsVisa, 'notification')
                    </div>
                     <div class="mt-4">
                        {{ $notificationsVisa->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- CI Renew Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'ci_renew' ? 'show active' : '' }}" id="n-ci-renew" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="ci_renew">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_ci_renew" placeholder="ค้นหา..." value="{{ request('search_ci_renew') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_ci_renew">
                        <option value="">-- ทุกสัญชาติ --</option>
                         @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_ci_renew') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_ci_renew">
                        <option value="">-- ทุกประเภท มติ. --</option>
                         @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_ci_renew') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_ci_renew">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_ci_renew') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                    <a href="{{ route('notifications.export', ['export_type' => 'ci_renew'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
                </div>
            </form>
            <h5 class="mb-3">รายการต่ออายุเล่ม CI ({{ $notificationsCiRenew->total() }})</h5>
            <div id="notificationCi_renewListContainer" class="vstack gap-2">
                @each('notifications._notification_item', $notificationsCiRenew, 'notification')
            </div>
            <div class="mt-4">
                {{ $notificationsCiRenew->withQueryString()->links() }}
            </div>
        </div>

        {{-- Resolution Renew Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'resolution_renew' ? 'show active' : '' }}" id="n-resolution-renew" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="resolution_renew">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_resolution_renew" placeholder="ค้นหา..." value="{{ request('search_resolution_renew') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_resolution_renew">
                        <option value="">-- ทุกสัญชาติ --</option>
                         @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_resolution_renew') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_resolution_renew">
                        <option value="">-- ทุกประเภท มติ. --</option>
                         @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_resolution_renew') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_resolution_renew">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_resolution_renew') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="step_resolution_renew">
                        <option value="">-- ทุกขั้นตอน --</option>
                        @foreach($resolutionSteps as $key => $name)
                        <option value="{{ $key }}" @selected(request('step_resolution_renew') == $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                    <a href="{{ route('notifications.export', ['export_type' => 'resolution_renew'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
                </div>
            </form>
            <h5 class="mb-3">รายการต่ออายุมติในประเทศ ({{ $notificationsResolutionRenew->total() }})</h5>
            <div id="notificationResolution_renewListContainer" class="vstack gap-2">
                @each('notifications._notification_item', $notificationsResolutionRenew, 'notification')
            </div>
            <div class="mt-4">
                {{ $notificationsResolutionRenew->withQueryString()->links() }}
            </div>
        </div>

        {{-- Cancelled Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'cancelled' ? 'show active' : '' }}" id="n-cancelled-renew" role="tabpanel">
            <form method="GET" action="{{ route('notifications.index') }}">
                <input type="hidden" name="tab" value="cancelled">
                <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                    <input type="text" class="form-control form-control-sm w-auto" name="search_cancelled" placeholder="ค้นหา..." value="{{ request('search_cancelled') }}">
                    <select class="form-select form-select-sm w-auto" name="nationality_cancelled">
                        <option value="">-- ทุกสัญชาติ --</option>
                         @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" @selected(request('nationality_cancelled') == $nat)>{{ $nat }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="mou_cancelled">
                        <option value="">-- ทุกประเภท มติ. --</option>
                         @foreach($mouTypes as $mou)
                        <option value="{{ $mou }}" @selected(request('mou_cancelled') == $mou)>{{ $mou }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm w-auto" name="month_cancelled">
                        <option value="">-- ทุกเดือน --</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected(request('month_cancelled') == $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                    <a href="{{ route('notifications.export', ['export_type' => 'cancelled'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
                </div>
            </form>
            <h5 class="mb-3">รายการที่ยกเลิกการต่ออายุ ({{ $notificationsCancelled->total() }})</h5>
            <div id="notificationCancelled_renewListContainer" class="vstack gap-2">
                @foreach($notificationsCancelled as $notification)
                    <div class="alert alert-info notification-item">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex justify-content-between align-items-start w-100">
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1">{{ $notification->employee->name_en ?? 'N/A' }}</h5>
                                    <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->name ?? 'N/A' }}</p>
                                    <p class="mb-0 small"><strong>ประเภทที่ยกเลิก:</strong> {{ $notification->type }}</p>
                                    <p class="mb-0 small text-danger"><strong>เหตุผล:</strong> {{ $notification->cancellation_reason }}</p>
                                </div>
                                <div class="text-end flex-shrink-0 ms-2">
                                    <form action="{{ route('notifications.restore', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-arrow-counterclockwise"></i> นำกลับ</button>
                                    </form>
                                    <a href="#" class="btn btn-info btn-sm"><i class="bi bi-search"></i> ดูข้อมูล</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $notificationsCancelled->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
