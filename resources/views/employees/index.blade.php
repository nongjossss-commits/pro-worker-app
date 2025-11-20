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
                <option value="เมียนมา" {{ request('nationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
                <option value="ลาว" {{ request('nationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
                <option value="กัมพูชา" {{ request('nationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
                <option value="เวียดนาม" {{ request('nationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
            </select>
            <select name="mou_group" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('All MOU Types') }} --</option>
                <option value="MOU" @if(request('mou_group') == 'MOU') selected @endif>MOU</option>
                <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>มติต่ออายุในประเทศ</option>
                <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>มติขึ้นทะเบียน</option>
                <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>อื่นๆ</option>
            </select>
            <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Pink Card') }} --</option>
                <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>{{ __('Has Pink Card') }}</option>
                <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>{{ __('No Pink Card') }}</option>
            </select>
            <select name="passport_type_myanmar" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Passport Type (Myanmar)') }} --</option>
                <option value="CI" {{ request('passport_type_myanmar') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                <option value="PJ" {{ request('passport_type_myanmar') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
            </select>
            <select name="passport_type_cambodia" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- {{ __('Passport Type (Cambodia)') }} --</option>
                <option value="เล่ม TD" {{ request('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>เล่ม TD</option>
                <option value="เล่มอินเตอร์" {{ request('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>เล่มอินเตอร์</option>
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
        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
            {{ __('Action on selected items') }}
        </button>
        <ul class="dropdown-menu" aria-labelledby="bulkActionDropdown">
            <li>
                <a class="dropdown-item" href="#" id="btn-bulk-download">
                    <i class="bi bi-download me-2"></i>ดาวน์โหลดไฟล์ (Download)
                </a>
            </li>
             <li>
                <a class="dropdown-item" href="#" id="btn-bulk-transfer" data-bs-toggle="modal" data-bs-target="#transferEmployeeModal">
                    <i class="bi bi-arrow-right-circle me-2"></i>ย้ายนายจ้าง (Transfer)
                </a>
            </li>
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
                        <td><input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}"></td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Bulk Download Handler
    const btnBulkDownload = document.getElementById('btn-bulk-download');
    if (btnBulkDownload) {
        btnBulkDownload.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
            const ids = Array.from(selectedCheckboxes).map(cb => cb.getAttribute('data-employee-id'));

            if (ids.length > 0) {
                window.dispatchEvent(new CustomEvent('open-download-modal', {
                    detail: { employeeIds: ids }
                }));
            }
        });
    }

    // 2. Single Download Handler (delegated)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-download-single');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-employee-id');
            window.dispatchEvent(new CustomEvent('open-download-modal', {
                detail: { employeeIds: [id] }
            }));
        }
    });

    // 3. Updated Bulk Selection Logic (integrates with layout global listener)
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const bulkActionDropdownBtn = document.getElementById('bulkActionDropdown');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const countSpan = document.getElementById('selected-count');

    // Override/Extend the global listener slightly to handle the dropdown button state
    function updateBulkState() {
         const selected = document.querySelectorAll('.employee-checkbox:checked');
         if (bulkActionDropdownBtn) {
             bulkActionDropdownBtn.disabled = selected.length === 0;
         }
         if (bulkActionBar) {
             bulkActionBar.style.display = selected.length > 0 ? 'flex' : 'none';
         }
         if (countSpan) {
             countSpan.textContent = selected.length;
         }
    }

    // Listen to changes (the global listener in layout handles the checking logic, we just ensure UI state)
    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox') || e.target.id === 'select-all-checkbox' || e.target.id === 'table-select-all-checkbox') {
            setTimeout(updateBulkState, 10); // Small delay to let global listener finish
        }
    });

    updateBulkState();
});
</script>
@endpush
@endsection
