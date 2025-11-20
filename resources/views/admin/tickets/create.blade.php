{{-- resources/views/admin/tickets/create.blade.php --}}
@extends('layouts.app')

@section('title', __('Admin: Create New Ticket'))

@section('content')
{{-- V2.4-S13: Initialize component for the Admin Create context.
    - `is_admin_create_view: true` enables the dynamic employer logic.
    - `employerId: old('employer_user_id')` ensures that if validation fails,
      the previously selected employer is restored and their employees are pre-fetched.
--}}
<div class="content-section" x-data="hybridAttachmentManager({
    is_admin_create_view: true,
    employerId: {{ old('employer_user_id', 'null') }}
})">

    <h2 class="mb-4">{{ __('Create New Ticket (Admin/Staff)') }}</h2>

    {{-- Error Display --}}
    @if (session('danger'))
        <div class="alert alert-danger mb-4" role="alert">
            {{ session('danger') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>{{ __('Errors Found:') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- The form now points to the admin store route --}}
    <form action="{{ route('admin.tickets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Hidden File Input for general attachments --}}
        <input type="file" multiple class="d-none" x-ref="generalFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)">

        <div class="row">
            {{-- Column 1: Main Information (Left Side) --}}
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">{{ __('Request Details') }}</div>
                    <div class="card-body">

                        {{-- V2.4-S12: Employer Selection Dropdown --}}
                        <div class="mb-3">
                            <label for="employer_user_id" class="form-label">{{ __('Select Employer') }} <span class="text-danger">*</span></label>
                            <select
                                class="form-select @error('employer_user_id') is-invalid @enderror"
                                id="employer_user_id"
                                name="employer_user_id"
                                required
                                {{-- V2.4-S13: This is the critical integration link.
                                     - `x-model="contextEmployerId"` binds the select value to our Alpine state.
                                     - `@change="handleEmployerChange($event.target.value)"` calls our new handler
                                       whenever the user selects a different employer.
                                --}}
                                x-model="contextEmployerId"
                                @change="handleEmployerChange($event.target.value)">
                                <option value="">-- {{ __('Select Employer') }} --</option>
                                @foreach($employers as $employerUser)
                                    {{-- The option value is the user ID, matching the backend validation --}}
                                    <option value="{{ $employerUser->id }}" {{ old('employer_user_id') == $employerUser->id ? 'selected' : '' }}>
                                        {{ $employerUser->employer->employerNameTh ?? 'N/A' }} ({{ $employerUser->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employer_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">{{ __('Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">{{ __('Message / Details') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="8" required>{{ old('message') }}</textarea>
                             @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 2: Attachment Basket (Right Side) --}}
            <div class="col-lg-5">
                <div class="card mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header">{{ __('Attachments (Attachment Basket)') }}</div>
                    <div class="card-body">

                        {{-- Upload Progress Bar --}}
                        <div x-show="isUploading" class="mb-3">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'">
                                    {{ __('Uploading') }} (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                </div>
                            </div>
                        </div>

                        {{-- V2.4-S13: Attachment buttons are now disabled if no employer is selected --}}
                        <div class="d-grid gap-2 mb-3" :class="{ 'opacity-50': isUploading || !contextEmployerId }">
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading || !contextEmployerId" title="{{ __('Please select employer first') }}">
                                <i class="bi bi-person-check me-2"></i> {{ __('Attach Existing Employee') }}
                            </button>
                            <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading || !contextEmployerId" title="{{ __('Please select employer first') }}">
                                <i class="bi bi-person-plus me-2"></i> {{ __('Register New Employee') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" :disabled="isUploading" title="{{ __('Attach General File') }}">
                                <i class="bi bi-file-earmark-arrow-up me-2"></i> {{ __('Attach File/Image') }}
                            </button>
                        </div>
                        <hr>

                        {{-- Basket Display Area --}}
                        <h6 class="mb-2">{{ __('Attached Items') }} (<span x-text="totalItemsCount()"></span> {{ __('items') }})</h6>
                        <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                            <template x-if="totalItemsCount() === 0">
                                <div class="text-muted fst-italic text-center py-3">{{ __('No items attached yet') }}</div>
                            </template>
                            {{-- Templates for existing_employees, new_employees, and files are identical to the employer view --}}
                            @include('tickets.partials._basket_display_templates')
                        </div>
                        <hr>

                        {{-- Submit Button --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" :disabled="isUploading">
                                <template x-if="!isUploading">
                                    <span><i class="bi bi-send-fill me-2"></i> {{ __('Create Ticket') }}</span>
                                </template>
                                <template x-if="isUploading">
                                    <span><span class="spinner-border spinner-border-sm me-2"></span>{{ __('Processing...') }}</span>
                                </template>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Modals MUST be inside the x-data scope to function correctly --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div>
@endsection

@push('scripts')
{{-- Load the unified script component --}}
@include('components.hybrid-attachment-scripts')
@endpush
