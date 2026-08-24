@extends('labor.layout')

@section('title', __('Contract History'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">{{ __('Contract History') }} (ประวัติการเบิกสัญญา)</h4>
        <a href="{{ route('labor.contracts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Issue Contract') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('labor.contracts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ __('Employer name or contract number...') }}">
                </div>
                @if($seesAllTeams)
                    <div class="col-md-4">
                        <label class="form-label small">{{ __('Team') }}</label>
                        <select name="team_id" class="form-select">
                            <option value="">{{ __('All Teams') }}</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i>{{ __('Search') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Contract No.') }}</th>
                        <th>{{ __('Employer') }}</th>
                        <th>{{ __('Template') }}</th>
                        <th>{{ __('Issued By') }}</th>
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Issued At') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                    <tr>
                        <td class="font-monospace">{{ $contract->contract_no }}</td>
                        <td>{{ $contract->employer_name_snapshot ?? '-' }}</td>
                        <td>{{ $contract->template->name ?? '-' }}</td>
                        <td>{{ $contract->issuer->name ?? '-' }} @if($contract->issuer?->staff_code) ({{ $contract->issuer->staff_code }}) @endif</td>
                        <td>{{ $contract->team->name ?? '-' }}</td>
                        <td class="text-nowrap">{{ $contract->issued_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('labor.contracts.view', $contract) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ __('Preview') }}">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary" title="{{ __('Download PDF') }}" data-bs-toggle="modal" data-bs-target="#downloadChoiceModal" data-download-url="{{ route('labor.contracts.download', $contract) }}">
                                <i class="bi bi-download"></i>
                            </button>
                            <a href="{{ route('labor.contracts.edit', $contract) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No contracts issued yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
        <div class="card-footer bg-white">{{ $contracts->links() }}</div>
        @endif
    </div>
</div>

@include('labor.contracts._download_choice_modal')
@endsection
