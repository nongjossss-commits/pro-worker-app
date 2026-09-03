@if($employees->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
        <p class="mt-2">{{ __('No appointments found for this date.') }}</p>
    </div>
@else

    {{-- ═══ Search Bar (inline) ═══ --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 bg-white border rounded shadow-sm">
        @can('edit-employees')
        <div class="form-check mb-0 me-1">
            <input class="form-check-input" type="checkbox" id="appt-inline-select-all" style="width:18px; height:18px; cursor:pointer;">
            <label class="form-check-label small fw-bold text-nowrap" for="appt-inline-select-all" style="cursor:pointer;">
                {{ __('Select All') }}
            </label>
        </div>
        <div class="vr"></div>
        @endcan
        <div class="input-group" style="min-width: 220px; flex: 1;">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" id="appt-search-input"
                   class="form-control border-start-0 border-end-0 bg-light"
                   placeholder="{{ __('Search name, passport, reference, RA, employer...') }}">
            <button class="btn btn-light border" type="button" id="appt-search-btn">
                <i class="bi bi-funnel"></i>
            </button>
            <button class="btn btn-outline-secondary d-none" type="button" id="appt-search-clear-btn">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <span class="text-muted small ms-1" id="appt-result-count">{{ $employees->count() }} {{ __('record(s)') }}</span>
    </div>

    {{-- ═══ Floating Selection Toolbar ═══ --}}
    @can('edit-employees')
    <div class="bulk-action-bar align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
         id="appt-toolbar"
         style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="appt-select-all-cb">
            <label class="form-check-label fw-bold small" for="appt-select-all-cb">
                {{ __('Select All') }} (<span id="appt-selected-count">0</span>)
            </label>
        </div>
        <div class="vr mx-1"></div>
        <div class="dropdown">
            <button class="btn btn-sm btn-secondary dropdown-toggle" id="appt-bulk-btn"
                    type="button" data-bs-toggle="dropdown" disabled>
                <i class="bi bi-lightning-fill me-1"></i>{{ __('Actions') }}
            </button>
            <ul class="dropdown-menu shadow">
                <li><a class="dropdown-item appt-action" data-action="advanced-edit" href="#">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('Advanced Edit') }}</a></li>
                <li><a class="dropdown-item appt-action" data-action="advanced-export" href="#">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>{{ __('Advanced Export') }}</a></li>
                <li><a class="dropdown-item appt-action" data-action="download" href="#">
                    <i class="bi bi-download me-2 text-info"></i>{{ __('Download Files') }}</a></li>
                @can('manage-tickets')
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item appt-action" data-action="generate-pdf" href="#">
                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ __('Automated PDF') }}</a></li>
                @endcan
            </ul>
        </div>
        <button class="btn btn-sm btn-outline-danger" id="appt-clear-btn" disabled
                onclick="if(window.clearGlobalSelection) window.clearGlobalSelection();">
            <i class="bi bi-x-circle me-1"></i>{{ __('Clear Selection') }}
        </button>
        <button class="btn btn-sm btn-info text-white" id="appt-view-btn" disabled
                onclick="if(window.openViewSelectedModal) window.openViewSelectedModal();">
            <i class="bi bi-eye me-1"></i>{{ __('View Selected') }}
            <span class="badge bg-white text-info ms-1" id="appt-view-count">0</span>
        </button>
    </div>
    @endcan

    {{-- ═══ Cards ═══ --}}
    <div class="row g-3">
        @foreach($employees as $employee)
        @php
            $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
            $reqNo = $employee->registration_request_number ?? $employee->renewal_request_number ?? $employee->request_number ?? null;
        @endphp
        <div class="col-12 appt-card-item"
             data-search="{{ strtolower(implode(' ', array_filter([
                 $employee->employeeNameTh,
                 $employee->employeeNameEn,
                 $employee->employeeTitleTh,
                 $employee->employeeTitleEn,
                 $employee->employeePassport,
                 $employee->employee_reference_id,
                 $employee->request_number,
                 $employee->registration_request_number,
                 $employee->renewal_request_number,
                 $employee->employer_employee_id,
                 $employee->employer->employerNameTh ?? '',
                 $employee->employer->employerNameEn ?? '',
             ]))) }}">
            <div class="card border-0 shadow-sm overflow-hidden position-relative">
                <div class="position-absolute top-0 start-0 h-100"
                     style="width:5px; background-color:{{ $employee->appointment_completed_at ? '#10B981' : '#F59E0B' }};"></div>
                <div class="card-body py-3 ps-4 pe-3">
                    <div class="d-flex align-items-start gap-3">

                        {{-- Checkbox (ใช้ class employee-checkbox เพื่อให้ app.blade.php จัดการ) --}}
                        @can('edit-employees')
                        <div class="pt-1 flex-shrink-0">
                            <input class="form-check-input employee-checkbox"
                                   type="checkbox"
                                   style="width:18px; height:18px; cursor:pointer;"
                                   value="{{ $employee->id }}"
                                   data-employee-id="{{ $employee->id }}"
                                   data-employer-id="{{ $employee->employer_id }}"
                                   data-name-th="{{ $employee->employeeNameTh ?? '' }}"
                                   data-name-en="{{ $employee->employeeNameEn ?? '' }}"
                                   data-title-th="{{ $employee->employeeTitleTh ?? '' }}"
                                   data-title-en="{{ $employee->employeeTitleEn ?? '' }}"
                                   data-nationality="{{ $employee->employeeNationality ?? '' }}"
                                   data-photo="{{ $employee->employeePhoto ? asset('storage/'.$employee->employeePhoto) : '' }}"
                                   data-employer-name="{{ $employee->employer->employerNameTh ?? '' }}"
                                   data-insurance-type="{{ $employee->insurance_type ?? '' }}"
                                   data-passport="{{ $employee->employeePassport ?? '' }}">
                        </div>
                        @endcan

                        {{-- Photo --}}
                        <div class="flex-shrink-0">
                            @if($employee->employeePhoto)
                                <img src="{{ Storage::disk('public')->url($employee->employeePhoto) }}?v={{ optional($employee->updated_at)->timestamp ?? '0' }}"
                                     class="rounded-circle shadow-sm"
                                     style="width:52px; height:52px; object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm fw-bold"
                                     style="width:52px; height:52px; font-size:1.3rem;">
                                    {{ mb_substr($employee->employeeNameTh ?? $employee->employeeNameEn ?? 'E', 0, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-grow-1" style="min-width:0;">

                            {{-- EN Name + icons --}}
                            <div class="fw-bold text-dark lh-sm d-flex align-items-center gap-1 flex-wrap">
                                <span>{{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? '-' }}</span>
                                <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                        data-model-type="employee" data-model-id="{{ $employee->id }}"
                                        title="{{ __('Preview Employee') }}">
                                    <i class="bi bi-search" style="font-size:0.8rem;"></i>
                                </button>
                            </div>

                            {{-- TH Name --}}
                            <div class="text-muted small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? '-' }}</div>

                            {{-- Detail badges --}}
                            <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.78rem; color:#6c757d;">
                                @if($employee->employeePassport)
                                <span><i class="bi bi-passport text-primary me-1"></i>{{ $employee->employeePassport }}</span>
                                @endif
                                @if($employee->employee_reference_id)
                                <span><i class="bi bi-person-badge me-1"></i>{{ $employee->employee_reference_id }}</span>
                                @endif
                                @if($reqNo)
                                <span><i class="bi bi-file-text me-1"></i>{{ $reqNo }}</span>
                                @endif
                                @if($employee->employeeNationality)
                                <span>
                                    @if($countryCode)
                                        <img src="{{ asset('images/flags/'.strtolower($countryCode).'.png') }}" style="width:15px; height:11px;" class="me-1">
                                    @endif
                                    {{ $employee->employeeNationality }}
                                </span>
                                @endif
                            </div>

                            {{-- Employer --}}
                            <div class="mt-1 d-flex align-items-center gap-1 flex-wrap" style="font-size:0.82rem;">
                                <i class="bi bi-building text-secondary me-1"></i>
                                <span class="fw-semibold">{{ $employee->employer->employerNameTh ?? '-' }}</span>
                                @if($employee->employer->employerNameEn ?? null)
                                    <span class="text-muted">({{ $employee->employer->employerNameEn }})</span>
                                @endif
                                <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                        data-model-type="employer" data-model-id="{{ $employee->employer_id }}"
                                        title="{{ __('Preview Employer') }}">
                                    <i class="bi bi-search" style="font-size:0.78rem;"></i>
                                </button>
                            </div>

                            {{-- Appointment time --}}
                            @if($employee->appointment_date)
                            <div class="mt-1" style="font-size:0.78rem;">
                                <i class="bi bi-clock me-1 {{ $employee->appointment_completed_at ? 'text-success' : 'text-warning' }}"></i>
                                <span class="fw-bold {{ $employee->appointment_completed_at ? 'text-success' : 'text-warning' }}">
                                    {{ \Carbon\Carbon::parse($employee->appointment_date)->format('H:i') }}
                                </span>
                                @if($employee->appointment_location)
                                    <i class="bi bi-geo-alt-fill text-info ms-2 me-1"></i>
                                    <span class="text-info">{{ $employee->appointment_location }}</span>
                                @endif
                                @if($employee->appointment_completed_at)
                                    <span class="badge bg-success ms-1">{{ __('Done') }}</span>
                                @endif
                            </div>
                            @endif
                        </div>

                        {{-- Action buttons (right side) — edit/update/complete all require
                             edit-employees, same as the bulk-select checkbox and floating
                             toolbar elsewhere in this partial; previously rendered
                             unconditionally so a user without the permission still saw
                             live, clickable buttons (backend correctly rejected the click,
                             but the button shouldn't have been shown at all). --}}
                        @can('edit-employees')
                        <div class="flex-shrink-0 d-flex flex-column gap-2 align-items-end">
                            {{-- Edit employee button — opens modal in current tab (no new tab) --}}
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    onclick="window.openEditEmployeeModal({{ $employee->id }})"
                                    title="{{ __('Edit Employee') }}">
                                <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                            </button>
                            {{-- Appointment buttons --}}
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm fw-bold {{ $employee->appointment_completed_at ? 'btn-success' : 'btn-warning text-dark' }}"
                                        onclick="editAppointment({{ $employee->id }}, '{{ $employee->appointment_date ? \Carbon\Carbon::parse($employee->appointment_date)->format('Y-m-d\TH:i') : '' }}', '{{ addslashes($employee->appointment_location ?? '') }}', {{ $employee->appointment_completed_at ? 'true' : 'false' }})">
                                    @if($employee->appointment_completed_at)
                                        <i class="bi bi-check-circle-fill me-1"></i>{{ __('Completed') }}
                                    @else
                                        <i class="bi bi-clock-fill me-1"></i>{{ __('Update') }}
                                    @endif
                                </button>
                                @if(!$employee->appointment_completed_at)
                                <button class="btn btn-sm btn-success fw-bold"
                                        onclick="markAppointmentCompleted({{ $employee->id }}, 'production/registration', this)"
                                        title="{{ __('Mark Completed') }}">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endcan

                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <script>
    (function() {
        // ── Toolbar refs ──
        const floatingBar   = document.getElementById('appt-toolbar');
        const selectAllCb   = document.getElementById('appt-select-all-cb');
        const inlineSelectAllCb = document.getElementById('appt-inline-select-all');
        const countSpan     = document.getElementById('appt-selected-count');
        const viewCountBadge = document.getElementById('appt-view-count');
        const bulkBtn       = document.getElementById('appt-bulk-btn');
        const viewBtn       = document.getElementById('appt-view-btn');
        const clearBtn      = document.getElementById('appt-clear-btn');
        const searchInput   = document.getElementById('appt-search-input');
        const searchBtn     = document.getElementById('appt-search-btn');
        const searchClearBtn = document.getElementById('appt-search-clear-btn');
        const resultCount   = document.getElementById('appt-result-count');
        const cards         = document.querySelectorAll('.appt-card-item');
        const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;

        // ── Toolbar updater — เรียกเมื่อ global selection เปลี่ยน ──
        function syncToolbar() {
            const data  = window.getGlobalSelectedData ? window.getGlobalSelectedData() : [];
            const count = data.length;
            const ids   = data.map(i => String(i.id));

            // Show/hide floating bar
            if (floatingBar) floatingBar.style.display = count > 0 ? 'flex' : 'none';

            if (countSpan) countSpan.textContent = count;
            if (viewCountBadge) viewCountBadge.textContent = count;
            if (bulkBtn)   bulkBtn.disabled   = count === 0;
            if (viewBtn)   viewBtn.disabled   = count === 0;
            if (clearBtn)  clearBtn.disabled  = count === 0;

            // Sync checkboxes ในหน้านี้
            document.querySelectorAll('.employee-checkbox').forEach(cb => {
                cb.checked = ids.includes(String(cb.value));
            });

            // Select All state (sync both floating toolbar and inline checkboxes)
            const allCbs = document.querySelectorAll('.employee-checkbox');
            const visible = Array.from(allCbs).filter(cb =>
                cb.closest('.appt-card-item') && cb.closest('.appt-card-item').style.display !== 'none'
            );
            const checkedVisible = visible.filter(cb => cb.checked).length;
            const isAllChecked = visible.length > 0 && checkedVisible === visible.length;
            const isIndeterminate = checkedVisible > 0 && checkedVisible < visible.length;

            [selectAllCb, inlineSelectAllCb].forEach(cb => {
                if (cb) {
                    cb.checked = isAllChecked;
                    cb.indeterminate = isIndeterminate;
                }
            });
        }

        // ฟัง event ที่ app.blade.php dispatch หลังทุก selection change
        document.addEventListener('global-selection-updated', syncToolbar);

        // ── Select All (shared handler for both floating toolbar and inline) ──
        function handleSelectAll(isChecked) {
            const allCbs = Array.from(document.querySelectorAll('.employee-checkbox'));
            const visible = allCbs.filter(cb =>
                cb.closest('.appt-card-item') && cb.closest('.appt-card-item').style.display !== 'none'
            );
            visible.forEach(cb => {
                cb.checked = isChecked;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() { handleSelectAll(this.checked); });
        }
        if (inlineSelectAllCb) {
            inlineSelectAllCb.addEventListener('change', function() { handleSelectAll(this.checked); });
        }

        // ── Search ──
        function doFilter() {
            const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
            let visible = 0;
            cards.forEach(card => {
                const match = !q || card.dataset.search.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (resultCount)    resultCount.textContent = visible + ' {{ __('record(s)') }}';
            if (searchClearBtn) searchClearBtn.classList.toggle('d-none', !q);
            syncToolbar(); // re-sync select-all state หลัง filter
        }

        if (searchBtn)      searchBtn.addEventListener('click', doFilter);
        if (searchClearBtn) searchClearBtn.addEventListener('click', () => { searchInput.value = ''; doFilter(); });
        if (searchInput) {
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') doFilter(); });
            searchInput.addEventListener('input',   () => { if (!searchInput.value) doFilter(); });
        }

        // ── Bulk Actions ──
        document.querySelectorAll('.appt-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const action = this.dataset.action;
                const ids = window.getGlobalSelectedIds ? window.getGlobalSelectedIds() : [];

                if (!ids.length) {
                    Swal.fire({ toast:true, icon:'warning', title:'{{ __('Please select employees first.') }}', position:'top-end', showConfirmButton:false, timer:2000 });
                    return;
                }

                if (action === 'advanced-edit') {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = '{{ route('employees.bulk_edit.select_fields') }}';
                    [['_token', csrfToken], ['redirect_to', window.location.href]].forEach(([n,v]) => {
                        const i = document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; form.appendChild(i);
                    });
                    ids.forEach(id => { const i=document.createElement('input'); i.type='hidden'; i.name='employee_ids[]'; i.value=id; form.appendChild(i); });
                    document.body.appendChild(form); form.submit();

                } else if (action === 'advanced-export') {
                    const el  = document.getElementById('export_employee_ids');
                    const src = document.getElementById('export_source_menu');
                    if (el) el.value = JSON.stringify(ids);
                    if (src) src.value = 'registration';
                    const modal = document.getElementById('advancedExportModal');
                    if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();

                } else if (action === 'download') {
                    if (window.openBulkDownloadModal) {
                        window.openBulkDownloadModal(ids);
                    } else {
                        const modal = document.getElementById('downloadOptionsModal');
                        if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
                    }

                } else if (action === 'generate-pdf') {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';
                    [['_token', csrfToken], ['redirect_url', window.location.href]].forEach(([n,v]) => {
                        const i = document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; form.appendChild(i);
                    });
                    ids.forEach(id => { const i=document.createElement('input'); i.type='hidden'; i.name='employees[]'; i.value=id; form.appendChild(i); });
                    document.body.appendChild(form); form.submit();
                }
            });
        });

        // ── Init ── restore checkboxes จาก sessionStorage แล้วอัพ toolbar
        if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
        else syncToolbar();

    })();
    </script>

@endif
