@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Bank Accounts') }}</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus"></i> {{ __('Add Bank Account') }}
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('Bank Name') }}</th>
                            <th>{{ __('Account Name') }}</th>
                            <th>{{ __('Account Number') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Current Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                            <tr>
                                <td>{{ $account->bank_name }}</td>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ $account->branch }}</td>
                                <td class="text-end fw-bold {{ $account->current_balance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($account->current_balance, 2) }}
                                </td>
                                <td>
                                    @if($account->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $account->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $account->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('finance.bank-accounts.update', $account) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('Edit Bank Account') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label>{{ __('Bank Name') }} *</label>
                                                    <input type="text" name="bank_name" class="form-control" value="{{ $account->bank_name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>{{ __('Account Name') }}</label>
                                                    <input type="text" name="account_name" class="form-control" value="{{ $account->account_name }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label>{{ __('Account Number') }}</label>
                                                    <input type="text" name="account_number" class="form-control" value="{{ $account->account_number }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label>{{ __('Branch') }}</label>
                                                    <input type="text" name="branch" class="form-control" value="{{ $account->branch }}">
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ $account->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ __('Active') }}</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $accounts->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('finance.bank-accounts.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Bank Account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>{{ __('Bank Name') }} *</label>
                        <input type="text" name="bank_name" class="form-control" required placeholder="{{ __('e.g., KBank, SCB, Cash in Register') }}">
                    </div>
                    <div class="mb-3">
                        <label>{{ __('Account Name') }}</label>
                        <input type="text" name="account_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>{{ __('Account Number') }}</label>
                        <input type="text" name="account_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>{{ __('Branch') }}</label>
                        <input type="text" name="branch" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>{{ __('Initial Balance') }} *</label>
                        <input type="number" step="0.01" name="initial_balance" class="form-control" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
