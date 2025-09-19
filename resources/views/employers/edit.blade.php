@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลนายจ้าง')

@push('styles')
<style>
    .highlight {
        border: 2px solid #f97316 !important;
        box-shadow: 0 0 15px rgba(249, 115, 22, 0.5);
    }
</style>
@endpush

@section('content')

{{-- Employer Info Form --}}
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลนายจ้าง</h2>
    <form id="employerForm" action="{{ route('employers.update', $employer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ... (Employer detail form fields like name, tax id, etc. go here) ... --}}

        <div class="mt-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกข้อมูลนายจ้าง</button>
            <a href="{{ route('employers.index') }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>

{{-- ... (Address Sections and Modals remain the same) ... --}}

<hr class="my-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    @php
        $totalEmployees = $employees->total();
        $maleCount = $employer->employees()->whereIn('employeeTitleTh', ['นาย'])->count();
        $femaleCount = $employer->employees()->whereIn('employeeTitleTh', ['นางสาว', 'นาง'])->count();
    @endphp
    <h5>ข้อมูลลูกจ้าง (รวม: {{ $totalEmployees }} | ชาย: {{ $maleCount }} | หญิง: {{ $femaleCount }})</h5>
    <a href="{{ route('employees.create', ['employer_id' => $employer->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-person-plus"></i> เพิ่มพนักงาน</a>
</div>

{{-- Bulk Action Bar --}}
<div id="bulk-action-bar-employer" class="alert alert-info d-flex justify-content-between align-items-center my-3" style="display: none !important;">
    <div>
        <input class="form-check-input" type="checkbox" id="select-all-checkbox-employer">
        <label class="form-check-label ms-2" for="select-all-checkbox-employer">
            เลือกทั้งหมด (<span id="selected-count-employer">0</span>)
        </label>
    </div>
    <button class="btn btn-primary btn-sm" disabled>ดำเนินการกับรายการที่เลือก</button>
</div>

{{-- Employee Filter Form --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('employers.edit', $employer->id) }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            {{-- ... (Filter inputs remain the same) ... --}}
        </form>
    </div>
</div>

{{-- Employee List Container --}}
<div id="employeeList">
    <div class="list-group">
    @forelse($employees as $employee)
        {{-- Use the single, unified partial --}}
        @include('partials._employee_card', ['employee' => $employee])
    @empty
        <p class="text-center text-muted">ไม่พบข้อมูลลูกจ้างที่ตรงกับเงื่อนไข</p>
    @endforelse
    </div>
</div>

<div class="mt-3">
    {{ $employees->links() }}
</div>

{{-- ... (Other Modals like history, terminate, etc. remain here) ... --}}

@endsection

@push('scripts')
{{-- ... (Existing scripts for addresses, history, etc. remain here) ... --}}

{{-- Bulk Action JavaScript --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('employeeList');
        const actionBar = document.getElementById('bulk-action-bar-employer');
        if (!container || !actionBar) return;

        const selectAllCheckbox = document.getElementById('select-all-checkbox-employer');
        const selectedCountSpan = document.getElementById('selected-count-employer');
        const actionButton = actionBar.querySelector('button');

        function updateActionBar() {
            const itemCheckboxes = container.querySelectorAll('.bulk-action-checkbox');
            const selectedCheckboxes = container.querySelectorAll('.bulk-action-checkbox:checked');
            const count = selectedCheckboxes.length;

            if (count > 0) {
                actionBar.style.display = 'flex !important';
                selectedCountSpan.textContent = count;
                actionButton.disabled = false;
            } else {
                actionBar.style.display = 'none !important';
                selectedCountSpan.textContent = 0;
                actionButton.disabled = true;
            }
            if(selectAllCheckbox){
                 selectAllCheckbox.checked = itemCheckboxes.length > 0 && count === itemCheckboxes.length;
            }
        }

        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('bulk-action-checkbox')) {
                updateActionBar();
            }
        });

        if(selectAllCheckbox){
            selectAllCheckbox.addEventListener('change', function() {
                const itemCheckboxes = container.querySelectorAll('.bulk-action-checkbox');
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateActionBar();
            });
        }
        updateActionBar();
    });
</script>
@endpush
