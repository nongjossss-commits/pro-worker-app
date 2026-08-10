@extends('labor.layout')

@section('title', 'Bills - Pro Walker Labor')

@section('content')
@include('labor.partials.finance-tabs')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Bills') }}</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateBillModal">
        <i class="bi bi-file-earmark-plus me-1"></i>{{ __('Generate Bill') }}
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-2">{{ __('Billing Header') }}</h6>
        <p class="text-muted small mb-3">{{ __('Which company letterhead (from the main program\'s Financial Profiles) new bills are issued under. Past bills keep whichever profile they were issued with.') }}</p>
        <form method="POST" action="{{ route('labor.billing-settings.update') }}" class="row g-2 align-items-end">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <select name="financial_profile_id" class="form-select">
                    <option value="">-- {{ __('None selected') }} --</option>
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" {{ (string) $currentProfileId === (string) $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }} @if($profile->tax_id) ({{ $profile->tax_id }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
            </div>
        </form>
        @if($profiles->isEmpty())
        <div class="alert alert-warning small mt-3 mb-0">
            {{ __('No biller-type Financial Profile exists yet in the main program. Create one there first (Finance → Profiles).') }}
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Bill No.') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Period') }}</th>
                    <th class="text-end">{{ __('Previous Balance') }}</th>
                    <th class="text-end">{{ __('Period Charges') }}</th>
                    <th class="text-end">{{ __('Total Due') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th>{{ __('Issued') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                <tr>
                    <td><a href="{{ route('labor.bills.show', $bill) }}">{{ $bill->bill_no }}</a></td>
                    <td>{{ $bill->team->name ?? '-' }}</td>
                    <td class="text-nowrap">{{ $bill->period_start->format('d/m/Y') }} - {{ $bill->period_end->format('d/m/Y') }}</td>
                    <td class="text-end">{{ number_format($bill->previous_balance, 2) }}</td>
                    <td class="text-end">{{ number_format($bill->period_charges, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($bill->total_due, 2) }}</td>
                    <td class="text-center">
                        @if($bill->status === 'void')
                            <span class="badge bg-danger">{{ __('Void') }}</span>
                        @else
                            <span class="badge bg-success">{{ __('Issued') }}</span>
                        @endif
                        @if($bill->is_auto_generated)
                            <span class="badge bg-secondary" title="{{ __('Auto-generated') }}"><i class="bi bi-robot"></i></span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ optional($bill->issued_at)->format('d/m/Y H:i') }}
                        <br>{{ $bill->creator->name ?? __('System') }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('labor.bills.download', $bill) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('Preview / Download') }}">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                        @if($bill->status !== 'void')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#voidBillModal{{ $bill->id }}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        @endif
                    </td>
                </tr>

                @if($bill->status !== 'void')
                <div class="modal fade" id="voidBillModal{{ $bill->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.bills.void', $bill) }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Void Bill') }} {{ $bill->bill_no }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">{{ __('Reason') }}</label>
                                    <input type="text" name="void_reason" class="form-control" required autofocus>
                                    <div class="form-text">{{ __('The bill stays on record for audit — it just gets marked void.') }}</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-danger">{{ __('Void') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">{{ __('No bills generated yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bills->hasPages())
    <div class="card-footer bg-white">
        {{ $bills->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="generateBillModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.bills.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Generate Bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team') }}</label>
                        <select name="labor_team_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Period Start') }}</label>
                            <input type="date" name="period_start" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Period End') }}</label>
                            <input type="date" name="period_end" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Billing Header (optional override)') }}</label>
                        <select name="financial_profile_id" class="form-select">
                            <option value="">-- {{ __('Use current default') }} --</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-light border small mb-0">
                        {{ __('Includes every ledger entry dated within the period, plus the balance carried forward from before it. Charges are not "closed" by billing — they stay outstanding until an actual payment is recorded.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Generate') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
