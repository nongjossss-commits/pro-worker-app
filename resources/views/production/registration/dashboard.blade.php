@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="calendarApp()">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-uppercase text-primary small fw-bold">{{ __('Registration Resolution') }}</div>
            <h2 class="fw-bold mb-0">{{ __('Dashboard & Appointments') }}</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.registration.operations') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="bi bi-list-task me-1"></i> {{ __('Go to Operations') }}
            </a>
        </div>
    </div>

    <!-- Layout with Upcoming Appointments -->
    {{-- align-items-start: ให้แต่ละคอลัมน์สูงตามเนื้อหาของตัวเอง ไม่ยืดตามกัน --}}
    <div class="row g-4 mb-4 align-items-start">
        <!-- Left Column: Upcoming Appointments -->
        <div class="col-lg-6">
            {{-- max-height + overflow-y: ถ้ารายการเยอะให้เลื่อนดูแทนการยืดยาว --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-event me-2"></i>{{ __('Upcoming Appointments (Short Term)') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if(isset($upcomingAppointments) && $upcomingAppointments->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-check fs-1 opacity-25"></i>
                            <p class="mt-2">{{ __('No upcoming appointments found in the next few days.') }}</p>
                            <small>{{ __('Check notification settings in Registration settings.') }}</small>
                        </div>
                    @else
                        {{-- Floating Toolbar (shows when items selected) --}}
                        @can('edit-employees')
                        <div class="bulk-action-bar align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
                             id="upcoming-toolbar"
                             style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 400px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="upcoming-select-all-cb">
                                <label class="form-check-label fw-bold small" for="upcoming-select-all-cb">
                                    {{ __('Select All') }} (<span id="upcoming-selected-count">0</span>)
                                </label>
                            </div>
                            <div class="vr mx-1"></div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" id="upcoming-bulk-btn"
                                        type="button" data-bs-toggle="dropdown" disabled>
                                    <i class="bi bi-lightning-fill me-1"></i>{{ __('Actions') }}
                                </button>
                                <ul class="dropdown-menu shadow">
                                    <li><a class="dropdown-item upcoming-action" data-action="advanced-edit" href="#">
                                        <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('Advanced Edit') }}</a></li>
                                    <li><a class="dropdown-item upcoming-action" data-action="advanced-export" href="#">
                                        <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>{{ __('Advanced Export') }}</a></li>
                                    <li><a class="dropdown-item upcoming-action" data-action="download" href="#">
                                        <i class="bi bi-download me-2 text-info"></i>{{ __('Download Files') }}</a></li>
                                    @can('manage-tickets')
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item upcoming-action" data-action="generate-pdf" href="#">
                                        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ __('Automated PDF') }}</a></li>
                                    @endcan
                                </ul>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" id="upcoming-clear-btn" disabled
                                    onclick="if(window.clearGlobalSelection) window.clearGlobalSelection();">
                                <i class="bi bi-x-circle me-1"></i>{{ __('Clear Selection') }}
                            </button>
                            <button class="btn btn-sm btn-info text-white" id="upcoming-view-btn" disabled
                                    onclick="if(window.openViewSelectedModal) window.openViewSelectedModal();">
                                <i class="bi bi-eye me-1"></i>{{ __('View Selected') }}
                                <span class="badge bg-white text-info ms-1" id="upcoming-view-count">0</span>
                            </button>
                        </div>
                        @endcan

                        <div class="table-responsive" style="max-height: 370px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        @can('edit-employees')
                                        <th style="width: 40px;" class="ps-3"></th>
                                        @endcan
                                        <th class="ps-2">{{ __('Date / Time') }}</th>
                                        <th>{{ __('Details') }}</th>
                                        <th class="text-end pe-4">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($upcomingAppointments))
                                    @foreach($upcomingAppointments as $item)
                                        @php
                                            $date = \Carbon\Carbon::parse($item->appointment_date);
                                            $isToday = $date->isToday();
                                            $isTomorrow = $date->isTomorrow();
                                            $colorClass = $isToday ? 'text-danger fw-bold' : ($isTomorrow ? 'text-warning fw-bold' : 'text-primary');
                                            $formatted = $date->format('d/m/Y H:i');
                                        @endphp
                                        <tr>
                                            @can('edit-employees')
                                            <td class="ps-3">
                                                <input class="form-check-input upcoming-employee-cb employee-checkbox"
                                                       type="checkbox"
                                                       style="width:17px; height:17px; cursor:pointer;"
                                                       value="{{ $item->id }}"
                                                       data-employee-id="{{ $item->id }}"
                                                       data-employer-id="{{ $item->employer_id }}"
                                                       data-name-th="{{ $item->employeeNameTh ?? '' }}"
                                                       data-name-en="{{ $item->employeeNameEn ?? '' }}"
                                                       data-title-th="{{ $item->employeeTitleTh ?? '' }}"
                                                       data-title-en="{{ $item->employeeTitleEn ?? '' }}"
                                                       data-nationality="{{ $item->employeeNationality ?? '' }}"
                                                       data-photo="{{ $item->employeePhoto ? asset('storage/'.$item->employeePhoto) : '' }}"
                                                       data-employer-name="{{ $item->employer->employerNameTh ?? '' }}"
                                                       data-insurance-type="{{ $item->insurance_type ?? '' }}"
                                                       data-passport="{{ $item->employeePassport ?? '' }}">
                                            </td>
                                            @endcan
                                            <td class="ps-2">
                                                <div class="{{ $colorClass }}">
                                                    <i class="bi bi-clock me-1"></i> {{ $formatted }}
                                                    @if($isToday) <span class="badge bg-danger ms-1">TODAY</span> @endif
                                                </div>
                                                <div class="small text-muted">{{ $date->diffForHumans() }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $item->employeeNameEn ?? $item->employeeNameTh ?? 'New Employee' }}</div>
                                                <div class="small text-muted">
                                                    {{ $item->employer->employerNameTh ?? $item->employer->employerNameEn ?? '-' }}
                                                    @if($item->employer)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                                        data-model-type="employer"
                                                        data-model-id="{{ $item->employer->id }}"
                                                        title="Preview Employer">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                @if($item->appointment_location)
                                                    <div class="small text-info"><i class="bi bi-geo-alt-fill"></i> {{ $item->appointment_location }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('production.registration.operations') }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Monthly Calendar + Appointments -->
        <div class="col-lg-6">
            {{-- ปฏิทินมีความสูงคงที่ ไม่ขึ้นกับรายการฝั่งซ้าย --}}
            <div class="card border-0 shadow-sm">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-month me-2"></i>{{ __('Monthly Overview') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-light border" @click="prevMonth()"><i class="bi bi-chevron-left"></i></button>
                            <span class="fw-bold text-uppercase" style="min-width: 120px; text-align: center;" x-text="monthNames[month] + ' ' + year"></span>
                            <button class="btn btn-sm btn-light border" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <!-- Calendar Grid -->
                        <div class="d-grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr); font-size: 0.8rem; font-weight: bold; color: #6c757d;">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        {{-- grid-template-rows: กำหนดความสูงแต่ละแถวให้คงที่ 55px --}}
                        <div class="d-grid" style="grid-template-columns: repeat(7, 1fr); grid-template-rows: repeat(6, 55px); gap: 5px;">
                            <template x-for="day in days" :key="day.dateStr">
                                <div
                                    class="border rounded p-1 d-flex flex-column align-items-center justify-content-between position-relative cursor-pointer transition-all"
                                    :class="{
                                        'bg-light text-muted': !day.isCurrentMonth,
                                        'bg-white': day.isCurrentMonth,
                                        'border-primary bg-primary bg-opacity-10 shadow-sm': day.dateStr === selectedDate,
                                        'border-info bg-info bg-opacity-10': day.isToday && day.dateStr !== selectedDate
                                    }"
                                    @click="openDay(day.dateStr)"
                                >
                                    <span class="fw-bold" style="font-size: 1.1rem;" x-text="day.dayNum"></span>

                                    <template x-if="counts[day.dateStr]">
                                        <span class="badge bg-danger rounded-pill mt-1" style="font-size: 0.75rem;" x-text="counts[day.dateStr]"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Appointments Panel (full width, below calendar row) --}}
    <div id="appt-panel-wrapper">
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-primary mb-0">
                    <i class="bi bi-list-check me-2"></i>{{ __('Appointments for') }}:
                    <span class="text-dark" x-text="selectedDateFormatted || '{{ __('Select a date') }}'"></span>
                </h5>
                <span class="badge bg-light text-muted border" x-show="!appointmentsLoaded">
                    <i class="bi bi-arrow-up-left me-1"></i>{{ __('Click a date on the calendar above') }}
                </span>
            </div>
            <div class="card-body bg-light position-relative p-3" style="min-height: 200px;">
                <div x-show="isLoading" class="position-absolute w-100 h-100 bg-white bg-opacity-75 top-0 start-0" style="z-index: 10;">
                    <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div id="dayAppointmentsContent">
                    <div x-show="!isLoading && !appointmentsLoaded" class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-event fs-1 opacity-25"></i>
                        <p class="mt-2">{{ __('Select a date to view appointments.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('employees.modals.advanced_export')
@include('components.download-modals')

@endsection

@push('scripts')
<script>
    // appointmentSearch handled inside injected partial

    function calendarApp() {
        return {
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            days: [],
            counts: {},
            selectedDate: null,
            selectedDateFormatted: '',
            isLoading: false,
            appointmentsLoaded: false,
            searchQuery: '',

            init() {
                this.generateCalendar();
                this.fetchCounts();
                window.currentAppointmentContext = { module: 'production/registration' };

                this.$watch('searchQuery', (value) => {
                    const cards = document.querySelectorAll('.appointment-card');
                    const query = value.toLowerCase();
                    cards.forEach(card => {
                        const nameTh = card.dataset.employeeNameTh || '';
                        const nameEn = card.dataset.employeeNameEn || '';
                        const employer = card.dataset.employerName || '';
                        const ref = card.dataset.reference || '';

                        if (nameTh.includes(query) || nameEn.includes(query) || employer.includes(query) || ref.includes(query)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            },

            prevMonth() {
                if (this.month === 0) {
                    this.month = 11;
                    this.year--;
                } else {
                    this.month--;
                }
                this.generateCalendar();
                this.fetchCounts();
            },

            nextMonth() {
                if (this.month === 11) {
                    this.month = 0;
                    this.year++;
                } else {
                    this.month++;
                }
                this.generateCalendar();
                this.fetchCounts();
            },

            generateCalendar() {
                const firstDay = new Date(this.year, this.month, 1);
                const lastDay = new Date(this.year, this.month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDay = firstDay.getDay(); // 0 = Sunday

                let calendarDays = [];

                // Previous month padding
                const prevMonthLastDay = new Date(this.year, this.month, 0).getDate();
                for (let i = startingDay - 1; i >= 0; i--) {
                    let d = prevMonthLastDay - i;
                    let pm = this.month - 1;
                    let py = this.year;
                    if(pm < 0) { pm = 11; py--; }

                    calendarDays.push({
                        dayNum: d,
                        isCurrentMonth: false,
                        isToday: false,
                        dateStr: `${py}-${String(pm+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
                    });
                }

                // Current Month
                const today = new Date();
                for (let i = 1; i <= daysInMonth; i++) {
                    const isToday = (i === today.getDate() && this.month === today.getMonth() && this.year === today.getFullYear());
                    calendarDays.push({
                        dayNum: i,
                        isCurrentMonth: true,
                        isToday: isToday,
                        dateStr: `${this.year}-${String(this.month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`
                    });
                }

                // Next Month padding
                const remaining = 42 - calendarDays.length;
                for (let i = 1; i <= remaining; i++) {
                    let nm = this.month + 1;
                    let ny = this.year;
                    if(nm > 11) { nm = 0; ny++; }

                    calendarDays.push({
                        dayNum: i,
                        isCurrentMonth: false,
                        isToday: false,
                        dateStr: `${ny}-${String(nm+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`
                    });
                }

                this.days = calendarDays;
            },

            fetchCounts() {
                fetch(`{{ route('production.registration.api.calendar') }}?month=${this.month + 1}&year=${this.year}`)
                    .then(res => res.json())
                    .then(data => {
                        this.counts = data;
                    });
            },

            openDay(dateStr) {
                this.selectedDate = dateStr;
                const d = new Date(dateStr);
                this.selectedDateFormatted = d.toLocaleDateString('{{ app()->getLocale() }}', { day: 'numeric', month: 'short', year: 'numeric' });
                this.isLoading = true;
                this.appointmentsLoaded = true;

                fetch(`{{ route('production.registration.api.appointments_by_date') }}?date=${dateStr}`)
                    .then(res => res.json())
                    .then(data => {
                        const apptContainer = document.getElementById('dayAppointmentsContent');
                        apptContainer.innerHTML = data.html;
                        // Execute <script> tags injected via innerHTML (browser blocks them by default)
                        apptContainer.querySelectorAll('script').forEach(oldScript => {
                            const newScript = document.createElement('script');
                            newScript.textContent = oldScript.textContent;
                            document.body.appendChild(newScript);
                            document.body.removeChild(newScript);
                        });
                        this.isLoading = false;
                        this.searchQuery = '';
                    })
                    .catch(() => {
                        this.isLoading = false;
                    });
            }
        }
    }

    // Needed for the appointment edit modal called from the day appointments list html
    window.editAppointment = function(employeeId, currentDate, currentLocation, isCompleted) {
        Swal.fire({
            title: '{{ __("Update Appointment") }}',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">{{ __("Appointment Date & Time") }}</label>
                    <input type="datetime-local" id="swal-appointment-date" class="form-control" value="${currentDate}">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold">{{ __("Location / Note") }}</label>
                    <input type="text" id="swal-appointment-location" class="form-control" value="${currentLocation}" placeholder="{{ __('e.g., Main Office') }}">
                </div>
                <div class="form-check form-switch text-start mt-4">
                    <input class="form-check-input" type="checkbox" id="swal-appointment-complete" ${isCompleted ? 'checked' : ''}>
                    <label class="form-check-label fw-bold" for="swal-appointment-complete">{{ __("Mark as Completed") }}</label>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __("Save Changes") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            focusConfirm: false,
            preConfirm: () => {
                return {
                    date: document.getElementById('swal-appointment-date').value,
                    location: document.getElementById('swal-appointment-location').value,
                    isComplete: document.getElementById('swal-appointment-complete').checked
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                const moduleUrl = window.currentAppointmentContext ? window.currentAppointmentContext.module : 'production/registration';

                fetch(`/${moduleUrl}/${employeeId}/appointment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        appointment_date: data.date,
                        appointment_location: data.location
                    })
                }).then(res => res.json()).then(response => {
                    if (data.isComplete !== isCompleted) {
                        return fetch(`/${moduleUrl}/${employeeId}/appointment-complete`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                    }
                    return Promise.resolve();
                }).then(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '{{ __("Appointment Updated") }}',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    const calendarScope = Alpine.$data(document.querySelector('[x-data="calendarApp()"]'));
                    if (calendarScope && calendarScope.selectedDate) {
                        calendarScope.fetchCounts();
                        calendarScope.openDay(calendarScope.selectedDate);
                    }
                }).catch(err => {
                    Swal.fire('Error', 'Could not update appointment.', 'error');
                });
            }
        });
    }
</script>
<script>
    // ── Upcoming Appointments table: selection & bulk actions ──
    (function() {
        const toolbar     = document.getElementById('upcoming-toolbar');
        const selectAllCb = document.getElementById('upcoming-select-all-cb');
        const countSpan   = document.getElementById('upcoming-selected-count');
        const viewCountBadge = document.getElementById('upcoming-view-count');
        const bulkBtn     = document.getElementById('upcoming-bulk-btn');
        const viewBtn     = document.getElementById('upcoming-view-btn');
        const clearBtn    = document.getElementById('upcoming-clear-btn');
        const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

        function syncUpcomingToolbar() {
            const data  = window.getGlobalSelectedData ? window.getGlobalSelectedData() : [];
            const count = data.length;
            const ids   = data.map(i => String(i.id));

            // Show/hide floating bar
            if (toolbar) toolbar.style.display = count > 0 ? 'flex' : 'none';

            if (countSpan) countSpan.textContent = count;
            if (viewCountBadge) viewCountBadge.textContent = count;
            if (bulkBtn)   bulkBtn.disabled  = count === 0;
            if (viewBtn)   viewBtn.disabled  = count === 0;
            if (clearBtn)  clearBtn.disabled = count === 0;

            document.querySelectorAll('.upcoming-employee-cb').forEach(cb => {
                cb.checked = ids.includes(String(cb.value));
            });

            if (selectAllCb) {
                const allCbs = document.querySelectorAll('.upcoming-employee-cb');
                const checked = Array.from(allCbs).filter(cb => cb.checked).length;
                selectAllCb.checked       = allCbs.length > 0 && checked === allCbs.length;
                selectAllCb.indeterminate = checked > 0 && checked < allCbs.length;
            }
        }

        document.addEventListener('global-selection-updated', syncUpcomingToolbar);

        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                document.querySelectorAll('.upcoming-employee-cb').forEach(cb => {
                    cb.checked = this.checked;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        document.querySelectorAll('.upcoming-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const action = this.dataset.action;
                const ids = window.getGlobalSelectedIds ? window.getGlobalSelectedIds() : [];

                if (!ids.length) {
                    Swal.fire({ toast:true, icon:'warning', title:'{{ __("Please select employees first.") }}', position:'top-end', showConfirmButton:false, timer:2000 });
                    return;
                }

                if (action === 'advanced-edit') {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = '{{ route("employees.bulk_edit.select_fields") }}';
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

        if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
        else syncUpcomingToolbar();
    })();

    window.markAppointmentCompleted = function(employeeId, module, btnElement) {
        Swal.fire({
            title: '{{ __("Complete Appointment?") }}',
            text: '{{ __("Are you sure you want to mark this appointment as completed?") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("Yes, Complete it!") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                let modulePath = module || window.currentAppointmentContext?.module || 'production/registration';

                const originalHtml = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
                btnElement.disabled = true;

                fetch(`/${modulePath}/${employeeId}/appointment-complete`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '{{ __("Appointment Completed") }}',
                            showConfirmButton: false,
                            timer: 2000
                        });

                        const cardElement = btnElement.closest('.appointment-card');
                        if (cardElement) {
                            cardElement.style.display = 'none';
                        }

                        const calendarScope = Alpine.$data(document.querySelector('[x-data="calendarApp()"]'));
                        if (calendarScope) {
                            calendarScope.fetchCounts();
                        }
                    } else {
                        btnElement.innerHTML = originalHtml;
                        btnElement.disabled = false;
                        Swal.fire('Error', 'Could not complete appointment.', 'error');
                    }
                })
                .catch(err => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.disabled = false;
                    Swal.fire('Error', 'Network error occurred.', 'error');
                });
            }
        });
    }
</script>
@endpush
