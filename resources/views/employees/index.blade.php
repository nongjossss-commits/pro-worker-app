@extends('layouts.app')
@section('title', __('Employees'))

@push('styles')
{{-- Styles are now handled in app.css or inline for guarantee --}}
@endpush

@section('content')
<x-help-button manual="employees" title="{{ __('Employees') }}" />
<div class="p-4 p-md-5 content-section">
@php
    $employeeQuotaMax = \App\Services\EmployeeQuotaService::getMax();
    $employeeQuotaRemaining = $employeeQuotaMax ? max(0, $employeeQuotaMax - $totalEmployees) : null;
    $employeeQuotaReached = $employeeQuotaMax && $totalEmployees >= $employeeQuotaMax;
    $employeeQuotaNearLimit = $employeeQuotaMax
        && !$employeeQuotaReached
        && $employeeQuotaRemaining <= max(5, intdiv($employeeQuotaMax, 20));
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        @if($employeeQuotaMax)
            {{ __('Employee List') }}
            <span class="badge {{ $employeeQuotaReached ? 'bg-danger' : ($employeeQuotaNearLimit ? 'bg-warning text-dark' : 'bg-secondary') }} ms-1"
                  title="{{ __('System cap set by Super Admin') }}">
                {{ number_format($totalEmployees) }} / {{ number_format($employeeQuotaMax) }}
            </span>
            @if($employeeQuotaReached)
                <small class="text-danger ms-1"><i class="bi bi-lock-fill"></i> {{ __('Cap reached') }}</small>
            @elseif($employeeQuotaNearLimit)
                <small class="text-warning ms-1"><i class="bi bi-exclamation-triangle-fill"></i> {{ __(':n slots left', ['n' => $employeeQuotaRemaining]) }}</small>
            @endif
        @else
            {{ __('Employee List (Total: :total)', ['total' => $totalEmployees]) }}
        @endif
    </h4>
    @can('create-employees')
        <div class="btn-group">
            <a href="{{ route('employees.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-2"></i>{{ __('Add New') }}
            </a>
            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('employees.create') }}"><i class="bi bi-person-plus me-2"></i>{{ __('Create Manually') }}</a></li>
                <li><a class="dropdown-item" href="{{ route('employees.import_view') }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Import from Excel/CSV') }}</a></li>
            </ul>
        </div>
    @endcan
</div>

<x-address-filter :provinces="$addressOptions['provinces']" :districts="$addressOptions['districts']" :subDistricts="$addressOptions['subDistricts']" />

<div class="card p-3 mb-3">
    <div class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-center gap-3">
        <form method="GET" action="{{ route('employees.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search') }}..." value="{{ request('search') }}" style="width: 200px;">
            <select name="nationality" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('All Nationalities') }} --</option>
                <option value="เมียนมา" {{ request('nationality') == 'เมียนมา' ? 'selected' : '' }}>{{ __('Myanmar') }}</option>
                <option value="ลาว" {{ request('nationality') == 'ลาว' ? 'selected' : '' }}>{{ __('Laos') }}</option>
                <option value="กัมพูชา" {{ request('nationality') == 'กัมพูชา' ? 'selected' : '' }}>{{ __('Cambodia') }}</option>
                <option value="เวียดนาม" {{ request('nationality') == 'เวียดนาม' ? 'selected' : '' }}>{{ __('Vietnam') }}</option>
            </select>
            <select name="mou_group" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('All MOU Types') }} --</option>
                <option value="MOU" @if(request('mou_group') == 'MOU') selected @endif>{{ __('MOU') }}</option>
                <option value="MOU 2 ปีหลัง" @if(request('mou_group') == 'MOU 2 ปีหลัง') selected @endif>{{ __('MOU 2 Years Later') }}</option>
                <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>{{ __('MOU Extension in Country') }}</option>
                <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>{{ __('MOU Registration') }}</option>
                <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>{{ __('Others') }}</option>
            </select>
            <select name="insurance_type" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Insurance Type') }} --</option>
                <option value="none" {{ request('insurance_type') == 'none' ? 'selected' : '' }}>{{ __('No Insurance') }}</option>
                <option value="ประกันสังคม" {{ request('insurance_type') == 'ประกันสังคม' ? 'selected' : '' }}>{{ __('Social Security') }}</option>
                <option value="ประกันโรงพยาบาล" {{ request('insurance_type') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>{{ __('Hospital Insurance') }}</option>
                <option value="ประกันเอกชน" {{ request('insurance_type') == 'ประกันเอกชน' ? 'selected' : '' }}>{{ __('Private Insurance') }}</option>
            </select>
            <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Pink Card') }} --</option>
                <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>{{ __('Has Pink Card') }}</option>
                <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>{{ __('No Pink Card') }}</option>
            </select>
            <select name="passport_status" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Passport Status') }} --</option>
                <option value="has_passport" {{ request('passport_status') == 'has_passport' ? 'selected' : '' }}>{{ __('Has Passport') }}</option>
                <option value="no_passport" {{ request('passport_status') == 'no_passport' ? 'selected' : '' }}>{{ __('No Passport') }}</option>
            </select>
            <select name="visa_status" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Visa Status') }} --</option>
                <option value="has_visa" {{ request('visa_status') == 'has_visa' ? 'selected' : '' }}>{{ __('Has Visa') }}</option>
                <option value="no_visa" {{ request('visa_status') == 'no_visa' ? 'selected' : '' }}>{{ __('No Visa') }}</option>
            </select>
            <select name="passport_type_myanmar" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Passport Type (Myanmar)') }} --</option>
                <option value="CI" {{ request('passport_type_myanmar') == 'CI' ? 'selected' : '' }}>{{ __('CI Book') }}</option>
                <option value="PJ" {{ request('passport_type_myanmar') == 'PJ' ? 'selected' : '' }}>{{ __('PJ Book') }}</option>
            </select>
            <select name="passport_type_cambodia" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Passport Type (Cambodia)') }} --</option>
                <option value="เล่ม TD" {{ request('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>{{ __('TD Book') }}</option>
                <option value="เล่มอินเตอร์" {{ request('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>{{ __('Inter Book') }}</option>
            </select>
            <select name="bank_account_status" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Bank Account Status') }} --</option>
                <option value="opened" {{ request('bank_account_status') == 'opened' ? 'selected' : '' }}>{{ __('Account Opened') }}</option>
                <option value="not_opened" {{ request('bank_account_status') == 'not_opened' ? 'selected' : '' }}>{{ __('Account Not Opened') }}</option>
            </select>
            <input type="date" name="work_permit_expiry_date" class="form-control form-control-sm" value="{{ request('work_permit_expiry_date') }}" title="{{ __('Search by work permit expiry date') }}">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
        </form>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> {{ __('Export') }}
            </a>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Card') }}</a>
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('Table') }}</a>
            </div>
            <div class="btn-group btn-group-sm">
                @foreach($perPageOptions as $option)
                    <a href="{{ route('employees.index', array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="bulk-action-bar align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
     style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;"
     id="bulkActionBar"
     draggable="true"
     ondragstart="window.startDragBulk && window.startDragBulk(event)">
    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="select-all-checkbox">
        <label class="form-check-label" for="select-all-checkbox">
            {{ __('Select All') }} (<span id="selected-count">0</span>)
        </label>
    </div>

    <div class="dropdown">
        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
            {{ __('Actions') }}
        </button>
        <ul class="dropdown-menu" aria-labelledby="bulkActionDropdown">
            <li><a class="dropdown-item" href="#" id="bulk-advanced-edit-btn"><i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="bulk-download-btn"><i class="bi bi-download me-2"></i>{{ __('Download Files') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-transfer-btn"><i class="bi bi-arrow-left-right me-2"></i>{{ __('Transfer') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-send-data-btn"><i class="bi bi-send me-2"></i>{{ __('Send Data') }}</a></li>
            <li><a class="dropdown-item" href="#" id="bulk-send-production-btn"><i class="bi bi-clipboard-data me-2"></i>{{ __('Send to P Production') }}</a></li>
            @can('manage-tickets')
            <li><a class="dropdown-item" href="#" id="bulk-generate-pdf-btn"><i class="bi bi-file-earmark-pdf me-2"></i>{{ __('Automated PDF') }}</a></li>
            @endcan
            <li><hr class="dropdown-divider"></li>
            @can('view-finance')
            <li><a class="dropdown-item text-primary" href="#" id="bulk-finance-btn"><i class="bi bi-cash-stack me-2"></i>{{ __('Finance') }}</a></li>
            @endcan
        </ul>
    </div>
    <button class="btn btn-sm btn-outline-danger" onclick="window.clearGlobalSelection();">{{ __('Clear Selection') }}</button>
    <button class="btn btn-sm btn-info text-white" id="btn-view-selected" onclick="window.openViewSelectedModal()">
        <i class="bi bi-eye me-1"></i> {{ __('View Selected') }}
    </button>
    <div class="ms-auto text-muted small d-none d-md-block">
        <i class="bi bi-arrows-move me-1"></i> {{ __('Drag to Chat') }}
    </div>
</div>

<div id="employeeListContainer">
    @if($currentView === 'card')
        <div class="list-group">
            @forelse($employees as $employee)
                <div>
                @include('partials._employee_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees, 'showLocateButton' => true])
                </div>
            @empty
                <p class="text-center text-muted">{{ __('No employees found') }}</p>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox"></th>
                        <th style="width: 1%;"></th> {{-- Drag Handle Column --}}
                        <th scope="col">{{ __('Employee') }}</th>
                        <th scope="col">{{ __('All Nationalities') }}</th>
                        <th scope="col">{{ __('Employers') }}</th>
                        <th scope="col">{{ __('Passport') }}</th>
                        <th scope="col">{{ __('Work Permit') }}</th>
                        <th scope="col">{{ __('90-Day Report') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}"
                                   data-employee-id="{{ $employee->id }}"
                                   data-employer-id="{{ $employee->employer_id }}"
                                   data-name-th="{{ $employee->employeeNameTh }}"
                                   data-name-en="{{ $employee->employeeNameEn }}"
                                   data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}"
                                   data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}"
                                   data-title-th="{{ $employee->employeeTitleTh }}"
                                   data-title-en="{{ $employee->employeeTitleEn }}"
                                   data-nationality="{{ $employee->employeeNationality }}"
                                   data-gender="{{ $employee->gender }}"
                                   data-country-code="{{ \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality) }}"
                                   data-insurance-type="{{ $employee->insurance_type }}"
                                   data-passport="{{ $employee->employeePassport }}"
                            ></td>
                        <td>
                            <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab"
                               draggable="true"
                               ondragstart="window.startDragGlobal(event, 'employee', {
                                    id: {{ $employee->id }},
                                    name: '{{ addslashes($employee->employeeNameTh) }} ({{ addslashes($employee->employeeNameEn) }})',
                                    subtitle: '{{ $employee->employeeNationality }}',
                                    url: '{{ route('employees.show', $employee->id) }}'
                                })"
                               title="{{ __('Drag') }}">
                            </i>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                <div>
                                    <div class="fw-bold">
                                        {{ $employee->employeeNameEn ?? 'N/A' }}
                                        <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Data') }}">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted">{{ $employee->employeeNameTh ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                            @endphp
                            @if($countryCode)
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" class="me-2" style="width: 20px;">
                                    <span>{{ $employee->employeeNationality }}</span>
                                </div>
                            @else
                                {{ $employee->employeeNationality ?? '-' }}
                            @endif
                        </td>
                        <td class="text-muted">
                            {{ $employee->employer->employerNameTh ?? 'N/A' }}
                            @if(request('addrProvince') && $employee->employer)
                                @foreach($employee->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                                    <div class="text-primary small fw-bold">{{ $label }}</div>
                                @endforeach
                            @endif
                            @if($employee->employer)
                                <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employer" data-model-id="{{ $employee->employer->id }}" title="{{ __('Preview Data') }}">
                                    <i class="bi bi-search"></i>
                                </button>
                            @endif
                        </td>
                        <td>{{ $employee->employeePassport ?? '-' }}</td>
                        <td>{{ $employee->employeeWorkPermit ?? '-' }}</td>
                        <td>{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</td>
                        <td class="text-nowrap">
                            <x-employee-action-buttons :employee="$employee" :show-locate-button="true" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">{{ __('No employees found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</div>

@include('partials._employee_action_modals')
@include('employees.modals.advanced_export')
@include('employees.modals.select_target_employer_modal')

@push('scripts')
<script>
    // Surface the EmployeeQuotaExceededException via Swal when a create
    // or import action got bounced. The exception flashes a payload to
    // the session; we just render it here once after the redirect.
    @if(session('quota_exceeded'))
        @php $qe = session('quota_exceeded'); @endphp
        document.addEventListener('DOMContentLoaded', function() {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: @json(__('Employee cap reached')),
                    html: @json($qe['message']) + '<br><br><b>' + @json(__('Current')) + ':</b> ' + @json($qe['current']) + ' / ' + @json($qe['max']),
                    confirmButtonText: 'OK',
                });
            } else {
                alert(@json($qe['message']));
            }
        });
    @endif

    // Special handler for bulk drag
    window.startDragBulk = function(e) {
        const ids = window.getGlobalSelectedIds();
        const count = ids.length;

        if (count === 0) {
            e.preventDefault();
            return;
        }

        const payload = {
            type: 'employees_bulk',
            title: count + ' Employees',
            count: count,
            ids: ids,
            url: window.location.href // Or a specific bulk action URL
        };
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('application/json', JSON.stringify(payload));
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // This script is superseded by the global listener in layouts/app.blade.php
    // which handles the .employee-checkbox class correctly.
    // However, we need to attach the bulk download handler here.

    const bulkExportBtn = document.getElementById('bulk-advanced-export-btn');
    if (bulkExportBtn) {
        bulkExportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Populate the hidden input with JSON array of IDs
            document.getElementById('export_employee_ids').value = JSON.stringify(selected);

            // Open the modal
            const modalEl = document.getElementById('advancedExportModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

    const bulkDownloadBtn = document.getElementById('bulk-download-btn');
    if (bulkDownloadBtn) {
        bulkDownloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();
            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            if (window.openBulkDownloadModal) {
                window.openBulkDownloadModal(selected);
            } else {
                console.error('Download modal function not found.');
            }
        });
    }

    // Handle Advanced Edit
    const bulkEditBtn = document.getElementById('bulk-advanced-edit-btn');
    if (bulkEditBtn) {
        bulkEditBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Create a form dynamically and submit POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('employees.bulk_edit.select_fields') }}';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_to';
            redirectInput.value = window.location.href;
            form.appendChild(redirectInput);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employee_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }

    // Handle Bulk Send Data (To Ticket)
    const bulkSendDataBtn = document.getElementById('bulk-send-data-btn');
    if (bulkSendDataBtn) {
        bulkSendDataBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedData = window.getGlobalSelectedData();
            const selectedIds = selectedData.map(item => item.id);

            if (selectedIds.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Step 1: Check if all selected employees belong to the same employer
            let employerIds = new Set();
            selectedData.forEach(item => {
                if (item.employer_id) employerIds.add(item.employer_id);
            });

            // Note: If we have items from previous pages that might NOT have captured employer_id (legacy data?), this check might be imperfect.
            // But with the new logic, we capture employer_id on check.
            if (employerIds.size > 1) {
                 Swal.fire({
                    icon: 'warning',
                    title: '{{ __('Multiple Employers Selected') }}',
                    text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                });
                return;
            }

            // Step 2: Store selected IDs in a global variable
            window.pendingTicketEmployeeIds = selectedIds;

            // Step 3: Open Modal to Select Target Employer
            const modalEl = document.getElementById('selectTargetEmployerModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

    // Handle Bulk Send to Production
    const bulkFinanceBtn = document.getElementById('bulk-finance-btn');
    if (bulkFinanceBtn) {
        bulkFinanceBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedData = window.getGlobalSelectedData();
            const selectedIds = selectedData.map(item => item.id);

            if (selectedIds.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            if (typeof window.FinancialSecurity !== 'undefined') {
                window.FinancialSecurity.checkAndRun(function() {
                    const form = document.createElement('form');
                    form.method = 'GET';
                    form.action = '{{ route("finance.create") }}';

                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'employee_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                });
            } else {
                console.error('FinancialSecurity module not loaded');
            }
        });
    }

    const bulkSendProductionBtn = document.getElementById('bulk-send-production-btn');
    if (bulkSendProductionBtn) {
        bulkSendProductionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedData = window.getGlobalSelectedData();
            const selectedIds = selectedData.map(item => item.id);

            if (selectedIds.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Check employers
            let employerIds = new Set();
            selectedData.forEach(item => {
                if (item.employer_id) employerIds.add(item.employer_id);
            });

            // REMOVED BLOCKING: Now allowing multiple employers to create Independent Project
            // if (employerIds.size > 1) { ... }

            // Redirect to Production Create with IDs
            // Use JSON string for array of IDs
            const idsJson = encodeURIComponent(JSON.stringify(selectedIds));
            const employerId = employerIds.size === 1 ? employerIds.values().next().value : '';

            let url = '{{ route("production.create") }}?employee_ids_json=' + idsJson;
            if(employerId) {
                url += '&employer_id=' + employerId;
            }

            window.location.href = url;
        });
    }

    // Handle Bulk Generate PDF
    const bulkGeneratePdfBtn = document.getElementById('bulk-generate-pdf-btn');
    if (bulkGeneratePdfBtn) {
        bulkGeneratePdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = window.getGlobalSelectedIds();

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Create form to post to generation modal setup
            const form = document.createElement('form');
            form.method = 'POST';
            // Use relative path to avoid protocol mismatch (http vs https) redirects which strip POST data
            form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);

            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_url';
            redirectInput.value = window.location.href;
            form.appendChild(redirectInput);

            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employees[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }
});
</script>
@endpush
@endsection
