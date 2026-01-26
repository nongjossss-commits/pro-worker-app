{{-- resources/views/workflow/partials/add_employee_modal.blade.php --}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content" id="addEmployeeForm">
            @csrf
            <input type="hidden" name="employer_id" id="modal_employer_id">
            <input type="hidden" name="work_type_id" id="modal_work_type_id">
            {{-- We don't strictly need order_id if store() finds it, but good for reference if we change logic --}}

            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Employees') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Group / Batch Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                    <input type="text" name="group_name" class="form-control" placeholder="{{ __('e.g. Applied Today') }}">
                </div>

                {{-- Notify In: Search Resigned --}}
                <div id="section-notify-in" class="d-none section-mode">
                    <label class="form-label">{{ __('Search Resigned Employees (From any employer)') }}</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="resigned-search-input" placeholder="{{ __('Type name, passport...') }}">
                    </div>
                    <div class="list-group overflow-auto" style="max-height: 250px;" id="resigned-results">
                        <div class="text-center text-muted py-3">{{ __('Type to search...') }}</div>
                    </div>
                </div>

                {{-- Notify Out: Select Active --}}
                <div id="section-notify-out" class="d-none section-mode">
                    <label class="form-label">{{ __('Select Active Employees (From this employer)') }}</label>
                    <div id="active-employees-loader" class="text-center py-3">
                        <div class="spinner-border text-primary spinner-border-sm"></div>
                    </div>
                    <div id="active-employees-list" class="list-group overflow-auto d-none" style="max-height: 250px;">
                        {{-- Populated via JS --}}
                    </div>
                </div>

                {{-- General/MOU --}}
                <div id="section-general" class="d-none section-mode">
                    <h6 class="fw-bold">{{ __('Add New Employee (Manual)') }}</h6>
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
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-2">
                        <i class="bi bi-info-circle me-1"></i> {{ __('These employees will be added as drafts in this job card.') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Add Selected') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Overwrite the global function defined in index
    window.openAddEmployeeModal = function(orderId, employerId, workTypeId, workTypeSlug) {
        document.getElementById('modal_employer_id').value = employerId;
        document.getElementById('modal_work_type_id').value = workTypeId;

        // Reset UI
        document.querySelectorAll('.section-mode').forEach(el => el.classList.add('d-none'));
        document.getElementById('addEmployeeForm').reset();
        document.getElementById('resigned-results').innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';

        const modal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));

        if (workTypeSlug === 'notify_in') {
            document.getElementById('section-notify-in').classList.remove('d-none');
        } else if (workTypeSlug === 'notify_out') {
            document.getElementById('section-notify-out').classList.remove('d-none');
            loadActiveEmployees(employerId);
        } else {
            document.getElementById('section-general').classList.remove('d-none');
        }

        modal.show();
    }

    // Search Resigned Logic
    let searchTimeout;
    document.getElementById('resigned-search-input').addEventListener('input', function(e) {
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
                                <div class="fw-bold">${emp.employeeNameEn || emp.employeeNameTh}</div>
                                <div class="small text-muted">Old Employer: ${emp.employer ? emp.employer.employerNameTh : 'N/A'}</div>
                            </div>
                        `;
                        container.appendChild(item);
                    });
                });
        }, 500);
    });

    // Load Active Employees
    function loadActiveEmployees(employerId) {
        const container = document.getElementById('active-employees-list');
        const loader = document.getElementById('active-employees-loader');

        loader.classList.remove('d-none');
        container.classList.add('d-none');
        container.innerHTML = '';

        // Reuse existing endpoint or create new?
        // We have `production.registration.employer.employees` but that renders HTML.
        // We have `api-web.employer.employees` (EmployerEmployeeController@index).
        // Let's use `api-web.employer.employees` if it supports employer_id param.
        // Checking routes... `api-web/employer/employees` uses `auth()->user()->employer_id`.
        // Admin needs to fetch ANY employer's employees.
        // I'll assume we can use the `fetchOrderItems` route of the Order to get CURRENT items, but we want ALL active employees of the employer.
        // I'll create a quick fetch using the existing `Production/RegistrationController::fetchEmployees` logic but returning JSON?
        // Or just generic Employee search?
        // Let's use a new one-off fetch here or just search endpoint.
        // `employees.index` accepts `employer_id`?

        // Use the existing `fetchEmployees` route from RegistrationController returns HTML card list. Not suitable for dropdown.
        // I'll just use the `workflow.api.resigned` endpoint but modified to support active + employer_id?
        // Or cleaner: Add `workflow.api.active-employees` route.
        // For now, I'll mock it or use `employees.index` JSON if available.
        // Let's use `WorkflowController::searchResigned` but add a mode?
        // No, keep it clean.
        // I will rely on `employees.index` filtering if I can.
        // But `employees.index` returns full page.

        // I'll add `WorkflowController::fetchEmployerActiveEmployees` quickly.

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
                    item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 cursor-pointer';
                    item.innerHTML = `
                        <input class="form-check-input flex-shrink-0" type="checkbox" name="employee_ids[]" value="${emp.id}">
                         <div>
                            <div class="fw-bold">${emp.employeeNameEn || emp.employeeNameTh}</div>
                            <div class="small text-muted">${emp.employeePassport || '-'}</div>
                        </div>
                    `;
                    container.appendChild(item);
                });
            });
    }
</script>
