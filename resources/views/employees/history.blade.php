@extends('layouts.app')
@section('title', 'ประวัติการจ้างงาน')

@section('content')
<div class="p-4 p-md-5 content-section">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        ประวัติการจ้างงานทั้งหมด (รวม: {{ $totalEmployees }} คน)
    </h4>
</div>

<div class="card p-3 mb-3">
    <div class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-center gap-3">
        <form method="GET" action="{{ route('employees.history') }}" class="d-flex flex-wrap align-items-center gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหา..." value="{{ request('search') }}" style="width: 200px;">
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
            <select name="passport_type" class="form-select form-select-sm" style="width: auto;">
                <option value="">-- ประเภทพาสปอร์ต --</option>
                <option value="CI" {{ request('passport_type') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                <option value="PJ" {{ request('passport_type') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
                <option value="TD" {{ request('passport_type') == 'TD' ? 'selected' : '' }}>เล่ม TD</option>
                <option value="International" {{ request('passport_type') == 'International' ? 'selected' : '' }}>เล่มอินเตอร์</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">กรอง</button>
            <a href="{{ route('employees.history') }}" class="btn btn-sm btn-secondary">ล้างค่า</a>
        </form>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('employees.export', array_merge(request()->query(), ['history' => 1])) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('employees.history', array_merge(request()->query(), ['view' => 'card'])) }}" class="btn {{ $currentView == 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">การ์ด</a>
                <a href="{{ route('employees.history', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $currentView == 'table' ? 'btn-primary' : 'btn-outline-secondary' }}">ตาราง</a>
            </div>
            <div class="btn-group btn-group-sm">
                @foreach($perPageOptions as $option)
                    <a href="{{ route('employees.history', array_merge(request()->query(), ['per_page' => $option])) }}" class="btn {{ $currentPerPage == $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="bulk-action-bar mb-3" id="history-bulk-action-bar" style="display: none;">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="history-select-all-checkbox-main">
        <label class="form-check-label" for="history-select-all-checkbox-main">
            เลือกทั้งหมด (<span id="history-selected-count">0</span>)
        </label>
    </div>
    <button id="history-bulk-action-btn" class="btn btn-sm btn-info" disabled><i class="bi bi-person-up"></i> ย้ายนายจ้าง</button>
</div>

<div id="employeeListContainer">
    @if($currentView === 'card')
        <div class="list-group">
            @forelse($employees as $employee)
                @include('employees._history_card', ['employee' => $employee, 'loop' => $loop, 'pagination' => $employees])
            @empty
                <p class="text-center text-muted">ไม่พบประวัติการจ้างงาน</p>
            @endforelse
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="history-select-all-checkbox-table"></th>
                        <th scope="col">Employee</th>
                        <th scope="col">Employer</th>
                        <th scope="col">Terminated Date</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    @forelse($employees as $employee)
                    <tr id="history-row-{{ $employee->id }}">
                        <td><input class="form-check-input history-employee-checkbox" type="checkbox" data-employee-id="{{ $employee->id }}"></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $employee->photo_url }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 0.75rem;">
                                <div>
                                    <div class="fw-bold">{{ $employee->employeeNameEn ?? 'N/A' }}</div>
                                    <div class="text-muted">{{ $employee->employeeFullName }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $employee->employer->employerNameTh ?? 'N/A' }}</td>
                        <td>
                            {{ $employee->terminated_at ? $employee->terminated_at->format('d/m/Y') : '-' }}
                            <span class="badge bg-secondary">{{ $employee->days_since_termination }} วัน</span>
                        </td>
                        <td>{{ $employee->termination_reason ?: '-' }}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-success btn-reinstate" title="Restore" data-employee-id="{{ $employee->id }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                            <button class="btn btn-sm btn-danger btn-move-to-trash" title="Move to Trash" data-employee-id="{{ $employee->id }}"><i class="bi bi-trash3-fill"></i></button>
                            <button class="btn btn-sm btn-info btn-transfer-employee" title="Transfer Employer" data-employee-id="{{ $employee->id }}" data-employee-name="{{ $employee->employeeFullName }}"><i class="bi bi-person-up"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">ไม่พบประวัติการจ้างงาน</td>
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
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let transferModalInstance = null;
    let isBulkTransfer = false;

    const tableBody = document.getElementById('historyTableBody');
    const bulkActionBar = document.getElementById('history-bulk-action-bar');
    const selectedCountSpan = document.getElementById('history-selected-count');
    const mainSelectAllCheckbox = document.getElementById('history-select-all-checkbox-main');
    const tableSelectAllCheckbox = document.getElementById('history-select-all-checkbox-table');
    const bulkActionButton = document.getElementById('history-bulk-action-btn');
    const transferModalEl = document.getElementById('transferEmployeeModal');
    const employeeToTransferIdInput = document.getElementById('employee-to-transfer-id');
    const employeeToTransferNameSpan = document.getElementById('employee-to-transfer-name');
    const employerSearchInput = document.getElementById('employer-search-input');
    const employerSearchResultsDiv = document.getElementById('employer-search-results');
    const selectedEmployerDisplay = document.getElementById('selected-employer-display');
    const selectedEmployerNameSpan = document.getElementById('selected-employer-name');
    const confirmTransferBtn = document.getElementById('confirm-transfer-btn');

    let selectedEmployer = null;

    function updateBulkActionBar() {
        const container = document.getElementById('employeeListContainer');
        const selectedCheckboxes = container.querySelectorAll('.history-employee-checkbox:checked');
        const count = selectedCheckboxes.length;
        bulkActionBar.style.display = count > 0 ? 'flex' : 'none';
        selectedCountSpan.textContent = count;
        bulkActionButton.disabled = count === 0;
        const allCheckboxes = container.querySelectorAll('.history-employee-checkbox');
        if (allCheckboxes.length > 0) {
            const isAllSelected = count === allCheckboxes.length;
            if(mainSelectAllCheckbox) mainSelectAllCheckbox.checked = isAllSelected;
            if(tableSelectAllCheckbox) tableSelectAllCheckbox.checked = isAllSelected;
        }
    }

    function openTransferModal() {
        employerSearchInput.value = '';
        employerSearchResultsDiv.innerHTML = '';
        employerSearchResultsDiv.style.display = 'block';
        selectedEmployer = null;
        selectedEmployerDisplay.style.display = 'none';
        confirmTransferBtn.disabled = true;

        if (!transferModalInstance) {
            transferModalInstance = new bootstrap.Modal(transferModalEl);
        }
        transferModalInstance.show();
    }

    const performAction = (url, body = {}) => {
        const method = body._method || 'POST';
        if(body._method) delete body._method;

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: Object.keys(body).length ? JSON.stringify(body) : null
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Action completed successfully.', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'An unknown error occurred.', 'danger');
            }
        })
        .catch(error => {
            console.error(`Error performing action to ${url}:`, error);
            showToast('An error occurred while communicating with the server.', 'danger');
        });
    };

    document.getElementById('employeeListContainer').addEventListener('click', (event) => {
        const target = event.target.closest('button');
        if (!target) return;
        const employeeId = target.dataset.employeeId;

        if (target.classList.contains('btn-reinstate')) {
            Swal.fire({ title: 'ยืนยันการคืนสถานะ', text: "ลูกจ้างจะถูกย้ายกลับไปอยู่ในรายชื่อลูกจ้างปัจจุบัน", icon: 'question', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก' })
                .then(result => {
                    if (result.isConfirmed) {
                        performAction(`/employees/${employeeId}/reinstate`);
                    }
                });
        } else if (target.classList.contains('btn-move-to-trash')) {
            Swal.fire({ title: 'ยืนยันการย้ายไปถังขยะ', text: "ลูกจ้างจะถูกย้ายไปที่ถังขยะส่วนกลาง", icon: 'warning', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#d33' })
                .then(result => {
                    if (result.isConfirmed) {
                         performAction(`/employees/${employeeId}`, { _method: 'DELETE' });
                    }
                });
        } else if (target.classList.contains('btn-transfer-employee')) {
            isBulkTransfer = false;
            employeeToTransferIdInput.value = employeeId;
            employeeToTransferNameSpan.textContent = `คุณกำลังจะย้ายลูกจ้าง: ${target.dataset.employeeName}`;
            openTransferModal();
        }
    });

    document.getElementById('employeeListContainer').addEventListener('change', (event) => {
        if (event.target.classList.contains('history-employee-checkbox')) {
            updateBulkActionBar();
        }
    });

    if(mainSelectAllCheckbox) {
        mainSelectAllCheckbox.addEventListener('change', (event) => {
            const isChecked = event.target.checked;
            document.querySelectorAll('.history-employee-checkbox').forEach(cb => cb.checked = isChecked);
            if(tableSelectAllCheckbox) tableSelectAllCheckbox.checked = isChecked;
            updateBulkActionBar();
        });
    }

    if(tableSelectAllCheckbox) {
        tableSelectAllCheckbox.addEventListener('change', (event) => {
            const isChecked = event.target.checked;
            document.querySelectorAll('.history-employee-checkbox').forEach(cb => cb.checked = isChecked);
            if(mainSelectAllCheckbox) mainSelectAllCheckbox.checked = isChecked;
            updateBulkActionBar();
        });
    }

    bulkActionButton.addEventListener('click', () => {
        const selectedCheckboxes = document.querySelectorAll('.history-employee-checkbox:checked');
        if (selectedCheckboxes.length === 0) return;
        isBulkTransfer = true;
        const employeeIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.employeeId);
        employeeToTransferIdInput.value = JSON.stringify(employeeIds);
        employeeToTransferNameSpan.textContent = `คุณกำลังจะย้ายลูกจ้างที่เลือกจำนวน ${selectedCheckboxes.length} คน`;
        openTransferModal();
    });

    const debounce = (func, delay) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const handleEmployerSearch = debounce(() => {
        const searchTerm = employerSearchInput.value.trim();
        if (searchTerm.length < 2) {
            employerSearchResultsDiv.innerHTML = '';
            return;
        }
        employerSearchResultsDiv.innerHTML = '<p class="text-muted p-2">กำลังค้นหา...</p>';

        fetch(`/api-web/employers/list?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok'))
            .then(data => {
                employerSearchResultsDiv.innerHTML = data.length === 0 ? '<p class="text-muted p-2">ไม่พบข้อมูลนายจ้าง</p>' : '';
                data.forEach(employer => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `<strong>${employer.employerNameTh}</strong> <small class="text-muted">(${employer.employerId})</small>`;
                    item.dataset.employerId = employer.id;
                    item.dataset.employerName = employer.employerNameTh;
                    employerSearchResultsDiv.appendChild(item);
                });
            })
            .catch(error => {
                console.error('Error fetching employers:', error);
                employerSearchResultsDiv.innerHTML = '<p class="text-danger p-2">เกิดข้อผิดพลาดในการค้นหา</p>';
            });
    }, 300);

    employerSearchInput.addEventListener('keyup', handleEmployerSearch);

    employerSearchResultsDiv.addEventListener('click', (event) => {
        const target = event.target.closest('button');
        if (!target) return;
        selectedEmployer = { id: target.dataset.employerId, name: target.dataset.employerName };
        selectedEmployerNameSpan.textContent = selectedEmployer.name;
        selectedEmployerDisplay.style.display = 'block';
        confirmTransferBtn.disabled = false;
        employerSearchResultsDiv.style.display = 'none';
        employerSearchInput.value = '';
    });

    confirmTransferBtn.addEventListener('click', () => {
        if (!selectedEmployer) return showToast('กรุณาเลือกนายจ้างใหม่ก่อน', 'danger');
        const { id: newEmployerId, name: newEmployerName } = selectedEmployer;

        const swalHtml = isBulkTransfer
            ? `คุณต้องการย้ายลูกจ้างที่เลือกทั้งหมดไปยัง <strong>${newEmployerName}</strong> ใช่หรือไม่?`
            : `คุณต้องการย้ายลูกจ้างไปยัง <strong>${newEmployerName}</strong> ใช่หรือไม่?`;

        Swal.fire({ title: 'ยืนยันการย้ายนายจ้าง', html: swalHtml, icon: 'warning', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก' })
            .then(result => {
                if (result.isConfirmed) {
                    if (isBulkTransfer) {
                        const employeeIds = JSON.parse(employeeToTransferIdInput.value);
                        performAction('/employees/bulk-transfer', { employee_ids: employeeIds, new_employer_id: newEmployerId });
                    } else {
                        const employeeId = employeeToTransferIdInput.value;
                        performAction(`/employees/${employeeId}/transfer`, { new_employer_id: newEmployerId });
                    }
                }
            });
    });

    if (transferModalEl) {
        transferModalEl.addEventListener('show.bs.modal', () => document.body.classList.add('modal-stack-active'));
        transferModalEl.addEventListener('hidden.bs.modal', () => document.body.classList.remove('modal-stack-active'));
    }

    updateBulkActionBar();
});
</script>
<style>
.modal-stack-active {
    overflow: hidden;
}
.modal-stack-active .modal.fade.show:not(#transferEmployeeModal) {
    opacity: 0.5;
}
</style>
@endpush
@endsection
