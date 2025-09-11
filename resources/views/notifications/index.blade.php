{{-- Verified Complete --}}
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
$activeTab = request()->input('tab', 'ninety_day_report');
@endphp

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>
    <ul class="nav nav-tabs" id="notificationTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'ninety_day_report' ? 'active' : '' }}" id="90day-tab" href="{{ route('notifications.index', ['tab' => 'ninety_day_report']) }}" role="tab">
                รายงานตัว 90 วัน <span class="badge bg-danger rounded-pill ms-1">{{ $groupedNotifications->get('ninety_day_report', collect())->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'passport_expiry' ? 'active' : '' }}" id="passport-tab" href="{{ route('notifications.index', ['tab' => 'passport_expiry']) }}" role="tab">
                Passport <span class="badge bg-danger rounded-pill ms-1">{{ $groupedNotifications->get('passport_expiry', collect())->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'permits' ? 'active' : '' }}" id="permits-tab" href="{{ route('notifications.index', ['tab' => 'permits']) }}" role="tab">
                ใบอนุญาต/วีซ่า <span class="badge bg-danger rounded-pill ms-1">{{ $groupedNotifications->get('work_permit_expiry', collect())->total() + $groupedNotifications->get('work_permit_expired', collect())->total() + $groupedNotifications->get('visa_expiry', collect())->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'ci_renewal' ? 'active' : '' }}" id="ci-renew-tab" href="{{ route('notifications.index', ['tab' => 'ci_renewal']) }}" role="tab">
                ต่ออายุ CI <span class="badge bg-danger rounded-pill ms-1">{{ $groupedNotifications->get('ci_renewal', collect())->total() }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'resolution_renewal' ? 'active' : '' }}" id="resolution-renew-tab" href="{{ route('notifications.index', ['tab' => 'resolution_renewal']) }}" role="tab">
                ต่ออายุมติ <span class="badge bg-danger rounded-pill ms-1">{{ $groupedNotifications->get('resolution_renewal', collect())->total() }}</span>
            </a>
        </li>
         <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTab === 'cancelled' ? 'active' : '' }}" id="cancelled-renew-tab" href="{{ route('notifications.index', ['tab' => 'cancelled']) }}" role="tab">
                รายการที่ยกเลิก <span class="badge bg-secondary rounded-pill ms-1">{{ $groupedNotifications->get('cancelled', collect())->total() }}</span>
            </a>
        </li>
    </ul>
    <div class="tab-content pt-4" id="notificationTabContent">
        {{-- 90 Day Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'ninety_day_report' ? 'show active' : '' }}" id="n-90day" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-ninety_day_report">
                <input type="hidden" name="tab" value="ninety_day_report">
                <input type="text" class="form-control form-control-sm w-auto" name="search_ninety_day_report" placeholder="ค้นหา..." value="{{ request('search_ninety_day_report') }}">
                <select class="form-select form-select-sm w-auto" name="nationality_ninety_day_report">
                    <option value="">-- ทุกสัญชาติ --</option>
                    @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality_ninety_day_report') == $nat)>{{ $nat }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="mou_ninety_day_report">
                    <option value="">-- ทุกประเภท มติ. --</option>
                     @foreach($mouTypes as $mou)
                    <option value="{{ $mou }}" @selected(request('mou_ninety_day_report') == $mou)>{{ $mou }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="month_ninety_day_report">
                    <option value="">-- ทุกเดือน --</option>
                    @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected(request('month_ninety_day_report') == $num)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('notifications.export', ['export_type' => 'ninety_day_report'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
            </div>
            <h5 class="mb-3">รายการรายงานตัว 90 วัน ({{ $groupedNotifications->get('ninety_day_report', collect())->total() }})</h5>
            <div id="notification90DayListContainer" class="vstack gap-2">
                @foreach($groupedNotifications->get('ninety_day_report', collect()) as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @endforeach
            </div>
            <div class="mt-4">
                {{ $groupedNotifications->get('ninety_day_report', collect())->links() }}
            </div>
        </div>

        {{-- Passport Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'passport_expiry' ? 'show active' : '' }}" id="n-passport" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-passport_expiry">
                <input type="hidden" name="tab" value="passport_expiry">
                <input type="text" class="form-control form-control-sm w-auto" name="search_passport_expiry" placeholder="ค้นหา..." value="{{ request('search_passport_expiry') }}">
                <select class="form-select form-select-sm w-auto" name="nationality_passport_expiry">
                    <option value="">-- ทุกสัญชาติ --</option>
                     @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality_passport_expiry') == $nat)>{{ $nat }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="mou_passport_expiry">
                    <option value="">-- ทุกประเภท มติ. --</option>
                    @foreach($mouTypes as $mou)
                    <option value="{{ $mou }}" @selected(request('mou_passport_expiry') == $mou)>{{ $mou }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="month_passport_expiry">
                    <option value="">-- ทุกเดือน --</option>
                    @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected(request('month_passport_expiry') == $num)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('notifications.export', ['export_type' => 'passport_expiry'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
            </div>
            <h5 class="mb-3">รายการ Passport หมดอายุ ({{ $groupedNotifications->get('passport_expiry', collect())->total() }})</h5>
            <div id="notificationPassportListContainer" class="vstack gap-2">
                @foreach($groupedNotifications->get('passport_expiry', collect()) as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @endforeach
            </div>
             <div class="mt-4">
                {{ $groupedNotifications->get('passport_expiry', collect())->links() }}
            </div>
        </div>

        {{-- Permits Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'permits' ? 'show active' : '' }}" id="n-permits" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-permits">
                <input type="hidden" name="tab" value="permits">
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
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">ใบอนุญาตทำงานใกล้หมดอายุ ({{ $groupedNotifications->get('work_permit_expiry', collect())->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'work_permit_expiry'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationWorkPermitListContainer" class="vstack gap-2">
                         @foreach($groupedNotifications->get('work_permit_expiry', collect()) as $notification)
                            @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $groupedNotifications->get('work_permit_expiry', collect())->links() }}
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">ขาดต่อขอรับใหม่ ({{ $groupedNotifications->get('work_permit_expired', collect())->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'work_permit_expired'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationWorkPermitExpiredListContainer" class="vstack gap-2">
                        @foreach($groupedNotifications->get('work_permit_expired', collect()) as $notification)
                            @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                        @endforeach
                    </div>
                     <div class="mt-4">
                        {{ $groupedNotifications->get('work_permit_expired', collect())->links() }}
                    </div>
                </div>
                <div class="col-lg-4">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">วีซ่าหมดอายุ ({{ $groupedNotifications->get('visa_expiry', collect())->total() }})</h5>
                        <a href="{{ route('notifications.export', ['export_type' => 'visa_expiry'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                    </div>
                    <div id="notificationVisaListContainer" class="vstack gap-2">
                        @foreach($groupedNotifications->get('visa_expiry', collect()) as $notification)
                            @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                        @endforeach
                    </div>
                     <div class="mt-4">
                        {{ $groupedNotifications->get('visa_expiry', collect())->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- CI Renew Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'ci_renewal' ? 'show active' : '' }}" id="n-ci-renew" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-ci_renewal">
                <input type="hidden" name="tab" value="ci_renewal">
                <input type="text" class="form-control form-control-sm w-auto" name="search_ci_renewal" placeholder="ค้นหา..." value="{{ request('search_ci_renewal') }}">
                <select class="form-select form-select-sm w-auto" name="nationality_ci_renewal">
                    <option value="">-- ทุกสัญชาติ --</option>
                     @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality_ci_renewal') == $nat)>{{ $nat }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="mou_ci_renewal">
                    <option value="">-- ทุกประเภท มติ. --</option>
                     @foreach($mouTypes as $mou)
                    <option value="{{ $mou }}" @selected(request('mou_ci_renewal') == $mou)>{{ $mou }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="month_ci_renewal">
                    <option value="">-- ทุกเดือน --</option>
                    @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected(request('month_ci_renewal') == $num)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('notifications.export', ['export_type' => 'ci_renewal'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
            </div>
            <h5 class="mb-3">รายการต่ออายุเล่ม CI ({{ $groupedNotifications->get('ci_renewal', collect())->total() }})</h5>
            <div id="notificationCi_renewListContainer" class="vstack gap-2">
                @foreach($groupedNotifications->get('ci_renewal', collect()) as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @endforeach
            </div>
            <div class="mt-4">
                {{ $groupedNotifications->get('ci_renewal', collect())->links() }}
            </div>
        </div>

        {{-- Resolution Renew Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'resolution_renewal' ? 'show active' : '' }}" id="n-resolution-renew" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-resolution_renewal">
                <input type="hidden" name="tab" value="resolution_renewal">
                <input type="text" class="form-control form-control-sm w-auto" name="search_resolution_renewal" placeholder="ค้นหา..." value="{{ request('search_resolution_renewal') }}">
                <select class="form-select form-select-sm w-auto" name="nationality_resolution_renewal">
                    <option value="">-- ทุกสัญชาติ --</option>
                     @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality_resolution_renewal') == $nat)>{{ $nat }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="mou_resolution_renewal">
                    <option value="">-- ทุกประเภท มติ. --</option>
                     @foreach($mouTypes as $mou)
                    <option value="{{ $mou }}" @selected(request('mou_resolution_renewal') == $mou)>{{ $mou }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="month_resolution_renewal">
                    <option value="">-- ทุกเดือน --</option>
                    @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected(request('month_resolution_renewal') == $num)>{{ $name }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm w-auto" name="step_resolution_renewal">
                    <option value="">-- ทุกขั้นตอน --</option>
                    @foreach($resolutionSteps as $key => $name)
                    <option value="{{ $key }}" @selected(request('step_resolution_renewal') == $key)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('notifications.export', ['export_type' => 'resolution_renewal'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
            </div>
            <h5 class="mb-3">รายการต่ออายุมติในประเทศ ({{ $groupedNotifications->get('resolution_renewal', collect())->total() }})</h5>
            <div id="notificationResolution_renewalListContainer" class="vstack gap-2">
                @foreach($groupedNotifications->get('resolution_renewal', collect()) as $notification)
                    @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                @endforeach
            </div>
            <div class="mt-4">
                {{ $groupedNotifications->get('resolution_renewal', collect())->links() }}
            </div>
        </div>

        {{-- Cancelled Tab Pane --}}
        <div class="tab-pane fade {{ $activeTab === 'cancelled' ? 'show active' : '' }}" id="n-cancelled-renew" role="tabpanel">
            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap" id="filter-controls-cancelled">
                <input type="hidden" name="tab" value="cancelled">
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
                <a href="{{ route('notifications.export', ['export_type' => 'cancelled'] + request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Export</a>
            </div>
            <h5 class="mb-3">รายการที่ยกเลิกการต่ออายุ ({{ $groupedNotifications->get('cancelled', collect())->total() }})</h5>
            <div id="notificationCancelled_renewListContainer" class="vstack gap-2">
                @foreach($groupedNotifications->get('cancelled', collect()) as $notification)
                    <div class="alert alert-info notification-item">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex justify-content-between align-items-start w-100">
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1">{{ $notification->employee->employeeNameEn ?? 'N/A' }}</h5>
                                    <p class="mb-1"><strong>นายจ้าง:</strong> {{ $notification->employee->employer->employerNameTh ?? 'N/A' }}</p>
                                    <p class="mb-0 small"><strong>ประเภทที่ยกเลิก:</strong> {{ $notification->type }}</p>
                                    <p class="mb-0 small text-danger"><strong>เหตุผล:</strong> {{ $notification->cancellation_reason }}</p>
                                </div>
                                <div class="text-end flex-shrink-0 ms-2">
                                    <a href="{{ route('employers.edit', $notification->employee->employer_id) }}#employee-card-{{ $notification->employee_id }}" class="btn btn-info btn-sm"><i class="bi bi-search"></i> View Info</a>
                                    <form action="{{ route('notifications.restore', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                    </form>
                                    <form action="{{ route('notifications.forceDelete', $notification) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this notification?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Permanent Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $groupedNotifications->get('cancelled', collect())->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="renew-form" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="renewModalLabel">ต่ออายุการแจ้งเตือน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_due_date" class="form-label">เลือกวันหมดอายุใหม่:</label>
                        <input type="date" class="form-control" id="new_due_date" name="new_due_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="cancel-form" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">ยืนยันการยกเลิกการต่ออายุ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการแจ้งเตือนนี้? การกระทำนี้จะย้ายรายการไปที่แท็บ "รายการที่ยกเลิก" และคุณสามารถกู้คืนได้ในภายหลัง</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const renewModal = document.getElementById('renewNotificationModal');
    if (renewModal) {
        renewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const notificationId = button.getAttribute('data-notification-id');
            const form = document.getElementById('renew-form');
            form.action = `/notifications/${notificationId}/renew`;
        });
    }

    const cancelModal = document.getElementById('cancelNotificationModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const notificationId = button.getAttribute('data-notification-id');
            const form = document.getElementById('cancel-form');
            form.action = `/notifications/${notificationId}/cancel`;
        });
    }
});
</script>
@endpush
