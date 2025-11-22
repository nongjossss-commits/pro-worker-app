@extends('layouts.app')
@section('title', __('Notification List'))

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ __('Notification List') }}</h2>
        @can('manage-users')
            <a href="{{ route('admin.notification_settings.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-gear-fill"></i> {{ __('Notification Settings') }}
            </a>
        @endcan
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex flex-column gap-3">
                <input type="hidden" name="active_tab" id="active_tab_input" value="{{ request('active_tab', 'ninety_day_report') }}">

                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search name or email...') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="month" class="form-select form-select-sm">
                            <option value="">-- {{ __('Filter') }} {{ __('Month') }} --</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" @selected(request('month') == $m)>{{ __('Month') }} {{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="nationality" class="form-select form-select-sm">
                            <option value="">-- {{ __('All Nationalities') }} --</option>
                            <option value="เมียนมา" @selected(request('nationality') == 'เมียนมา')>{{ __('Myanmar') }}</option>
                            <option value="ลาว" @selected(request('nationality') == 'ลาว')>{{ __('Laos') }}</option>
                            <option value="กัมพูชา" @selected(request('nationality') == 'กัมพูชา')>{{ __('Cambodia') }}</option>
                            <option value="เวียดนาม" @selected(request('nationality') == 'เวียดนาม')>{{ __('Vietnam') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="mou_type" class="form-select form-select-sm">
                            <option value="">-- {{ __('All MOU Types') }} --</option>
                            <option value="MOU" @selected(request('mou_type') == 'MOU')>{{ __('MOU') }}</option>
                            <option value="มติต่ออายุในประเทศ" @selected(request('mou_type') == 'มติต่ออายุในประเทศ')>{{ __('MOU Extension in Country') }}</option>
                            <option value="มติขึ้นทะเบียน" @selected(request('mou_type') == 'มติขึ้นทะเบียน')>{{ __('MOU Registration') }}</option>
                            <option value="อื่นๆ" @selected(request('mou_type') == 'อื่นๆ')>{{ __('Others') }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> {{ __('Filter') }}</button>
                    </div>
                    <div class="col-6 col-md-auto">
                        <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm w-100">{{ __('Clear') }}</a>
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
                        <option value="{{ $option }}" @selected(request('per_page', $perPageOptions[0]) == $option)>{{ __('Show') }} {{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- NEW: Bulk Action Bar --}}
    <div id="bulk-action-bar-notifications" class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 py-2 px-3 bg-light border rounded gap-2" style="display: none;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications">
            <label class="form-check-label ms-2" for="select-all-checkbox-notifications">
                {{ __('Select All') }} (<span id="selected-count-notifications">0</span>)
            </label>
        </div>

        <div class="dropdown w-100 w-md-auto" style="max-width: 300px; position: relative; z-index: 1000;">
            <button class="btn btn-primary btn-sm dropdown-toggle w-100" type="button" id="notificationBulkActionBtn" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                {{ __('Action on selected items') }}
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="notificationBulkActionBtn">
                <li><a class="dropdown-item" href="#" id="notification-bulk-download-btn"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
                <li><a class="dropdown-item" href="#" id="notification-bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="mb-3">
        <ul class="nav nav-tabs flex-wrap" id="notificationTab" role="tablist">
            @foreach($tabs as $type => $title)
                <li class="nav-item" role="presentation">
                    {{-- Note: $title here typically comes from Controller/Config. I should translate it if possible or check if it's a key --}}
                    {{-- Assuming $title might be a key or raw string. Let's try to translate it if it matches a key, or output as is --}}
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $type }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $type }}-pane" type="button">
                         {{ __($title) }} <span class="badge bg-danger rounded-pill ms-1">{{ $counts[$type]['total'] ?? 0 }}</span>
                    </button>
                </li>
            @endforeach
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-pane" type="button">
                    {{ __('Cancelled Items') }} <span class="badge bg-secondary rounded-pill ms-1">{{ $counts['cancelled'] ?? 0 }}</span>
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
                        <strong>{{ __('Total') }}:</strong> {{ $counts[$type]['total'] }} {{ __('persons') }}
                        ({{ __('Male') }}: {{ $counts[$type]['male'] }} {{ __('persons') }}, {{ __('Female') }}: {{ $counts[$type]['female'] }} {{ __('persons') }})
                    </div>
                    @else
                    <div></div>
                    @endif

                    <div>
                        <a href="{{ route('notifications.export', array_merge(request()->query(), ['export_type' => $type])) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> {{ __('Export Data') }} ({{ __($tabs[$type] ?? 'Cancelled Items') }})
                        </a>
                    </div>
                </div>

                @if($type === 'work_permit_mou')
                    @if($currentView == 'table')
                        @include('notifications._work_permit_mou_table', ['notifications' => $notifications])
                    @else
                        @include('notifications._work_permit_mou_pane', ['notifications' => $notifications])
                    @endif
                @elseif($type === 'employer_document_expiry')
                     {{-- Custom View for Employer Document Expiry --}}
                     @if($currentView == 'table')
                         <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-employer"></th>
                                        <th style="width: 1%;">#</th>
                                        <th>{{ __('Employer Name') }}</th>
                                        <th>{{ __('Document') }}</th>
                                        <th>{{ __('Expiry Date') }}</th>
                                        <th>{{ __('Status / Days Remaining') }}</th>
                                        <th class="text-center">{{ __('Manage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($notifications as $notification)
                                        @php
                                            $itemNumber = $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1));
                                        @endphp
                                        <tr>
                                            <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $notification->id }}"></td>
                                            <td>{{ $itemNumber }}</td>
                                            <td>{{ optional($notification->employer)->employerNameTh ?? '-' }}</td>
                                            <td>{{ __('Company Certificate / ID Card') }}</td>
                                            <td>{{ $notification->due_date ? \Carbon\Carbon::parse($notification->due_date)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                 @if($notification->days_remaining <= 0)
                                                    <span class="badge bg-danger">{{ __('Expired') }}</span>
                                                 @else
                                                    <span class="badge bg-warning text-dark">{{ __('Expires in') }} {{ $notification->days_remaining }} {{ __('days') }}</span>
                                                 @endif
                                            </td>
                                            <td class="text-center">
                                                {{-- Reuse the standard notification action buttons or simplified ones --}}
                                                 <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#renewModal-{{ $notification->id }}">
                                                                <i class="bi bi-arrow-repeat text-success me-2"></i> {{ __('Renew') }}
                                                            </button>
                                                        </li>
                                                         <li>
                                                            <form action="{{ route('notifications.cancel', $notification->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-warning" onclick="return confirm('{{ __('Are you sure you want to cancel this notification?') }}')">
                                                                    <i class="bi bi-x-circle me-2"></i> {{ __('Cancel') }}
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                @include('notifications._renew_modal', ['notification' => $notification])
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No data found') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                         </div>
                     @else
                        {{-- Reusing Card View logic but might need tweaking if it expects Employee data --}}
                        @forelse($notifications as $notification)
                            @php
                                $itemNumber = $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1));
                            @endphp
                            @include('notifications._notification_item', ['notification' => $notification, 'itemNumber' => $itemNumber])
                        @empty
                            <p class="text-center text-muted py-4">{{ __('No data found') }}</p>
                        @endforelse
                     @endif
                     <div class="mt-4">
                        @if($notifications->hasPages())
                            {{ $notifications->links() }}
                        @endif
                    </div>

                @else
                    {{-- Standard rendering for all other tabs --}}
                    @if($currentView == 'table')
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox-notifications-std"></th>
                                        <th style="width: 1%;">#</th>
                                        <th>{{ __('Employee Name') }}</th>
                                        <th>{{ __('Nationality') }}</th>
                                        <th>{{ __('Employer') }}</th>
                                        <th>{{ __('Expiry Date') }}</th>
                                        <th>{{ __('Status / Days Remaining') }}</th>
                                        <th class="text-center">{{ __('Manage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($notifications as $notification)
                                        @php
                                            $itemNumber = $loop->iteration + ($notifications->perPage() * ($notifications->currentPage() - 1));
                                        @endphp
                                        @include('notifications._notification_table_row', ['notification' => $notification, 'itemNumber' => $itemNumber])
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No data found') }}</td></tr>
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
                            <p class="text-center text-muted py-4">{{ __('No data found') }}</p>
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
@include('employees.modals.advanced_export')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const notificationExportBtn = document.getElementById('notification-bulk-advanced-export-btn');
        const container = document.querySelector('.tab-content'); // Defined once here at top level scope if needed, but safer in specific functions

        if (notificationExportBtn) {
            notificationExportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const activePane = document.querySelector('.tab-content .tab-pane.active');
                if (!activePane) return;

                const selected = Array.from(activePane.querySelectorAll('.bulk-action-checkbox:checked')).map(cb => cb.value);

                if (selected.length === 0) {
                    showToast('{{ __('Please select employees first.') }}', 'danger');
                    return;
                }

                document.getElementById('export_employee_ids').value = JSON.stringify(selected);
                const modalEl = document.getElementById('advancedExportModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        }

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
        const actionBar = document.getElementById('bulk-action-bar-notifications');
        const selectAllCheckbox = document.getElementById('select-all-checkbox-notifications');
        const selectAllCheckboxStd = document.getElementById('select-all-checkbox-notifications-std');
        const selectAllCheckboxEmployer = document.getElementById('select-all-checkbox-notifications-employer');
        const selectAllCheckboxMOU1 = document.getElementById('select-all-checkbox-notifications-mou1');
        const selectAllCheckboxMOU2 = document.getElementById('select-all-checkbox-notifications-mou2');
        const selectedCountSpan = document.getElementById('selected-count-notifications');
        const actionButton = document.getElementById('notificationBulkActionBtn');
        const downloadBtn = document.getElementById('notification-bulk-download-btn');

        function updateActionBar() {
            const activePane = document.querySelector('.tab-content .tab-pane.active');
            if (!activePane) return;

            const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
            const selectedCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox:checked');
            const count = selectedCheckboxes.length;

            if (count > 0) {
                actionBar.style.display = 'flex';
                selectedCountSpan.textContent = count;
                actionButton.disabled = false;
            } else {
                actionBar.style.display = 'none';
                selectedCountSpan.textContent = 0;
                actionButton.disabled = true;
            }

            const allChecked = itemCheckboxes.length > 0 && count === itemCheckboxes.length;
            if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
            if (selectAllCheckboxStd) selectAllCheckboxStd.checked = allChecked;
            if (selectAllCheckboxEmployer) selectAllCheckboxEmployer.checked = allChecked;
        }

        // Use the previously defined 'container' if available or query it
        const tabContentContainer = document.querySelector('.tab-content');
        if (tabContentContainer) {
            tabContentContainer.addEventListener('change', function(e) {
                if (e.target.classList.contains('bulk-action-checkbox')) {
                    updateActionBar();
                }
            });
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const activePane = document.querySelector('.tab-content .tab-pane.active');
                if (!activePane) return;
                const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateActionBar();
            });
        }

        // Function to handle section-specific select all (for MOU table)
        function handleSectionSelectAll(triggerCheckbox) {
             const thead = triggerCheckbox.closest('thead');
             if(!thead) return;
             const tbody = thead.nextElementSibling;
             if(tbody && tbody.tagName === 'TBODY') {
                 const checkboxes = tbody.querySelectorAll('.bulk-action-checkbox');
                 checkboxes.forEach(cb => cb.checked = triggerCheckbox.checked);
                 updateActionBar();
             }
        }

        if (selectAllCheckboxMOU1) {
            selectAllCheckboxMOU1.addEventListener('change', function() {
                handleSectionSelectAll(this);
            });
        }

        if (selectAllCheckboxMOU2) {
            selectAllCheckboxMOU2.addEventListener('change', function() {
                handleSectionSelectAll(this);
            });
        }

        if (selectAllCheckboxStd) {
            selectAllCheckboxStd.addEventListener('change', function() {
                const activePane = document.querySelector('.tab-content .tab-pane.active');
                if (!activePane) return;
                const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateActionBar();
            });
        }

        if (selectAllCheckboxEmployer) {
            selectAllCheckboxEmployer.addEventListener('change', function() {
                const activePane = document.querySelector('.tab-content .tab-pane.active');
                if (!activePane) return;
                const itemCheckboxes = activePane.querySelectorAll('.bulk-action-checkbox');
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateActionBar();
            });
        }

        // Download Handler
        if (downloadBtn) {
             downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const activePane = document.querySelector('.tab-content .tab-pane.active');
                if (!activePane) return;

                const selected = Array.from(activePane.querySelectorAll('.bulk-action-checkbox:checked')).map(cb => cb.value);
                if (selected.length === 0) return;

                if (window.openBulkDownloadModal) {
                    window.openBulkDownloadModal(selected);
                } else {
                    alert('{{ __('Download function not ready.') }}');
                }
            });
        }

        // Listen for tab changes to reset the selection
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function() {
                if(selectAllCheckbox) selectAllCheckbox.checked = false;
                if(selectAllCheckboxStd) selectAllCheckboxStd.checked = false;
                if(selectAllCheckboxEmployer) selectAllCheckboxEmployer.checked = false;
                if(selectAllCheckboxMOU1) selectAllCheckboxMOU1.checked = false;
                if(selectAllCheckboxMOU2) selectAllCheckboxMOU2.checked = false;
                updateActionBar();
            });
        });

        // Initial check in case of page reload with an active tab
        // Use a small timeout to ensure tab activation script has run
        setTimeout(updateActionBar, 100);
    });
</script>
@endpush
