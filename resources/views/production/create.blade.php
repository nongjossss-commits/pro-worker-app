@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold">Start New Production Project</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('production.store') }}" method="POST">
                        @csrf

                        <!-- Employer Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Employer <span class="text-danger">*</span></label>
                            <!-- Using a simple select for now, ideally an AJAX search for scalability -->
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <select name="employer_id" class="form-select" required>
                                    <option value="">-- Choose Employer --</option>
                                    @php
                                        // Fetching directly in view for simplicity as per "First Draft" instruction
                                        // In production refactor to controller passing variable
                                        $employers = \App\Models\Employer::select('id', 'employerNameTh', 'employerNameEn', 'employerId')->limit(100)->get();
                                    @endphp
                                    @foreach($employers as $emp)
                                        <option value="{{ $emp->id }}" {{ (isset($employerId) && $employerId == $emp->id) ? 'selected' : '' }}>
                                            {{ $emp->employerId }} - {{ $emp->employerNameEn }} ({{ $emp->employerNameTh }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text">Choose the employer this project belongs to.</div>
                        </div>

                        <!-- Pre-selected Employees -->
                        @if(isset($preSelectedEmployees) && $preSelectedEmployees->count() > 0)
                            <div class="mb-4">
                                <label class="form-label fw-bold">Selected Employees ({{ $preSelectedEmployees->count() }})</label>
                                <div class="card p-2 bg-light border">
                                    <ul class="list-unstyled mb-0" style="max-height: 150px; overflow-y: auto;">
                                        @foreach($preSelectedEmployees as $emp)
                                            <li class="d-flex align-items-center mb-1">
                                                <i class="bi bi-person-fill me-2 text-muted"></i>
                                                <small>{{ $emp->employeeNameTh }} ({{ $emp->employeeNameEn }})</small>
                                                <input type="hidden" name="selected_employees[]" value="{{ $emp->id }}">
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <!-- Project Details -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Project Information</label>
                            <div class="mb-2">
                                <input type="text" name="project_name" class="form-control" placeholder="Project Name (e.g. MOU Import October)" required>
                            </div>
                            <div>
                                <textarea name="description" class="form-control" rows="3" placeholder="Project Description / Notes"></textarea>
                            </div>
                        </div>

                        <!-- Initial Financials -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Financial / Business Info (Optional)</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="text" name="financial[quotation_no]" class="form-control" placeholder="Quotation No.">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="financial[invoice_no]" class="form-control" placeholder="Invoice No.">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" step="0.01" name="financial[total_amount]" class="form-control" placeholder="Total Amount">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" step="0.01" name="financial[paid_amount]" class="form-control" placeholder="Paid / Deposit">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('production.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                Create Project & Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
