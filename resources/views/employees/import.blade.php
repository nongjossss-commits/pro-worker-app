@extends('layouts.app')

@section('title', __('Import Employees'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Import Employees from Excel/CSV') }}</h5>
                </div>
                <div class="card-body p-4">

                    @if(session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0)
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">{{ __('Import Failed') }}</h5>
                            <p>{{ __('The import process was stopped due to the following errors:') }}</p>
                            <hr>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            {{ __('Use this feature to create multiple employees at once by uploading an Excel file.') }}<br>
                            {{ __('Please download the template below, fill in the data, and upload it back.') }}
                             <br>
                            <small class="text-muted">{{ __('Note: Ensure data format is correct. Photos can be inserted directly into the designated cell in the Excel file.') }}</small>
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
                            <label for="file" class="form-label fw-bold required">{{ __('Upload Excel File') }}</label>
                            <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx, .xls" required>
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
@endsection
