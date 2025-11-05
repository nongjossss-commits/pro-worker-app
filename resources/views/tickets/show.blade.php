{{-- resources/views/tickets/show.blade.php AND resources/views/admin/tickets/show.blade.php --}}
@extends('layouts.app')
{{-- ... (@php block remains the same) ... --}}
@php
    $isAdminView = request()->routeIs('admin.tickets.*');
    $isClosed = in_array($ticket->status, ['resolved', 'rejected']);
    $viewTitle = $isAdminView ? 'รายละเอียดตั๋ว (Admin)' : 'รายละเอียดตั๋ว';
@endphp
@section('title', $viewTitle . ' #' . $ticket->id)
@section('content')
{{-- V2.4-S11: Initialize the unified Alpine.js component --}}
<div class="content-section" x-data="hybridAttachmentManager()">
{{-- <div class="content-section" x-data="replyBox()"> <-- OLD --}}
{{-- ... (Global Error/Success, Header, Triage, History sections remain the same) ... --}}
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>พบข้อผิดพลาด:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <span class="fw-bold">{{ $ticket->subject }}</span>
                        <span class="badge {{ $ticket->status_color_class }} ms-2">{{ $ticket->status_text }}</span>
                    </h5>
                    <span>#{{ $ticket->id }}</span>
                </div>
                <div class="card-body">
                    <p><strong>สร้างโดย:</strong> {{ $ticket->employerUser->name }} ({{ $ticket->employerUser->employer->employerNameTh }})</p>
                    <p><strong>สร้างเมื่อ:</strong> {{ $ticket->created_at->format('d/m/Y H:i') }} ({{ $ticket->created_at->diffForHumans() }})</p>
                </div>
            </div>
{{-- (Jules: ค้นหา Section 2: Communication History และทำการแก้ไข Section 3 ด้านล่าง) --}}
            {{-- Section 2: Communication History --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ประวัติการสื่อสาร</h5>
                </div>
                <div class="card-body">
                    @if($ticket->messages->isEmpty())
                        <p class="text-muted">ยังไม่มีข้อความ</p>
                    @else
                        @foreach($ticket->messages as $message)
                           @include('tickets.partials._message_display', ['message' => $message])
                        @endforeach
                    @endif
                </div>
            </div>
            {{-- Section 3: Reply Box (V2.4-S11 Implementation - Major Overhaul) --}}
            @if(!$isClosed)
                <div class="card">
                    {{-- Hidden File Input --}}
                    {{-- V2.4-S11: Ensure the correct function is called and ref is replyFileInput --}}
                    <input type="file" multiple class="d-none" x-ref="replyFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)">
                    {{-- Determine the correct route --}}
                    @php
                        $replyRoute = $isAdminView ? 'admin.tickets.replies.store' : 'tickets.replies.store';
                    @endphp
                    <form action="{{ route($replyRoute, $ticket) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <h5 class="mb-3">ตอบกลับ / เพิ่มข้อมูล</h5>
                            {{-- Upload Progress Bar --}}
                            <div x-show="isUploading" class="mb-3">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'">
                                        กำลังอัปโหลด (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                    </div>
                                </div>
                            </div>
                            {{-- Message Textarea --}}
                            <textarea class="form-control mb-3 @error('message') is-invalid @enderror" rows="4" name="message" placeholder="พิมพ์ข้อความตอบกลับ..." :disabled="isUploading">{{ old('message') }}</textarea>
                            {{-- V2.4-S11: Hybrid Basket Display Area (Replaces Attached Files Display) --}}
                            <div x-show="totalItemsCount() > 0" class="mb-3 p-3 border rounded bg-light">
                                <h6 class="mb-2">รายการที่แนบในการตอบกลับนี้ (<span x-text="totalItemsCount()"></span> รายการ)</h6>
                                <div class="list-group" style="max-height: 200px; overflow-y: auto;">
                                    {{-- 1. Display Existing Employees (Copied from create.blade.php) --}}
                                    <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <img :src="item.photo_url" alt="Photo" class="rounded-circle" style="width: 30px; height: 30px; object-fit: cover;">
                                                <span>
                                                    <i class="bi bi-person-check me-1 text-primary"></i>
                                                    <span x-text="item.employeeNameTh"></span>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('existing_employees', index, item.employeeNameTh)">ลบ</button>
                                            <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
                                        </div>
                                    </template>
                                    {{-- 2. Display New Employees (Copied from create.blade.php) --}}
                                    <template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-person-plus fs-5 text-success"></i>
                                                <span>
                                                    ใหม่: <span x-text="item.employeeNameTh"></span>
                                                    <small class="text-muted d-block" style="font-size: 0.8em;" x-text="'Passport: ' + item.employeePassport"></small>
                                                </span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('new_employees', index, item.employeeNameTh)">ลบ</button>
                                            {{-- Hidden input (JSON string) --}}
                                            <input type="hidden" :name="'attachments[new_employees][' + index + ']'" :value="JSON.stringify(item)">
                                        </div>
                                    </template>
                                    {{-- 3. Display General File Attachments (Adapted from S10 replyBox) --}}
                                    <template x-for="(item, index) in basket.files" :key="'f-' + index">
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-1">
                                            <span class="text-truncate" style="max-width: 70%;">
                                                <i class="bi bi-file-earmark-text me-2 text-secondary"></i>
                                                <a :href="item.url" target="_blank" x-text="item.name" class="text-decoration-none"></a>
                                            </span>
                                            <div>
                                                <small class="text-muted me-3" x-text="formatBytes(item.size)"></small>
                                                {{-- V2.4-S11: Use removeConfirm instead of removeFile --}}
                                                <button type="button" class="btn btn-sm btn-danger" @click="removeConfirm('files', index, item.name)">ลบ</button>
                                            </div>
                                            {{-- Hidden inputs --}}
                                            <input type="hidden" :name="'attachments[files][' + index + '][path]'" :value="item.path">
                                            <input type="hidden" :name="'attachments[files][' + index + '][name]'" :value="item.name">
                                            <input type="hidden" :name="'attachments[files][' + index + '][size]'" :value="item.size">
                                        </div>
                                    </template>
                                </div>
                            </div>
                            {{-- End Hybrid Basket Display Area --}}
                            {{-- Action Buttons (V2.4-S11: New Button Layout) --}}
                            <div class="d-flex justify-content-between align-items-center">
                                {{-- Attachment Buttons Group --}}
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" :disabled="isUploading" title="แนบลูกจ้างที่มีอยู่">
                                        <i class="bi bi-person-check me-2"></i> ลูกจ้างเดิม
                                    </button>
                                    <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" :disabled="isUploading" title="แนบลูกจ้างใหม่/แจ้งเข้า">
                                        <i class="bi bi-person-plus me-2"></i> ลูกจ้างใหม่
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" :disabled="isUploading" title="แนบไฟล์/รูปภาพ">
                                        <i class="bi bi-paperclip me-2"></i> ไฟล์
                                    </button>
                                </div>
                                {{-- Submit Button --}}
                                <button type="submit" class="btn btn-primary" :disabled="isUploading">
                                    <template x-if="!isUploading">
                                        <span><i class="bi bi-send-fill me-2"></i> ส่งข้อความ</span>
                                    </template>
                                    <template x-if="isUploading">
                                        <span><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังดำเนินการ...</span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
            {{-- ... (Closed message remains the same) ... --}}
                <div class="alert alert-secondary text-center">
                    <i class="bi bi-lock-fill me-2"></i>
                    ตั๋วงานนี้ถูกปิดแล้ว ไม่สามารถตอบกลับหรือดำเนินการใดๆ เพิ่มเติมได้
                </div>
            @endif
            {{-- End Section 3: Reply Box --}}
        </div>
        {{-- End Column 1 --}}
        {{-- ... (Column 2: Metadata Sidebar remains the same) ... --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลเมตา</h5>
                </div>
                <div class="card-body">
                    <p><strong>ผู้รับผิดชอบ:</strong> {{ $ticket->assignedStaff->name ?? 'ยังไม่มี' }}</p>
                    <p><strong>อัปเดตล่าสุด:</strong> {{ $ticket->updated_at->format('d/m/Y H:i') }}</p>
                </div>
                @if ($isAdminView && !$isClosed)
                <div class="card-footer">
                    {{-- Admin actions can go here --}}
                </div>
                @endif
            </div>
        </div>
    </div> {{-- End Row --}}
    {{-- V2.4-S11: Include Modals (Required for the Hybrid System) --}}
    {{-- Include them regardless of $isClosed as Alpine needs initialization, but UI elements are hidden by the @if(!$isClosed) around the reply box --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div> {{-- End content-section (x-data) --}}
@endsection
{{-- V2.4-S11: Update Scripts Section --}}
@push('scripts')
{{-- Load the unified script component --}}
@include('components.hybrid-attachment-scripts')
{{-- (Jules: ลบ Script เดิม <script> function replyBox() { ... } </script> ที่อยู่ด้านล่างนี้ออกทั้งหมด) --}}
@endpush
