@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ __('รายการแจ้งเตือน') }}</h2>
        @can('manage-users')
            <a href="{{ route('admin.notification_settings.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-gear-fill"></i> ตั้งค่าการแจ้งเตือน
            </a>
        @endcan
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-column gap-3">
                <input type="hidden" name="active_tab" id="active_tab_input" value="{{ request('active_tab', 'ninety_day_report') }}">

                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหาชื่อ..." value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="month" class="form-select form-select-sm">
                            <option value="">-- ทุกเดือน --</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" @selected(request('month') == $m)>เดือนที่ {{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="nationality" class="form-select form-select-sm">
                            <option value="">-- ทุกสัญชาติ --</option>
                            <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>เมียนมา</option>
                            <option value="ลาว" @selected(request('nationality') == 'ลาว')>ลาว</option>
                            <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>กัมพูชา</option>
                            <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>เวียดนาม</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="mou_type" class="form-select form-select-sm">
                            <option value="">-- ทุกประเภท มติ. --</option>
                            <option value="MOU" @selected(request('mou_type') == 'MOU')>MOU</option>
                            <option value="มติต่ออายุในประเทศ" @selected(request('mou_type') == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
                            <option value="มติขึ้นทะเบียน" @selected(request('mou_type') == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
                            <option value="อื่นๆ" @selected(request('mou_type') == 'อื่นๆ')>อื่นๆ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> กรอง</button>
                    </div>
                    <div class="col-6 col-md-auto">
                        <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm w-100">ล้างค่า</a>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top pt-3">
                    {{-- VIEW CONTROLS --}}
                    <div class="btn-group btn-group-sm">
                        <input type="radio" class="btn-check" name="view" id="view-card" value="card" onchange="this.form.submit()" @checked(request('view', 'card') === 'card')>
                        <label class="btn btn-outline-primary" for="view-card"><i class="bi bi-grid-3x3-gap-fill"></i></label>

                        <input type="radio" class="btn-check" name="view" id="view-table" value="table" onchange="this.form.submit()" @checked(request('view') === 'table')>
                        <label class="btn btn-outline-primary" for="view-table"><i class="bi bi-table"></i></label>
                    </div>
                    <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected(request('per_page', $perPageOptions[0]) == $option)>แสดง {{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- NEW: Bulk Action Bar --}}
    <div id="bulk-action-bar-notifications" class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 py-2 px-3 bg-light border rounded gap-2" style="display: none !important;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications">
            <label class="form-check-label ms-2" for="select-all-checkbox-notifications">
                เลือกทั้งหมด (<span id="selected-count-notifications">0</span>)
            </label>
        </div>

        <div class="dropdown w-100 w-md-auto" style="max-width: 300px;">
            <button class="btn btn-primary btn-sm dropdown-toggle w-100" type="button" id="notificationBulkActionBtn" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                ดำเนินการกับรายการที่เลือก
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="notificationBulkActionBtn">
                <li><a class="dropdown-item" href="#" id="notification-bulk-download-btn"><i class="bi bi-download me-2"></i>Download Files</a></li>
            </ul>
        </div>
    </div>

    <div class="mb-3">
        <ul class="nav nav-tabs flex-wrap" id="notificationTab" role="tablist">
            @foreach($tabs as $type => $title)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $type }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $type }}-pane" type="button">
                        {{ $title }} <span class="badge bg-danger rounded-pill ms-1">{{ $counts[$type]['total'] ?? 0 }}</span>
                    </button>
                </li>
            @endforeach
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-pane" type="button">
                    รายการที่ยกเลิก <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['cancelled'] ?? 0 }}</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content pt-4" id="notificationTabContent">
        @foreach($notificationsData as $type => $notifications)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $type }}-pane" role="tabpanel">

                {{-- NEW: Gender Count Display & Export Button --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                    @if(isset($counts[$type]['total']))
                    <div class="text-muted">
                        <strong>ยอดรวม:</strong> {{ $counts[$type]['total'] }} คน
                        (ชาย: {{ $counts[$type]['male'] }} คน, หญิง: {{ $counts[$type]['female'] }} คน)
                    </div>
                    @else
                    <div></div>
                    @endif

                    <div>
                        <a href="{{ route('notifications.export', array_merge(request()->query(), ['export_type' => $type])) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> Export ข้อมูล ({{ $tabs[$type] ?? 'รายการที่ยกเลิก' }})
                        </a>
                    </div>
                </div>

                @if($type === 'work_permit_mou')
                    @if($currentView == 'table')
                        @include('notifications._work_permit_mou_table', ['notifications' => $notifications])
                    @else
                        @include('notifications._work_permit_mou_pane', ['notifications' => $notifications])
                    @endif
                @else
                    {{-- Standard rendering for all other tabs --}}
                    @if($currentView == 'table')
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-std"></th>
                                        <th style="width: 1%;">#</th>
                                        <th>ชื่อลูกจ้าง</th>
                                        <th>สัญชาติ</th>
                                        <th>นายจ้าง</th>
                                        <th>วันที่ครบกำหนด</th>
                                        <th>สถานะ / วันคงเหลือ</th>
                                        <th class="text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($notifications as $notification)
                                        @php
                                            $itemNumber = $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1));
                                        @endphp
                                        @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $itemNumber])
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else {{-- Card View --}}
                        @forelse($notifications as $notification)
                            @php
                                $itemNumber = $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1));
                            @endphp
                            @include('notifications._notification_item', ['notification' => $notification, 'itemNumber' => $itemNumber])
                        @empty
                            <p class="text-center text-muted py-4">ไม่พบข้อมูล</p>
                        @endforelse
                    @endif

                    <div class="mt-4">
                        @if($notifications->hasPages())
                            {{ $notifications->links() }}
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('#notificationTab .nav-link');
        const activeTabInput = document.getElementById('active_tab_input');

        tabs.forEach(tab => {
            tab.addEventListener('show.bs.tab', function (event) {
                // Get the pane ID from the tab's data-bs-target and remove '-pane'
                const paneId = event.target.getAttribute('data-bs-target').replace('#', '').replace('-pane', '');
                if (activeTabInput) {
                    activeTabInput.value = paneId;
                }
            });
        });

        // On page load, if an active_tab is in the URL, click it.
        const currentActiveTab = '{{ request('active_tab', 'ninety_day_report') }}';
        const tabToActivate = document.getElementById(currentActiveTab + '-tab');
        if (tabToActivate) {
            new bootstrap.Tab(tabToActivate).show();
        }

        // --- Bulk Action Script ---
        const container = document.querySelector('.tab-content'); // Target tab content for notifications
        const actionBar = document.getElementById('bulk-action-bar-notifications');
        const selectAllCheckbox = document.getElementById('select-all-checkbox-notifications');
        const selectedCountSpan = document.getElementById('selected-count-notifications');
        const actionButton = document.getElementById('notificationBulkActionBtn');
        const downloadBtn = document.getElementById('notification-bulk-download-btn');

        function updateActionBar() {
            // Only select checkboxes in the currently active tab pane
            const activePane = container.querySelector('.tab-pane.active');
            if (!activePane) return;

            const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
            const selectedCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox:checked');
            const count = selectedCheckboxes.length;

            if (count > 0) {
                actionBar.style.display = 'flex'; // Removed !important
                selectedCountSpan.textContent = count;
                actionButton.disabled = false;
            } else {
                actionBar.style.display = 'none'; // Removed !important
                selectedCountSpan.textContent = 0;
                actionButton.disabled = true;
            }
            selectAllCheckbox.checked = itemCheckboxes.length > 0 && count === itemCheckboxes.length;
        }

        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('bulk-action-checkbox')) {
                updateActionBar();
            }
        });

        selectAllCheckbox.addEventListener('change', function() {
            const activePane = container.querySelector('.tab-pane.active');
            if (!activePane) return;
            const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateActionBar();
        });

        // Download Handler
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const activePane = container.querySelector('.tab-pane.active');
            if (!activePane) return;

            const selected = Array.from(activePane.querySelectorAll('.bulk-action-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) return;

            if (window.openBulkDownloadModal) {
                window.openBulkDownloadModal(selected);
            } else {
                alert('Download function not ready.');
            }
        });

        // Listen for tab changes to reset the selection
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function() {
                if(selectAllCheckbox) selectAllCheckbox.checked = false;
                updateActionBar();
            });
        });

        // Initial check in case of page reload with an active tab
        // Use a small timeout to ensure tab activation script has run
        setTimeout(updateActionBar, 100);
    });
</script>
@endpush
