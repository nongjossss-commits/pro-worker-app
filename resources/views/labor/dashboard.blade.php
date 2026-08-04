@extends('labor.layout')

@section('title', 'Dashboard - Pro Walker Labor')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0 bg-dark text-white">
            <div class="card-body">
                <div class="text-white-50 small text-uppercase fw-bold">{{ __('Grand Total (All Teams)') }}</div>
                <div class="fs-2 fw-bold">{{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">{{ __('Total Teams') }}</div>
                <div class="fs-2 fw-bold">{{ $teams->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">{{ __('Teams Summary') }}</h5>
        @can('manage-labor-ledger')
        <a href="{{ route('labor.teams.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>{{ __('Manage Teams') }}
        </a>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Team') }}</th>
                    <th class="text-end">{{ __('Total Owed') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teams as $team)
                <tr>
                    <td>{{ $team->name }}</td>
                    <td class="text-end fw-bold {{ $team->total_owed > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($team->total_owed ?? 0, 2) }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.teams.show', $team) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>{{ __('View') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">{{ __('No teams yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
