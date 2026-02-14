{{-- resources/views/tickets/create.blade.php --}}
@extends('layouts.app')

@section('title', 'สร้างคำขอใหม่')

@section('content')
{{-- Initialize Alpine.js Component (V2.4-S11 Update) --}}
<div class="content-section" x-data="hybridAttachmentManager({
    employerId: @json(optional(Auth::user()->employer)->id),
    preselectedEmployeeIds: @json(session('preselected_employee_ids', []))
})">

    <h2 class="mb-4">{{ __('Create New Request (Smart Ticket)') }}</h2>

    {{-- Error Display --}}
    @if (session('error'))
        <div class="alert alert-danger mb-4" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>{{ __('Errors found') }}:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Hidden inputs for basket items --}}
        {{-- Separated from display templates to ensure reliable form submission --}}
        @include('tickets.partials._basket_form_inputs')

        {{-- V2.4-S7: Hidden File Input (Triggered by the button) --}}
        <input type="file" multiple class="d-none" id="general-attachment-input" x-ref="generalFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)" onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">

        <div class="row">
            {{-- Column 1: Main Information (Left Side) --}}
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">{{ __('Request Details') }}</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="subject" class="form-label">{{ __('Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="เช่น แจ้งเข้าพนักงานใหม่ 2 คน, แก้ไขเอกสาร Passport">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">{{ __('Message/Additional Details') }}</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="8">{{ old('message') }}</textarea>
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
                    <div class="card-header">{{ __('Attachments') }}</div>
                    <div class="card-body">

                        {{-- V2.4-S7: Upload Progress Bar --}}
                        <div x-show="isUploading" class="mb-3">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
                                    {{ __('Uploading') }} (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                </div>
                            </div>
                        </div>

                        {{-- Attachment Buttons (V2.4-S7: Enable the final button and disable during upload) --}}
                        <div class="d-grid gap-2 mb-3" :class="{ 'opacity-50': isUploading }">
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading">
                                <i class="bi bi-person-check me-2"></i> {{ __('Attach Existing Employee') }}
                            </button>
                            <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading">
                                <i class="bi bi-person-plus me-2"></i> {{ __('Attach New Employee/Register') }}
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary flex-grow-1" @click="triggerScanner" :disabled="isUploading">
                                    <i class="bi bi-camera me-2"></i> {{ __('Scan') }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary flex-grow-1" @click="triggerFileInput" :disabled="isUploading">
                                    <i class="bi bi-file-earmark-arrow-up me-2"></i> {{ __('Attach File') }}
                                </button>
                            </div>
                        </div>
                        <hr>

                        {{-- Basket Display Area --}}
                        <h6 class="mb-2">{{ __('Attached Items') }} (<span x-text="totalItemsCount()"></span> รายการ)</h6>
                        <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                            <template x-if="totalItemsCount() === 0">
                                <div class="text-muted fst-italic text-center py-3">{{ __('No items attached') }}</div>
                            </template>
                            {{-- Include shared templates --}}
                            @include('tickets.partials._basket_display_templates')
                        </div>
                        <hr>

                        {{-- Submit Button (V2.4-S7: Disable while uploading) --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" :disabled="isUploading">
                                <template x-if="!isUploading">
                                    <span><i class="bi bi-send-fill me-2"></i> {{ __('Submit Request') }}</span>
                                </template>
                                <template x-if="isUploading">
                                    <span><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Loading...') }}</span>
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
{{-- V2.4-S11: Load the unified script component --}}
@include('components.hybrid-attachment-scripts')
@endpush
