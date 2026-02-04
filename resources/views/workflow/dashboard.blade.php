@extends('layouts.app')

@section('title', 'Workflow Dashboard')

@section('content')
<div class="container-fluid py-4">
    {{-- Search & Global Actions --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4 bg-white p-3 rounded shadow-sm border">
        {{-- Search Bar --}}
        <form action="{{ route('workflow.index') }}" method="GET" class="d-flex gap-2 flex-grow-1 w-100" style="max-width: 500px;">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search project, employer, employee...') }}">
                <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
            </div>
        </form>

        <div class="d-flex gap-2">
            <button class="btn btn-success fw-bold shadow-sm text-nowrap"
                    onclick="openAddEmployeeModal(null, null, null, '')">
                <i class="bi bi-person-plus-fill me-1"></i> {{ __('Add Employee') }}
            </button>
        </div>
    </div>

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

        {{-- Pending Daily Check (NEW) --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-warning">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['pending_daily_check'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Pending Daily Check') }}</p>
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
                                                <a href="{{ route('workflow.index', ['tab' => $item->order->workType->slug]) }}#item-card-{{ $item->id }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
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
                                    <span class="badge bg-danger rounded-pill shadow-sm animate__animated animate__pulse animate__infinite" x-text="counts[day.dateStr]" style="font-size: 0.9rem;"></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Day Details Modal (Updated for Full Cards) --}}
                <div class="modal fade" id="dayDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">{{ __('Appointments for') }} <span x-text="selectedDateFormatted"></span></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>

                            {{-- Toolbar --}}
                            <div class="modal-body p-0 border-bottom">
                                <div class="bg-light p-3 d-flex justify-content-between align-items-center sticky-top border-bottom">
                                    <div class="form-check ms-1">
                                        <input class="form-check-input" type="checkbox" id="selectAllDay" @change="toggleSelectAll($el.checked)">
                                        <label class="form-check-label fw-bold" for="selectAllDay">{{ __('Select All') }}</label>
                                    </div>
                                    <button class="btn btn-success" @click="exportSelected()">
                                        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> {{ __('Export / Download') }}
                                    </button>
                                </div>
                                <div id="dayAppointmentsContent" class="p-3 bg-light" style="min-height: 200px;">
                                     {{-- Rendered Content Injected Here --}}
                                </div>
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
                <a href="{{ route('workflow.index', ['tab' => $tab->slug]) }}" class="text-decoration-none">
                    <div class="card h-100 shadow-sm border hover-shadow transition-all">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-kanban fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">{{ $tab->name }}</h6>
                                <div class="text-muted small">{{ $tab->orders_count ?? 0 }} Active Jobs</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach

        <div class="col">
             <div class="card h-100 border-dashed bg-light text-center hover-shadow transition-all cursor-pointer" onclick="openAddEmployeeModal(null, null, null, '')">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="bi bi-person-plus-fill fs-3 mb-2 text-success"></i>
                    <span class="fw-bold">{{ __('Add Employee') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden Form for Export --}}
    <form id="exportForm" action="{{ route('workflow.appointments.export') }}" method="POST" target="_blank" class="d-none">
        @csrf
        <div id="exportIdsContainer"></div>
    </form>

</div>

{{-- Create Job Modal --}}
@include('workflow.partials.create_modal')

{{-- Add Employee Modal --}}
@include('workflow.partials.add_employee_modal')

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

{{-- Cropper Modal (Required for Employee Edit) --}}
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">ครอบตัดรูปภาพ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <style>
                    .img-container {
                        max-height: 500px;
                        display: block;
                    }
                    .img-container img {
                        max-width: 100%;
                        display: block;
                    }
                </style>
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="cropImageBtn">ครอบตัดและบันทึก</button>
            </div>
        </div>
    </div>
</div>

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

            init() {
                this.generateCalendar();
                this.fetchCounts();
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

            openDayModal(dateStr) {
                this.selectedDate = dateStr;
                this.selectedDateFormatted = moment(dateStr).format('D MMM YYYY');

                const container = document.getElementById('dayAppointmentsContent');
                container.innerHTML = '<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>';

                // Reset Select All
                document.getElementById('selectAllDay').checked = false;

                new bootstrap.Modal(document.getElementById('dayDetailsModal')).show();

                // Fetch rendered HTML
                fetch(`/workflow/api/appointments-by-date?date=${dateStr}`)
                    .then(res => res.json())
                    .then(data => {
                        container.innerHTML = data.html;
                        // Re-initialize Alpine components in the injected HTML
                        if(window.Alpine) {
                             Alpine.initTree(container);
                        }
                    });
            },

            toggleSelectAll(checked) {
                const container = document.getElementById('dayAppointmentsContent');
                const checkboxes = container.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => cb.checked = checked);
            },

            exportSelected() {
                const container = document.getElementById('dayAppointmentsContent');
                const checkboxes = container.querySelectorAll('.item-checkbox:checked');

                if(checkboxes.length === 0) {
                    Swal.fire('{{ __('No Items Selected') }}', '{{ __('Please select at least one item to export.') }}', 'warning');
                    return;
                }

                const ids = Array.from(checkboxes).map(cb => cb.value);
                const form = document.getElementById('exportForm');
                const containerInput = document.getElementById('exportIdsContainer');
                containerInput.innerHTML = '';

                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    containerInput.appendChild(input);
                });

                form.submit();
            }
        }
    }
</script>
@endpush
