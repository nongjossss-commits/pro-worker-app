@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold">Workflow Dashboard</h1>
            <p class="text-muted">Track active projects and tasks</p>
        </div>
        {{-- No Create Button here as per requirement --}}
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Project Name</th>
                            <th>Type</th>
                            <th>Employer</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Progress</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $order->project_name ?? 'Untitled Project' }}</div>
                                    <div class="small text-muted">{{ Str::limit($order->description, 50) }}</div>
                                    <div class="small text-muted">Started {{ $order->updated_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    @if($order->type === 'independent')
                                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Independent</span>
                                    @else
                                        <span class="badge bg-primary">Standard</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->type === 'employer' && $order->employer)
                                        <div class="fw-bold">{{ $order->employer->name_en ?? $order->employer->name_th }}</div>
                                    @elseif($order->type === 'independent')
                                        <div class="text-muted fst-italic">Mixed / Independent</div>
                                    @else
                                        <span class="text-danger">Unknown</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info rounded-pill">{{ $order->items_count }}</span>
                                </td>
                                <td class="text-center">
                                    {{-- Placeholder for progress bar later --}}
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('workflow.show', $order->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-kanban me-1"></i>Track Board
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No active workflows found.
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
