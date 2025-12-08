@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Board Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Active Workflows</h1>
            <p class="text-muted">Monitor progress and manage active jobs</p>
        </div>
        <!-- "Create" button REMOVED as per requirements (must come from Pre-Production) -->
    </div>

    @if(isset($orders))
        <!-- DASHBOARD VIEW: List of Active Jobs -->
        <div class="row g-4">
            @forelse($orders as $order)
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="fw-bold mb-0 text-truncate">{{ $order->project_name }}</h5>
                            @if($order->type === 'independent')
                                <span class="badge bg-purple text-white small" style="background-color: #6f42c1;">Independent</span>
                            @else
                                <span class="badge bg-primary small">Employer</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                {{ Str::limit($order->description, 80) }}
                            </p>

                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="bi bi-people me-1"></i> {{ $order->items->count() }} Employees</span>
                                <span><i class="bi bi-clock me-1"></i> Started {{ $order->updated_at->diffForHumans() }}</span>
                            </div>

                            <!-- Progress Bar (Fake for visual) -->
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 45%"></div>
                            </div>

                            <div class="d-grid">
                                <!-- Link to the Detailed Job View (Same as 'Prepare' but read-only context or specialized workflow view)
                                     Actually, we reuse the tracking logic. The controller 'show' method determines view.
                                     Wait, the ProductionController@show renders 'workflow_dashboard' (this file) with a SINGLE production.
                                     But WorkflowController@index renders 'workflow_dashboard' with a LIST of orders.
                                     We need to split this template or handle both cases.
                                -->
                                <a href="{{ route('production.show', $order->id) }}" class="btn btn-outline-primary">
                                    Open Board
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="text-muted mb-3"><i class="bi bi-inbox fs-1"></i></div>
                    <h4>No Active Workflows</h4>
                    <p class="text-muted">Jobs sent from Pre-Production will appear here.</p>
                    <a href="{{ route('production.index') }}" class="btn btn-primary mt-2">Go to Pre-Production</a>
                </div>
            @endforelse
        </div>
        <div class="mt-4">
            {{ $orders->links() }}
        </div>

    @elseif(isset($production))
        <!-- INDIVIDUAL JOB VIEW: The Tracking Board -->
        <!-- This section renders when viewing a single ProductionOrder -->

        <div class="card shadow-sm border-0 mb-4">
             <div class="card-body d-flex justify-content-between align-items-center">
                 <div>
                     <h4 class="fw-bold mb-1">{{ $production->project_name }}</h4>
                     <div class="text-muted small">
                         @if($production->type === 'employer' && $production->employer)
                             {{ $production->employer->name_en ?? $production->employer->name_th }}
                         @else
                             Independent / Mixed Job
                         @endif
                     </div>
                 </div>
                 <a href="{{ route('workflow.index') }}" class="btn btn-outline-secondary">Back to List</a>
             </div>
        </div>

        <!-- Kanban / Status Board -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Employee Status Tracking</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Employee</th>
                                        <th>Passport</th>
                                        <th>Current Status</th>
                                        <!-- Dynamic Columns based on Custom Fields -->
                                        @foreach($production->custom_field_definitions ?? [] as $def)
                                            <th>{{ $def['label'] }}</th>
                                        @endforeach
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($production->items as $item)
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ $item->photo_url }}" class="rounded-circle border" width="36" height="36" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold">{{ $item->display_name }}</div>
                                                        @if(!$item->employee_id) <span class="badge bg-warning text-dark ultra-small">Ghost</span> @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $item->passport_number }}</td>
                                            <td>
                                                @if($item->currentBarrier)
                                                    <span class="badge bg-{{ $item->currentBarrier->color }}">{{ $item->currentBarrier->name }}</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">Pending</span>
                                                @endif
                                            </td>

                                            <!-- Custom Field Values -->
                                            @foreach($production->custom_field_definitions ?? [] as $def)
                                                <td>
                                                    {{ $item->custom_field_values[$def['key']] ?? '-' }}
                                                </td>
                                            @endforeach

                                            <td class="text-end pe-4">
                                                <a href="{{ route('workflow.item.show', $item->id) }}" class="btn btn-sm btn-primary">
                                                    Timeline
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center py-4">No employees found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
