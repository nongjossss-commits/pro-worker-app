@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Production Projects</h1>
            <p class="text-muted">Manage preparation and workflow tracking</p>
        </div>
        <a href="{{ route('production.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>New Project
        </a>
    </div>

    <!-- Filters could go here -->

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Project Name</th>
                            <th>Employer</th>
                            <th>Status</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Financial</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $order->project_name ?? 'Untitled Project' }}</div>
                                    <div class="small text-muted">{{ Str::limit($order->description, 50) }}</div>
                                    <div class="small text-muted">Created {{ $order->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    @if($order->employer)
                                        <div class="fw-bold">{{ $order->employer->name_en ?? $order->employer->name_th }}</div>
                                        <div class="small text-muted">{{ $order->employer->employer_id }}</div>
                                    @else
                                        <span class="text-danger">Unknown Employer</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->status === 'pre_production')
                                        <span class="badge bg-warning text-dark">Preparation</span>
                                    @elseif($order->status === 'active')
                                        <span class="badge bg-success">In Workflow</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info rounded-pill">{{ $order->items_count }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $fin = $order->financial_data ?? [];
                                    @endphp
                                    @if(!empty($fin['total_amount']))
                                        <div class="small">Total: {{ number_format($fin['total_amount']) }}</div>
                                        @if(!empty($fin['paid_amount']))
                                            <div class="small text-success">Paid: {{ number_format($fin['paid_amount']) }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($order->status === 'pre_production')
                                        <a href="{{ route('production.edit', $order->id) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-gear-fill me-1"></i>Prepare
                                        </a>
                                    @else
                                        <a href="{{ route('production.show', $order->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-kanban me-1"></i>Track
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                                    No production projects found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>
@endsection
