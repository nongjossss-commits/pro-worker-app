{{-- resources/views/tickets/create.blade.php --}}
@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่')
{{-- Ensure there are no duplicate directives here --}}
@section('content')
{{-- Initialize Alpine.js Component --}}
<div class="content-section" x-data="attachmentBasket()">
    {{-- ... (Header, Error Display) ... --}}
    <h2 class="mb-4">สร้างคำขอใหม่ (Smart Ticket)</h2>

    {{-- Flash Message for Controller Errors & Validation --}}
    @if (session('danger'))
        <div class="alert alert-danger mb-4" role="alert">
            {{ session('danger') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>พบข้อผิดพลาดในการตรวจสอบข้อมูล:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" @submit="populateAttachmentInput">
@csrf
<input type="hidden" name="attachments" x-ref="attachmentsInput">
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
<script>
// V2.4-S7: Finalized Alpine.js component for the Attachment Basket
function attachmentBasket() {
return {
// --- Core Basket State (Persistent) ---
basket: {
existing_employees: [],
new_employees: [],
// S7 Format: { path: 'temp_uploads/uuid.jpg', url: 'http://...', name: 'filename.jpg', size: 1024 }
files: [],
},
// --- V2.4-S7: General Upload State ---
isUploading: false, // Used for general file uploads and disabling buttons/submit
uploadProgress: 0,
filesToUploadCount: 0,
filesUploadedCount: 0,
// --- Modal Instances (Bootstrap) ---
modalInstances: {
existing: null,
new: null
},
// --- V2.4-S5/S6: Existing/New Employee States (Transient) ---
// (Keep all existing S5/S6 state variables as they are)
availableEmployees: [],
selectedEmployeeIds: [],
isLoading: false,
searchQuery: '',
defaultNewEmployeeForm: {
employeeTitleTh: 'นาย',
employeeNameTh: '',
employeeDob: '',
employeeNationality: '',
employeePassport: '',
nature_of_work: '',
employeePhoto: null,
document_1: null,
},
newEmployeeForm: {},
uploadStatus: {}, // For New Employee Modal uploads
// Initialize the component
init() {
// Initialize Bootstrap Modals
this.$nextTick(() => {
if (typeof bootstrap !== 'undefined') {
this.modalInstances.existing = new bootstrap.Modal(document.getElementById('existingEmployeeModal'));
this.modalInstances.new = new bootstrap.Modal(document.getElementById('newEmployeeModal'));
}
});
// Initialize New Employee Form State
this.resetNewEmployeeForm();
},
// --- Core Basket Functions ---
totalItemsCount() {
return this.basket.existing_employees.length + this.basket.new_employees.length + this.basket.files.length;
},
// Helper function for formatting file size (Used in S7)
formatBytes(bytes, decimals = 2) {
if (!+bytes) return '0 Bytes'
const k = 1024
const dm = decimals < 0 ? 0 : decimals
const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
const i = Math.floor(Math.log(bytes) / Math.log(k))
return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
},
// (removeConfirm remains the same)
removeConfirm(type, index, itemName) {
if (typeof Swal === 'undefined') {
if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ ' + itemName + ' ออกจากตะกร้า?')) {
this.basket[type].splice(index, 1);
}
return;
}
Swal.fire({
title: 'ยืนยันการลบ?',
text: "คุณต้องการลบ '" + itemName + "' ออกจากตะกร้าใช่หรือไม่?",
icon: 'warning',
showCancelButton: true,
confirmButtonColor: '#d33',
cancelButtonColor: '#6c757d',
confirmButtonText: 'ใช่, ลบเลย!',
cancelButtonText: 'ยกเลิก'
}).then((result) => {
if (result.isConfirmed) {
this.$nextTick(() => {
this.basket[type].splice(index, 1);
});
}
});
},
// --- V2.4-S5: Existing Employee Functions (No Changes) ---
// (Jules: Keep these functions exactly as they were previously)
async fetchEmployees() {
if (this.availableEmployees.length > 0) return;
this.isLoading = true;
try {
const response = await fetch('{{ route('api-web.employer.employees.index') }}');
if (!response.ok) throw new Error('Failed to fetch employees');
this.availableEmployees = await response.json();
} catch (error) {
console.error(error);
alert('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง');
} finally {
this.isLoading = false;
}
},
async openExistingEmployeeModal() {
await this.fetchEmployees();
this.selectedEmployeeIds = this.basket.existing_employees.map(e => e.id.toString());
if (this.modalInstances.existing) this.modalInstances.existing.show();
},
filteredEmployees() {
if (!this.searchQuery) return this.availableEmployees;
const query = this.searchQuery.toLowerCase();
return this.availableEmployees.filter(employee => {
return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
(employee.employeeNameEn && employee.employeeNameEn.toLowerCase().includes(query)) ||
(employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
});
},
confirmSelection() {
const transientIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));
this.basket.existing_employees = this.availableEmployees.filter(employee => {
return transientIds.has(employee.id);
});
if (this.modalInstances.existing) this.modalInstances.existing.hide();
this.searchQuery = '';
},
// --- V2.4-S6: New Employee Functions (Slight modification for S7 integration) ---
// (Jules: Keep these functions exactly as they were previously)
resetNewEmployeeForm() {
this.newEmployeeForm = JSON.parse(JSON.stringify(this.defaultNewEmployeeForm));
this.uploadStatus = {};
Object.keys(this.defaultNewEmployeeForm).forEach(key => {
if (key === 'employeePhoto' || key.startsWith('document_')) {
this.uploadStatus[key] = { loading: false, error: null, url: null };
}
});
const formElement = document.getElementById('newEmployeeActualForm');
if (formElement) {
formElement.reset();
}
},
openNewEmployeeModal() {
this.resetNewEmployeeForm();
if (this.modalInstances.new) this.modalInstances.new.show();
},
async handleFileUpload(event, fieldName) {
const file = event.target.files[0];
if (!file) return;
if (!this.uploadStatus[fieldName]) return;
const status = this.uploadStatus[fieldName];
status.loading = true;
status.error = null;
const formData = new FormData();
formData.append('file', file);
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
try {
const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
method: 'POST',
headers: {
'X-CSRF-TOKEN': csrfToken,
'Accept': 'application/json',
},
body: formData,
});
const data = await response.json();
if (!response.ok) {
throw new Error(data.error || 'Upload failed');
}
this.newEmployeeForm[fieldName] = data.path;
status.url = data.url;
} catch (error) {
console.error('Upload error:', error);
status.error = error.message;
this.newEmployeeForm[fieldName] = null;
event.target.value = null;
} finally {
status.loading = false;
}
},
submitNewEmployeeForm() {
// Check for modal-specific uploads
const isModalUploading = Object.values(this.uploadStatus).some(status => status.loading);
if (isModalUploading) {
Swal.fire({
icon: 'warning',
title: 'รอสักครู่',
text: 'กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อนเพิ่มเข้าตะกร้า',
});
return;
}
this.basket.new_employees.push(JSON.parse(JSON.stringify(this.newEmployeeForm)));
if (this.modalInstances.new) {
this.modalInstances.new.hide();
}
this.resetNewEmployeeForm();
},
// --- V2.4-S7: General File Attachment Functions ---
// V2.4-S10: The REAL Fix - This function runs on form submit
// It bundles the entire basket into a single JSON string for the backend.
populateAttachmentInput() {
    const finalAttachments = {
        existing_employees: this.basket.existing_employees.map(e => e.id),
        new_employees: this.basket.new_employees, // Keep as objects
        files: this.basket.files.map(f => ({ // Only send necessary data
            path: f.path,
            name: f.name,
            size: f.size
        }))
    };
    this.$refs.attachmentsInput.value = JSON.stringify(finalAttachments);
},
// 1. Trigger the hidden file input
triggerFileInput() {
// Use $refs to access the hidden input element
this.$refs.generalFileInput.click();
},
// 2. Handle Multiple File Uploads (Sequential Batch Upload)
async handleGeneralFileUpload(event) {
const files = Array.from(event.target.files);
if (files.length === 0) return;
this.isUploading = true;
this.filesToUploadCount = files.length;
this.filesUploadedCount = 0;
let errors = [];
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// Process files sequentially
for (const file of files) {
try {
// Update progress before starting the upload
this.uploadProgress = Math.round((this.filesUploadedCount / this.filesToUploadCount) * 100);
const formData = new FormData();
formData.append('file', file);
const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
method: 'POST',
headers: {
'X-CSRF-TOKEN': csrfToken,
'Accept': 'application/json',
},
body: formData,
});
const data = await response.json();
if (!response.ok) {
throw new Error(data.error || 'Upload failed');
}
// Success: Add to basket with metadata
this.basket.files.push({
path: data.path,
name: file.name, // Store the original file name
size: file.size, // Store the file size
url: data.url
});
this.filesUploadedCount++;
} catch (error) {
console.error('Upload error for file ' + file.name + ':', error);
// Collect errors
errors.push(`${file.name}: ${error.message}`);
}
}
// Cleanup
this.isUploading = false;
this.uploadProgress = 0;
event.target.value = null; // Reset input
// Feedback to user
if (errors.length > 0) {
Swal.fire({
icon: 'error',
title: 'เกิดข้อผิดพลาดในการอัปโหลดบางไฟล์',
html: errors.join('<br>'),
});
}
},
}
}
</script>
@endpush
