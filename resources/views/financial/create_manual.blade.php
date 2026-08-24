@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ __('Create Manual Bill') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('finance.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="employer_id" class="form-label">{{ __('Select Employer') }} <span class="text-danger">*</span></label>
                            <select name="employer_id" id="employer_id" class="form-select" required>
                                <option value="">-- {{ __('Select Employer') }} --</option>
                                @foreach($employers as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employer_id', request('employer_id')) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employerNameTh }} {{ $emp->employerNameEn ? '('.$emp->employerNameEn.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('The bill will be linked to this employer.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Bill Description') }}</label>
                            <input type="text" class="form-control" id="description" name="description" value="{{ old('description') }}" placeholder="{{ __('e.g. Service Fee for X') }}">
                            <div class="form-text">{{ __('Leave blank to use default (Manual Bill - Date).') }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bill_date" class="form-label">{{ __('Bill Date') }}</label>
                                <input type="date" class="form-control" id="bill_date" name="bill_date" value="{{ old('bill_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">{{ __('Initial Amount (Optional)') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00">
                                </div>
                                <div class="form-text">{{ __('If entered, a pending transaction will be created immediately.') }}</div>
                            </div>
                        </div>

                        @if(isset($selectedEmployees) && $selectedEmployees->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">{{ __('Selected Employees') }} <span class="badge bg-info text-white">{{ $selectedEmployees->count() }}</span></label>
                            <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <ul class="list-unstyled mb-0">
                                    @foreach($selectedEmployees as $emp)
                                        <li class="mb-1">
                                            <i class="bi bi-person me-2 text-secondary"></i>
                                            {{ $emp->employeeNameTh ?? $emp->employeeNameEn }}
                                            @if($emp->employer_employee_id)
                                                <span class="text-muted small">({{ $emp->employer_employee_id }})</span>
                                            @endif
                                            <input type="hidden" name="employee_ids[]" value="{{ $emp->id }}">
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="form-text text-success"><i class="bi bi-info-circle me-1"></i>{{ __('These employees will be automatically added to the bill.') }}</div>
                        </div>
                        @endif

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('finance.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> {{ __('Create Bill & Continue') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
