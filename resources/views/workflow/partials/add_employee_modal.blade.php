{{-- resources/views/workflow/partials/add_employee_modal.blade.php --}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content" id="addEmployeeForm">
            @csrf
            <input type="hidden" name="employer_id" id="modal_employer_id">
            <input type="hidden" name="work_type_id" id="modal_work_type_id">
            <input type="hidden" name="production_order_id" id="modal_production_order_id"> {{-- For adding to existing --}}

            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Employees') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="addEmployeeTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#tab-existing" type="button">{{ __('Select Existing') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#tab-new" type="button">{{ __('New / Manual') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#tab-import" type="button">{{ __('Import Excel') }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="addEmployeeTabsContent">
                    {{-- Tab 1: Existing --}}
                    <div class="tab-pane fade show active" id="tab-existing" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Group / Batch Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                            <input type="text" name="group_name" class="form-control" placeholder="{{ __('e.g. Batch 1') }}">
                        </div>

                        {{-- Mode: Search Resigned --}}
                        <div id="section-notify-in" class="d-none section-mode">
                            <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i> {{ __('Searching for Resigned employees (Change Employer)') }}</div>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="resigned-search-input" placeholder="{{ __('Type name, passport...') }}">
                            </div>
                            <div class="list-group overflow-auto custom-scrollbar" style="max-height: 300px;" id="resigned-results">
                                <div class="text-center text-muted py-3">{{ __('Type to search...') }}</div>
                            </div>
                        </div>

                        {{-- Mode: Select Active --}}
                        <div id="section-notify-out" class="d-none section-mode">
                            <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i> {{ __('Listing Active employees of this employer') }}</div>
                            <div class="d-flex justify-content-between mb-2">
                                <input type="text" class="form-control form-control-sm w-50" id="active-filter-input" placeholder="{{ __('Filter...') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllActive()">{{ __('Select All') }}</button>
                            </div>
                            <div id="active-employees-loader" class="text-center py-3">
                                <div class="spinner-border text-primary spinner-border-sm"></div>
                            </div>
                            <div id="active-employees-list" class="list-group overflow-auto custom-scrollbar" style="max-height: 300px;"></div>
                        </div>
                    </div>

                    {{-- Tab 2: New Manual --}}
                    <div class="tab-pane fade" id="tab-new" role="tabpanel">
                        <div class="alert alert-light border">
                             <h6 class="fw-bold mb-2">{{ __('Add New Employee (Draft)') }}</h6>
                             <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">{{ __('Name (TH)') }}</label>
                                    <input type="text" name="new_employee[name_th]" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">{{ __('Name (EN)') }}</label>
                                    <input type="text" name="new_employee[name_en]" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">{{ __('Passport No') }}</label>
                                    <input type="text" name="new_employee[passport]" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">{{ __('Nationality') }}</label>
                                    <select name="new_employee[nationality]" class="form-select form-select-sm">
                                        <option value="">{{ __('Select...') }}</option>
                                        <option value="Myanmar">Myanmar</option>
                                        <option value="Laos">Laos</option>
                                        <option value="Cambodia">Cambodia</option>
                                        <option value="Vietnam">Vietnam</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Import --}}
                    <div class="tab-pane fade" id="tab-import" role="tabpanel">
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark-spreadsheet fs-1 text-success mb-3"></i>
                            <h4>{{ __('Import from Excel') }}</h4>
                            <p class="text-muted">{{ __('Upload an Excel file to add multiple employees at once.') }}</p>
                            <a href="#" id="btn-go-import" class="btn btn-success px-4 py-2">
                                <i class="bi bi-upload me-2"></i> {{ __('Go to Import Page') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-add">{{ __('Add Employees') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openAddEmployeeModal = function(orderId, employerId, workTypeId, workTypeSlug) {
        document.getElementById('modal_employer_id').value = employerId;
        document.getElementById('modal_work_type_id').value = workTypeId;
        document.getElementById('modal_production_order_id').value = orderId; // Important for adding to existing

        // Setup Import Link
        const importUrl = `{{ route('employees.import_view') }}?production_id=${orderId}&employer_id=${employerId}`;
        document.getElementById('btn-go-import').href = importUrl;

        // Reset UI
        document.querySelectorAll('.section-mode').forEach(el => el.classList.add('d-none'));
        document.getElementById('addEmployeeForm').reset();
        document.getElementById('resigned-results').innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';
        document.getElementById('active-employees-list').innerHTML = ''; // Clear previous

        const modal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));

        // Logic for Tabs/Modes
        const tabExisting = new bootstrap.Tab(document.querySelector('#existing-tab'));
        tabExisting.show();

        if (workTypeSlug === 'notify_in') {
            // Change Employer -> Search Resigned
            document.getElementById('section-notify-in').classList.remove('d-none');
        } else {
            // Notify Out, MOU Renewal, MOU Import (Internal) -> List Active
            document.getElementById('section-notify-out').classList.remove('d-none');
            loadActiveEmployees(employerId);
        }

        modal.show();
    }

    // Search Resigned Logic
    let searchTimeout;
    const resignedInput = document.getElementById('resigned-search-input');
    if(resignedInput) {
        resignedInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            if(query.length < 2) return;

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('workflow.api.resigned') }}?q=${query}`)
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('resigned-results');
                        container.innerHTML = '';
                        if(data.length === 0) {
                            container.innerHTML = '<div class="text-center text-muted py-2">No employees found.</div>';
                            return;
                        }
                        data.forEach(emp => {
                            const item = document.createElement('label');
                            item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 cursor-pointer';
                            item.innerHTML = `
                                <input class="form-check-input flex-shrink-0" type="checkbox" name="employee_ids[]" value="${emp.id}">
                                <div>
                                    <div class="fw-bold">${emp.employeeNameEn || emp.employeeNameTh || '-'}</div>
                                    <div class="small text-muted">Old Employer: ${emp.employer ? (emp.employer.employerNameTh || emp.employer.employerNameEn) : 'N/A'}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Passport: ${emp.employeePassport || '-'}</div>
                                </div>
                            `;
                            container.appendChild(item);
                        });
                    });
            }, 500);
        });
    }

    // Load Active Employees
    function loadActiveEmployees(employerId) {
        const container = document.getElementById('active-employees-list');
        const loader = document.getElementById('active-employees-loader');

        loader.classList.remove('d-none');
        container.classList.add('d-none');
        container.innerHTML = '';

        fetch(`/workflow/api/active-employees/${employerId}`)
            .then(res => res.json())
            .then(data => {
                loader.classList.add('d-none');
                container.classList.remove('d-none');

                if(data.length === 0) {
                     container.innerHTML = '<div class="text-center text-muted">No active employees found.</div>';
                     return;
                }

                data.forEach(emp => {
                    const item = document.createElement('label');
                    item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 cursor-pointer active-emp-item';
                    const searchStr = (emp.employeeNameTh + ' ' + emp.employeeNameEn + ' ' + emp.employeePassport).toLowerCase();
                    item.dataset.name = searchStr;
                    item.innerHTML = `
                        <input class="form-check-input flex-shrink-0 active-emp-checkbox" type="checkbox" name="employee_ids[]" value="${emp.id}">
                         <div>
                            <div class="fw-bold">${emp.employeeNameEn || emp.employeeNameTh || '-'}</div>
                            <div class="small text-muted">${emp.employeePassport || '-'}</div>
                        </div>
                    `;
                    container.appendChild(item);
                });
            });
    }

    // Filter Active List
    const activeFilter = document.getElementById('active-filter-input');
    if(activeFilter) {
        activeFilter.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.active-emp-item').forEach(el => {
                if(el.dataset.name.includes(term)) el.classList.remove('d-none');
                else el.classList.add('d-none');
            });
        });
    }

    // Toggle Select All Active
    window.toggleSelectAllActive = function() {
        // Only visible items
        const visibleItems = Array.from(document.querySelectorAll('.active-emp-item')).filter(el => !el.classList.contains('d-none'));
        const visibleCheckboxes = visibleItems.map(el => el.querySelector('.active-emp-checkbox'));

        if(visibleCheckboxes.length === 0) return;

        const allChecked = visibleCheckboxes.every(cb => cb.checked);
        visibleCheckboxes.forEach(cb => cb.checked = !allChecked);
    }
</script>
