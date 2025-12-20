@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Upload New PDF Template</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.pdf-templates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Work Permit Application Form">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Template Type</label>
                            <select name="type" class="form-select" id="typeSelect" x-data="{ type: 'global' }" x-model="type">
                                <option value="global">Global (All Employers)</option>
                                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                                    <option value="employer">Specific Employer</option>
                                @elseif(auth()->user()->hasRole('employer'))
                                    <option value="employer" selected>My Organization</option>
                                @endif
                            </select>
                        </div>

                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff'))
                        <div class="mb-3" id="employerSelectDiv" style="display: none;">
                            <label class="form-label">Select Employer</label>
                            <select name="employer_id" class="form-select">
                                <option value="">-- Choose Employer --</option>
                                @foreach($employers as $employer)
                                    <option value="{{ $employer->id }}">{{ $employer->employerNameTh }} ({{ $employer->employerNameEn }})</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="employer_id" value="{{ auth()->user()->employer->id ?? '' }}">
                        @endif

                        <div class="mb-4">
                            <label class="form-label">Upload PDF File</label>
                            <input type="file" name="file" class="form-control" accept="application/pdf" required>
                            <div class="form-text">Max size: 10MB. Must be a valid PDF file.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Upload & Go to Builder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('typeSelect');
        const employerSelectDiv = document.getElementById('employerSelectDiv');

        function toggleEmployerSelect() {
            if (employerSelectDiv) {
                if (typeSelect.value === 'employer') {
                    employerSelectDiv.style.display = 'block';
                } else {
                    employerSelectDiv.style.display = 'none';
                }
            }
        }

        if(typeSelect) {
            typeSelect.addEventListener('change', toggleEmployerSelect);
            toggleEmployerSelect(); // Initial state
        }
    });
</script>
@endpush
@endsection
