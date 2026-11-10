@extends('layouts.app')

@section('title', 'Workflow Dashboard')

@push('styles')
<style>
/* ภาพเคลื่อนไหวตัวเลขในปฏิทิน — ใช้ transform เพื่อให้ GPU รับผิดชอบแทน CPU */
@keyframes calBadgePulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.25); }
}
.cal-badge-pulse {
    display: inline-block;
    animation: calBadgePulse 2s ease-in-out infinite;
    will-change: transform;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    {{-- Scoreboard (Global Stats) --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        {{-- Total Employees --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0" style="background-color: #FBBF24;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['total_employees'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Total Employees') }}</p>
                </div>
            </div>
        </div>

        {{-- Not Started --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0" style="background-color: #EF4444;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['not_started'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Not Started') }}</p>
                </div>
            </div>
        </div>

        {{-- Cancelled --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0" style="background-color: #6B7280;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['cancelled'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Cancelled') }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0" style="background-color: #10B981;">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Completed') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Left Column: Upcoming List --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-event me-2"></i>{{ __('Upcoming Appointments (Short Term)') }}</h5>
                </div>
                <div class="card-body p-0">
                    @if($upcomingAppointments->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-check fs-1 opacity-25"></i>
                            <p class="mt-2">{{ __('No upcoming appointments found in the next few days.') }}</p>
                            <small>{{ __('Check notification settings in each Work Type tab.') }}</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">{{ __('Date / Time') }}</th>
                                        <th>{{ __('Details') }}</th>
                                        <th class="text-end pe-4">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingAppointments as $item)
                                        @php
                                            $date = $item->appointment_date;
                                            $isToday = $date->isToday();
                                            $isTomorrow = $date->isTomorrow();
                                            $colorClass = $isToday ? 'text-danger fw-bold' : ($isTomorrow ? 'text-warning fw-bold' : 'text-primary');
                                            $formatted = $date->format('d/m/Y H:i');
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <div class="{{ $colorClass }}">
                                                    <i class="bi bi-clock me-1"></i> {{ $formatted }}
                                                    @if($isToday) <span class="badge bg-danger ms-1">TODAY</span> @endif
                                                </div>
                                                <div class="small text-muted">{{ $date->diffForHumans() }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee' }}</div>
                                                <div class="small text-muted">
                                                    {{ $item->order->employer->employerNameTh ?? $item->order->employer->employerNameEn ?? '-' }}
                                                    @if($item->order->employer)
                                                    <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                                        data-model-type="employer"
                                                        data-model-id="{{ $item->order->employer->id }}"
                                                        title="Preview Employer">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                <div class="small text-muted" style="font-size: 0.75rem;">{{ $item->order->project_name ?? '-' }}</div>
                                                @if($item->appointment_location)
                                                    <div class="small text-info"><i class="bi bi-geo-alt-fill"></i> {{ $item->appointment_location }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                @if($item->order->workType)
                                                <a href="{{ route('workflow.index', ['tab' => $item->order->workType->slug]) }}#item-card-{{ $item->id }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: Monthly Calendar --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" x-data="calendarApp()">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-month me-2"></i>{{ __('Monthly Overview') }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-light border" @click="prevMonth()"><i class="bi bi-chevron-left"></i></button>
                        <span class="fw-bold text-uppercase" style="min-width: 120px; text-align: center;" x-text="monthNames[month] + ' ' + year"></span>
                        <button class="btn btn-sm btn-light border" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
                <div class="px-3 pt-3">
                    {{-- Search every upcoming appointment in Workflow (not just one
                         day) by employer/employee name, passport, ID no., etc. —
                         opens the same day modal, grouped by date. --}}
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 bg-light" x-model="tabSearchQuery" @focus="openSearchModal()"
                               placeholder="{{ __('Search any date: employer, employee, passport, ID no....') }}">
                    </div>
                </div>
                <div class="card-body p-3">
                    {{-- Calendar Grid --}}
                    <div class="d-grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr); font-size: 0.8rem; font-weight: bold; color: #6c757d;">
                        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                    </div>
                    <div class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 5px;">
                        <template x-for="day in days" :key="day.dateStr">
                            <div
                                class="border rounded p-2 d-flex flex-column align-items-center justify-content-between position-relative cursor-pointer transition-all"
                                :class="{ 'bg-light text-muted': !day.isCurrentMonth, 'bg-white': day.isCurrentMonth, 'border-primary bg-primary bg-opacity-10': day.isToday }"
                                style="min-height: 80px;"
                                @click="openDayModal(day.dateStr)"
                            >
                                <span class="small" x-text="day.dayNum"></span>
                                <template x-if="counts[day.dateStr]">
                                    <span class="badge bg-danger rounded-pill shadow-sm cal-badge-pulse" x-text="counts[day.dateStr]" style="font-size: 0.9rem;"></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Day Details Modal --}}
                <div class="modal fade" id="dayDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" style="max-width: 1100px;">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white py-2 flex-wrap gap-2">
                                <h5 class="modal-title mb-0">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    <template x-if="!isSearchActive()">
                                        <span>{{ __('Appointments for') }} <span x-text="selectedDateFormatted"></span></span>
                                    </template>
                                    <template x-if="isSearchActive()">
                                        <span>{{ __('Search results for') }}: <span x-text="tabSearchQuery"></span></span>
                                    </template>
                                </h5>
                                <div class="input-group input-group-sm" style="max-width: 280px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" x-model="tabSearchQuery"
                                           placeholder="{{ __('Search any date: employer, employee, passport, ID no....') }}">
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0" id="dayAppointmentsContent" style="min-height: 300px;">
                                {{-- Injected by openDayModal() --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Work Type Navigation (Links to Tabs) --}}
    <h5 class="fw-bold text-secondary mb-3">{{ __('Workflows') }}</h5>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
        @foreach($tabs as $tab)
            <div class="col">
                <div class="card h-100 shadow-sm border hover-shadow transition-all position-relative">
                    @if(auth()->check() && auth()->user()->hasRole('super-admin'))
                        <div class="position-absolute d-flex gap-1" style="top: 8px; right: 10px; z-index: 2; font-size: 0.75rem; opacity: 0.6;">
                            <span onclick="event.preventDefault(); editWorkTypeTab({{ $tab->id }}, '{{ addslashes($tab->name) }}', {{ $tab->allow_multiple_orders ? 'true' : 'false' }}, {{ $tab->is_system ? 'true' : 'false' }})" title="{{ __('Edit Name') }}"><i class="bi bi-pencil-fill"></i></span>
                            @if(!$tab->is_system)
                                <span onclick="event.preventDefault(); deleteWorkTypeTab({{ $tab->id }}, '{{ addslashes($tab->name) }}', {{ (int) $tab->orders()->count() }})" title="{{ __('Delete Tab') }}"><i class="bi bi-trash-fill"></i></span>
                            @endif
                        </div>
                    @endif
                    <a href="{{ route('workflow.index', ['tab' => $tab->slug]) }}" class="text-decoration-none stretched-link">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">{{ $tab->name }}</h6>
                                <div class="text-muted small">{{ $tab->orders_count ?? 0 }} Active Jobs</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endforeach

        @if(auth()->check() && auth()->user()->hasRole('super-admin'))
            <div class="col">
                <div class="card h-100 border-dashed bg-light text-center">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-muted">
                        <i class="bi bi-folder-plus fs-3 mb-2"></i>
                        <span>{{ __('Add New Tab') }}</span>
                        <button type="button" onclick="createWorkTypeTab()" class="btn btn-sm stretched-link"></button>
                    </div>
                </div>
            </div>
        @endif

        <div class="col">
             <div class="card h-100 border-dashed bg-light text-center">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="bi bi-plus-circle fs-3 mb-2"></i>
                    <span>{{ __('Create New Job') }}</span>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#createJobModal" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>
    @if(auth()->check() && auth()->user()->hasRole('super-admin'))
        @include('components.work-type-tab-scripts')
    @endif


</div>

{{-- Create Job Modal --}}
@include('workflow.partials.create_modal')

{{-- Bulk Action Modals (required for Advanced Export & Download) --}}
@include('employees.modals.advanced_export')

{{-- Edit Employee Modal (Full Form) --}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editEmployeeModalLabel"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Employee') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="editEmployeeModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cropper Modal (Required for Employee Edit) — shared component so the
     photo tools (rotate, face crop, background, Refine) match the other dashboards --}}
<x-cropper-modal />

@include('employees.partials._edit_scripts')
@include('production.registration.partials.edit_modal_script')

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script>
    function calendarApp() {
        return {
            month: new Date().getMonth(),
            year: new Date().getFullYear(),
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            days: [],
            counts: {},
            selectedDate: null,
            selectedDateFormatted: '',
            tabSearchQuery: '',
            isLoading: false,

            init() {
                this.generateCalendar();
                this.fetchCounts();

                // Cross-date search — every upcoming appointment in Workflow
                // matching the term, grouped by date (not just the
                // currently open day). Clearing the box goes back to
                // whichever day was last selected, if any.
                this.$watch('tabSearchQuery', (value) => {
                    clearTimeout(this._tabSearchDebounce);
                    const q = value.trim();
                    if (q.length < 2) {
                        if (this.selectedDate) this.openDayModal(this.selectedDate, true);
                        return;
                    }
                    this._tabSearchDebounce = setTimeout(() => {
                        const container = document.getElementById('dayAppointmentsContent');
                        container.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
                        fetch(`{{ route('workflow.api.appointments_search') }}?q=${encodeURIComponent(q)}`)
                            .then(res => res.json())
                            .then(data => { container.innerHTML = data.html; })
                            .catch(() => {
                                container.innerHTML = '<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle fs-1"></i><p class="mt-2">{{ __("Failed to load appointments.") }}</p></div>';
                            });
                    }, 300);
                });
            },

            isSearchActive() {
                return this.tabSearchQuery.trim().length >= 2;
            },

            openSearchModal() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('dayDetailsModal')).show();
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
                    // Calculate correct previous month/year
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

                // Next Month padding (to fill grid to 42 cells 6x7, or just enough to finish week)
                // Let's just finish the week
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
                fetch(`/workflow/api/calendar?month=${this.month + 1}&year=${this.year}`)
                    .then(res => res.json())
                    .then(data => {
                        this.counts = data;
                    });
            },

            openDayModal(dateStr, keepSearch) {
                this.selectedDate = dateStr;
                this.selectedDateFormatted = moment(dateStr).format('D MMM YYYY');
                if (!keepSearch) this.tabSearchQuery = '';

                const container = document.getElementById('dayAppointmentsContent');
                container.innerHTML = '<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';

                bootstrap.Modal.getOrCreateInstance(document.getElementById('dayDetailsModal')).show();

                fetch(`/workflow/api/appointments-by-date?date=${dateStr}`)
                    .then(res => res.json())
                    .then(data => {
                        container.innerHTML = data.html;
                        // Re-execute injected <script> tags
                        container.querySelectorAll('script').forEach(oldScript => {
                            const newScript = document.createElement('script');
                            newScript.textContent = oldScript.textContent;
                            document.body.appendChild(newScript);
                            document.body.removeChild(newScript);
                        });
                        // Sync global selection checkboxes
                        if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
                    })
                    .catch(() => {
                        container.innerHTML = '<div class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle fs-1"></i><p class="mt-2">{{ __("Failed to load appointments.") }}</p></div>';
                    });
            }
        }
    }

    // search_appointment_card.blade.php (cross-date search results) calls
    // these same two global functions as day_appointments_list.blade.php's
    // own injected <script> — defined there too, but ONLY once that script
    // has actually run (i.e. a day was opened at least once). Defining them
    // here as well means searching works even if the user never opened a
    // single day first. Identical logic in both places; whichever runs
    // last simply wins, harmlessly.
    if (!window.wfUpdateAppointment) {
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
                            allowInput: true,
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
                    }
                });
            });
        };
    }

    if (!window.wfMarkAppointmentComplete) {
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
                        const updateBtn = btn.parentElement.querySelector('.btn-warning, .btn-success');
                        if (updateBtn && updateBtn !== btn) {
                            updateBtn.classList.remove('btn-warning', 'text-dark');
                            updateBtn.classList.add('btn-success');
                            updateBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>{{ __("Completed") }}';
                        }
                        btn.remove();
                    }
                }
            });
        };
    }
</script>
@endpush
