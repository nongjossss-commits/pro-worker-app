@if($items->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-calendar-x fs-1 opacity-25"></i>
        <p class="mt-2">{{ __('No appointments found for this date.') }}</p>
    </div>
@else

    {{-- ═══ Toolbar ═══ --}}
    <div class="d-flex flex-wrap align-items-center gap-2 p-2 bg-white border-bottom sticky-top" id="wf-appt-toolbar">
        @can('edit-employees')
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="wf-select-all-cb">
            <label class="form-check-label fw-bold small" for="wf-select-all-cb">
                {{ __('Select All') }} (<span id="wf-selected-count">0</span>)
            </label>
        </div>
        <div class="vr mx-1"></div>

        <div class="dropdown">
            <button class="btn btn-sm btn-secondary dropdown-toggle" id="wf-bulk-btn"
                    type="button" data-bs-toggle="dropdown" disabled>
                <i class="bi bi-lightning-fill me-1"></i>{{ __('Actions') }}
            </button>
            <ul class="dropdown-menu shadow">
                <li><a class="dropdown-item wf-action" data-action="advanced-edit" href="#">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('Advanced Edit') }}</a></li>
                <li><a class="dropdown-item wf-action" data-action="advanced-export" href="#">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>{{ __('Advanced Export') }}</a></li>
                <li><a class="dropdown-item wf-action" data-action="download" href="#">
                    <i class="bi bi-download me-2 text-info"></i>{{ __('Download Files') }}</a></li>
                @can('manage-tickets')
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item wf-action" data-action="generate-pdf" href="#">
                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ __('Automated PDF') }}</a></li>
                @endcan
            </ul>
        </div>

        <button class="btn btn-sm btn-info text-white" id="wf-view-btn" disabled
                onclick="if(window.openViewSelectedModal) window.openViewSelectedModal();">
            <i class="bi bi-eye me-1"></i>{{ __('View Selected') }}
        </button>

        <button class="btn btn-sm btn-outline-danger" id="wf-clear-btn" disabled
                onclick="if(window.clearGlobalSelection) window.clearGlobalSelection();">
            <i class="bi bi-x-circle me-1"></i>{{ __('Clear Selection') }}
        </button>
        <div class="vr mx-1"></div>
        @endcan

        <div class="input-group input-group-sm" style="min-width: 200px; flex: 1;">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" id="wf-search-input"
                   class="form-control border-start-0 border-end-0 bg-light"
                   placeholder="{{ __('Search employee, employer, work type...') }}">
            <button class="btn btn-light border" type="button" id="wf-search-btn">
                <i class="bi bi-funnel"></i>
            </button>
            <button class="btn btn-outline-secondary d-none" type="button" id="wf-search-clear-btn">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <span class="text-muted small ms-1" id="wf-result-count">{{ $items->count() }} {{ __('record(s)') }}</span>
    </div>

    {{-- ═══ Cards ═══ --}}
    <div class="p-3">
    @foreach($items as $item)
    @php
        $employee  = $item->employee;
        $order     = $item->order;
        $employer  = $order->employer ?? null;
        $workType  = $order->workType ?? null;

        $titleEn = $employee->employeeTitleEn ?? '';
        $nameEn  = $employee->employeeNameEn  ?? 'N/A';
        $titleTh = $employee->employeeTitleTh ?? '';
        $nameTh  = $employee->employeeNameTh  ?? '';

        $passport    = $employee->employeePassport    ?? '-';
        $nationality = $employee->employeeNationality ?? '';
        $countryCode = $nationality ? \App\Helpers\CountryHelper::getCountryCode($nationality) : null;

        $photoUrl = ($employee && $employee->employeePhoto)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($employee->employeePhoto)
            : null;
        $initials = mb_substr(($nameTh ?: $nameEn) ?: 'E', 0, 1);

        $isAppCompleted = $item->appointment_completed_at ? true : false;
        $appDate        = $item->appointment_date;
        $appDisplay     = '-';
        if ($appDate) {
            $appDisplay = ($appDate->format('H:i:s') === '00:00:00')
                ? $appDate->format('d/m/Y')
                : $appDate->format('d/m/Y H:i');
        }
        $appDateValue = $appDate ? $appDate->format('Y-m-d H:i') : '';
        $appLocation  = $item->appointment_location ?? '';
    @endphp

    <div class="wf-appt-card-item mb-3"
         data-search="{{ strtolower(implode(' ', array_filter([
             $nameTh, $nameEn, $titleTh, $titleEn,
             $passport, $nationality,
             $employer->employerNameTh ?? '',
             $employer->employerNameEn ?? '',
             $workType->name ?? '',
             $order->project_name ?? '',
         ]))) }}">
        <div class="card border-0 shadow-sm overflow-hidden position-relative">
            <div class="position-absolute top-0 start-0 h-100"
                 style="width:5px; background-color:{{ $isAppCompleted ? '#10B981' : '#F59E0B' }};"></div>
            <div class="card-body py-3 ps-4 pe-3">
                <div class="d-flex align-items-start gap-3">

                    {{-- Checkbox --}}
                    @can('edit-employees')
                    @if($item->employee_id)
                    <div class="pt-1 flex-shrink-0">
                        <input class="form-check-input employee-checkbox"
                               type="checkbox"
                               style="width:18px; height:18px; cursor:pointer;"
                               value="{{ $item->employee_id }}"
                               data-employee-id="{{ $item->employee_id }}"
                               data-employer-id="{{ $employer->id ?? '' }}"
                               data-name-th="{{ $nameTh }}"
                               data-name-en="{{ $nameEn }}"
                               data-title-th="{{ $titleTh }}"
                               data-title-en="{{ $titleEn }}"
                               data-nationality="{{ $nationality }}"
                               data-photo="{{ $photoUrl ?? '' }}"
                               data-employer-name="{{ $employer->employerNameTh ?? '' }}"
                               data-insurance-type="{{ $employee->insurance_type ?? '' }}"
                               data-passport="{{ $passport !== '-' ? $passport : '' }}">
                    </div>
                    @endif
                    @endcan

                    {{-- Photo --}}
                    <div class="flex-shrink-0 position-relative" style="width:52px; height:52px;">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}"
                                 loading="lazy"
                                 class="rounded-circle shadow-sm"
                                 style="width:52px; height:52px; object-fit:cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="rounded-circle bg-secondary text-white align-items-center justify-content-center shadow-sm fw-bold"
                                 style="display:none; width:52px; height:52px; font-size:1.3rem; position:absolute; top:0; left:0;">
                                {{ $initials }}
                            </div>
                        @else
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm fw-bold"
                                 style="width:52px; height:52px; font-size:1.3rem;">
                                {{ $initials }}
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-grow-1" style="min-width:0;">

                        {{-- EN Name + preview --}}
                        <div class="fw-bold text-dark lh-sm d-flex align-items-center gap-1 flex-wrap">
                            <span>{{ $titleEn }} {{ $nameEn }}</span>
                            @if($item->employee_id)
                            <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                    data-model-type="employee" data-model-id="{{ $item->employee_id }}"
                                    title="{{ __('Preview Employee') }}">
                                <i class="bi bi-search" style="font-size:0.8rem;"></i>
                            </button>
                            @endif
                        </div>

                        {{-- TH Name --}}
                        <div class="text-muted small">{{ $titleTh }} {{ $nameTh }}</div>

                        {{-- Detail badges --}}
                        <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.78rem; color:#6c757d;">
                            @if($passport && $passport !== '-')
                            <span><i class="bi bi-passport text-primary me-1"></i>{{ $passport }}</span>
                            @endif
                            @if($nationality)
                            <span class="d-inline-flex align-items-center gap-1">
                                @if($countryCode)
                                    <img src="{{ asset('images/flags/'.strtolower($countryCode).'.png') }}"
                                         loading="lazy"
                                         style="width:15px; height:11px;" onerror="this.style.display='none'">
                                @endif
                                {{ $nationality }}
                            </span>
                            @endif
                            @if($workType)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:0.72rem;">
                                <i class="bi bi-kanban me-1"></i>{{ $workType->name }}
                            </span>
                            @endif
                        </div>

                        {{-- Employer --}}
                        @if($employer)
                        <div class="mt-1 d-flex align-items-center gap-1 flex-wrap" style="font-size:0.82rem;">
                            <i class="bi bi-building text-secondary me-1"></i>
                            <span class="fw-semibold">{{ $employer->employerNameTh ?? '-' }}</span>
                            @if($employer->employerNameEn)
                                <span class="text-muted">({{ $employer->employerNameEn }})</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                    data-model-type="employer" data-model-id="{{ $employer->id }}"
                                    title="{{ __('Preview Employer') }}">
                                <i class="bi bi-search" style="font-size:0.78rem;"></i>
                            </button>
                        </div>
                        @endif

                        {{-- Appointment date / location / status --}}
                        <div class="mt-1" style="font-size:0.78rem;">
                            <i class="bi bi-clock me-1 {{ $isAppCompleted ? 'text-success' : 'text-warning' }}"></i>
                            <span class="fw-bold {{ $isAppCompleted ? 'text-success' : 'text-warning' }}">{{ $appDisplay }}</span>
                            @if($appLocation)
                                <i class="bi bi-geo-alt-fill text-info ms-2 me-1"></i>
                                <span class="text-info">{{ $appLocation }}</span>
                            @endif
                            @if($isAppCompleted)
                                <span class="badge bg-success ms-1">{{ __('Done') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Right: Action buttons --}}
                    <div class="flex-shrink-0 d-flex flex-column gap-2 align-items-end">
                        <a href="{{ route('workflow.index', ['tab' => $workType->slug ?? '']) }}#item-card-{{ $item->id }}"
                           class="btn btn-sm btn-outline-secondary" target="_blank"
                           title="{{ __('Open in Workflow Board') }}">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm fw-bold {{ $isAppCompleted ? 'btn-success' : 'btn-warning text-dark' }}"
                                    onclick="wfUpdateAppointment({{ $item->id }}, '{{ addslashes($appDateValue) }}', '{{ addslashes($appLocation) }}', this)"
                                    title="{{ __('Update Appointment') }}">
                                @if($isAppCompleted)
                                    <i class="bi bi-check-circle-fill me-1"></i>{{ __('Completed') }}
                                @else
                                    <i class="bi bi-clock-fill me-1"></i>{{ __('Update') }}
                                @endif
                            </button>
                            @if(!$isAppCompleted)
                            <button class="btn btn-sm btn-success fw-bold"
                                    onclick="wfMarkAppointmentComplete({{ $item->id }}, this)"
                                    title="{{ __('Mark as Completed') }}">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endforeach
    </div>

    <script>
    (function() {
        const selectAllCb    = document.getElementById('wf-select-all-cb');
        const countSpan      = document.getElementById('wf-selected-count');
        const bulkBtn        = document.getElementById('wf-bulk-btn');
        const viewBtn        = document.getElementById('wf-view-btn');
        const clearBtn       = document.getElementById('wf-clear-btn');
        const searchInput    = document.getElementById('wf-search-input');
        const searchBtn      = document.getElementById('wf-search-btn');
        const searchClearBtn = document.getElementById('wf-search-clear-btn');
        const resultCount    = document.getElementById('wf-result-count');
        const cards          = document.querySelectorAll('.wf-appt-card-item');
        const csrfToken      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        // ── Sync toolbar state with global selection ──
        // รวมการ query DOM และการอัปเดต UI ให้ทำในรอบเดียวผ่าน requestAnimationFrame
        // เพื่อลดการ repaint ที่ไม่จำเป็น
        function syncToolbar() {
            const data  = window.getGlobalSelectedData ? window.getGlobalSelectedData() : [];
            const count = data.length;
            const ids   = data.map(i => String(i.id));

            requestAnimationFrame(() => {
                if (countSpan) countSpan.textContent = count;
                if (bulkBtn)   bulkBtn.disabled  = count === 0;
                if (viewBtn)   viewBtn.disabled  = count === 0;
                if (clearBtn)  clearBtn.disabled = count === 0;

                // Query checkboxes แค่ครั้งเดียว แล้วใช้ซ้ำสำหรับ sync + indeterminate
                const allCbs = Array.from(document.querySelectorAll('.employee-checkbox'));

                allCbs.forEach(cb => {
                    cb.checked = ids.includes(String(cb.value));
                });

                if (selectAllCb) {
                    const visible = allCbs.filter(cb => {
                        const item = cb.closest('.wf-appt-card-item');
                        return item && item.style.display !== 'none';
                    });
                    const checkedCount = visible.filter(cb => cb.checked).length;
                    selectAllCb.checked       = visible.length > 0 && checkedCount === visible.length;
                    selectAllCb.indeterminate = checkedCount > 0 && checkedCount < visible.length;
                }
            });
        }

        document.addEventListener('global-selection-updated', syncToolbar);

        // ── Select All ──
        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                const allCbs = Array.from(document.querySelectorAll('.employee-checkbox'));
                const visible = allCbs.filter(cb =>
                    cb.closest('.wf-appt-card-item') && cb.closest('.wf-appt-card-item').style.display !== 'none'
                );
                visible.forEach(cb => {
                    cb.checked = this.checked;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
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
            syncToolbar();
        }

        if (searchBtn)      searchBtn.addEventListener('click', doFilter);
        if (searchClearBtn) searchClearBtn.addEventListener('click', () => { searchInput.value = ''; doFilter(); searchInput.focus(); });
        if (searchInput) {
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') doFilter(); });
            searchInput.addEventListener('input',   () => { if (!searchInput.value) doFilter(); });
        }

        // ── Bulk Actions ──
        document.querySelectorAll('.wf-action').forEach(btn => {
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
                    form.method = 'POST';
                    form.action = '{{ route('employees.bulk_edit.select_fields') }}';
                    [['_token', csrfToken], ['redirect_to', window.location.href]].forEach(([n, v]) => {
                        const inp = document.createElement('input'); inp.type='hidden'; inp.name=n; inp.value=v; form.appendChild(inp);
                    });
                    ids.forEach(id => {
                        const inp = document.createElement('input'); inp.type='hidden'; inp.name='employee_ids[]'; inp.value=id; form.appendChild(inp);
                    });
                    document.body.appendChild(form);
                    form.submit();

                } else if (action === 'advanced-export') {
                    const el  = document.getElementById('export_employee_ids');
                    const src = document.getElementById('export_source_menu');
                    if (el)  el.value  = JSON.stringify(ids);
                    if (src) src.value = 'workflow';
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
                    form.method = 'POST';
                    form.action = '{{ route("admin.pdf-templates.generate.modal", [], false) }}';
                    [['_token', csrfToken], ['redirect_url', window.location.href]].forEach(([n, v]) => {
                        const inp = document.createElement('input'); inp.type='hidden'; inp.name=n; inp.value=v; form.appendChild(inp);
                    });
                    ids.forEach(id => {
                        const inp = document.createElement('input'); inp.type='hidden'; inp.name='employees[]'; inp.value=id; form.appendChild(inp);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // ── Init ──
        if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
        else syncToolbar();

    })();

    // ── Appointment Update (global so onclick can call it) ──
    window.wfUpdateAppointment = function(itemId, currentDate, currentLocation, btn) {
        Swal.fire({
            title: '{{ __("Update Appointment") }}',
            html: `
                <div class='mb-3 text-start'>
                    <label class='form-label fw-bold'>{{ __('Date & Time') }}</label>
                    <input type='text' id='wf-swal-date' class='form-control bg-white' placeholder='{{ __('Select Date & Time...') }}'>
                </div>
                <div class='mb-3 text-start'>
                    <label class='form-label fw-bold'>{{ __('Location') }}</label>
                    <input type='text' id='wf-swal-location' class='form-control' value='${currentLocation}' placeholder='{{ __('e.g., Office A') }}'>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-lg"></i> {{ __("Save") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            confirmButtonColor: '#198754',
            focusConfirm: false,
            didOpen: () => {
                if (window.flatpickr) {
                    flatpickr('#wf-swal-date', {
                        enableTime: true,
                        dateFormat: 'Y-m-d H:i',
                        altInput: true,
                        altFormat: 'd/m/Y H:i',
                        time_24hr: true,
                        defaultDate: currentDate || null
                    });
                }
            },
            preConfirm: () => ({
                date:     document.getElementById('wf-swal-date').value,
                location: document.getElementById('wf-swal-location').value
            })
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(`/workflow/item/${itemId}/appointment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ appointment_date: result.value.date, appointment_location: result.value.location })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'{{ __("Saved") }}', showConfirmButton:false, timer:1500 });
                    // Update button display text
                    if (btn && result.value.date) {
                        try {
                            const parts = result.value.date.split(' ');
                            const [y, m, d] = parts[0].split('-');
                            const t = parts[1] || '00:00';
                            const disp = t === '00:00' ? `${d}/${m}/${y}` : `${d}/${m}/${y} ${t}`;
                            btn.title = '{{ __("Update Appointment") }}';
                        } catch(e) {}
                    }
                }
            });
        });
    };

    window.wfMarkAppointmentComplete = function(itemId, btn) {
        fetch(`/workflow/item/${itemId}/appointment-complete`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                Swal.fire({ toast:true, position:'top-end', icon:'success', title:'{{ __("Updated") }}', showConfirmButton:false, timer:1500 });
                if (btn) {
                    // Update Update button style (sibling)
                    const updateBtn = btn.parentElement.querySelector('.btn-warning, .btn-success');
                    if (updateBtn && updateBtn !== btn) {
                        updateBtn.classList.remove('btn-warning', 'text-dark');
                        updateBtn.classList.add('btn-success');
                        updateBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>{{ __("Completed") }}';
                    }
                    // Remove the ✓ button
                    btn.remove();
                    // Change side bar color
                    const card = btn.closest('.wf-appt-card-item');
                    if (card) {
                        const bar = card.querySelector('.position-absolute.top-0.start-0.h-100');
                        if (bar) bar.style.backgroundColor = '#10B981';
                    }
                }
            }
        });
    };
    </script>

@endif
