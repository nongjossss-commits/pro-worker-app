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
                            @php
                                $employerListInternal = isset($employers) ? $employers->map(fn($e) => [
                                    'id' => $e->id,
                                    'name_th' => $e->employerNameTh,
                                    'name_en' => $e->employerNameEn,
                                    'search_str' => strtolower(($e->employerNameTh ?? '') . ' ' . ($e->employerNameEn ?? ''))
                                ])->values() : collect([]);
                            @endphp

                            <div id="section-select-employer" class="mb-3 d-none"
                                 x-data='employerSelectorInternal(@json($employerListInternal))'
                                 @reset-employer-search.window="resetSearch()">
                                <label class="form-label fw-bold">{{ __('Select Employer') }} <span class="text-danger">*</span></label>
                                <div class="position-relative" @click.outside="open = false">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text"
                                               class="form-control"
                                               placeholder="{{ __('Type to search employer...') }}"
                                               x-model="search"
                                               @focus="open = true"
                                               @input="open = true; selectedId = ''"
                                               @keydown.escape="open = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                    </div>

                                    {{-- Dropdown List --}}
                                    <div class="card position-absolute w-100 shadow-sm mt-1 border-0"
                                         style="z-index: 1050; max-height: 250px; overflow-y: auto;"
                                         x-show="open && filteredEmployers.length > 0"
                                         x-transition
                                         style="display: none;">
                                        <ul class="list-group list-group-flush">
                                            <template x-for="emp in filteredEmployers" :key="emp.id">
                                                <li class="list-group-item list-group-item-action cursor-pointer"
                                                    @click="selectEmployer(emp)">
                                                    <div class="fw-bold" x-text="emp.name_th || emp.name_en"></div>
                                                    <div class="small text-muted" x-text="emp.name_en"></div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>

                                    {{-- No Results --}}
                                    <div class="card position-absolute w-100 shadow-sm mt-1 border-0"
                                         style="z-index: 1050;"
                                         x-show="open && filteredEmployers.length === 0"
                                         x-transition
                                         style="display: none;">
                                         <div class="list-group list-group-flush">
                                            <div class="list-group-item text-muted text-center">{{ __('No employers found.') }}</div>
                                         </div>
                                    </div>
                                </div>
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
    function employerSelectorInternal(employersData) {
        return {
            search: '',
            open: false,
            selectedId: '',
            employers: employersData,

            get filteredEmployers() {
                if (this.search === '') return this.employers;
                const term = this.search.toLowerCase();
                return this.employers.filter(e => e.search_str.includes(term));
            },

            selectEmployer(emp) {
                this.selectedId = emp.id;
                this.search = emp.name_th || emp.name_en;
                this.open = false;

                document.getElementById('modal_employer_id').value = emp.id;

                // Dispatch legacy event for other parts (if needed)
                window.dispatchEvent(new CustomEvent('set-employer-id', {
                    detail: { id: emp.id }
                }));
            },

            resetSearch() {
                this.search = '';
                this.selectedId = '';
                this.open = false;
                document.getElementById('modal_employer_id').value = '';
            }
        }
    }

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

        if (!orderId && !employerId) {
            // Global Add Mode
            employerSearchSection.classList.remove('d-none');
            // Reset Alpine Component
            window.dispatchEvent(new CustomEvent('reset-employer-search'));
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
                        let handled = false;

                        if (data.order_ids && Array.isArray(data.order_ids) && data.order_ids.length > 0) {
                             // Multiple Orders (e.g. Resignation List Grouped)
                             let anyUpdated = false;
                             data.order_ids.forEach(id => {
                                 if (window.refreshOrderContent) {
                                     // Check if this order exists in current view
                                     const container = document.getElementById(`order-content-${id}`);
                                     if(container) {
                                         window.refreshOrderContent(id);
                                         anyUpdated = true;
                                     }
                                 }
                             });

                             if (anyUpdated) {
                                 handled = true;
                             }
                        }

                        if (!handled && data.order_id) {
                            if (window.refreshOrderContent) {
                                // Check if visible
                                const container = document.getElementById(`order-content-${data.order_id}`);
                                if (container) {
                                    window.refreshOrderContent(data.order_id);
                                    if (window.updateOrderHeaderStats && data.order_stats) {
                                        window.updateOrderHeaderStats(data.order_id, data.order_stats);
                                    }
                                    handled = true;
                                }
                            }
                        }

                        if (!handled) {
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

</script>
