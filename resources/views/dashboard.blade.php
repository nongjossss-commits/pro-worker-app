@extends('layouts.app')

@section('title', __('Statistics Dashboard'))

@section('content')
<x-help-button manual="dashboard" title="{{ __('Dashboard') }}" />
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-dark mb-0"><i class="bi bi-graph-up-arrow me-2"></i>{{ __('Statistics Dashboard') }}</h1>
        <div class="text-muted small">{{ __('Overview of system data and performance') }}</div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Employees (All Time) --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white bg-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0 fw-bold">{{ __('Total History') }}</h6>
                        <i class="bi bi-people-fill fs-4 text-white-50"></i>
                    </div>
                    <h2 class="display-6 fw-bold mb-0">{{ number_format($totalEmployees) }}</h2>
                    <div class="small mt-2 text-white-50">{{ __('All-time records') }}</div>
                </div>
            </div>
        </div>

        {{-- Active Employees (Current Workforce) --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white bg-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0 fw-bold">{{ __('Current Workforce') }}</h6>
                        <i class="bi bi-person-check-fill fs-4 text-white-50"></i>
                    </div>
                    <h2 class="display-6 fw-bold mb-0">{{ number_format($activeEmployees) }}</h2>
                    <div class="small mt-2 text-white-50">{{ __('Currently employed') }}</div>
                </div>
            </div>
        </div>

        {{-- Active Orders --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-info text-dark bg-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0 fw-bold">{{ __('Active Jobs') }}</h6>
                        <i class="bi bi-diagram-3-fill fs-4 text-dark-50"></i>
                    </div>
                    <h2 class="display-6 fw-bold mb-0">{{ number_format($activeOrders) }}</h2>
                    <div class="small mt-2 text-dark-50">{{ __('In Workflow') }}</div>
                </div>
            </div>
        </div>

        {{-- Completed Orders --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-secondary text-white bg-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0 fw-bold">{{ __('Completed Jobs') }}</h6>
                        <i class="bi bi-check-circle-fill fs-4 text-white-50"></i>
                    </div>
                    <h2 class="display-6 fw-bold mb-0">{{ number_format($completedOrders) }}</h2>
                    <div class="small mt-2 text-white-50">{{ __('Finished workflows') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-4 mb-4">
        {{-- Activity Chart --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2"></i>{{ __('System Activity (Last 14 Days)') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Added wrapper div to fix infinite resize loop --}}
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Breakdown --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill me-2"></i>{{ __('Employee Status') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Added wrapper div to fix infinite resize loop --}}
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ __('Active (Confirmed)') }}
                                <span class="badge bg-success rounded-pill">{{ $statusBreakdown['active'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ __('Registration Pending') }}
                                <span class="badge bg-info rounded-pill">{{ $statusBreakdown['registration_pending'] }}</span>
                            </li>
                            @if($statusBreakdown['renewal_pending'] > 0)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ __('Renewal Pending') }}
                                <span class="badge bg-warning text-dark rounded-pill">{{ $statusBreakdown['renewal_pending'] }}</span>
                            </li>
                            @endif
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ __('Terminated/Resigned') }}
                                <span class="badge bg-danger rounded-pill">{{ $statusBreakdown['terminated'] }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity & Workflow Status --}}
    <div class="row g-4">
        {{-- Workflow Status --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                 <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2"></i>{{ __('Item Status Breakdown') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Added wrapper div to fix infinite resize loop --}}
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="workflowChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Logs --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('Recent Activities') }}</h6>
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-link text-decoration-none">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentActivities as $log)
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 70%;">{{ $log->description }}</h6>
                                    <small class="text-muted text-nowrap">{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 small text-muted">
                                    <i class="bi bi-person-circle me-1"></i> {{ $log->user->name ?? 'System' }}
                                </p>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">{{ __('No recent activity.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activity Line Chart
        const ctxActivity = document.getElementById('activityChart').getContext('2d');
        new Chart(ctxActivity, {
            type: 'line',
            data: {
                labels: @json($activityLabels),
                datasets: [{
                    label: '{{ __("Actions Per Day") }}',
                    data: @json($activityCounts),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Status Pie Chart
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: [
                    '{{ __("Active") }}',
                    '{{ __("Registration Pending") }}',
                    @if($statusBreakdown['renewal_pending'] > 0) '{{ __("Renewal Pending") }}', @endif
                    '{{ __("Terminated") }}'
                ],
                datasets: [{
                    data: [
                        {{ $statusBreakdown['active'] }},
                        {{ $statusBreakdown['registration_pending'] }},
                        @if($statusBreakdown['renewal_pending'] > 0) {{ $statusBreakdown['renewal_pending'] }}, @endif
                        {{ $statusBreakdown['terminated'] }}
                    ],
                    backgroundColor: [
                        '#198754', // Active - Green
                        '#0dcaf0', // Reg Pending - Cyan/Info
                        @if($statusBreakdown['renewal_pending'] > 0) '#ffc107', @endif // Renewal - Yellow/Warning
                        '#dc3545'  // Terminated - Red
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Workflow Bar Chart
        const itemStats = @json($itemStats);
        const labels = Object.keys(itemStats).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '));
        const data = Object.values(itemStats);
        // Generate colors dynamically or map specific statuses
        const colors = labels.map(l => {
            if(l.includes('Completed')) return '#198754'; // Green
            if(l.includes('Cancelled')) return '#6c757d'; // Gray
            if(l.includes('Pending')) return '#ffc107'; // Yellow
            if(l.includes('Active')) return '#0dcaf0'; // Cyan
            return '#0d6efd'; // Blue default
        });

        const ctxWorkflow = document.getElementById('workflowChart').getContext('2d');
        new Chart(ctxWorkflow, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '{{ __("Items") }}',
                    data: data,
                    backgroundColor: colors,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>
@endpush
