@extends('layouts.app')
@section('title', 'ข้อมูลลูกจ้าง')

@push('styles')
<style>
    .employee-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease-in-out;
    }
    .employee-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.07);
    }
    .employee-photo-thumb {
        width: 48px; height: 48px; object-fit: cover; border-radius: 50%;
        margin-right: 1rem; background-color: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="p-4 p-md-5 content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h2 class="mb-3 mb-md-0">รายการข้อมูลลูกจ้างทั้งหมด</h2>
        <h2 class="h5 text-muted fw-normal">
            (รวม: {{ $totalEmployees }} | ชาย: {{ $maleCount }} | หญิง: {{ $femaleCount }})
        </h2>
        <a href="{{ route('employers.index') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('employees.index') }}" method="GET" id="filter-form" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="ค้นหา..." value="{{ request('search') }}">

                <div class="btn-group btn-group-sm ms-md-auto">
                    <input type="radio" class="btn-check" name="view" id="view-card" value="card" onchange="this.form.submit()" @checked($currentView === 'card')>
                    <label class="btn btn-outline-primary" for="view-card"><i class="bi bi-grid-3x3-gap-fill"></i> การ์ด</label>

                    <input type="radio" class="btn-check" name="view" id="view-table" value="table" onchange="this.form.submit()" @checked($currentView === 'table')>
                    <label class="btn btn-outline-primary" for="view-table"><i class="bi bi-table"></i> ตาราง</label>
                </div>

                <select name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($currentPerPage == $option)>แสดง {{ $option }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary btn-sm">ค้นหา</button>
            </form>
        </div>
    </div>

    {{-- NEW: Bulk Action Bar --}}
    <div id="bulk-action-bar" class="alert alert-info d-flex justify-content-between align-items-center mb-4" style="display: none !important;">
        <div>
            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
            <label class="form-check-label ms-2" for="select-all-checkbox">
                เลือกทั้งหมด (<span id="selected-count">0</span>)
            </label>
        </div>
        <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
    </div>

    @if($currentView === 'card')
        {{-- Card View --}}
        <div class="card-view">
            @forelse($employees as $employee)
                @include('partials._employee_card', ['employee' => $employee])
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
                        <th></th>
                        <th>#</th>
                        <th>รูป</th>
                        <th>ชื่อ (อังกฤษ)</th>
                        <th>ชื่อ (ไทย)</th>
                        <th>สัญชาติ</th>
                        <th>Passport</th>
                        <th>นายจ้าง</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td><input class="form-check-input bulk-action-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_table_checkbox_{{ $employee->id }}"></td>
                        <td>{{ $loop->iteration + $employees->firstItem() - 1 }}</td>
                        <td>
                            {{-- ADDED .employee-photo-thumb class --}}
                            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                                 class="employee-photo-thumb"
                                 style="width: 48px; height: 48px; border-radius: 50%;"
                                 alt="Photo">
                        </td>
                        <td>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn }}</td>
                        <td>{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh }}</td>
                        <td>
                            {{-- ADDED FLAG LOGIC --}}
                            @php
                                $flagCodes = ['เมียนมา' => 'mm', 'ลาว' => 'la', 'กัมพูชา' => 'kh', 'เวียดนาม' => 'vn'];
                                $nationality = $employee->employeeNationality ?? null;
                                $flagCode = $nationality ? ($flagCodes[$nationality] ?? null) : null;
                            @endphp
                            {{ $nationality }}
                            @if($flagCode)
                                <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $nationality }}" class="ms-1" style="width: 20px; vertical-align: middle;">
                            @endif
                        </td>
                        <td>{{ $employee->employeePassport }}</td>
                        <td>{{ $employee->employer->employerNameTh ?? 'N/A' }}</td>
                        <td class="text-center">
                            <a href="{{ route('employees.locate', $employee->id) }}" class="btn btn-sm btn-outline-info" title="ดูข้อมูลนายจ้าง"><i class="bi bi-geo-alt-fill"></i></a>
                            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-outline-primary" title="แก้ไข"><i class="bi bi-pencil-fill"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-employee-btn" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-url="{{ route('employees.destroy', $employee->id) }}" title="ลบ"><i class="bi bi-trash-fill"></i></button>
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

    <div class="mt-4">
        {{ $employees->links() }}
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.querySelector('.content-section');
        const actionBar = document.getElementById('bulk-action-bar');
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const selectedCountSpan = document.getElementById('selected-count');
        const actionButton = actionBar.querySelector('button');

        function getItemCheckboxes() {
            // Select checkboxes only from the currently visible view (card or table)
            const activeView = container.querySelector('.card-view, .table-responsive');
            return activeView ? activeView.querySelectorAll('.bulk-action-checkbox') : [];
        }

        function updateActionBar() {
            const itemCheckboxes = getItemCheckboxes();
            const selectedCheckboxes = document.querySelectorAll('.bulk-action-checkbox:checked');
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

            const totalCheckboxes = itemCheckboxes.length;
            selectAllCheckbox.checked = totalCheckboxes > 0 && count === totalCheckboxes;
        }

        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('bulk-action-checkbox')) {
                updateActionBar();
            }
        });

        selectAllCheckbox.addEventListener('change', function() {
            const itemCheckboxes = getItemCheckboxes();
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateActionBar();
        });

        // Also listen for form changes that switch views
        const filterForm = document.getElementById('filter-form');
        if(filterForm) {
            const viewRadios = filterForm.querySelectorAll('input[name="view"]');
            viewRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Reset on view change
                    if(selectAllCheckbox) selectAllCheckbox.checked = false;
                    updateActionBar();
                });
            });
        }

        // Initial state setup
        updateActionBar();
    });
</script>
@endpush
@endsection
