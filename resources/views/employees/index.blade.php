@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')

@push('styles')
{{-- Styles are now handled in app.css or inline for guarantee --}}
@endpush

@section('content')
<div class="p-4 p-md-5 content-section">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        รายการข้อมูลลูกจ้างทั้งหมด (รวม: {{ $totalEmployees }} คน)
    </h4>
    @can('create-employees')
        <a href="{{ route('employees.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>เพิ่มข้อมูลใหม่
        </a>
    @endcan
</div>

<div class="card p-3 mb-3">
    <div class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-center gap-3">
        <form method="GET" action="{{ route('employees.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหา (ชื่อ, Passport, Work Permit, นายจ้าง)..." value="{{ request('search') }}" style="width: 250px;">
            <select name="nationality" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- ทุกสัญชาติ --</option>
                <option value="เมียนมา" {{ request('nationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
                <option value="ลาว" {{ request('nationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
                <option value="กัมพูชา" {{ request('nationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
                <option value="เวียดนาม" {{ request('nationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
            </select>
            <select name="mou_group" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- ทุกประเภท มติ. --</option>
                <option value="MOU" @if(request('mou_group') == 'MOU') selected @endif>MOU</option>
                <option value="มติต่ออายุในประเทศ" @if(request('mou_group') == 'มติต่ออายุในประเทศ') selected @endif>มติต่ออายุในประเทศ</option>
                <option value="มติขึ้นทะเบียน" @if(request('mou_group') == 'มติขึ้นทะเบียน') selected @endif>มติขึ้นทะเบียน</option>
                <option value="อื่นๆ" @if(request('mou_group') == 'อื่นๆ') selected @endif>อื่นๆ</option>
            </select>
            <select name="pink_card" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- บัตรชมพู --</option>
                <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>มีบัตรชมพู</option>
                <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>ไม่มีบัตรชมพู</option>
            </select>

            {{-- START: NEW DATE FILTERS --}}
            <select name="expiry_month" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- เดือนหมดอายุ --</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('expiry_month') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            <select name="expiry_type" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- ทุกประเภทวันหมดอายุ --</option>
                <option value="passportExpiryDate" {{ request('expiry_type') == 'passportExpiryDate' ? 'selected' : '' }}>Passport</option>
                <option value="workPermitExpiryDate" {{ request('expiry_type') == 'workPermitExpiryDate' ? 'selected' : '' }}>Work Permit</option>
                <option value="visaExpiryDate" {{ request('expiry_type') == 'visaExpiryDate' ? 'selected' : '' }}>Visa</option>
                <option value="ninetyDayReportDate" {{ request('expiry_type') == 'ninetyDayReportDate' ? 'selected' : '' }}>90-Day Report</option>
            </select>
            {{-- END: NEW DATE FILTERS --}}

            <button type="submit" class="btn btn-sm btn-primary">กรอง</button>
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-secondary">ล้างค่า</a>
        </form>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">การ์ด</a>
                <a href="{{ route('employees.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">ตาราง</a>
            </div>
            <div class="btn-group btn-group-sm">
                @foreach($perPageOptions as $option)
                    <a href="{{ route('employees.index', array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="bulk-action-bar mb-3" style="display: none;">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="select-all-checkbox">
        <label class="form-check-label" for="select-all-checkbox">
            เลือกทั้งหมด (<span id="selected-count">0</span>)
        </label>
    </div>
    <button class="btn btn-sm btn-outline-danger" disabled>ดำเนินการกับรายการที่เลือก</button>
</div>

<div id="employeeListContainer">
    @if($currentView === 'card')
        <div class="list-group">
            @forelse($employees as $employee)
                @include('partials._employee_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees, 'showLocateButton' => true])
            @empty
                <p class="text-center text-muted">ไม่พบข้อมูลลูกจ้าง</p>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="table-select-all-checkbox"></th>
                        <th scope="col">Employee</th>
                        <th scope="col">สัญชาติ</th>
                        <th scope="col">Employer</th>
                        <th scope="col">Passport</th>
                        <th scope="col">Work Permit</th>
                        <th scope="col">90-Day Report</th>
                        <th scope="col">Actions</th>
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
                        <td colspan="8" class="text-center text-muted">ไม่พบข้อมูลลูกจ้าง</td>
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
    const containerId = 'employeeListContainer';
    const checkboxClass = 'bulk-action-checkbox';
    const selectAllId = 'select-all-checkbox';
    const countId = 'selected-count';
    const barId = 'bulk-action-bar';
    const actionButtonSelector = '.btn';

    const container = document.getElementById(containerId);
    if (!container) return;

    const selectAllCheckbox = document.getElementById(selectAllId);
    const selectedCountSpan = document.getElementById(countId);
    const actionBar = document.getElementById(barId);
    // Ensure actionBar and its children exist before proceeding
    if (!actionBar || !selectAllCheckbox || !selectedCountSpan) {
        console.error('Bulk action UI elements not found.');
        return;
    }
    const actionButton = actionBar.querySelector(actionButtonSelector);
    if (!actionButton) {
        console.error('Bulk action button not found.');
        return;
    }

    const getCheckboxes = () => container.querySelectorAll(`.${checkboxClass}`);

    function updateSelection() {
        const checkboxes = getCheckboxes();
        const selectedCheckboxes = container.querySelectorAll(`.${checkboxClass}:checked`);
        const selectedCount = selectedCheckboxes.length;

        if (selectedCount > 0) {
            actionBar.style.setProperty('display', 'flex', 'important');
            selectedCountSpan.textContent = selectedCount;
            actionButton.disabled = false;
        } else {
            actionBar.style.setProperty('display', 'none', 'important');
            selectedCountSpan.textContent = 0;
            actionButton.disabled = true;
        }

        if (checkboxes.length > 0) {
            selectAllCheckbox.checked = selectedCount === checkboxes.length;
        } else {
            selectAllCheckbox.checked = false;
        }
    }

    // Use event delegation on the container
    container.addEventListener('change', function(event) {
        if (event.target.classList.contains(checkboxClass)) {
            updateSelection();
        }
    });

    selectAllCheckbox.addEventListener('change', () => {
        const checkboxes = getCheckboxes();
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSelection();
    });

    // Initial state check on page load
    updateSelection();
});
</script>
@endpush
@endsection
