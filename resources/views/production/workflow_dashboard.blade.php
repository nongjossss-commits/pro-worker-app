@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="text-uppercase text-primary small fw-bold">Active Workflow Tracking</div>
            <h2 class="fw-bold mb-0">{{ $production->project_name ?? 'Untitled Project' }}</h2>
            <div class="text-muted">{{ $production->employer->name_en ?? $production->employer->name_th }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
            <!-- Maybe a "Complete Project" button later -->
        </div>
    </div>

    <!-- Stats / Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body">
                    <div class="small opacity-75">Total Employees</div>
                    <div class="fs-2 fw-bold">{{ $production->items->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Employee Tracking List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Passport</th>
                            <th>Latest Update</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($production->items as $item)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $item->employee->fullname_th ?? '-' }}</div>
                                    <div class="small text-muted">{{ $item->employee->fullname_en ?? '-' }}</div>
                                </td>
                                <td>{{ $item->employee->employeePassport ?? '-' }}</td>
                                <td>
                                    {{ $item->updated_at->diffForHumans() }}
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('workflow.item.show', $item->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye-fill me-1"></i>Open Timeline
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">No employees in this project.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
