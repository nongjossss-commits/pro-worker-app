@extends('layouts.app')

@section('title', 'Workflow Dashboard')

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

        {{-- Total Projects --}}
        <div class="col">
            <div class="card text-white h-100 shadow-sm border-0 bg-primary bg-gradient">
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <h1 class="display-4 fw-bold mb-0">{{ $stats['total_projects'] ?? 0 }}</h1>
                    <p class="fs-5 fw-light mb-0">{{ __('Active Projects') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming Appointments Section --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-primary mb-0"><i class="bi bi-calendar-event me-2"></i>{{ __('Upcoming Appointments') }}</h5>
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
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Employer / Project') }}</th>
                                <th>{{ __('Work Type') }}</th>
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

                                    // Use format depending on time presence?
                                    // Logic: if time is 00:00:00, show only date.
                                    // But Carbon defaults to 00:00:00.
                                    // We can just show standard format d/m/Y H:i
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
                                        <div class="d-flex align-items-center">
                                            @php
                                                $photo = $item->employee && $item->employee->employeePhoto ? asset('storage/' . $item->employee->employeePhoto) : 'https://placehold.co/40x40?text=IMG';
                                                $name = $item->employee->employeeNameEn ?? $item->new_employee_data['name_en'] ?? 'New Employee';
                                            @endphp
                                            <img src="{{ $photo }}" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                                            <span class="fw-bold">{{ $name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->order->project_name ?? '-' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $item->order->workType->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        {{-- Link to the specific tab and highlight the item if possible (Need ID logic) --}}
                                        <a href="{{ route('workflow.index', ['tab' => $item->order->workType->slug]) }}#item-card-{{ $item->id }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-right"></i> {{ __('View') }}
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
             <div class="card h-100 border-dashed bg-light text-center">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="bi bi-plus-circle fs-3 mb-2"></i>
                    <span>{{ __('Create New Job') }}</span>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#createJobModal" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Create Job Modal (Include reusing partial) --}}
@include('workflow.partials.create_modal')

@endsection

@push('scripts')
<script>
    // Add hover effect logic if needed
</script>
@endpush
