@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')

@push('styles')
{{-- Styles are now handled in app.css or inline for guarantee --}}
@endpush

@section('content')
<div class="p-4 p-md-5 content-section">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        {{ __('Employee List (Total: :total)', ['total' => $totalEmployees]) }}
    </h4>
    @can('create-employees')
        <a href="{{ route('employees.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>{{ __('Add New') }}
        </a>
    @endcan
</div>

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
                <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>{{ __('MOU Extension in Country') }}</option>
                <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>{{ __('MOU Registration') }}</option>
                <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>{{ __('Others') }}</option>
            </select>
            <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Pink Card') }} --</option>
                <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>{{ __('Has Pink Card') }}</option>
                <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>{{ __('No Pink Card') }}</option>
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

<div class="bulk-action-bar mb-3 align-items-center gap-2" style="display: none;">
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
        </ul>
    </div>
</div>

<div id="employeeListContainer">
    @if($currentView === 'card')
        <div class="list-group">
            @forelse($employees as $employee)
                @include('partials._employee_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees, 'showLocateButton' => true])
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
                        <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}" data-employer-id="{{ $employee->employer_id }}"></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                <div>
                                    <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
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
                        <td class="text-muted">{{ $employee->employer->employerNameTh ?? 'N/A' }}</td>
                        <td>{{ $employee->employeePassport ?? '-' }}</td>
                        <td>{{ $employee->employeeWorkPermit ?? '-' }}</td>
                        <td>{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}</td>
                        <td class="text-nowrap">
                            <x-employee-action-buttons :employee="$employee" :show-locate-button="true" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">{{ __('No employees found') }}</td>
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
document.addEventListener('DOMContentLoaded', function () {
    // This script is superseded by the global listener in layouts/app.blade.php
    // which handles the .employee-checkbox class correctly.
    // However, we need to attach the bulk download handler here.

    const bulkExportBtn = document.getElementById('bulk-advanced-export-btn');
    if (bulkExportBtn) {
        bulkExportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

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
            const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);
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
            const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

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
            const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
            const selected = Array.from(checkboxes).map(cb => cb.value);

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            // Step 1: Check if all selected employees belong to the same employer (based on current view context)
            // Note: In table view, we added data-employer-id to the checkbox.
            // If checking fails, we alert the user.
            let employerIds = new Set();
            checkboxes.forEach(cb => {
                const empId = cb.getAttribute('data-employer-id');
                if (empId) employerIds.add(empId);
            });

            if (employerIds.size > 1) {
                 Swal.fire({
                    icon: 'warning',
                    title: '{{ __('Multiple Employers Selected') }}',
                    text: '{{ __('You selected employees from different employers. Please select employees from the same employer for one transaction.') }}'
                });
                return;
            }

            // Step 2: Store selected IDs in a global variable
            window.pendingTicketEmployeeIds = selected;

            // Step 3: Open Modal to Select Target Employer
            const modalEl = document.getElementById('selectTargetEmployerModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }
});
</script>
@endpush
@endsection
