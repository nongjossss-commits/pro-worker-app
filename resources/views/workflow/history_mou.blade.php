@extends('layouts.app')

@section('title', 'MOU Import History')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2"></i>{{ __('MOU Import History') }}</h1>
            <p class="text-muted mb-0">{{ __('Completed projects and their employee records.') }}</p>
        </div>
        <a href="{{ route('workflow.index', ['tab' => 'mou_import']) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('Back to Workflow') }}
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">{{ __('Project Name') }}</th>
                            <th>{{ __('Employer') }}</th>
                            <th>{{ __('Completed At') }}</th>
                            <th class="text-center">{{ __('Employees') }}</th>
                            <th>{{ __('Created By') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">{{ $order->project_name }}</td>
                                <td>{{ $order->employer->employerNameTh ?? 'N/A' }}</td>
                                <td>{{ $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill">{{ $order->items->count() }}</span>
                                </td>
                                <td>{{ $order->creator->name ?? 'System' }}</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#history-{{ $order->id }}">
                                        <i class="bi bi-eye"></i> {{ __('Details') }}
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="p-0 border-0">
                                    <div class="collapse bg-light p-3" id="history-{{ $order->id }}">
                                        <h6 class="fw-bold mb-3">{{ __('Employee List') }}</h6>
                                        <div class="row g-2">
                                            @foreach($order->items as $item)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="card border shadow-sm h-100">
                                                        <div class="card-body d-flex align-items-center gap-3 py-2">
                                                            <div class="avatar">
                                                                @if($item->employee && $item->employee->employeePhoto)
                                                                    <img src="{{ asset('storage/'.$item->employee->employeePhoto) }}" class="rounded-circle" width="40" height="40">
                                                                @else
                                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                        <i class="bi bi-person"></i>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold small">{{ $item->employee->employeeNameEn ?? 'N/A' }}</div>
                                                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $item->employee->employeePassport ?? '-' }}</div>
                                                            </div>
                                                            <div class="ms-auto text-end">
                                                                <span class="badge bg-success">{{ __('Completed') }}</span>
                                                                <div class="text-muted small" style="font-size: 0.65rem;">{{ $item->completed_at ? $item->completed_at->format('d/m/Y') : '' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-archive fs-1 d-block mb-3 opacity-50"></i>
                                    {{ __('No history found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
