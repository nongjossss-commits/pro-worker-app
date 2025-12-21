@extends('layouts.app')

@section('title', __('Import Employees'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Import Employees from Excel') }}</h5>
                </div>
                <div class="card-body p-4">

                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <strong>{{ __('Import completed with some errors:') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            {{ __('Use this feature to create multiple employees at once by uploading an Excel file (.xlsx, .xlsm).') }}<br>
                            {{ __('Please download the template below, fill in the data, and upload it back.') }}
                            <br>
                            <small class="text-muted">{{ __('Note: You can import photos by inserting them into the "Photo" column in the Excel file.') }}</small>
                        </div>
                    </div>

                    <div class="mb-4 text-center">
                        <a href="{{ route('employees.template') }}" class="btn btn-outline-primary">
                            <i class="bi bi-download me-2"></i>{{ __('Download Excel Template') }}
                        </a>
                    </div>

                    <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @if(isset($production) && $production)
                            <div class="alert alert-primary mb-4">
                                <i class="bi bi-diagram-3-fill me-2"></i>
                                <strong>Importing for Project:</strong> {{ $production->project_name }}
                                <br><small>Employees will be added with "Pending Confirmation" status.</small>
                            </div>
                            <input type="hidden" name="production_id" value="{{ $production->id }}">
                        @endif

                        @if(request('target_status'))
                            <input type="hidden" name="target_status" value="{{ request('target_status') }}">
                        @endif

                        <div class="mb-4">
                            <label for="employer_id" class="form-label fw-bold required">{{ __('Select Employer') }}</label>

                            @php
                                $isLocked = false;
                                $selectedEmployerId = old('employer_id');

                                if(isset($production) && $production->type === 'employer') {
                                    $isLocked = true;
                                    $selectedEmployerId = $production->employer_id;
                                }
                            @endphp

                            @if($isLocked)
                                <input type="hidden" name="employer_id" value="{{ $selectedEmployerId }}">
                                <input type="text" class="form-control bg-light" value="{{ $production->employer->employerNameTh ?? '' }} ({{ $production->employer->employerNameEn ?? '' }})" readonly>
                            @else
                                <div x-data="importEmployerSelector()">
                                    {{-- Hidden Input for Form Submission --}}
                                    <input type="hidden" name="employer_id" :value="selectedId" required>

                                    {{-- Searchable Dropdown --}}
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text"
                                                   class="form-control @error('employer_id') is-invalid @enderror"
                                                   placeholder="{{ __('Type to search employer...') }}"
                                                   x-model="search"
                                                   @focus="open = true"
                                                   @click.away="open = false"
                                                   @keydown.escape="open = false"
                                                   :class="{'is-invalid': !selectedId && touched}">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                        </div>

                                        {{-- Selected Display --}}
                                        <div class="form-text text-success fw-bold mt-1" x-show="selectedName">
                                            <i class="bi bi-check-circle-fill me-1"></i> {{ __('Selected') }}: <span x-text="selectedName"></span>
                                        </div>

                                        {{-- Dropdown List --}}
                                        <div class="card position-absolute w-100 shadow-sm mt-1 border-0"
                                             style="z-index: 1050; max-height: 250px; overflow-y: auto;"
                                             x-show="open"
                                             x-transition
                                             style="display: none;">
                                            <ul class="list-group list-group-flush">
                                                <template x-for="emp in filteredEmployers" :key="emp.id">
                                                    <li class="list-group-item list-group-item-action cursor-pointer"
                                                        @click="selectEmployer(emp)">
                                                        <div class="fw-bold" x-text="emp.name_th"></div>
                                                        <div class="small text-muted" x-text="emp.name_en"></div>
                                                    </li>
                                                </template>
                                                <li class="list-group-item text-muted text-center" x-show="filteredEmployers.length === 0">
                                                    {{ __('No results found') }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    function importEmployerSelector() {
                                        return {
                                            search: '',
                                            open: false,
                                            selectedId: '{{ $selectedEmployerId }}',
                                            selectedName: '',
                                            touched: false,
                                            employers: @json($employers->map(fn($e) => [
                                                'id' => $e->id,
                                                'name_th' => $e->employerNameTh,
                                                'name_en' => $e->employerNameEn,
                                                'search_str' => strtolower($e->employerNameTh . ' ' . $e->employerNameEn)
                                            ])),

                                            init() {
                                                if (this.selectedId) {
                                                    const found = this.employers.find(e => e.id == this.selectedId);
                                                    if (found) {
                                                        this.selectEmployer(found, false);
                                                    }
                                                }
                                            },

                                            get filteredEmployers() {
                                                if (this.search === '') return this.employers;
                                                const term = this.search.toLowerCase();
                                                return this.employers.filter(e => e.search_str.includes(term));
                                            },

                                            selectEmployer(emp, close = true) {
                                                this.selectedId = emp.id;
                                                this.selectedName = emp.name_th + ' (' + emp.name_en + ')';
                                                this.search = emp.name_th;
                                                if(close) this.open = false;
                                                this.touched = true;
                                            }
                                        }
                                    }
                                </script>
                            @endif

                            @error('employer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('All imported employees will be assigned to this employer.') }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold required">{{ __('Upload File (Excel)') }}</label>
                            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx, .xls, .xlsm" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            @if(isset($back_route))
                                <a href="{{ $back_route }}" class="btn btn-light">{{ __('Back') }}</a>
                            @elseif(isset($production))
                                <a href="{{ route('production.edit', $production->id) }}" class="btn btn-light">{{ __('Back to Project') }}</a>
                            @else
                                <a href="{{ route('employees.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            @endif

                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-upload me-2"></i>{{ __('Import Employees') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Imported Employees Summary Modal --}}
@if(isset($sessionImportedEmployees) && $sessionImportedEmployees->isNotEmpty())
@php
    $importedIds = $sessionImportedEmployees->pluck('id')->toArray();
@endphp
<div class="modal fade" id="importedEmployeesModal" tabindex="-1" aria-labelledby="importedEmployeesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="importedEmployeesModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ __('Import Successful') }}
                </h5>
                {{-- Disable close button to force explicit action --}}
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    {{ __('Successfully imported') }} <strong>{{ $sessionImportedEmployees->count() }}</strong> {{ __('employees.') }}
                    {{ __('Please review the list below. You can edit individual records or use Advanced Edit for bulk changes.') }}
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-bold text-secondary">
                        <span id="selected-count">0</span> {{ __('Selected') }}
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary" id="btn-advanced-edit" disabled>
                            <i class="bi bi-ui-checks-grid me-1"></i> {{ __('Advanced Edit (Bulk)') }}
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="import-table">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="select-all-import"></th>
                                <th style="width: 80px;">{{ __('Photo') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Nationality') }}</th>
                                <th>{{ __('Passport No.') }}</th>
                                <th>{{ __('Work Permit') }}</th>
                                <th style="width: 100px;">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="import-table-body">
                            @foreach($sessionImportedEmployees as $employee)
                                @include('employees.partials._import_table_row', ['employee' => $employee])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="btn-cancel-import">
                    <i class="bi bi-x-circle me-1"></i> {{ __('Cancel Import') }}
                </button>
                @if(session('finish_route'))
                    <a href="{{ session('finish_route') }}" class="btn btn-primary px-4">{{ __('Finish Import') }}</a>
                @elseif(isset($back_route)) {{-- Fallback if passed directly --}}
                    <a href="{{ $back_route }}" class="btn btn-primary px-4">{{ __('Finish Import') }}</a>
                @elseif(session('production_id'))
                    <a href="{{ route('production.edit', session('production_id')) }}" class="btn btn-primary px-4">{{ __('Finish & Return to Project') }}</a>
                @else
                    <a href="{{ route('employees.index') }}" class="btn btn-primary px-4">{{ __('Finish Import') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Generic Action Modal (Nested) --}}
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Store IDs for JS usage
    window.importedEmployeeIds = @json($importedIds);
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Main Modal
    var importedModalEl = document.getElementById('importedEmployeesModal');
    var importedModal = new bootstrap.Modal(importedModalEl);
    importedModal.show();

    // 2. Variable Definitions
    const selectAllCheckbox = document.getElementById('select-all-import');
    const tableBody = document.getElementById('import-table-body');
    const advancedEditBtn = document.getElementById('btn-advanced-edit');
    const selectedCountSpan = document.getElementById('selected-count');
    const actionModalEl = document.getElementById('actionModal');
    const actionModal = new bootstrap.Modal(actionModalEl);
    const actionModalBody = document.getElementById('actionModalBody');
    const actionModalTitle = document.getElementById('actionModalTitle');
    const cancelImportBtn = document.getElementById('btn-cancel-import');

    // 3. Selection Logic
    function updateSelectionState() {
        const checkboxes = document.querySelectorAll('.import-checkbox');
        const checked = document.querySelectorAll('.import-checkbox:checked');
        selectedCountSpan.textContent = checked.length;
        advancedEditBtn.disabled = checked.length === 0;

        const allChecked = checkboxes.length > 0 && checked.length === checkboxes.length;
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = checked.length > 0 && !allChecked;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.import-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectionState();
        });
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('import-checkbox')) {
            updateSelectionState();
        }
    });

    // 4. Refresh Table Function
    window.refreshImportTable = function() {
        if (!window.importedEmployeeIds || window.importedEmployeeIds.length === 0) return;

        fetch('{{ route("employees.fetch_batch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: window.importedEmployeeIds })
        })
        .then(response => response.json())
        .then(data => {
            // Update rows
            for (const [id, html] of Object.entries(data.rows)) {
                const row = document.getElementById(`row-${id}`);
                if (row) {
                    row.outerHTML = html;
                }
            }
            // Re-bind listeners? Handled by delegation or re-query.
            // Restore selection if possible, or just clear it.
            // For simplicity, we might clear selection to avoid stale state.
            updateSelectionState();
        })
        .catch(error => console.error('Error refreshing table:', error));
    };

    // 5. Individual Edit Logic
    tableBody.addEventListener('click', function(e) {
        // Find closest button for edit
        const editBtn = e.target.closest('.btn-edit-individual');

        // Handle Delete Button
        const deleteBtn = e.target.closest('.btn-delete-individual');

        if (deleteBtn) {
            const employeeId = deleteBtn.dataset.id;

            Swal.fire({
                title: '{{ __("Move to Trash?") }}',
                text: '{{ __("Are you sure you want to delete this employee? They will be moved to the Trash.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __("Yes, delete it!") }}',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/employees/${employeeId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (response.ok) return response.json();
                        throw new Error('Network response was not ok');
                    })
                    .then(data => {
                        // Remove row from table
                        const row = document.getElementById(`row-${employeeId}`);
                        if (row) row.remove();

                        // Remove from imported IDs list if we want to keep it consistent,
                        // though cancellation uses the original full list.
                        // It's safer to keep the ID in window.importedEmployeeIds
                        // or filter it out. Let's filter it out to avoid errors on "Cancel Import".
                        if (window.importedEmployeeIds) {
                            window.importedEmployeeIds = window.importedEmployeeIds.filter(id => id != employeeId);
                        }

                        updateSelectionState();

                        Swal.fire(
                            '{{ __("Deleted!") }}',
                            '{{ __("Employee has been moved to trash.") }}',
                            'success'
                        );
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            '{{ __("Error!") }}',
                            '{{ __("Failed to delete employee.") }}',
                            'error'
                        );
                    });
                }
            });
            return;
        }

        if (!editBtn) return;

        const employeeId = editBtn.dataset.id;
        actionModalTitle.textContent = '{{ __("Edit Employee") }}';
        actionModalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        actionModal.show();

        // Z-Index fix for nested modal
        // importedModalEl.style.zIndex = 1040; // Default is 1055, keep it behind
        // actionModalEl.style.zIndex = 1060;

        fetch(`/employees/${employeeId}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            // Extract content-section if possible, or just dump html.
            // Since we need to strip layout, we might need a DOMParser
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('.content-section') || doc.body;

            // Remove back buttons and adjust styling
            const backBtns = content.querySelectorAll('a.btn-secondary, button[onclick="history.back();"]');
            backBtns.forEach(b => b.remove());

            actionModalBody.innerHTML = '';
            actionModalBody.appendChild(content);

            // Re-initialize scripts (datepicker, etc) if needed
            // NOTE: AlpineJS or inline scripts in the fetched view won't run automatically via innerHTML.
            // We might need to manually execute scripts.
            const scripts = content.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                actionModalBody.appendChild(newScript);
            });

            // Re-initialize the Edit Form interactive logic
            if (typeof window.initEmployeeEditForm === 'function') {
                window.initEmployeeEditForm();
            }
        });
    });

    // 6. Advanced Edit (Bulk) Logic
    advancedEditBtn.addEventListener('click', function() {
        const checked = document.querySelectorAll('.import-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);

        actionModalTitle.textContent = '{{ __("Advanced Edit (Select Fields)") }}';
        actionModalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        actionModal.show();

        // Step 1: Get Field Selector
        fetch('{{ route("employees.bulk_edit.select_fields") }}', { // We use POST for this usually?
            method: 'POST', // Check route definition, usually GET or POST.
            // Wait, bulkEditSelectFields in controller takes Request.
            // Route usually: Route::match(['get', 'post'], ...)
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ employee_ids: ids })
        })
        .then(response => response.text())
        .then(html => {
             const parser = new DOMParser();
             const doc = parser.parseFromString(html, 'text/html');
             const content = doc.getElementById('bulk-edit-selector-wrapper') || doc.querySelector('.container-fluid') || doc.body;

             // Hijack form submit
             const form = content.querySelector('form');
             if(form) {
                 form.addEventListener('submit', function(e) {
                     e.preventDefault();
                     e.stopPropagation();

                     // Disable button to prevent double submit
                     const btn = form.querySelector('button[type="submit"]');
                     if(btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
                     }

                     loadBulkEditForm(new FormData(form));
                 });
             }

             actionModalBody.innerHTML = '';
             actionModalBody.appendChild(content);

             // Re-run scripts from the fetched content to initialize event listeners (e.g., enable/disable Proceed button)
             const scripts = content.querySelectorAll('script');
             scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                actionModalBody.appendChild(newScript);
             });
        });
    });

    function loadBulkEditForm(formData) {
        actionModalTitle.textContent = '{{ __("Advanced Edit (Bulk Form)") }}';
        actionModalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

        // Convert FormData to JSON or URLSearchParams?
        // Controller expects standard request inputs.
        // We can send JSON.
        const object = {};
        formData.forEach((value, key) => {
            // Handle array inputs like selected_fields[]
            if(key.endsWith('[]')) {
                const k = key.slice(0, -2);
                if(!object[k]) object[k] = [];
                object[k].push(value);
            } else {
                object[key] = value;
            }
        });

        fetch('{{ route("employees.bulk_edit.form") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(object)
        })
        .then(response => response.text())
        .then(html => {
             const parser = new DOMParser();
             const doc = parser.parseFromString(html, 'text/html');
             const content = doc.getElementById('bulk-edit-wrapper') || doc.querySelector('.container-fluid') || doc.body;

             // Remove layout clutter (like Fixed Bottom bar wrapper if it causes issues in modal)
             // The view has `fixed-bottom`. In a modal, this might be weird.
             // We can change its class to `sticky-bottom` or just static.
             const bottomBar = content.querySelector('.fixed-bottom');
             if(bottomBar) {
                 bottomBar.classList.remove('fixed-bottom');
                 bottomBar.classList.add('mt-4', 'border-top', 'pt-3');
             }

             // DISABLE SAVE BUTTON TEMPORARILY to prevent phantom clicks or double events
             const saveBtn = content.querySelector('button[type="submit"]');
             if(saveBtn) {
                 saveBtn.disabled = true;
                 setTimeout(() => {
                     saveBtn.disabled = false;
                 }, 800); // 800ms delay safety
             }

             // Define success callback for the bulk edit form script
             window.onBulkEditSuccess = function() {
                actionModal.hide();
                refreshImportTable();
                if(typeof showToast === 'function') {
                    showToast('Bulk update successful', 'success');
                } else {
                    alert('Bulk update successful');
                }
             };

             actionModalBody.innerHTML = '';
             actionModalBody.appendChild(content);

             // Re-run scripts (for master controls)
             const scripts = content.querySelectorAll('script');
             scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                actionModalBody.appendChild(newScript);
             });
        });
    }

    function submitBulkUpdate(formData) {
        actionModalBody.innerHTML += '<div class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center"><div class="spinner-border text-primary"></div></div>';

        fetch('{{ route("employees.bulk_update") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData // Send as FormData to handle files if any
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                actionModal.hide();
                refreshImportTable();

                // Show toast or alert
                // Assuming showToast exists globally
                if(typeof showToast === 'function') {
                    showToast('Bulk update successful', 'success');
                } else {
                    alert('Bulk update successful');
                }
            } else {
                alert('Update failed');
                // Remove spinner overlay
                 const overlay = actionModalBody.lastElementChild;
                 if(overlay) overlay.remove();
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred');
             const overlay = actionModalBody.lastElementChild;
             if(overlay) overlay.remove();
        });
    }

    // 7. Intercept Individual Edit Form Submit
    actionModalEl.addEventListener('submit', function(e) {
        if(e.target.tagName === 'FORM' && e.target.getAttribute('action').includes('/employees/')) {
            // Check if it's the bulk form (already handled) or individual
            if(!e.target.action.includes('bulk_update') && !e.target.action.includes('bulk_edit')) {
                e.preventDefault();

                const form = e.target;
                const formData = new FormData(form);

                // Add loading
                 // Find submit button
                const btn = form.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        return response.json().catch(() => ({ success: true })); // Handle empty JSON or redirect
                    }
                    throw new Error('Network response was not ok');
                })
                .then(data => {
                    actionModal.hide();
                    refreshImportTable();
                     if(typeof showToast === 'function') {
                        showToast('Employee updated successfully', 'success');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error saving data. Please check required fields.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
        }
    });

    // 8. Cancel Import Logic
    if (cancelImportBtn) {
        cancelImportBtn.addEventListener('click', function() {
            if (!window.importedEmployeeIds || window.importedEmployeeIds.length === 0) {
                // If list is empty (e.g. all individually deleted), just close or reload
                window.location.reload();
                return;
            }

            Swal.fire({
                title: '{{ __("Cancel Import?") }}',
                text: '{{ __("This will permanently delete all imported employees from this session. This action cannot be undone.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __("Yes, delete all!") }}',
                cancelButtonText: '{{ __("No, keep them") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    const originalText = cancelImportBtn.innerHTML;
                    cancelImportBtn.disabled = true;
                    cancelImportBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

                    fetch('{{ route("employees.import.cancel") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ ids: window.importedEmployeeIds })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                '{{ __("Cancelled!") }}',
                                data.message,
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            '{{ __("Error!") }}',
                            '{{ __("Failed to cancel import.") }}',
                            'error'
                        );
                        cancelImportBtn.disabled = false;
                        cancelImportBtn.innerHTML = originalText;
                    });
                }
            });
        });
    }
});
</script>
@endpush
@endif

@endsection
