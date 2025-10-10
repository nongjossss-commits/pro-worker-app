@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')

@push('styles')
{{-- Styles are now handled in app.css or inline for guarantee --}}
@endpush

@section('content')
<div class="p-4 p-md-5 content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">รายการข้อมูลลูกจ้างทั้งหมด</h2>
        <h2 class="h5 text-muted fw-normal">
            (รวม: {{ $totalEmployees }} คน)
        </h2>
        @can('create-employees')
        <a href="{{ route('employers.index') }}" class="btn btn-primary" title="ไปที่หน้านายจ้างเพื่อเพิ่มลูกจ้างใหม่"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
        @endcan
    </div>

    <div class="card mb-4">
        <div class="card-body">
<form method="GET" action="{{ route('employees.index') }}">
    <div class="filter-controls">
        {{-- Nationality Filter --}}
        <select name="nationality">
            <option value="">-- ทุกสัญชาติ --</option>
            <option value="เมียนมา" {{ request('nationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
            <option value="ลาว" {{ request('nationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
            <option value="กัมพูชา" {{ request('nationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
            <option value="เวียดนาม" {{ request('nationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
        </select>

        {{-- MOU Group Filter --}}
        <select name="mou_group">
            <option value="">-- ทุกประเภท มติ. --</option>
            <option value="MOU" {{ request('mou_group') == 'MOU' ? 'selected' : '' }}>MOU</option>
            <option value="มติต่ออายุในประเทศ" {{ request('mou_group') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
            <option value="มติขึ้นทะเบียน" {{ request('mou_group') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
            <option value="อื่นๆ" {{ request('mou_group') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ</option>
        </select>

        {{-- Pink Card Filter --}}
        <select name="pink_card">
            <option value="">-- บัตรชมพู --</option>
            <option value="yes" {{ request('pink_card') == 'yes' ? 'selected' : '' }}>มีบัตรชมพู</option>
            <option value="no" {{ request('pink_card') == 'no' ? 'selected' : '' }}>ไม่มีบัตรชมพู</option>
        </select>

        {{-- Search Input --}}
        <input type="text" name="search" placeholder="ค้นหา..." value="{{ request('search') }}">

        <button type="submit">กรอง</button>

        <a href="{{ route('employees.index') }}">ล้างการกรอง</a>
    </div>
</form>
        </div>
    </div>

    <div id="bulk-action-bar" class="alert alert-info d-flex justify-content-between align-items-center mb-4" style="display: none !important;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
            <label class="form-check-label ms-2" for="select-all-checkbox">
                เลือกทั้งหมด (<span id="selected-count">0</span>)
            </label>
        </div>
        <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
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
                        <th scope="col" style="width: 1rem;"></th>
                        <th scope="col">Employee</th>
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
                        <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}"></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/40x40/e2e8f0/6c757d?text=PIC' }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                <div>
                                    <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
                                    <div class="text-muted">{{ $employee->employeeNameTh ?? 'N/A' }}</div>
                                </div>
                            </div>
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
                        <td colspan="7" class="text-center text-muted">ไม่พบข้อมูลลูกจ้าง</td>
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
