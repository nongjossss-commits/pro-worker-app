{{-- resources/views/sales/partials/_manage_employees_modal.blade.php --}}
<div class="modal fade" id="manageEmployeesModal-{{ $lead->id }}" tabindex="-1" aria-labelledby="manageEmployeesLabel-{{ $lead->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageEmployeesLabel-{{ $lead->id }}"><i class="bi bi-people-fill me-2"></i>จัดการลูกจ้าง - {{ $lead->employerNameTh }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addEmployeeCollapse-{{ $lead->id }}">
                            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มลูกจ้างใหม่
                        </button>
                    </div>
                </div>

                {{-- Add Employee Form Collapse --}}
                <div class="collapse mb-4 border border-info rounded p-3 bg-light" id="addEmployeeCollapse-{{ $lead->id }}">
                    <h5 class="text-info border-bottom border-info pb-2 mb-3">เพิ่มลูกจ้างเข้ารายการเสนอราคา</h5>

                    <ul class="nav nav-pills mb-3" id="employeeTypeTabs-{{ $lead->id }}" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="exist-emp-tab-{{ $lead->id }}" data-bs-toggle="tab" data-bs-target="#exist-emp-pane-{{ $lead->id }}" type="button" role="tab" aria-controls="exist-emp-pane-{{ $lead->id }}" aria-selected="true">
                                เลือกลูกจ้างเดิม (ในระบบ)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="new-emp-tab-{{ $lead->id }}" data-bs-toggle="tab" data-bs-target="#new-emp-pane-{{ $lead->id }}" type="button" role="tab" aria-controls="new-emp-pane-{{ $lead->id }}" aria-selected="false">
                                กรอกข้อมูลลูกจ้างใหม่ (ชั่วคราว)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success fw-bold" id="import-emp-tab-{{ $lead->id }}" data-bs-toggle="tab" data-bs-target="#import-emp-pane-{{ $lead->id }}" type="button" role="tab" aria-controls="import-emp-pane-{{ $lead->id }}" aria-selected="false">
                                <i class="bi bi-file-earmark-excel me-1"></i> นำเข้าผ่าน Excel
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="employeeTypeTabsContent-{{ $lead->id }}">

                        {{-- Wrapper for manual forms --}}
                        <div class="tab-pane fade show active" id="manual-emp-wrapper-{{ $lead->id }}" role="tabpanel" tabindex="0">
                            <form action="{{ route('sales.employees.store', $lead->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="tab-content">

                            {{-- Existing Employee --}}
                            <div class="tab-pane fade show active" id="exist-emp-pane-{{ $lead->id }}" role="tabpanel" aria-labelledby="exist-emp-tab-{{ $lead->id }}" tabindex="0">
                                <div class="mb-3">
                                    <label class="form-label">ค้นหาลูกจ้าง <span class="text-danger">*</span></label>
                                    <select class="form-select select2-employee-{{ $lead->id }}" name="employee_id" style="width: 100%;">
                                        <option value="">-- พิมพ์ชื่อ, พาสปอร์ต, หรือเลขประจำตัว --</option>
                                        {{-- Using AJAX in real scenario, but for now we'll fetch all or use select2 ajax. --}}
                                        {{-- As requested, search existing employees drop down. --}}
                                        <option value="temp-disabled" disabled>กรุณาพิมพ์เพื่อค้นหา (ระบบจะเชื่อมต่อ API)</option>
                                    </select>
                                    <div class="form-text text-muted">ระบบจะดึงข้อมูลที่เกี่ยวข้องมาแสดงในใบเสนอราคา</div>
                                </div>
                            </div>

                            {{-- New Employee --}}
                            <div class="tab-pane fade" id="new-emp-pane-{{ $lead->id }}" role="tabpanel" aria-labelledby="new-emp-tab-{{ $lead->id }}" tabindex="0">
                                <div class="alert alert-warning py-2 mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ข้อมูลนี้จะไม่ถูกบันทึกจริงจนกว่าจะคอนเฟิร์มงาน (บังคับแค่ชื่อภาษาอังกฤษ)
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อ-นามสกุล (ภาษาอังกฤษ) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="employeeNameEn" id="empNameEn-{{ $lead->id }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อ-นามสกุล (ภาษาไทย)</label>
                                        <input type="text" class="form-control" name="employeeNameTh">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">เพศ</label>
                                        <select class="form-select" name="employeeGender">
                                            <option value="">ไม่ได้ระบุ</option>
                                            <option value="Male">ชาย (Male)</option>
                                            <option value="Female">หญิง (Female)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">เลขพาสปอร์ต</label>
                                        <input type="text" class="form-control" name="employeePassport">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">เลขใบอนุญาตทำงาน</label>
                                        <input type="text" class="form-control" name="employeeWorkPermit">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">รูปถ่ายหน้าตรง (ถ้ามี)</label>
                                        <input type="file" class="form-control" name="photo" accept="image/*">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">เอกสารอื่นๆ (ถ้ามี)</label>
                                        <input type="file" class="form-control" name="document" accept=".pdf,.doc,.docx,.jpg,.png">
                                    </div>
                                </div>
                            </div>

                                </div>

                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-success" id="btn-save-emp-{{ $lead->id }}" onclick="return validateAddEmployee(this, {{ $lead->id }})">
                                        <i class="bi bi-save me-1"></i> บันทึกเข้ารายการ
                                    </button>
                                </div>
                            </form>
                        </div>

                    {{-- Excel Import Form --}}
                    <div class="tab-pane fade mt-3" id="import-emp-pane-{{ $lead->id }}" role="tabpanel" aria-labelledby="import-emp-tab-{{ $lead->id }}" tabindex="0">
                        <form action="{{ route('sales.import', $lead->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> กรุณาอัปโหลดไฟล์ Excel (.xlsx, .xls) โดยเรียงคอลัมน์ดังนี้: NO, Name En, Name Th, Gender, Nationality, Passport, Work Permit
                            </div>
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" name="excel_file" accept=".xlsx, .xls" required>
                                <button class="btn btn-success" type="submit" id="button-import"><i class="bi bi-upload me-1"></i> อัปโหลดและนำเข้า</button>
                            </div>
                        </form>
                    </div>

                </div>

                {{-- Employees List Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>ชื่อลูกจ้าง</th>
                                <th>พาสปอร์ต</th>
                                <th>ใบอนุญาตทำงาน</th>
                                <th>ประเภท</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lead->employees as $emp)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $emp->employeeNameEn }}</div>
                                        <div class="small text-muted">{{ $emp->employeeNameTh }}</div>
                                    </td>
                                    <td>{{ $emp->employeePassport ?? '-' }}</td>
                                    <td>{{ $emp->employeeWorkPermit ?? '-' }}</td>
                                    <td>
                                        @if($emp->employee_id)
                                            <span class="badge bg-info"><i class="bi bi-database me-1"></i>ในระบบ</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>ชั่วคราว</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('sales.employees.destroy', [$lead->id, $emp->id]) }}" method="POST" onsubmit="return confirm('ยืนยันการลบลูกจ้างออกจากรายการนี้?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">ยังไม่มีข้อมูลลูกจ้างในรายการนี้</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Basic validation handler for employee submission
    window.validateAddEmployee = function(btn, leadId) {
        const isNewTabActive = document.getElementById('new-emp-tab-' + leadId).classList.contains('active');
        const form = btn.closest('form');

        if (isNewTabActive) {
            const nameEn = form.querySelector('input[name="employeeNameEn"]').value;
            if(!nameEn) {
                alert('กรุณากรอกชื่อ-นามสกุล (ภาษาอังกฤษ)');
                return false;
            }
            // Clear select2
            $(form.querySelector('select[name="employee_id"]')).val(null).trigger('change');
        } else {
            const empId = form.querySelector('select[name="employee_id"]').value;
            if(!empId) {
                alert('กรุณาเลือกลูกจ้างเดิม');
                return false;
            }
            // Clear text inputs
            form.querySelector('input[name="employeeNameEn"]').value = '';
        }
        return true;
    };

    // Initialize Select2 for employees with AJAX
    if (typeof jQuery !== 'undefined') {
        $('.select2-employee-{{ $lead->id }}').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#manageEmployeesModal-{{ $lead->id }}'),
            ajax: {
                url: '{{ route('employees.search') }}', // Existing route for employee search
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.employeeNameEn + ' ' + (item.employeePassport ? '('+item.employeePassport+')' : '')
                            }
                        }),
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            placeholder: '-- พิมพ์ชื่อ, พาสปอร์ต, หรือเลขประจำตัว --',
            minimumInputLength: 2,
        });
    }
});
</script>
@endpush