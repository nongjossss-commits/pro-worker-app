@extends('labor.layout')

@section('title', 'Customers - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('External Customers') }}</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="bi bi-person-plus me-1"></i>{{ __('Add Customer') }}
    </button>
</div>
<p class="text-muted small">{{ __('Each team\'s own external customer — recorded once here, then reused every time an invoice needs to be placed with them. Team is fixed at creation and cannot be changed afterwards.') }}</p>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('Team') }}</label>
                <select name="team_id" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}" {{ (string) request('team_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-secondary w-100">{{ __('Filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Tax ID') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Invoices') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->team->name ?? '-' }}</td>
                    <td>{{ $customer->tax_id ?? '-' }}</td>
                    <td class="text-center">
                        @if($customer->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-end">{{ $customer->tax_invoices_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('labor.tax-invoices.create', ['labor_customer_id' => $customer->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-plus"></i> {{ __('New Invoice') }}
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $customer->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('labor.customers.destroy', $customer) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Remove this customer? Existing invoices keep their record.') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editCustomerModal{{ $customer->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('labor.customers.update', $customer) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit Customer') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Team') }}</label>
                                        <input type="text" class="form-control" value="{{ $customer->team->name ?? '-' }}" disabled>
                                        <div class="form-text">{{ __('Team is locked at creation and cannot be changed.') }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" class="form-control" value="{{ $customer->name }}" required>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Tax ID') }}</label>
                                            <input type="text" name="tax_id" class="form-control" maxlength="15" value="{{ $customer->tax_id }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Branch') }}</label>
                                            <input type="text" name="branch" class="form-control" maxlength="50" value="{{ $customer->branch }}">
                                        </div>
                                    </div>
                                    <div class="mb-3 mt-2">
                                        <label class="form-label">{{ __('Address') }}</label>
                                        <textarea name="address" class="form-control" rows="2">{{ $customer->address }}</textarea>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Contact Name') }}</label>
                                            <input type="text" name="contact_name" class="form-control" value="{{ $customer->contact_name }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('Contact Phone') }}</label>
                                            <input type="text" name="contact_phone" class="form-control" maxlength="30" value="{{ $customer->contact_phone }}">
                                        </div>
                                    </div>
                                    <div class="mb-3 mt-2">
                                        <label class="form-label">{{ __('Notes') }}</label>
                                        <textarea name="notes" class="form-control" rows="2">{{ $customer->notes }}</textarea>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                               id="customerActive{{ $customer->id }}" {{ $customer->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customerActive{{ $customer->id }}">{{ __('Active') }}</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('No customers yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white">
        {{ $customers->links() }}
    </div>
    @endif
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.customers.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Team') }}</label>
                        <select name="labor_team_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Cannot be changed after creating — pick carefully.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" required autofocus>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Tax ID') }}</label>
                            <input type="text" name="tax_id" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Branch') }}</label>
                            <input type="text" name="branch" class="form-control" maxlength="50">
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">{{ __('Address') }}</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Contact Name') }}</label>
                            <input type="text" name="contact_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Contact Phone') }}</label>
                            <input type="text" name="contact_phone" class="form-control" maxlength="30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
