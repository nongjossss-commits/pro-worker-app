@extends('layouts.app')

@section('title', __('Import Employees'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Import Employees from Excel') }}</h5>
                </div>
                <div class="card-body p-4">

                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <strong>{{ __('Import completed with some errors:') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            {{ __('Use this feature to create multiple employees at once by uploading an Excel file (.xlsx, .xlsm).') }}<br>
                            {{ __('Please download the template below, fill in the data, and upload it back.') }}
                            <br>
                            <small class="text-muted">{{ __('Note: You can import photos by inserting them into the "Photo" column in the Excel file.') }}</small>
                        </div>
                    </div>

                    <div class="mb-4 text-center">
                        <a href="{{ route('employees.template') }}" class="btn btn-outline-primary">
                            <i class="bi bi-download me-2"></i>{{ __('Download Excel Template') }}
                        </a>
                    </div>

                    <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="employer_id" class="form-label fw-bold required">{{ __('Select Employer') }}</label>
                            <select name="employer_id" id="employer_id" class="form-select @error('employer_id') is-invalid @enderror" required>
                                <option value="">-- {{ __('Select Employer') }} --</option>
                                @foreach($employers as $employer)
                                    <option value="{{ $employer->id }}">{{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})</option>
                                @endforeach
                            </select>
                            @error('employer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('All imported employees will be assigned to this employer.') }}</div>
                        </div>

                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold required">{{ __('Upload File (Excel)') }}</label>
                            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx, .xls, .xlsm" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('employees.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-upload me-2"></i>{{ __('Import Employees') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Imported Employees Summary Modal --}}
@if(session('imported_employees'))
<div class="modal fade" id="importedEmployeesModal" tabindex="-1" aria-labelledby="importedEmployeesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="importedEmployeesModalLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ __('Import Successful') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    {{ __('Successfully imported') }} <strong>{{ count(session('imported_employees')) }}</strong> {{ __('employees.') }}
                    {{ __('Please review the list below. You can edit any missing or incorrect information.') }}
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 80px;">{{ __('Photo') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Nationality') }}</th>
                                <th>{{ __('Passport No.') }}</th>
                                <th>{{ __('Work Permit') }}</th>
                                <th style="width: 100px;">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('imported_employees') as $employee)
                                <tr>
                                    <td class="text-center">
                                        <img src="{{ $employee->photo_url }}" alt="Photo" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $employee->employeeNameTh }}</div>
                                        <div class="text-muted small">{{ $employee->employeeNameEn }}</div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $flag = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                                        @endphp
                                        @if($flag)
                                            <img src="{{ asset('images/flags/'.strtolower($flag).'.png') }}" alt="{{ $employee->employeeNationality }}" width="24" class="me-1">
                                        @endif
                                        {{ $employee->employeeNationality }}
                                    </td>
                                    <td class="text-center">{{ $employee->employeePassport ?? '-' }}</td>
                                    <td class="text-center">{{ $employee->employeeWorkPermit ?? '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('employees.edit', $employee->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <a href="{{ route('employees.index') }}" class="btn btn-primary">{{ __('Go to Employee List') }}</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('importedEmployeesModal'));
        myModal.show();
    });
</script>
@endpush
@endif

@endsection
