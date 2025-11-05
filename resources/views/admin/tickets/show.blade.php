@extends('layouts.app')

{{-- Determine the correct context and prepare data --}}
@php
    $isAdminView = request()->routeIs('admin.tickets.*');
    $viewTitle = $isAdminView ? 'จัดการต๋วงาน' : 'รายละเอียดคำขอ';

    // Helper function for file size formatting (defined here as it's not globally available)
    if (!function_exists('formatBytes')) {
        function formatBytes($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            // Check if $pow is 0 to handle very small file sizes correctly
            if ($pow == 0) {
                return round($bytes, $precision) . ' ' . $units[$pow];
            }

            $bytes /= pow(1024, $pow);
            return round($bytes, $precision) . ' ' . $units[$pow];
        }
    }

    // Access the pre-processed attachments via the accessor
    $attachments = $ticket->categorized_attachments;
    // V2.4-S10: Check if the ticket is closed
    $isClosed = in_array($ticket->status, ['resolved', 'rejected']);
@endphp

@section('title', $viewTitle . ' #' . $ticket->id)

@section('content')
{{-- V2.4-S11: Initialize the unified Alpine.js component --}}
<div class="content-section" x-data="hybridAttachmentManager()">

    {{-- V2.4-S10: Global Error/Success Display (Crucial for feedback after reply) --}}
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $ticket->subject }}</h2>
        {{-- Display Status Badge --}}
        <span class="badge bg-{{ $ticket->status_color }} fs-5">
            {{ $ticket->status_name }}
        </span>
    </div>

    <div class="row">
        {{-- Column 1: Main Content (Triage, History, Reply) --}}
        <div class="col-lg-8">

            {{-- Section 1: Triage (Attachments Summary) --}}
            @if($attachments->existing_employees->isNotEmpty() || $attachments->new_employees->isNotEmpty() || $attachments->files->isNotEmpty())
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>สิ่งที่แนบมา (Attachments Triage)</h5>
                </div>
                <div class="card-body">

                    {{-- 1.1 Existing Employees --}}
                    @if($attachments->existing_employees->isNotEmpty())
                        <h6 class="text-primary mt-3">ลูกจ้างที่มีอยู่ ({{ $attachments->existing_employees->count() }} คน)</h6>
                        <div class="list-group mb-3">
                            @foreach($attachments->existing_employees as $employee)
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <img src="{{ $employee->photo_url }}" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <span class="flex-grow-1">
                                        <strong>{{ $employee->employeeNameTh }}</strong>
                                        <small class="text-muted">({{ $employee->employeePassport }})</small>
                                    </span>
                                    {{-- V2.4-S9: Historical Integrity Check --}}
                                    @if($employee->trashed())
                                        <span class="badge bg-danger me-2">ลบ/จำหน่ายแล้ว</span>
                                    @endif
                                    {{-- Link to employee detail page (if user has permission) --}}
                                    @can('view-employees')
                                        {{-- Use $employee->id explicitly as withTrashed might affect route model binding --}}
                                        <a href="{{ route('employees.show', $employee->id) }}" target="_blank" class="ms-auto btn btn-sm btn-outline-secondary">ดูข้อมูล</a>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1.2 New Employees --}}
                    @if($attachments->new_employees->isNotEmpty())
                        <h6 class="text-success mt-3">ลูกจ้างใหม่/แจ้งเข้า ({{ $attachments->new_employees->count() }} คน)</h6>
                        <div class="list-group mb-3">
                            @foreach($attachments->new_employees as $newEmployee)
                                <div class="list-group-item py-2">
                                    <strong>{{ $newEmployee->employeeTitleTh ?? '' }}{{ $newEmployee->employeeNameTh }}</strong>
                                    <small class="text-muted">({{ $newEmployee->employeePassport }})</small>
                                    {{-- Display links to uploaded files (using URLs generated by the accessor) --}}
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        @if(isset($newEmployee->employeePhoto_url))
                                            <a href="{{ $newEmployee->employeePhoto_url }}" target="_blank" class="btn btn-sm btn-outline-info">รูปถ่าย</a>
                                        @endif
                                        @if(isset($newEmployee->document_1_url))
                                            <a href="{{ $newEmployee->document_1_url }}" target="_blank" class="btn btn-sm btn-outline-info">เอกสาร 1</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1.3 General Files --}}
                    @if($attachments->files->isNotEmpty())
                        <h6 class="text-secondary mt-3">ไฟล์แนบทั่วไป ({{ $attachments->files->count() }} ไฟล์)</h6>
                        <div class="list-group mb-3">
                            @foreach($attachments->files as $file)
                                @if($file->url)
                                    <a href="{{ $file->url }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                        <span><i class="bi bi-file-earmark-text me-2"></i> {{ $file->name }}</span>
                                        <small class="text-muted">{{ formatBytes($file->size) }}</small>
                                    </a>
                                @else
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 text-danger">
                                        <span><i class="bi bi-exclamation-triangle me-2"></i> {{ $file->name }} (ไฟล์สูญหาย)</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif
            {{-- End Section 1: Triage --}}


            {{-- Section 2: Communication History (Chat View) --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>ประวัติการสนทนา</h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @php $hasConversation = false; @endphp
                    {{-- Messages are already sorted chronologically by the Controller --}}
                    @foreach($ticket->messages as $message)
                        {{-- Display only 'comment' and 'system_activity' types in the main chat history --}}
                        @if($message->message_type == 'comment')
                            @php $hasConversation = true; @endphp
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3">
                                    {{-- Placeholder Avatar --}}
                                    <i class="bi bi-person-circle fs-2 text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>{{ $message->user->name ?? 'ผู้ใช้งาน (ลบแล้ว)' }}</strong>
                                        <small class="text-muted">{{ $message->created_at->format('d M Y H:i') }}</small>
                                    </div>
                                    <div class="p-3 rounded" style="background-color: #f1f5f9;">
                                        {{-- Use nl2br to respect line breaks in the message body --}}
                                        {!! nl2br(e($message->body)) !!}
                                    </div>
                                </div>
                            </div>
                            <hr>
                        @elseif($message->message_type == 'system_activity')
                             @php $hasConversation = true; @endphp
                            {{-- Future implementation for system logs --}}
                             <div class="text-center my-3">
                                 <span class="badge bg-secondary">{{ $message->body }} - {{ $message->created_at->format('H:i') }}</span>
                             </div>
                             <hr>
                        @endif
                    @endforeach

                    @if(!$hasConversation)
                        <p class="text-center text-muted py-4">ยังไม่มีการสนทนา</p>
                    @endif
                </div>
            </div>
            {{-- End Section 2: Communication History --}}


            {{-- Section 3: Reply Box (V2.4-S11 Implementation - Major Overhaul) --}}
            @if(!$isClosed)
                <div class="card mb-4" id="reply-box">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-send me-2"></i> ตอบกลับ / ส่งข้อความ</h5></div>
                    <div class="card-body">
                        {{-- Hidden File Input --}}
                        {{-- V2.4-S11: Ensure the correct function is called and ref is replyFileInput --}}
                        <input type="file" multiple class="d-none" x-ref="replyFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)">

                        {{-- Determine the correct route --}}
                        @php
                            $replyRoute = $isAdminView ? 'admin.tickets.replies.store' : 'tickets.replies.store';
                        @endphp
                        <form x-ref="replyForm" action="{{ route($replyRoute, $ticket->id) }}" method="POST">
                            @csrf
                            {{-- Text Area --}}
                            <div class="mb-3">
                                <label for="message" class="form-label">ข้อความ:</label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" placeholder="พิมพ์ข้อความตอบกลับของคุณที่นี่...">{{ old('message') }}</textarea>
                                @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Attachment Basket --}}
                            <div class="mb-3">
                                <label class="form-label">สิ่งที่แนบมา (<span x-text="totalItemsCount()"></span> รายการ):</label>

                                {{-- V2.4-S11: Upload Progress Bar --}}
                                <div x-show="isUploading" class="mb-2">
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
                                            กำลังอัปโหลด (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group" style="max-height: 250px; overflow-y: auto;">
                                    <template x-if="totalItemsCount() === 0">
                                        <div class="list-group-item text-muted fst-italic">ยังไม่มีรายการที่แนบ</div>
                                    </template>
                                    {{-- 1. Display Existing Employees --}}
                                    <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
                                                <span>
                                                    <i class="bi bi-person-check me-1 text-primary"></i>
                                                    <span x-text="item.employeeNameTh"></span>
                                                    <span class="text-muted" x-text="item.employeeNameEn ? '(' + item.employeeNameEn + ')' : ''"></span>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
                                            <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
                                        </div>
                                    </template>
                                    {{-- 2. Display New Employees --}}
                                    <template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <i class="bi bi-person-plus fs-4 text-success"></i>
                                                <span>
                                                    ใหม่: <span x-text="item.employeeNameTh"></span>
                                                    <small class="text-muted d-block" x-text="'Passport: ' + item.employeePassport"></small>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
                                            <input type="hidden" :name="'attachments[new_employees][' + index + ']'" :value="JSON.stringify(item)">
                                        </div>
                                    </template>
                                    {{-- 3. Display Files --}}
                                    <template x-for="(item, index) in basket.files" :key="'f-' + index">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <i class="bi bi-file-earmark-text fs-4 text-secondary"></i>
                                                <span>
                                                    <a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
                                                    <small class="text-muted d-block" x-text="formatBytes(item.size)"></small>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
                                            <input type="hidden" :name="'attachments[files][' + index + '][path]'" :value="item.path">
                                            <input type="hidden" :name="'attachments[files][' + index + '][name]'" :value="item.name">
                                            <input type="hidden" :name="'attachments[files][' + index + '][size]'" :value="item.size">
                                        </div>
                                    </template>
                                </div>
                                 @error('attachments') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.files.*.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.existing_employees.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.new_employees.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- Action Buttons --}}
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="btn-group" role="group" aria-label="Attachment options">
                                    <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading">
                                        <i class="bi bi-person-check"></i> แนบลูกจ้าง
                                    </button>
                                    <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading">
                                        <i class="bi bi-person-plus"></i> แจ้งเข้า
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" :disabled="isUploading">
                                        <i class="bi bi-paperclip"></i> แนบไฟล์
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary" :disabled="isUploading">
                                    <template x-if="isUploading">
                                        <span><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังดำเนินการ...</span>
                                    </template>
                                    <template x-if="!isUploading">
                                        <span><i class="bi bi-send-fill me-2"></i> ส่งข้อความ</span>
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-warning text-center">
                    <i class="bi bi-lock-fill me-2"></i> ตั๋วงานนี้ถูกปิดแล้ว ({{ $ticket->status_name }}). หากต้องการความช่วยเหลือเพิ่มเติม กรุณาสร้างตั๋วงานใหม่.
                </div>
            @endif
            {{-- End Section 3: Reply Box --}}

        </div>

        {{-- Column 2: Metadata Sidebar --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลตั๋วงาน</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Ticket ID:</strong> #{{ $ticket->id }}
                    </li>
                    <li class="list-group-item">
                        <strong>สร้างเมื่อ:</strong> {{ $ticket->created_at->format('d M Y H:i') }}
                    </li>
                     <li class="list-group-item">
                        <strong>อัปเดตล่าสุด:</strong> {{ $ticket->updated_at->format('d M Y H:i') }}
                    </li>
                    {{-- Show Employer Info (More detailed in Admin view) --}}
                    <li class="list-group-item">
                        <strong>ผู้ส่งคำขอ:</strong>
                        @if($isAdminView && $ticket->employerUser)
                             {{-- Eager loaded in Admin Controller --}}
                            {{ $ticket->employerUser->employer->employerNameTh ?? '' }} ({{ $ticket->employerUser->name }})
                        @elseif($ticket->employerUser)
                            {{ $ticket->employerUser->name }}
                        @else
                            N/A (ผู้ใช้งานถูกลบ)
                        @endif
                    </li>
                    {{-- Show Assigned Staff --}}
                    <li class="list-group-item">
                        <strong>ผู้รับผิดชอบ:</strong>
                        {{ $ticket->assignedStaff->name ?? ($isAdminView ? 'ยังไม่ได้มอบหมาย' : 'รอเจ้าหน้าที่รับเรื่อง') }}
                    </li>
                </ul>
                {{-- Admin Action Buttons (Placeholder for V2.4-S11) --}}
                @if($isAdminView)
                    <div class="card-body d-grid gap-2">
                         <h5 class="mb-3">การจัดการ (Admin/Staff)</h5>
                        <button class="btn btn-outline-success" disabled>Mark as Resolved (V2.4-S11)</button>
                        <button class="btn btn-outline-danger" disabled>Reject Ticket (V2.4-S11)</button>
                        <button class="btn btn-outline-primary" disabled>Forward to P-Workflow (V2.4-S11)</button>
                         <button class="btn btn-outline-secondary" disabled>Change Assignment (V2.4-S11)</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- V2.4-S11: Include Modals (Required for the Hybrid System) --}}
    {{-- CRITICAL (V2.4-S11.2 Fix): Modals MUST be inside the x-data scope --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div> {{-- End content-section (x-data) --}}

@endsection

{{-- V2.4-S11: Update Scripts Section --}}
@push('scripts')
{{-- Load the unified script component --}}
@include('components.hybrid-attachment-scripts')
@endpush
