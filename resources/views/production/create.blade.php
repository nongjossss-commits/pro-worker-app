@extends('layouts.app')

@section('content')
<div class="container py-4" x-data="productionCreator()">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold">Start New Pre-Production Job</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('production.store') }}" method="POST">
                        @csrf

                        <!-- Job Type Selection -->
                        <div class="mb-4 text-center">
                            <label class="form-label fw-bold d-block mb-3">Job Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type" id="type_employer" value="employer"
                                    x-model="jobType" autocomplete="off" {{ $jobType === 'employer' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="type_employer">
                                    <i class="bi bi-building me-1"></i> Employer Job
                                </label>

                                <input type="radio" class="btn-check" name="type" id="type_independent" value="independent"
                                    x-model="jobType" autocomplete="off" {{ $jobType === 'independent' ? 'checked' : '' }}>
                                <label class="btn btn-outline-purple" for="type_independent" style="--bs-btn-color: #6f42c1; --bs-btn-border-color: #6f42c1; --bs-btn-hover-bg: #6f42c1; --bs-btn-hover-color: white; --bs-btn-active-bg: #6f42c1; --bs-btn-active-color: white;">
                                    <i class="bi bi-people me-1"></i> Independent / Mixed
                                </label>
                            </div>
                            <div class="form-text mt-2" x-text="jobType === 'employer' ? 'Links this job to a specific employer. Employees should belong to this employer.' : 'Allows employees from different employers. No single employer is linked to the job.'"></div>
                        </div>

                        <!-- Employer Selection (Only if Type is Employer) -->
                        <div class="mb-4" x-show="jobType === 'employer'" x-transition>
                            <label class="form-label fw-bold">Select Employer <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <select name="employer_id" class="form-select" :required="jobType === 'employer'">
                                    <option value="">-- Choose Employer --</option>
                                    @foreach($employers as $emp)
                                        <option value="{{ $emp->id }}" {{ (isset($employerId) && $employerId == $emp->id) ? 'selected' : '' }}>
                                            {{ $emp->name_en ?? $emp->name_th }} ({{ $emp->employer_id ?? $emp->id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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
                                                <small>{{ $emp->fullname_th ?? $emp->name_th }} ({{ $emp->fullname_en ?? $emp->name_en }})</small>
                                                <!-- Hidden input to pass IDs -->
                                                <input type="hidden" name="selected_employees[]" value="{{ $emp->id }}">
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="form-text text-warning" x-show="jobType === 'independent' && {{ $preSelectedEmployees->pluck('employer_id')->unique()->count() > 1 ? 'true' : 'false' }}">
                                    <i class="bi bi-info-circle"></i> Employees from mixed employers detected. Switched to Independent mode.
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

<script>
function productionCreator() {
    return {
        jobType: '{{ $jobType }}',
        init() {
            // Watch for changes if needed
        }
    }
}
</script>
@endsection
