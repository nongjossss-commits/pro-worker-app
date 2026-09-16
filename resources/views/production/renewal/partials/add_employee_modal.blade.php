{{--
    "Add Employee" modal for Renewal Resolution — 3 tabs, modeled on
    resources/views/workflow/partials/add_employee_modal.blade.php:
      1. New Employee      — the existing create form, unchanged, just
                              relocated from the standalone create.blade.php
                              page into a modal tab (native form submit,
                              same POST target, same duplicate-check wiring).
      2. Search Internal    — NEW: search employees already in the system
                              under a chosen employer and pull them into
                              THIS renewal tab (see
                              RenewalController::searchExistingEmployees()/
                              addExisting()). Employer-scoped server-side,
                              not just in the UI.
      3. Import Excel       — links to the existing import flow (already
                              tab-aware after the resolution_tab_id fix in
                              RenewalController::importView()).

    Expects $currentTab in scope (already available on
    production/renewal/index.blade.php). Deliberately queries its OWN full
    employer list below rather than reusing that page's own $employers —
    index()/operations() paginates $employers down to just the employers
    with employees already visible in the CURRENT tab (RenewalController.php
    ~line 516), which is exactly wrong for both tabs here: "New Employee"
    must be able to pick ANY employer (matching RenewalController::create()'s
    own unpaginated Employer::orderBy(...)->get()), and "Search Internal"'s
    whole purpose is finding employees under an employer who may have NOBODY
    in this tab yet.
--}}
@php
    $renewalModalAllEmployers = \App\Models\Employer::orderBy('employerNameTh')->get();
@endphp
<div class="modal fade" id="renewalAddEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Employee') }} (Renewal Resolution)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="renewalAddEmployeeTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="renewal-new-tab" data-bs-toggle="tab" data-bs-target="#renewal-tab-new" type="button" role="tab">{{ __('New Employee') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="renewal-existing-tab" data-bs-toggle="tab" data-bs-target="#renewal-tab-existing" type="button" role="tab">{{ __('Search Internal') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="renewal-import-tab" data-bs-toggle="tab" data-bs-target="#renewal-tab-import" type="button" role="tab">{{ __('Import Excel') }}</button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: New Employee — reuses the exact same form/route
                         as the old standalone create page, so store()'s
                         validation/creation logic and duplicate-check are
                         entirely untouched. --}}
                    <div class="tab-pane fade show active" id="renewal-tab-new" role="tabpanel">
                        <form action="{{ route('production.renewal.store', ['resolutionTab' => $currentTab->id]) }}" method="POST" enctype="multipart/form-data"
                            data-duplicate-check-url="{{ route('employees.check_duplicate') }}"
                            data-duplicate-model-type="employee"
                            data-duplicate-fields='["employeePassport","employeeWorkPermit","pinkCardNo","employee_id_number","name_list_number","employeeEmail"]'
                            data-duplicate-label-title="{{ __('Duplicate data found') }}"
                            data-duplicate-label-proceed="{{ __('Save anyway') }}"
                            data-duplicate-label-fix="{{ __('Cancel, fix data first') }}"
                            data-duplicate-label-ok="{{ __('OK') }}"
                            data-duplicate-label-terminated="{{ __('Terminated') }}">
                            @csrf

                            @include('employees.partials.create_form_partial_content', ['employers' => $renewalModalAllEmployers])

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">{{ __('Save Employee') }}</button>
                            </div>
                        </form>
                    </div>

                    {{-- Tab 2: Search Internal — new feature. Employer is
                         either locked (opened from an employer's card) or
                         must be picked first (opened from the top button). --}}
                    <div class="tab-pane fade" id="renewal-tab-existing" role="tabpanel">
                        <div id="renewalExistingEmployerLocked" class="alert alert-secondary d-none py-2 mb-3">
                            {{ __('Employer') }}: <strong id="renewalExistingEmployerLockedName"></strong>
                        </div>

                        @php
                            $renewalModalEmployerList = $renewalModalAllEmployers->map(fn($e) => [
                                'id' => $e->id,
                                'name_th' => $e->employerNameTh,
                                'name_en' => $e->employerNameEn,
                                'search_str' => strtolower(($e->employerNameTh ?? '') . ' ' . ($e->employerNameEn ?? '')),
                            ])->values();
                        @endphp
                        <div id="renewalExistingEmployerPicker" class="mb-3 d-none"
                             x-data="renewalEmployerSelector(@js($renewalModalEmployerList))"
                             @click.outside="open = false">
                            <label class="form-label fw-bold">{{ __('Select Employer') }} <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" placeholder="{{ __('Type to search employer...') }}"
                                           x-model="search" @focus="open = true" @input="open = true" autocomplete="off">
                                </div>
                                <div class="card position-absolute w-100 shadow-sm mt-1 border-0" style="z-index: 1055; max-height: 250px; overflow-y: auto;"
                                     x-show="open && filteredEmployers.length > 0" x-transition x-cloak>
                                    <ul class="list-group list-group-flush">
                                        <template x-for="emp in filteredEmployers" :key="emp.id">
                                            <li class="list-group-item list-group-item-action cursor-pointer" @click="pick(emp)">
                                                <div class="fw-bold" x-text="emp.name_th || emp.name_en"></div>
                                                <div class="small text-muted" x-text="emp.name_en"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="renewalExistingSearchSection" class="d-none">
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="renewal-existing-search-input"
                                       placeholder="{{ __('Search by name, passport, RA number, work permit no., ID number, employee code...') }}">
                            </div>
                            <div class="list-group overflow-auto custom-scrollbar" style="max-height: 320px;" id="renewal-existing-results">
                                <div class="text-center text-muted py-3">{{ __('Type to search...') }}</div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-primary" id="renewal-add-existing-btn" disabled>
                                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add Selected') }} (<span id="renewal-existing-selected-count">0</span>)
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Import Excel — reuses the existing import flow,
                         already tab-aware after the resolution_tab_id fix. --}}
                    <div class="tab-pane fade" id="renewal-tab-import" role="tabpanel">
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark-spreadsheet fs-1 text-success mb-3"></i>
                            <h4>{{ __('Import from Excel') }}</h4>
                            <p class="text-muted">{{ __('Upload an Excel file to add multiple employees at once.') }}</p>
                            <a href="{{ route('production.renewal.import', ['resolutionTab' => $currentTab->id]) }}" class="btn btn-success px-4 py-2">
                                <i class="bi bi-upload me-2"></i> {{ __('Go to Import Page') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    function renewalEmployerSelector(employersData) {
        return {
            search: '',
            open: false,
            employers: Array.isArray(employersData) ? employersData : [],
            get filteredEmployers() {
                if (this.search === '') return this.employers;
                const term = this.search.toLowerCase();
                return this.employers.filter(e => e.search_str.includes(term));
            },
            pick(emp) {
                this.open = false;
                window.renewalSetExistingEmployer(emp.id, emp.name_th || emp.name_en);
            },
        };
    }

    (function () {
        let currentEmployerId = null;
        let searchTimeout = null;
        const selectedIds = new Set();

        window.renewalSetExistingEmployer = function (employerId, employerName) {
            currentEmployerId = employerId;
            selectedIds.clear();
            updateSelectedCount();

            document.getElementById('renewalExistingEmployerPicker').classList.add('d-none');
            const lockedBox = document.getElementById('renewalExistingEmployerLocked');
            lockedBox.classList.remove('d-none');
            document.getElementById('renewalExistingEmployerLockedName').textContent = employerName || '';

            document.getElementById('renewalExistingSearchSection').classList.remove('d-none');
            document.getElementById('renewal-existing-results').innerHTML =
                '<div class="text-center text-muted py-3">{{ __("Type to search...") }}</div>';
        };

        window.openRenewalAddEmployeeModal = function (employerId, employerName) {
            // Reset UI first — same "reset then set" ordering as Workflow's
            // modal, since form.reset() would otherwise wipe values set
            // before it.
            document.getElementById('renewalExistingEmployerLocked').classList.add('d-none');
            document.getElementById('renewalExistingEmployerPicker').classList.add('d-none');
            document.getElementById('renewalExistingSearchSection').classList.add('d-none');
            document.getElementById('renewal-existing-search-input').value = '';
            document.getElementById('renewal-add-existing-btn').disabled = true;
            selectedIds.clear();
            updateSelectedCount();

            const forms = document.querySelectorAll('#renewalAddEmployeeModal form');
            forms.forEach(f => f.reset());

            // Re-fill the New Employee tab's employer field (via the same
            // set-employer-id event create_form_partial_content.blade.php
            // already listens for).
            window.dispatchEvent(new CustomEvent('set-employer-id', { detail: { id: employerId } }));

            currentEmployerId = employerId || null;
            if (employerId) {
                window.renewalSetExistingEmployer(employerId, employerName);
            } else {
                document.getElementById('renewalExistingEmployerPicker').classList.remove('d-none');
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('renewalAddEmployeeModal'));
            const newTab = new bootstrap.Tab(document.querySelector('#renewal-new-tab'));
            newTab.show();
            modal.show();
        };

        function updateSelectedCount() {
            document.getElementById('renewal-existing-selected-count').textContent = selectedIds.size;
            document.getElementById('renewal-add-existing-btn').disabled = selectedIds.size === 0;
        }

        // Guards against out-of-order responses: two successive searches
        // (e.g. fast typing) can have their fetch() responses arrive in
        // the wrong order — without this, an older, broader search's
        // results could land AFTER a newer, more specific search's and
        // silently overwrite it. Only the response matching the latest
        // request actually seen is ever rendered.
        let searchRequestSeq = 0;

        function fetchExistingEmployees(query) {
            if (!currentEmployerId) return;
            const requestId = ++searchRequestSeq;
            const container = document.getElementById('renewal-existing-results');
            container.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm text-primary"></span></div>';

            const url = `/production/renewal/{{ $currentTab->id }}/employer/${currentEmployerId}/search-existing?q=${encodeURIComponent(query)}`;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    if (requestId !== searchRequestSeq) return; // a newer search superseded this one
                    container.innerHTML = '';
                    if (data.length === 0) {
                        container.innerHTML = '<div class="text-center text-muted py-2">{{ __("No employees found.") }}</div>';
                        return;
                    }
                    data.forEach(emp => {
                        const item = document.createElement('label');
                        item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 cursor-pointer';
                        item.innerHTML = `
                            <input class="form-check-input flex-shrink-0 renewal-existing-cb" type="checkbox" value="${emp.id}" ${selectedIds.has(String(emp.id)) ? 'checked' : ''}>
                            <img src="${emp.photo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(emp.employeeNameEn || emp.employeeNameTh || 'User') + '&color=FFFFFF&background=F97316&size=128'}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="Photo">
                            <div class="flex-grow-1">
                                <div class="fw-bold">${emp.employeeNameTh || emp.employeeNameEn || '-'} ${emp.employeeNameTh && emp.employeeNameEn ? '(' + emp.employeeNameEn + ')' : ''}</div>
                                <div class="small text-muted">Passport: ${emp.employeePassport || '-'} &middot; RA: ${emp.name_list_number || '-'}</div>
                                <div class="small text-muted">{{ __('Status') }}: ${emp.status || '-'}</div>
                            </div>
                        `;
                        const cb = item.querySelector('.renewal-existing-cb');
                        cb.addEventListener('change', function () {
                            if (this.checked) selectedIds.add(this.value);
                            else selectedIds.delete(this.value);
                            updateSelectedCount();
                        });
                        container.appendChild(item);
                    });
                })
                .catch(() => {
                    if (requestId !== searchRequestSeq) return;
                    container.innerHTML = '<div class="text-center text-danger py-2">{{ __("Error loading employees.") }}</div>';
                });
        }

        const searchInput = document.getElementById('renewal-existing-search-input');
        if (searchInput) {
            searchInput.addEventListener('focus', function (e) {
                if (!this.dataset.fetched) {
                    fetchExistingEmployees(e.target.value);
                    this.dataset.fetched = 'true';
                }
            });
            searchInput.addEventListener('input', function (e) {
                clearTimeout(searchTimeout);
                const query = e.target.value;
                searchTimeout = setTimeout(() => fetchExistingEmployees(query), 400);
            });
        }

        const addBtn = document.getElementById('renewal-add-existing-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                if (!currentEmployerId || selectedIds.size === 0) return;
                const originalText = addBtn.innerHTML;
                addBtn.disabled = true;
                addBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __("Processing...") }}';

                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('employer_id', currentEmployerId);
                selectedIds.forEach(id => formData.append('employee_ids[]', id));

                fetch('{{ route("production.renewal.add_existing", ["resolutionTab" => $currentTab->id]) }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData,
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: '{{ __("Success") }}', text: data.message, timer: 1800, showConfirmButton: false });
                            }
                            setTimeout(() => window.location.reload(), 600);
                        } else {
                            addBtn.disabled = false;
                            addBtn.innerHTML = originalText;
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('{{ __("Error") }}', data.message || '{{ __("Something went wrong.") }}', 'error');
                            }
                        }
                    })
                    .catch(() => {
                        addBtn.disabled = false;
                        addBtn.innerHTML = originalText;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('{{ __("Error") }}', '{{ __("Network error or server error.") }}', 'error');
                        }
                    });
            });
        }
    })();
</script>
