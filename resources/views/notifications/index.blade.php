@extends('layouts.app')
@section('title', 'รายการแจ้งเตือน')

@section('content')
<div class="p-4 p-md-5 content-section">
    <h2 class="mb-4">รายการแจ้งเตือน</h2>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="active_tab" id="active_tab_input" value="{{ request('active_tab', 'ninety_day_report') }}">
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

                {{-- VIEW CONTROLS --}}
                <div class="btn-group btn-group-sm ms-md-auto">
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
            </form>
        </div>
    </div>

    {{-- NEW: Bulk Action Bar --}}
    <div id="bulk-action-bar-notifications" class="alert alert-info d-flex justify-content-between align-items-center mb-4" style="display: none !important;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications">
            <label class="form-check-label ms-2" for="select-all-checkbox-notifications">
                เลือกทั้งหมด (<span id="selected-count-notifications">0</span>)
            </label>
        </div>
        <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
    </div>

    <ul class="nav nav-tabs" id="notificationTab" role="tablist">
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

    <div class="tab-content pt-4" id="notificationTabContent">
        @foreach($notificationsData as $type => $notifications)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $type }}-pane" role="tabpanel">

                {{-- NEW: Gender Count Display --}}
                @if(isset($counts[$type]['total']))
                <div class="mb-3 text-muted">
                    <strong>ยอดรวม:</strong> {{ $counts[$type]['total'] }} คน
                    (ชาย: {{ $counts[$type]['male'] }} คน, หญิง: {{ $counts[$type]['female'] }} คน)
                </div>
                @endif
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('notifications.export', array_merge(request()->query(), ['export_type' => $type])) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download"></i> Export ข้อมูล ({{ $tabs[$type] ?? 'รายการที่ยกเลิก' }})
    </a>
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
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ชื่อลูกจ้าง</th>
                                        <th>นายจ้าง</th>
                                        <th>วันที่ครบกำหนด</th>
                                        <th>สถานะ</th>
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
                                        <tr><td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td></tr>
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
                            <p class="text-center text-muted">ไม่พบข้อมูล</p>
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
        const actionButton = actionBar.querySelector('button');

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
