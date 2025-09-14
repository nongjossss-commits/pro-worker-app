@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@section('content')
<div class="p-4 p-md-5 content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">รายการแจ้งเตือน</h2>
        <div class="btn-group" role="group">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'card']) }}" class="btn btn-sm {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-grid"></i> Card View
            </a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="btn btn-sm {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-table"></i> Table View
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหาชื่อลูกจ้าง..." value="{{ request('search') }}" style="width: 200px;">
                <select name="nationality" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">-- ทุกสัญชาติ --</option>
                    <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>เมียนมา</option>
                    <option value="ลาว" @selected(request('nationality') == 'ลาว')>ลาว</option>
                    <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>กัมพูชา</option>
                    <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>เวียดนาม</option>
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
            <button class="nav-link" id="work-permit-tab" data-bs-toggle="tab" data-bs-target="#work_permit_expiry-pane" type="button">
                ใบอนุญาตทำงาน <span class="badge bg-danger rounded-pill ms-1">{{ $counts['work_permit_expiry'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="visa-tab" data-bs-toggle="tab" data-bs-target="#visa_expiry-pane" type="button">
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
        @foreach($notificationsData as $type => $notifications)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $type }}-pane" role="tabpanel">

                @if($currentView == 'table')
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">ชื่อลูกจ้าง</th>
                                    <th scope="col">สัญชาติ</th>
                                    <th scope="col">นายจ้าง</th>
                                    <th scope="col">ประเภท</th>
                                    <th scope="col">วันที่ครบกำหนด</th>
                                    <th scope="col">สถานะ</th>
                                    <th scope="col">ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notification)
                                    @include('notifications._notification_table_row', ['notification' => $notification, 'loop' => $loop])
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">ไม่พบการแจ้งเตือนในหมวดนี้</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    @forelse($notifications as $notification)
                        @include('notifications._notification_item', ['notification' => $notification, 'loop' => $loop])
                    @empty
                        <p class="text-center text-muted">ไม่พบการแจ้งเตือนในหมวดนี้</p>
                    @endforelse
                @endif

                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
