{{-- resources/views/admin/tickets/create.blade.php --}}
@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่ (Admin)')

{{-- V2.4-S16: Add x-cloak definition if not globally defined in CSS --}}
@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
{{-- Initialize Alpine.js --}}
<div class="content-section" x-data="hybridAttachmentManager({ isAdminCreate: true, employerId: '{{ old('employer_id') }}' })">
    <h2 class="mb-4">สร้างคำขอใหม่ (Admin/Staff)</h2>

    {{-- Global Error Display (Ensure it exists) --}}
    @if (session('error'))
        <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
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

    <form action="{{ route('admin.tickets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Hidden File Input (remains the same) --}}
        <input type="file" multiple class="d-none" x-ref="generalFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)">

        {{-- SECTION - Employer Selection (V2.4-S16: UX Refinements) --}}
        <div class="card mb-4 border-primary shadow-sm"> {{-- Added shadow-sm --}}
            <div class="card-header bg-primary text-white">
                {{-- V2.4-S16: Add Step Indicator --}}
                <i class="bi bi-1-circle-fill me-2"></i> ขั้นตอนที่ 1: เลือกนายจ้าง (ผู้ส่งคำขอ)
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="employer_id" class="form-label">นายจ้าง <span class="text-danger">*</span></label>
                    {{-- Dropdown (No changes to logic) --}}
                    <select class="form-select form-select-lg @error('employer_id') is-invalid @enderror" id="employer_id" name="employer_id" required x-model="contextEmployerId">
                        <option value="" disabled>-- กรุณาเลือกนายจ้าง --</option>
                        @foreach($employers as $employer)
                            <option value="{{ $employer->id }}">
                                {{ $employer->employerNameTh }}
                                {{ $employer->employerNameEn ? '(' . $employer->employerNameEn . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('employer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('employer_user_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                {{-- V2.4-S16: Visual Feedback for Next Step (Use x-cloak to prevent flicker) --}}
                <div x-cloak>
                    <div x-show="!contextEmployerId" class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-arrow-down-circle me-2"></i> กรุณาเลือกนายจ้างเพื่อดำเนินการต่อ
                    </div>
                    <div x-show="contextEmployerId" class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-2"></i> เลือกนายจ้างสำเร็จ สามารถกรอกรายละเอียดและแนบข้อมูลได้แล้ว
                    </div>
                </div>
            </div>
        </div>
        {{-- End Employer Selection --}}

        {{-- V2.4-S16: Add Header for Step 2 --}}
        <h4 class="mb-3 mt-4"><i class="bi bi-2-circle-fill me-2"></i> ขั้นตอนที่ 2: รายละเอียดและสิ่งที่แนบมา</h4>

        <div class="row">
            {{-- Column 1: Main Information (Left Side) --}}
            {{-- Apply disabled state (pe-none and opacity-50) (No changes to logic) --}}
            <div class="col-lg-7" :class="{ 'opacity-50 pe-none': !contextEmployerId }">
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
                        {{-- Upload Progress Bar --}}
                        <div x-show="isUploading" class="mb-3">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
                                    กำลังอัปโหลด (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                </div>
                            </div>
                        </div>
                        {{-- Attachment Buttons --}}
                        <div class="d-grid gap-2 mb-3" :class="{ 'opacity-50': isUploading || !contextEmployerId }">
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading || !contextEmployerId">
                                <i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่
                            </button>
                            <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading || !contextEmployerId">
                                <i class="bi bi-person-plus me-2"></i> แนบลูกจ้างใหม่/แจ้งเข้า
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" :disabled="isUploading || !contextEmployerId">
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
                            {{-- Display Templates --}}
                             @include('tickets.partials._basket_display_templates')
                        </div>
                        <hr>
                        {{-- Submit Button --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" :disabled="isUploading || !contextEmployerId">
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

    {{-- Modals and Scripts --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div>
@endsection

@push('scripts')
@include('components.hybrid-attachment-scripts')
@endpush
