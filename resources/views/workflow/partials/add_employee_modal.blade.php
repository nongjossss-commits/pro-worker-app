{{-- resources/views/workflow/partials/add_employee_modal.blade.php --}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content" id="addEmployeeForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="employer_id" id="modal_employer_id">
            <input type="hidden" name="work_type_id" id="modal_work_type_id">
            <input type="hidden" name="production_order_id" id="modal_production_order_id"> {{-- For adding to existing --}}
            <input type="hidden" name="is_pre_production" id="add_employee_is_pre_production" value="0">

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
                    </div>

                    {{-- Tab 2: New Manual --}}
                    <div class="tab-pane fade" id="tab-new" role="tabpanel">
                        <div class="alert alert-light border">
                             @include('employees.partials.create_form_partial_content')
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
        document.getElementById('modal_employer_id').value = employerId || '';
        document.getElementById('modal_work_type_id').value = workTypeId || '';
        document.getElementById('modal_production_order_id').value = orderId || ''; // Empty if Global Add

        // Dispatch event to Alpine component in the partial to set/clear employer
        window.dispatchEvent(new CustomEvent('set-employer-id', {
            detail: { id: employerId } // If null/undefined, partial handles it
        }));

        // Setup Import Link with return_to parameter
        const importUrl = `{{ route('employees.import_view') }}?production_id=${orderId || ''}&employer_id=${employerId || ''}&return_to=workflow`;
        document.getElementById('btn-go-import').href = importUrl;

        // Reset UI
        document.querySelectorAll('.section-mode').forEach(el => el.classList.add('d-none'));
        document.getElementById('addEmployeeForm').reset();
        document.getElementById('resigned-results').innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';
        const globalRes = document.getElementById('global-results');
        if(globalRes) globalRes.innerHTML = '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';

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

        // Initialize Image Cropper listeners for New Employee form (Empty prefix)
        // This fixes the image upload bug by ensuring the 'triggerFile' listener is active
        if (window.initEmployeeForm) {
            window.initEmployeeForm('');
        }

        modal.show();
    }

    // AJAX Form Submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addEmployeeForm');
        if(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const btn = document.getElementById('btn-submit-add');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                        // Do NOT set Content-Type for FormData, browser sets it with boundary
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;

                    if(data.success) {
                        // Close Modal
                        bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();

                        // Sweet Alert
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Success") }}',
                            text: data.message || '{{ __("Employees added successfully.") }}',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Dynamic Update
                        if (data.order_id) {
                            // If we added to a known order, refresh it
                            if (window.refreshOrderContent) {
                                window.refreshOrderContent(data.order_id);
                            } else {
                                // Fallback: try to find the container manually
                                const container = document.getElementById(`order-content-${data.order_id}`);
                                if(container) {
                                    // Re-fetch items
                                    const url = `{{ route('workflow.index') }}/${data.order_id}/items`;
                                    fetch(url)
                                        .then(res => res.text())
                                        .then(html => {
                                            container.innerHTML = html;
                                            // Init Alpine if needed (handled by scripts usually)
                                        });
                                }
                            }

                            // Update Header Stats
                            if (window.updateOrderHeaderStats && data.order_stats) {
                                window.updateOrderHeaderStats(data.order_id, data.order_stats);
                            }
                        } else {
                            // Global Add (New Order created) -> Reload Page to show new card
                            if(data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                location.reload();
                            }
                        }
                    } else {
                        // Error (e.g. Validation)
                        let msg = data.message || '{{ __("Something went wrong.") }}';
                        if (data.errors) {
                            msg = Object.values(data.errors).flat().join('<br>');
                        }
                        Swal.fire('{{ __("Error") }}', msg, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
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
