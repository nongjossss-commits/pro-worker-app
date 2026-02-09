{{-- resources/views/workflow/partials/add_employee_modal.blade.php --}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Employees') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="addEmployeeTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#tab-existing" type="button" role="tab" aria-controls="tab-existing" aria-selected="true">{{ __('Select Existing') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#tab-new" type="button" role="tab" aria-controls="tab-new" aria-selected="false">{{ __('New / Manual') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#tab-import" type="button" role="tab" aria-controls="tab-import" aria-selected="false">{{ __('Import Excel') }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="addEmployeeTabsContent">
                    {{-- Tab 1: Existing --}}
                    <div class="tab-pane fade show active" id="tab-existing" role="tabpanel" aria-labelledby="existing-tab">
                        <form action="{{ route('workflow.store') }}" method="POST" id="formExisting">
                            @csrf
                            <input type="hidden" name="employer_id" id="modal_employer_id">
                            <input type="hidden" name="work_type_id" id="modal_work_type_id">
                            <input type="hidden" name="production_order_id" id="modal_production_order_id">
                            <input type="hidden" name="is_pre_production" id="add_employee_is_pre_production" value="0">

                            {{-- Employer Search (Visible only if no Order ID) --}}
                            <div id="section-select-employer" class="mb-3 d-none">
                                <label class="form-label fw-bold">{{ __('Select Employer') }} <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                        <input type="text" class="form-control" id="employer-search-input" placeholder="{{ __('Type to search employer...') }}" autocomplete="off">
                                        <button class="btn btn-outline-secondary d-none" type="button" id="btn-clear-employer"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                    <div class="list-group position-absolute w-100 shadow-sm custom-scrollbar" id="employer-search-results" style="max-height: 200px; z-index: 1050; display: none;"></div>
                                </div>
                                <div class="form-text text-muted" id="selected-employer-text"></div>
                            </div>

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

                            {{-- Mode: Global Search --}}
                            <div id="section-global-search" class="d-none section-mode">
                                <div class="alert alert-primary py-2 small"><i class="bi bi-globe me-1"></i> {{ __('Search all employees (Global)') }}</div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="global-search-input" placeholder="{{ __('Type name, passport to search...') }}">
                                </div>
                                <div class="list-group overflow-auto custom-scrollbar" style="max-height: 300px;" id="global-results">
                                    <div class="text-center text-muted py-3">{{ __('Type to search...') }}</div>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab 2: New Manual --}}
                    <div class="tab-pane fade" id="tab-new" role="tabpanel" aria-labelledby="new-tab">
                        <form action="{{ route('workflow.store') }}" method="POST" id="formNew" enctype="multipart/form-data">
                            @csrf
                            {{-- We need these hidden inputs here too if we want to attach to an order --}}
                            {{-- However, for New Employee, we usually use the inputs inside the partial or context --}}
                            {{-- The partial has employer_id selector. We might need to inject work_type_id etc. --}}
                            <input type="hidden" name="work_type_id" id="modal_work_type_id_new">
                            <input type="hidden" name="production_order_id" id="modal_production_order_id_new">
                            <input type="hidden" name="is_pre_production" id="add_employee_is_pre_production_new" value="0">
                            {{-- Also inject group name if needed, or let user type it? New employee usually is 1 by 1. --}}
                            <input type="hidden" name="group_name" id="modal_group_name_new">

                            <div class="alert alert-light border">
                                 @include('employees.partials.create_form_partial_content')
                            </div>
                        </form>
                    </div>

                    {{-- Tab 3: Import --}}
                    <div class="tab-pane fade" id="tab-import" role="tabpanel" aria-labelledby="import-tab">
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
                <button type="button" class="btn btn-primary" id="btn-submit-add">{{ __('Add Employees') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.openAddEmployeeModal = function(orderId, employerId, workTypeId, workTypeSlug, context = 'workflow') {
        // Set values for Existing Form
        document.getElementById('modal_employer_id').value = employerId || '';
        document.getElementById('modal_work_type_id').value = workTypeId || '';
        document.getElementById('modal_production_order_id').value = orderId || '';

        const isPreProduction = (context === 'production');
        document.getElementById('add_employee_is_pre_production').value = isPreProduction ? '1' : '0';

        // Set values for New Form
        document.getElementById('modal_work_type_id_new').value = workTypeId || '';
        document.getElementById('modal_production_order_id_new').value = orderId || '';
        document.getElementById('add_employee_is_pre_production_new').value = isPreProduction ? '1' : '0';

        // Dispatch event to Alpine component in the partial to set/clear employer
        window.dispatchEvent(new CustomEvent('set-employer-id', {
            detail: { id: employerId } // If null/undefined, partial handles it
        }));

        // Setup Import Link
        const importUrl = `{{ route('employees.import_view') }}?production_id=${orderId || ''}&employer_id=${employerId || ''}&work_type_id=${workTypeId || ''}&return_to=${context}`;
        document.getElementById('btn-go-import').href = importUrl;

        // Reset UI
        document.querySelectorAll('.section-mode').forEach(el => el.classList.add('d-none'));
        document.getElementById('formExisting').reset();
        document.getElementById('formNew').reset();

        document.getElementById('resigned-results').innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';
        const globalRes = document.getElementById('global-results');
        if(globalRes) globalRes.innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';

        // Toggle Employer Search Section
        const employerSearchSection = document.getElementById('section-select-employer');
        const employerSearchInput = document.getElementById('employer-search-input');
        const employerClearBtn = document.getElementById('btn-clear-employer');
        const selectedEmployerText = document.getElementById('selected-employer-text');

        if (!orderId && !employerId) {
            // Global Add Mode
            employerSearchSection.classList.remove('d-none');
            employerSearchInput.value = '';
            employerClearBtn.classList.add('d-none');
            selectedEmployerText.innerText = '';
            document.getElementById('modal_employer_id').value = '';
        } else {
            // Contextual Mode
            employerSearchSection.classList.add('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));

        // Default Tabs Logic
        if (!orderId) {
             const tabNew = new bootstrap.Tab(document.querySelector('#new-tab'));
             tabNew.show();
        } else {
             const tabExisting = new bootstrap.Tab(document.querySelector('#existing-tab'));
             tabExisting.show();
        }

        if (workTypeSlug === 'notify_in') {
            document.getElementById('section-notify-in').classList.remove('d-none');
        } else {
            document.getElementById('section-global-search').classList.remove('d-none');
        }

        if (window.initEmployeeForm) {
            window.initEmployeeForm('');
        }

        modal.show();
    }

    // Unified Submission Logic
    document.addEventListener('DOMContentLoaded', function() {
        const submitBtn = document.getElementById('btn-submit-add');

        // Toggle Submit Button based on Tab
        const tabs = document.querySelectorAll('#addEmployeeTabs button[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('data-bs-target');
                if(target === '#tab-import') {
                    submitBtn.classList.add('d-none');
                } else {
                    submitBtn.classList.remove('d-none');
                }
            });
        });

        if(submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Determine active tab
                const activeTabLink = document.querySelector('#addEmployeeTabs .nav-link.active');
                const targetId = activeTabLink ? activeTabLink.getAttribute('data-bs-target') : null;

                let form = null;
                if(targetId === '#tab-existing') {
                    form = document.getElementById('formExisting');
                } else if (targetId === '#tab-new') {
                    form = document.getElementById('formNew');
                }

                if (!form) {
                    // Import tab or error
                    return;
                }

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if(data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Success") }}',
                            text: data.message || '{{ __("Employees added successfully.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Dynamic Update
                        if (data.order_id) {
                            if (window.refreshOrderContent) {
                                window.refreshOrderContent(data.order_id);
                            } else {
                                location.reload();
                            }
                            if (window.updateOrderHeaderStats && data.order_stats) {
                                window.updateOrderHeaderStats(data.order_id, data.order_stats);
                            }
                        } else {
                            if(data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                location.reload();
                            }
                        }
                    } else {
                        let msg = data.message || '{{ __("Something went wrong.") }}';
                        if (data.errors) {
                            msg = Object.values(data.errors).flat().join('<br>');
                        }
                        Swal.fire('{{ __("Error") }}', msg, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    Swal.fire('{{ __("Error") }}', '{{ __("Network error or server error.") }}', 'error');
                });
            });
        }
    });

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

    // Global Search Logic
    let globalSearchTimeout;
    const globalInput = document.getElementById('global-search-input');
    if(globalInput) {
        globalInput.addEventListener('input', function(e) {
            clearTimeout(globalSearchTimeout);
            const query = e.target.value;
            if(query.length < 2) return;

            globalSearchTimeout = setTimeout(() => {
                fetch(`{{ route('workflow.api.global') }}?q=${query}`)
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('global-results');
                        container.innerHTML = '';
                        if(data.length === 0) {
                            container.innerHTML = '<div class="text-center text-muted py-2">No employees found.</div>';
                            return;
                        }
                        data.forEach(emp => {
                            const item = document.createElement('label');
                            item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 cursor-pointer';
                            const currentEmployer = emp.employer ? (emp.employer.employerNameTh || emp.employer.employerNameEn) : 'No Employer';
                            item.innerHTML = `
                                <input class="form-check-input flex-shrink-0" type="checkbox" name="employee_ids[]" value="${emp.id}">
                                <div>
                                    <div class="fw-bold">${emp.employeeNameEn || emp.employeeNameTh || '-'}</div>
                                    <div class="small text-muted">Current: ${currentEmployer}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">Passport: ${emp.employeePassport || '-'}</div>
                                </div>
                            `;
                            container.appendChild(item);
                        });
                    });
            }, 500);
        });
    }

    // Employer Search Logic (Existing Tab)
    let employerSearchTimeout;
    const employerInput = document.getElementById('employer-search-input');
    const employerResults = document.getElementById('employer-search-results');
    const employerClearBtn = document.getElementById('btn-clear-employer');

    if (employerInput) {
        employerInput.addEventListener('input', function(e) {
            clearTimeout(employerSearchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                employerResults.style.display = 'none';
                return;
            }

            employerSearchTimeout = setTimeout(() => {
                fetch(`{{ route('api-web.employers.list') }}?search=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        employerResults.innerHTML = '';
                        if (data.length === 0) {
                            employerResults.innerHTML = '<div class="list-group-item text-muted text-center py-2">{{ __("No employers found.") }}</div>';
                        } else {
                            data.forEach(emp => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action';

                                const nameDiv = document.createElement('div');
                                nameDiv.className = 'fw-bold';
                                nameDiv.textContent = emp.employerNameTh || emp.employerNameEn;

                                const subDiv = document.createElement('small');
                                subDiv.className = 'text-muted';
                                subDiv.textContent = emp.employerNameEn || '';

                                item.appendChild(nameDiv);
                                item.appendChild(subDiv);

                                item.onclick = (e) => {
                                    e.preventDefault();
                                    selectEmployer(emp);
                                };
                                employerResults.appendChild(item);
                            });
                        }
                        employerResults.style.display = 'block';
                    })
                    .catch(err => {
                        employerResults.innerHTML = '<div class="list-group-item text-danger text-center py-2">Error searching.</div>';
                        employerResults.style.display = 'block';
                    });
            }, 300);
        });

        // Hide results on click outside
        document.addEventListener('click', function(e) {
            if (!employerInput.contains(e.target) && !employerResults.contains(e.target)) {
                employerResults.style.display = 'none';
            }
        });

        // Clear Button
        if (employerClearBtn) {
            employerClearBtn.addEventListener('click', function() {
                employerInput.value = '';
                document.getElementById('modal_employer_id').value = '';
                document.getElementById('selected-employer-text').innerText = '';
                employerClearBtn.classList.add('d-none');
                employerInput.focus();
            });
        }
    }

    function selectEmployer(emp) {
        document.getElementById('modal_employer_id').value = emp.id;
        document.getElementById('employer-search-input').value = emp.employerNameTh || emp.employerNameEn;
        document.getElementById('selected-employer-text').innerText = `Selected: ${emp.employerNameTh || emp.employerNameEn} (${emp.employerId})`;
        document.getElementById('employer-search-results').style.display = 'none';
        document.getElementById('btn-clear-employer').classList.remove('d-none');

        // Sync with New Employee Tab
        window.dispatchEvent(new CustomEvent('set-employer-id', {
            detail: { id: emp.id }
        }));
    }
</script>
