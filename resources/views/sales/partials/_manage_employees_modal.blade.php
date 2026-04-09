{{-- resources/views/sales/partials/_manage_employees_modal.blade.php --}}
<div class="modal fade" id="manageEmployeesModal-{{ $lead->id }}" tabindex="-1" aria-labelledby="manageEmployeesLabel-{{ $lead->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageEmployeesLabel-{{ $lead->id }}"><i class="bi bi-people-fill me-2"></i>จัดการลูกจ้าง - {{ $lead->employerNameTh }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- Employees List FIRST (shown immediately) --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>รายชื่อลูกจ้าง ({{ $lead->employees->count() }} คน)</h6>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addEmployeeCollapse-{{ $lead->id }}">
                            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มลูกจ้างใหม่
                        </button>
                    </div>

                    @forelse($lead->employees as $emp)
                        <div class="card mb-2 shadow-sm border {{ $emp->employee_id ? 'border-info' : 'border-warning' }}">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <img src="{{ $emp->photo_path ? asset('storage/'.$emp->photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($emp->employeeNameEn ?? 'E').'&background=random&size=64' }}"
                                             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="fw-bold">{{ $emp->employeeTitleEn }} {{ $emp->employeeNameEn }}</span>
                                            @if($emp->employee_id)
                                                <button type="button" class="btn btn-link btn-sm p-0 text-info btn-preview" data-model-type="employee" data-model-id="{{ $emp->employee_id }}" title="ดูข้อมูลลูกจ้าง">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                                <span class="badge bg-info" style="font-size: 0.6rem;">ในระบบ</span>
                                            @else
                                                <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">ชั่วคราว</span>
                                            @endif
                                        </div>
                                        @if($emp->employeeNameTh)
                                            <div class="small text-muted">{{ $emp->employeeTitleTh }} {{ $emp->employeeNameTh }}</div>
                                        @endif
                                        @if($emp->employeeNationality)
                                            <div class="small text-muted">{{ $emp->employeeNationality }}</div>
                                        @endif
                                        @if($emp->employeePassport)
                                            <div class="small text-muted font-monospace">PP: {{ $emp->employeePassport }}</div>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0 d-flex gap-1">
                                        {{-- Edit button for all employees --}}
                                        @if(!$emp->employee_id)
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editEmployeeModal-{{ $emp->id }}" title="แก้ไข">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('employees.edit', ['employee' => $emp->employee_id, 'return_url' => request()->fullUrl()]) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="แก้ไขในระบบ" target="_blank">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('sales.employees.destroy', [$lead->id, $emp->id]) }}" method="POST" onsubmit="return confirm('ยืนยันการลบลูกจ้าง?');" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="ลบ"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Edit Modal for temporary employees --}}
                        @if(!$emp->employee_id)
                            @include('sales.partials._edit_employee_modal', ['lead' => $lead, 'emp' => $emp])
                        @endif
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-people display-6 d-block mb-2 opacity-50"></i>
                            ยังไม่มีลูกจ้าง — กดปุ่ม "เพิ่มลูกจ้างใหม่" เพื่อเริ่มเพิ่ม
                        </div>
                    @endforelse
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
                            <button class="nav-link" type="button" data-bs-toggle="modal" data-bs-target="#newEmployeeFormModal-{{ $lead->id }}">
                                <i class="bi bi-person-plus me-1"></i> กรอกข้อมูลลูกจ้างใหม่ (ชั่วคราว)
                            </button>
                        </li>
                        @if($lead->employer_id)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-primary fw-bold" id="bulk-emp-tab-{{ $lead->id }}" data-bs-toggle="tab" data-bs-target="#bulk-emp-pane-{{ $lead->id }}" type="button" role="tab">
                                <i class="bi bi-people-fill me-1"></i> นำเข้าจากนายจ้าง
                            </button>
                        </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-success fw-bold" id="import-emp-tab-{{ $lead->id }}" data-bs-toggle="tab" data-bs-target="#import-emp-pane-{{ $lead->id }}" type="button" role="tab" aria-controls="import-emp-pane-{{ $lead->id }}" aria-selected="false">
                                <i class="bi bi-file-earmark-excel me-1"></i> นำเข้าผ่าน Excel
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="employeeTypeTabsContent-{{ $lead->id }}">

                        {{-- Existing Employee Search --}}
                        <div class="tab-pane fade show active" id="exist-emp-pane-{{ $lead->id }}" role="tabpanel" tabindex="0">
                            <form id="addEmpForm-{{ $lead->id }}" action="{{ route('sales.employees.store', $lead->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">ค้นหาลูกจ้างในระบบ <span class="text-danger">*</span></label>
                                    <select class="form-select select2-employee-{{ $lead->id }}" name="employee_id" style="width: 100%;">
                                        <option value="">-- พิมพ์ชื่อ, พาสปอร์ต, หรือเลขประจำตัว --</option>
                                    </select>
                                </div>
                                <div id="addEmpMsg-{{ $lead->id }}" class="mt-2" style="display:none;"></div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success" id="btn-save-emp-{{ $lead->id }}">
                                        <i class="bi bi-save me-1"></i> บันทึกเข้ารายการ
                                    </button>
                                </div>
                            </form>
                        </div>

                    {{-- Bulk Import from Employer --}}
                    @if($lead->employer_id)
                    <div class="tab-pane fade mt-3" id="bulk-emp-pane-{{ $lead->id }}" role="tabpanel">
                        <div class="alert alert-info py-2">
                            <i class="bi bi-info-circle me-2"></i> เลือกลูกจ้างจากนายจ้าง <strong>{{ $lead->employer?->employerNameTh }}</strong> แล้วกดนำเข้า
                        </div>
                        {{-- Search box --}}
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="bulkEmpSearch-{{ $lead->id }}" placeholder="ค้นหาชื่อ, พาสปอร์ต, เลขประจำตัว, ID:..." oninput="filterBulkEmpList({{ $lead->id }}, this.value)">
                        </div>
                        <div id="bulkEmpList-{{ $lead->id }}" style="max-height: 350px; overflow-y: auto;">
                            <div class="text-center py-3 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div> กำลังโหลด...
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary" id="btnBulkImport-{{ $lead->id }}" disabled onclick="bulkImportEmployees({{ $lead->id }})">
                                <i class="bi bi-download me-1"></i> นำเข้าที่เลือก
                            </button>
                        </div>
                    </div>
                    @endif

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

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Employee Modals --}}
@foreach($lead->employees as $emp)
    @if(!$emp->employee_id)
        @include('sales.partials._edit_employee_modal', ['lead' => $lead, 'emp' => $emp])
    @endif
@endforeach

{{-- Nested Modal: New Employee Form --}}
<div class="modal fade" id="newEmployeeFormModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>เพิ่มลูกจ้างใหม่ (ชั่วคราว) - {{ $lead->employerNameTh }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ข้อมูลนี้จะไม่ถูกบันทึกจริงจนกว่าจะคอนเฟิร์มงาน (บังคับแค่ชื่อภาษาอังกฤษ)
                </div>
                <form id="newEmpForm-{{ $lead->id }}" action="{{ route('sales.employees.store', $lead->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('sales.partials._sales_employee_form', ['lead' => $lead, 'emp' => null])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="btn-save-new-emp-{{ $lead->id }}">
                    <i class="bi bi-save me-1"></i> บันทึกลูกจ้างใหม่
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // AJAX submit for existing employee (search + add)
    (function() {
        var form = document.getElementById('addEmpForm-{{ $lead->id }}');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var msgEl = document.getElementById('addEmpMsg-{{ $lead->id }}');
            var btn = document.getElementById('btn-save-emp-{{ $lead->id }}');
            var empSelect = form.querySelector('select[name="employee_id"]');

            if (!empSelect || !empSelect.value) {
                alert('กรุณาเลือกลูกจ้างเดิม');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(function(resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.text();
            })
            .then(function() {
                msgEl.style.display = 'block';
                msgEl.innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i> เพิ่มลูกจ้างสำเร็จ!</div>';
                setTimeout(function() { msgEl.style.display = 'none'; }, 3000);
                if (typeof jQuery !== 'undefined') $('.select2-employee-{{ $lead->id }}').val(null).trigger('change');
                refreshEmployeeList{{ $lead->id }}();
            })
            .catch(function(err) {
                msgEl.style.display = 'block';
                msgEl.innerHTML = '<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i> ' + err.message + '</div>';
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> บันทึกเข้ารายการ';
            });
        });
    })();

    // AJAX submit for NEW employee form (nested modal)
    (function() {
        var saveBtn = document.getElementById('btn-save-new-emp-{{ $lead->id }}');
        if (!saveBtn) return;

        saveBtn.addEventListener('click', function() {
            var form = document.getElementById('newEmpForm-{{ $lead->id }}');
            if (!form) return;

            var nameInput = form.querySelector('input[name="employeeNameEn"]');
            if (nameInput && !nameInput.value.trim()) {
                alert('กรุณากรอกชื่อ-นามสกุล (ภาษาอังกฤษ)');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(function(resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.text();
            })
            .then(function() {
                // Close nested modal
                var nestedModalEl = document.getElementById('newEmployeeFormModal-{{ $lead->id }}');
                var nestedModal = bootstrap.Modal.getInstance(nestedModalEl);
                if (nestedModal) nestedModal.hide();

                form.reset();

                // Show success toast
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: 'บันทึกลูกจ้างใหม่เรียบร้อยแล้ว', timer: 2000, showConfirmButton: false });
                }

                // Refresh employee list (reload page with modal re-open)
                refreshEmployeeList{{ $lead->id }}();
            })
            .catch(function(err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: err.message });
                } else {
                    alert('เกิดข้อผิดพลาด: ' + err.message);
                }
            })
            .finally(function() {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save me-1"></i> บันทึกลูกจ้างใหม่';
            });
        });
    })();

    // Refresh: reload page with flag to re-open this modal
    function refreshEmployeeList{{ $lead->id }}() {
        // Store which modal to re-open after reload
        sessionStorage.setItem('reopenModal', 'manageEmployeesModal-{{ $lead->id }}');
        window.location.reload();
    }

    // Initialize Select2 for lead {{ $lead->id }}
    function initSelect2ForLead{{ $lead->id }}() {
        if (typeof jQuery === 'undefined' || typeof $.fn.select2 === 'undefined') return;
        try {
            $('.select2-employee-{{ $lead->id }}').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#manageEmployeesModal-{{ $lead->id }}'),
                ajax: {
                    url: '{{ route("employees.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { q: params.term, page: params.page }; },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.map(function(item) {
                                return { id: item.id, text: item.employeeNameEn + ' ' + (item.employeePassport ? '('+item.employeePassport+')' : '') };
                            }),
                            pagination: { more: (params.page * 30) < data.total_count }
                        };
                    },
                    cache: true
                },
                placeholder: '-- พิมพ์ชื่อ, พาสปอร์ต, หรือเลขประจำตัว --',
                minimumInputLength: 2,
            });
        } catch(e) {
            console.warn('Select2 init error for lead {{ $lead->id }}:', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSelect2ForLead{{ $lead->id }});
    } else {
        initSelect2ForLead{{ $lead->id }}();
    }
})();

@if($lead->employer_id)
// Bulk import: load employees when tab is clicked
(function() {
    var tabBtn = document.getElementById('bulk-emp-tab-{{ $lead->id }}');
    if (!tabBtn) return;
    var loaded = false;

    tabBtn.addEventListener('shown.bs.tab', function() {
        if (loaded) return;
        loaded = true;

        var container = document.getElementById('bulkEmpList-{{ $lead->id }}');
        var existingIds = @json($lead->employees->pluck('employee_id')->filter()->values());

        // Store data globally for search filtering
        window._bulkEmpData_{{ $lead->id }} = [];

        fetch('{{ route("employees.search") }}?employer_id={{ $lead->employer_id }}&limit=200', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            window._bulkEmpData_{{ $lead->id }} = data;
            if (!data.length) {
                container.innerHTML = '<div class="text-center py-3 text-muted">ไม่พบลูกจ้างภายใต้นายจ้างนี้</div>';
                return;
            }
            renderBulkEmpList({{ $lead->id }}, data, existingIds);
        })
        .catch(function() {
            container.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดรายชื่อลูกจ้างได้</div>';
        });
    });
})();
@endif
</script>

<script>
function renderBulkEmpList(leadId, data, existingIds) {
    var container = document.getElementById('bulkEmpList-' + leadId);
    if (!data.length) {
        container.innerHTML = '<div class="text-center py-3 text-muted">ไม่พบผลลัพธ์</div>';
        return;
    }
    var html = '<div class="list-group">';
    html += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-2 bg-light">';
    html += '<input type="checkbox" class="form-check-input" onchange="toggleBulkAll(' + leadId + ', this.checked)"> <strong>เลือกทั้งหมด (' + data.length + ' คน)</strong>';
    html += '</label>';
    data.forEach(function(emp) {
        var alreadyAdded = existingIds.includes(emp.id);
        var photoUrl = emp.photo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(emp.employeeNameEn || 'E') + '&background=random&size=40';
        var displayName = (emp.employeeNameEn || emp.employeeNameTh || 'ID:' + emp.id);
        var passport = emp.employeePassport ? ' PP:' + emp.employeePassport : '';

        html += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-2 ' + (alreadyAdded ? 'bg-light text-muted' : '') + '" data-search="' + (displayName + ' ' + (emp.employeePassport || '') + ' ' + emp.id).toLowerCase() + '">';
        html += '<input type="checkbox" class="form-check-input bulk-emp-cb-' + leadId + '" value="' + emp.id + '" ' + (alreadyAdded ? 'disabled checked' : '') + ' onchange="updateBulkBtn(' + leadId + ')">';
        html += '<img src="' + photoUrl + '" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" loading="lazy">';
        html += '<div class="flex-grow-1 overflow-hidden">';
        html += '<div class="fw-semibold text-truncate">' + displayName + '</div>';
        html += '<div class="small text-muted">' + passport + (emp.id ? ' ID:' + emp.id : '') + '</div>';
        html += '</div>';
        if (alreadyAdded) html += '<span class="badge bg-secondary">เพิ่มแล้ว</span>';
        html += '</label>';
    });
    html += '</div>';
    container.innerHTML = html;
    updateBulkBtn(leadId);
}

function filterBulkEmpList(leadId, query) {
    var data = window['_bulkEmpData_' + leadId] || [];
    var existingIds = @json($lead->employees->pluck('employee_id')->filter()->values());
    if (!query.trim()) {
        renderBulkEmpList(leadId, data, existingIds);
        return;
    }
    var q = query.toLowerCase().trim();
    var filtered = data.filter(function(emp) {
        var searchStr = ((emp.employeeNameEn || '') + ' ' + (emp.employeeNameTh || '') + ' ' + (emp.name_suffix || '') + ' ' + (emp.employeePassport || '') + ' ' + emp.id).toLowerCase();
        return searchStr.indexOf(q) !== -1;
    });
    renderBulkEmpList(leadId, filtered, existingIds);
}

function toggleBulkAll(leadId, checked) {
    document.querySelectorAll('.bulk-emp-cb-' + leadId + ':not(:disabled)').forEach(function(cb) { cb.checked = checked; });
    updateBulkBtn(leadId);
}

function updateBulkBtn(leadId) {
    var checked = document.querySelectorAll('.bulk-emp-cb-' + leadId + ':checked:not(:disabled)').length;
    var btn = document.getElementById('btnBulkImport-' + leadId);
    if (btn) {
        btn.disabled = checked === 0;
        btn.innerHTML = '<i class="bi bi-download me-1"></i> นำเข้าที่เลือก' + (checked > 0 ? ' (' + checked + ' คน)' : '');
    }
}

function bulkImportEmployees(leadId) {
    var btn = document.getElementById('btnBulkImport-' + leadId);
    var checkboxes = document.querySelectorAll('.bulk-emp-cb-' + leadId + ':checked:not(:disabled)');
    if (checkboxes.length === 0) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังนำเข้า...';

    var promises = [];
    checkboxes.forEach(function(cb) {
        var formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('employee_id', cb.value);

        promises.push(
            fetch('/sales/' + leadId + '/employees', { method: 'POST', body: formData })
        );
    });

    Promise.all(promises).then(function() {
        window.location.reload();
    }).catch(function() {
        alert('เกิดข้อผิดพลาดในการนำเข้า');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download me-1"></i> นำเข้าที่เลือก';
    });
}
</script>
@endpush