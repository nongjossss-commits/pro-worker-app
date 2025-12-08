@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Pre-Production Projects</h1>
            <p class="text-muted">Prepare and organize jobs before sending to workflow</p>
        </div>
        <a href="{{ route('production.create') }}" class="btn btn-warning text-dark">
            <i class="bi bi-plus-lg me-2"></i>New Pre-Production Job
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Project Name</th>
                            <th>Type</th>
                            <th>Employer/Context</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $order->project_name ?? 'Untitled Project' }}</div>
                                    <div class="small text-muted">{{ Str::limit($order->description, 50) }}</div>
                                </td>
                                <td>
                                    @if($order->type === 'independent')
                                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Independent</span>
                                    @else
                                        <span class="badge bg-primary">Employer</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->type === 'employer' && $order->employer)
                                        <div class="fw-bold">{{ $order->employer->name_en ?? $order->employer->name_th }}</div>
                                        <div class="small text-muted">{{ $order->employer->employer_id }}</div>
                                    @elseif($order->type === 'independent')
                                        <div class="text-muted fst-italic">Mixed / Independent</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info rounded-pill">{{ $order->items_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="small text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('production.edit', $order->id) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-gear-fill me-1"></i>Prepare
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                                    No pre-production jobs found.
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
