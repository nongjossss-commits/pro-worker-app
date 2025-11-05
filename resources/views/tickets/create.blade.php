{{-- resources/views/tickets/create.blade.php --}}
@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่')
{{-- Ensure there are no duplicate directives here --}}
@section('content')
{{-- Initialize Alpine.js Component (V2.4-S11 Update) --}}
<div class="content-section" x-data="hybridAttachmentManager()">
    {{-- ... (Header, Error Display) ... --}}
    <h2 class="mb-4">สร้างคำขอใหม่ (Smart Ticket)</h2>

    {{-- Error Display --}}
    @if (session('error'))
        <div class="alert alert-danger mb-4" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>พบข้อผิดพลาด:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
@csrf
{{-- V2.4-S7: Hidden File Input (Triggered by the button) --}}
<input type="file" multiple class="d-none" x-ref="generalFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)">
<div class="row">
{{-- Column 1: Main Information (Left Side) - No Changes --}}
<div class="col-lg-7">
    <div class="card mb-4">
        <div class="card-header">รายละเอียดคำขอ</div>
        <div class="card-body">
            <div class="mb-3">
                <label for="subject" class="form-label">หัวเรื่อง <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="เช่น แจ้งเข้าพนักงานใหม่ 2 คน, แก้ไขเอกสาร Passport">
                @error('subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">ข้อความ/รายละเอียดเพิ่มเติม (ถ้ามี)</label>
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
<div class="card-header">สิ่งที่แนบมา (Attachment Basket)</div>
<div class="card-body">
{{-- V2.4-S7: Upload Progress Bar --}}
<div x-show="isUploading" class="mb-3">
<div class="progress" style="height: 25px;">
<div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
กำลังอัปโหลด (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
</div>
</div>
</div>
{{-- Attachment Buttons (V2.4-S7: Enable the final button and disable during upload) --}}
<div class="d-grid gap-2 mb-3" :class="{ 'opacity-50': isUploading }">
{{-- ... (Existing/New Employee buttons) ... --}}
<button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading">
<i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่
</button>
<button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading">
<i class="bi bi-person-plus me-2"></i> แนบลูกจ้างใหม่/แจ้งเข้า
</button>
{{-- ENABLE THIS BUTTON --}}
<button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" :disabled="isUploading">
<i class="bi bi-file-earmark-arrow-up me-2"></i> แนบไฟล์/รูปภาพ
</button>
</div>
<hr>
{{-- Basket Display Area --}}
<h6 class="mb-2">รายการที่แนบ (<span x-text="totalItemsCount()"></span> รายการ)</h6>
<div class="list-group" style="max-height: 300px; overflow-y: auto;">
<template x-if="totalItemsCount() === 0">
<div class="text-muted fst-italic text-center py-3">ยังไม่มีรายการที่แนบ</div>
</template>
                            {{-- Display Existing Employees (V2.4-S5E: Richer Display) --}}
                            <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center gap-3">
                                        {{-- V2.4-S6: Show Photo and Both Names --}}
                                        <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                                        <span>
                                            <i class="bi bi-person-check me-1 text-primary"></i>
                                            <span x-text="item.employeeNameTh"></span>
                                            <span class="text-muted" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                                        </span>
                                    </div>
                                    {{-- V2.4-S6: Use SweetAlert for deletion (removeConfirm) - Pass the name --}}
                                    <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
                                    <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
                                </div>
                            </template>
                            {{-- Display New Employees (V2.4-S6 Feature) --}}
                            <template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-person-plus fs-4 text-success"></i>
                                        <span>
                                            ใหม่: <span x-text="item.employeeNameTh"></span>
                                            <small class="text-muted d-block" x-text="'Passport: ' + item.employeePassport"></small>
                                        </span>
                                    </div>
                                    {{-- V2.4-S6: Use SweetAlert for deletion (removeConfirm) - Pass the name --}}
                                    <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
                                    {{-- Hidden input (JSON string) --}}
                                    <input type="hidden" :name="'attachments[new_employees][' + index + ']'" :value="JSON.stringify(item)">
                                </div>
                            </template>
{{-- ... (Existing/New Employees templates remain the same) ... --}}
{{-- V2.4-S7: Display General File Attachments --}}
<template x-for="(item, index) in basket.files" :key="'f-' + index">
<div class="list-group-item d-flex justify-content-between align-items-center py-2">
<div class="d-flex align-items-center gap-3">
<i class="bi bi-file-earmark-text fs-4 text-secondary"></i>
<span>
<a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
{{-- V2.4-S7: Display File Size --}}
<small class="text-muted d-block" x-text="formatBytes(item.size)"></small>
</span>
</div>
{{-- Use SweetAlert for deletion (removeConfirm) --}}
<button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
{{-- Hidden inputs for backend processing (Must match StoreTicketRequest validation) --}}
<input type="hidden" :name="'attachments[files][' + index + '][path]'" :value="item.path">
<input type="hidden" :name="'attachments[files][' + index + '][name]'" :value="item.name">
<input type="hidden" :name="'attachments[files][' + index + '][size]'" :value="item.size">
</div>
</template>
</div>
<hr>
{{-- Submit Button (V2.4-S7: Disable while uploading) --}}
<div class="d-grid">
<button type="submit" class="btn btn-primary btn-lg" :disabled="isUploading">
<template x-if="!isUploading">
<span><i class="bi bi-send-fill me-2"></i> ส่งคำขอ</span>
</template>
<template x-if="isUploading">
<span><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังดำเนินการ...</span>
</template>
</button>
</div>
</div>
</div>
</div>
</div>
</form>
{{-- ... (Include Modals) ... --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div>
@endsection

@push('scripts')
{{-- V2.4-S11: Load the unified script component --}}
@include('components.hybrid-attachment-scripts')
@endpush
