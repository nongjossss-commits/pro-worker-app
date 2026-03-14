@extends('layouts.app')

{{-- Determine the correct context and prepare data --}}
@php
    $isAdminView = request()->routeIs('admin.tickets.*');
    $viewTitle = $isAdminView ? 'จัดการตั๋วงาน' : 'รายละเอียดคำขอ';

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
{{-- V2.4-S13: Initialize component, PASSING employerId for context --}}
{{-- V2.4-S13-P1: CRITICAL FIX - Use @json directive for safe and correct data passing to Alpine.js --}}
{{-- This ensures that the PHP value (integer or null) is correctly encoded as a JavaScript literal. --}}
<div class="content-section" x-data="hybridAttachmentManager({ employerId: @json(optional($ticket->employerUser->employer)->id) })">

    {{-- V2.4-S10: Global Error/Success Display (Crucial for feedback after reply) --}}
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>{{ __('พบข้อผิดพลาด:') }}</strong>
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
                    <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>{{ __('Attachments') }} (Attachments Triage)</h5>
                </div>
                <div class="card-body">

                    {{-- 1.1 Existing Employees --}}
                    @if($attachments->existing_employees->isNotEmpty())
                        <h6 class="text-primary mt-3">{{ __('Existing Employees') }} ({{ $attachments->existing_employees->count() }} คน)</h6>

                        <x-bulk-action-bar id="ticket-employer-existing-employees-bar">
                            <li><a class="dropdown-item" href="#" id="ticket-existing-bulk-advanced-export-btn"><i class="bi bi-file-earmark-spreadsheet me-2"></i>{{ __('Advanced Export') }}</a></li>
                        </x-bulk-action-bar>

                        <div class="list-group mb-3">
                            @foreach($attachments->existing_employees as $item)
                                @php $employee = $item->employee; @endphp
                                <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}">
                                    </div>
                                    <img src="{{ $employee->photo_url }}" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <span class="flex-grow-1">
                                        <strong>{{ $employee->employeeNameTh }}</strong>
                                        <small class="text-muted">({{ $employee->employeePassport }})</small>
                                    </span>
                                    @if($employee->trashed())
                                        <span class="badge bg-danger me-2">{{ __('Deleted/Terminated') }}</span>
                                    @endif
                                    <div class="ms-auto btn-group align-items-center">
                                        {{-- Preview Button --}}
                                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        {{-- Delete Button --}}
                                        @if(!$isClosed && (auth()->id() === $ticket->employer_user_id || auth()->user()->can('manage-tickets')))
                                        <form action="{{ route($isAdminView ? 'admin.tickets.messages.destroy' : 'tickets.messages.destroy', $item->message_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                                    data-swal-title="{{ __('Confirm Delete') }}"
                                                    data-swal-text="{{ __('Are you sure you want to delete ...?') }}"
                                                    data-swal-icon="warning"
                                                    data-swal-confirm-text="{{ __('Yes, delete it') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <span class="btn btn-sm btn-light border cursor-grab ms-1"
                                              draggable="true"
                                              ondragstart="startDragGlobal(event, 'employee', {
                                                id: {{ $employee->id }},
                                                title: '{{ $employee->employeeNameTh }}',
                                                subtitle: '{{ $employee->employeeNameEn }}',
                                                url: '{{ route('employees.show', $employee->id) }}'
                                             })"
                                             title="{{ __('Drag') }}">
                                            <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1.1.5 External Employees (Non-Affiliated) --}}
                    @if($attachments->external_employees->isNotEmpty())
                        <h6 class="text-warning text-dark mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>ลูกจ้างภายนอก (External) ({{ $attachments->external_employees->count() }} คน)</h6>
                        <div class="list-group mb-3 border border-warning rounded">
                            @foreach($attachments->external_employees as $item)
                                @php $employee = $item->employee; @endphp
                                <div class="list-group-item d-flex align-items-center gap-3 py-2 bg-light">
                                    {{-- External employees don't have bulk actions for now --}}
                                    <div class="position-relative">
                                        <img src="{{ $employee->photo_url }}" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size: 0.6em;">Ext</span>
                                    </div>
                                    <span class="flex-grow-1">
                                        <strong class="text-dark">{{ $employee->employeeNameTh }}</strong>
                                        <small class="text-muted d-block">Passport: {{ $employee->employeePassport }}</small>
                                        <small class="text-info d-block" style="font-size: 0.8rem;">
                                            <i class="bi bi-building me-1"></i>
                                            {{ optional($employee->employer)->employerNameTh ?? 'ไม่ทราบสังกัด' }}
                                        </small>
                                    </span>

                                    <div class="ms-auto btn-group align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        @if(!$isClosed && (auth()->id() === $ticket->employer_user_id || auth()->user()->can('manage-tickets')))
                                        <form action="{{ route($isAdminView ? 'admin.tickets.messages.destroy' : 'tickets.messages.destroy', $item->message_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                                    data-swal-title="{{ __('ยืนยันการลบ') }}"
                                                    data-swal-text="ต้องการลบ '{{ $employee->employeeNameTh }}' ออกจากตั๋วนี้ใช่หรือไม่?"
                                                    data-swal-icon="warning"
                                                    data-swal-confirm-text="ใช่, ลบเลย">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <span class="btn btn-sm btn-light border cursor-grab ms-1"
                                              draggable="true"
                                              ondragstart="startDragGlobal(event, 'employee', {
                                                id: {{ $employee->id }},
                                                title: '{{ $employee->employeeNameTh }}',
                                                subtitle: '{{ $employee->employeeNameEn }}',
                                                url: '{{ route('employees.show', $employee->id) }}'
                                             })"
                                             title="{{ __('Drag') }}">
                                            <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1.2 New Employees --}}
                    @if($attachments->new_employees->isNotEmpty())
                        <h6 class="text-success mt-3">{{ __('New Employees') }} ({{ $attachments->new_employees->count() }} คน)</h6>
                        <div class="list-group mb-3">
                            <script>
                                // Initialize the global map if it doesn't exist
                                if (typeof window.newEmployeeDataMap === 'undefined') {
                                    window.newEmployeeDataMap = {};
                                }
                            </script>
                            @foreach($attachments->new_employees as $index => $item)
                                @php
                                    $newEmployee = $item->data;
                                    // Use a unique key combining message ID and index to ensure uniqueness
                                    $mapKey = 'msg_' . $item->message_id . '_' . $index;
                                @endphp
                                <script>
                                    // Store data in the global map
                                    window.newEmployeeDataMap['{{ $mapKey }}'] = @json($newEmployee);
                                </script>

                                <div class="list-group-item py-2 d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        {{-- Display Both Thai and English names --}}
                                        <strong>{{ $newEmployee->employeeTitleTh ?? '' }}{{ $newEmployee->employeeNameTh }}</strong>
                                        @if(!empty($newEmployee->employeeNameEn))
                                            <span class="text-muted">/ {{ $newEmployee->employeeTitleEn ?? '' }}{{ $newEmployee->employeeNameEn }}</span>
                                        @endif
                                        <small class="d-block text-muted">Passport: {{ $newEmployee->employeePassport ?? 'N/A' }}</small>

                                        {{-- Dynamically list all available documents --}}
                                        <div class="mt-2 d-flex gap-2 flex-wrap">
                                            @php
                                                // Create a map for user-friendly labels
                                                $docLabels = [
                                                    'employeePhoto' => __('รูปถ่าย'),
                                                    'employee_doc_1' => 'Passport',
                                                    'employee_doc_2' => 'Visa',
                                                    'employee_doc_3' => 'Work Permit',
                                                    'employee_doc_4' => 'Pink Card',
                                                    'employee_doc_5' => __('ทะเบียนบ้าน'),
                                                    'employee_doc_6' => __('บัตรประชาชน'),
                                                    'employee_doc_7' => __('ใบแจ้งที่พักอาศัย'),
                                                    'employee_doc_8' => __('เอกสารบ้านเกิด'),
                                                    'employee_doc_9' => __('เอกสารอื่นๆ 1'),
                                                    'employee_doc_10' => __('เอกสารอื่นๆ 2'),
                                                    'employee_doc_11' => __('เอกสารอื่นๆ 3'),
                                                    'employee_doc_12' => __('เอกสารอื่นๆ 4'),
                                                    'insurance_document_path_social' => __('ประกันสังคม'),
                                                    'insurance_document_path_hospital' => __('ประกัน รพ.'),
                                                    'insurance_document_path_private' => __('ประกันเอกชน')
                                                ];
                                            @endphp

                                            @foreach($docLabels as $field => $label)
                                                @php $urlField = $field . '_url'; @endphp
                                                @if(isset($newEmployee->$urlField))
                                                    <a href="{{ $newEmployee->$urlField }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-file-earmark-arrow-down"></i> {{ $label }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="ms-auto btn-group align-items-center">
                                        {{-- Preview Button --}}
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="openNewEmployeePreview('{{ $mapKey }}')">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        {{-- Delete Button --}}
                                        @if(!$isClosed && (auth()->id() === $ticket->employer_user_id || auth()->user()->can('manage-tickets')))
                                        <form action="{{ route($isAdminView ? 'admin.tickets.messages.destroy' : 'tickets.messages.destroy', $item->message_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                                    data-swal-title="{{ __('Confirm Delete') }}"
                                                    data-swal-text="{{ __('Are you sure you want to delete ...?') }}"
                                                    data-swal-icon="warning"
                                                    data-swal-confirm-text="{{ __('Yes, delete it') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <span class="btn btn-sm btn-light border cursor-grab ms-1"
                                              draggable="true"
                                              ondragstart="startDragGlobal(event, 'new_employee_draft', {
                                                title: 'New: {{ $newEmployee->employeeNameTh }}',
                                                subtitle: 'Passport: {{ $newEmployee->employeePassport ?? 'N/A' }}',
                                                data: @json($newEmployee)
                                             })"
                                             title="{{ __('Drag') }}">
                                            <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1.3 General Files --}}
                    @if($attachments->files->isNotEmpty())
                        <h6 class="text-secondary mt-3">{{ __('General Files') }} ({{ $attachments->files->count() }} ไฟล์)</h6>
                        <div class="list-group mb-3">
                            @foreach($attachments->files as $item)
                                @php $file = $item->data; @endphp
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                    <a href="{{ $file->url ?? '#' }}" @if($file->url) target="_blank" @endif class="text-decoration-none text-body flex-grow-1">
                                        @if($file->url)
                                            <span><i class="bi bi-file-earmark-text me-2"></i> {{ $file->name }}</span>
                                        @else
                                            <span class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i> {{ $file->name }} (ไฟล์สูญหาย)</span>
                                        @endif
                                    </a>
                                    <small class="text-muted me-3">{{ formatBytes($file->size) }}</small>
                                    @if(!$isClosed && (auth()->id() === $ticket->employer_user_id || auth()->user()->can('manage-tickets')))
                                    <form action="{{ route($isAdminView ? 'admin.tickets.messages.destroy' : 'tickets.messages.destroy', $item->message_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                                data-swal-title="{{ __('Confirm Delete') }}"
                                                data-swal-text="{{ __('Are you sure you want to delete ...?') }}"
                                                data-swal-icon="warning"
                                                data-swal-confirm-text="{{ __('Yes, delete it') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <span class="btn btn-sm btn-light border cursor-grab ms-1"
                                          draggable="true"
                                          ondragstart="startDragGlobal(event, 'file', {
                                            title: '{{ $file->name }}',
                                            subtitle: '{{ formatBytes($file->size) }}',
                                            url: '{{ $file->url ?? '#' }}',
                                            mime: '{{ $file->mime ?? 'application/octet-stream' }}'
                                         })"
                                         title="{{ __('Drag') }}">
                                        <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                                    </span>
                                </div>
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
                    <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>{{ __('Conversation History') }}</h5>
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
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted me-2">{{ $message->created_at->format('d M Y H:i') }}</small>
                                            @if(!$isClosed && (auth()->id() === $message->user_id || auth()->user()->can('manage-tickets')))
                                                <form action="{{ route($isAdminView ? 'admin.tickets.messages.destroy' : 'tickets.messages.destroy', $message->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                                            data-swal-title="{{ __('Confirm Delete') }}"
                                                            data-swal-text="{{ __('Are you sure you want to delete ...?') }}"
                                                            data-swal-icon="warning"
                                                            data-swal-confirm-text="{{ __('Yes, delete it') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
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
                        <p class="text-center text-muted py-4">{{ __('ยังไม่มีการสนทนา') }}</p>
                    @endif
                </div>
            </div>
            {{-- End Section 2: Communication History --}}


            {{-- Section 3: Reply Box --}}
            @if(!$isClosed)
                <div class="card mb-4" id="reply-box" @dragover.prevent @drop.prevent="handleDrop($event)">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-send me-2"></i> {{ __('Reply / Send Message') }}</h5></div>
                    <div class="card-body">
                        {{-- Hidden File Input --}}
                        <input type="file" multiple class="d-none" id="general-attachment-input" x-ref="generalFileInput" accept="image/jpeg,image/png,image/gif,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleGeneralFileUpload($event)" onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">

                        {{-- Determine the correct route --}}
                        @php
                            $replyRoute = $isAdminView ? 'admin.tickets.replies.store' : 'tickets.replies.store';
                        @endphp
                        <form x-ref="replyForm" action="{{ route($replyRoute, $ticket->id) }}" method="POST">
                            @csrf

                            {{-- Hidden inputs for basket items --}}
                            @include('tickets.partials._basket_form_inputs')

                            {{-- Text Area --}}
                            <div class="mb-3">
                                <label for="message" class="form-label">{{ __('ข้อความ:') }}</label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" placeholder="{{ __('Type your reply here...') }}">{{ old('message') }}</textarea>
                                @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Action Buttons (Moved Above Basket) --}}
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-primary w-100" @click="openExistingEmployeeModal" :disabled="isUploading">
                                        <i class="bi bi-person-check me-2"></i> {{ __('Attach Existing Employee') }}
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-success w-100" @click="openNewEmployeeModal" :disabled="isUploading">
                                        <i class="bi bi-person-plus me-2"></i> {{ __('Attach New Employee/Register') }}
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary flex-grow-1" @click="triggerScanner" :disabled="isUploading">
                                            <i class="bi bi-camera me-1"></i> {{ __('Scan') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary flex-grow-1" @click="triggerFileInput" :disabled="isUploading">
                                            <i class="bi bi-file-earmark-arrow-up me-1"></i> {{ __('File') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Attachment Basket --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('Attached Items') }} (<span x-text="totalItemsCount()"></span> {{ __('รายการ):') }}</label>

                                {{-- Upload Progress Bar --}}
                                <div x-show="isUploading" class="mb-2">
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" :style="'width: ' + uploadProgress + '%'" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100">
                                            {{ __('Uploading') }} (<span x-text="filesUploadedCount"></span> / <span x-text="filesToUploadCount"></span>)...
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group" style="max-height: 250px; overflow-y: auto;">
                                    <template x-if="totalItemsCount() === 0">
                                        <div class="list-group-item text-muted fst-italic">{{ __('No items attached') }}</div>
                                    </template>
                                    @include('tickets.partials._basket_display_templates')
                                </div>
                                 @error('attachments') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.files.*.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.existing_employees.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                 @error('attachments.new_employees.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" :disabled="isUploading">
                                    <template x-if="isUploading">
                                        <span><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Loading...') }}</span>
                                    </template>
                                    <template x-if="!isUploading">
                                        <span><i class="bi bi-send-fill me-2"></i> {{ __('Send Message') }}</span>
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-warning text-center">
                    <i class="bi bi-lock-fill me-2"></i> {{ __('This ticket is closed') }} ({{ $ticket->status_name }}). หากต้องการความช่วยเหลือเพิ่มเติม กรุณาสร้างตั๋วงานใหม่.
                </div>
            @endif
            {{-- End Section 3: Reply Box --}}

        </div>

        {{-- Column 2: Metadata Sidebar --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Ticket Info') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Ticket ID:</strong> #{{ $ticket->id }}
                    </li>
                    <li class="list-group-item">
                        <strong>{{ __('สร้างเมื่อ:') }}</strong> {{ $ticket->created_at->format('d M Y H:i') }}
                    </li>
                     <li class="list-group-item">
                        <strong>{{ __('อัปเดตล่าสุด:') }}</strong> {{ $ticket->updated_at->format('d M Y H:i') }}
                    </li>
                    {{-- Show Employer Info (More detailed in Admin view) --}}
                    <li class="list-group-item">
                        <strong>{{ __('Requester') }}:</strong>
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
                        <strong>{{ __('Assignee') }}:</strong>
                        {{ $ticket->assignedStaff->name ?? ($isAdminView ? 'ยังไม่ได้มอบหมาย' : 'รอเจ้าหน้าที่รับเรื่อง') }}
                    </li>
                </ul>
                {{-- Admin Action Buttons (Placeholder for V2.4-S11) --}}
                @if($isAdminView)
                    <div class="card-body d-grid gap-2">
                         <h5 class="mb-3">{{ __('Management') }} (Admin/Staff)</h5>
                        {{-- Mark as Resolved Button --}}
                        <form id="resolve-form" action="{{ route('admin.tickets.resolve', $ticket) }}" method="POST" class="d-grid">
                            @csrf
                            <button type="button" class="btn btn-outline-success btn-submit-swal"
                                    data-swal-title="{{ __('ยืนยันการปิดตั๋ว') }}"
                                    data-swal-text="คุณแน่ใจหรือไม่ว่าต้องการปิดตั๋วนี้เป็น 'Resolved'?"
                                    data-swal-icon="success"
                                    data-swal-confirm-text="ใช่, ปิดตั๋วเลย"
                                    {{ $isClosed ? 'disabled' : '' }}>
                                <i class="bi bi-check-circle-fill me-2"></i> Mark as Resolved
                            </button>
                        </form>

                        {{-- Reject Ticket Button --}}
                        <form id="reject-form" action="{{ route('admin.tickets.reject', $ticket) }}" method="POST" class="d-grid">
                            @csrf
                            <button type="button" class="btn btn-outline-danger btn-submit-swal"
                                    data-swal-title="{{ __('ยืนยันการปฏิเสธตั๋ว') }}"
                                    data-swal-text="คุณแน่ใจหรือไม่ว่าต้องการ 'Reject' ตั๋วนี้?"
                                    data-swal-icon="warning"
                                    data-swal-confirm-text="ใช่, ปฏิเสธตั๋ว"
                                    {{ $isClosed ? 'disabled' : '' }}>
                                <i class="bi bi-x-octagon-fill me-2"></i> Reject Ticket
                            </button>
                        </form>
                        {{-- Forward to P-Workflow Button --}}
                        <form id="forward-form" action="{{ route('admin.tickets.forward', $ticket) }}" method="POST" class="d-grid">
                            @csrf
                            <button type="button" class="btn btn-outline-primary btn-submit-swal"
                                    data-swal-title="{{ __('ยืนยันการส่งต่องาน') }}"
                                    data-swal-text="คุณต้องการส่งต่องานนี้เข้าสู่ P-Workflow (สถานะ In Progress) ใช่หรือไม่?"
                                    data-swal-icon="info"
                                    data-swal-confirm-text="ใช่, ส่งต่อเลย"
                                    {{ $isClosed ? 'disabled' : '' }}>
                                <i class="bi bi-arrow-right-circle-fill me-2"></i>
                                Forward to P-Workflow
                            </button>
                        </form>
                        {{-- Change Assignment Button --}}
                        <button type="button" class="btn btn-outline-secondary d-grid" data-bs-toggle="modal" data-bs-target="#changeAssignmentModal" {{ $isClosed ? 'disabled' : '' }}>
                            <i class="bi bi-person-fill-gear me-2"></i> Change Assignment
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- V2.4-S11: Include Modals (Required for the Hybrid System) --}}
    {{-- CRITICAL (V2.4-S11.2 Fix): Modals MUST be inside the x-data scope --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div> {{-- END of x-data scope --}}

{{-- Preview Modal for New Employees --}}
@include('tickets.partials._new_employee_preview_modal')
@include('employees.modals.advanced_export')

{{-- Change Assignment Modal (Admin only) --}}
@if($isAdminView)
<div class="modal fade" id="changeAssignmentModal" tabindex="-1" aria-labelledby="changeAssignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.tickets.updateAssignment', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="changeAssignmentModalLabel">Change Ticket Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Current Assignee: <strong>{{ $ticket->assignedTo->name ?? 'Unassigned' }}</strong></p>
                    <div class="mb-3">
                        <label for="assigned_to_user_id" class="form-label">Assign to New Staff:</label>
                        <select class="form-select" id="assigned_to_user_id" name="assigned_to_user_id" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staffAndAdmins ?? [] as $user)
                                <option value="{{ $user->id }}" {{ $ticket->assigned_to_user_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
{{-- Load the unified script component --}}
@include('components.hybrid-attachment-scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkExportBtn = document.getElementById('ticket-existing-bulk-advanced-export-btn');
    if (bulkExportBtn) {
        bulkExportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Note: Checkboxes in ticket show are .employee-checkbox
            const selected = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);

            if (selected.length === 0) {
                showToast('{{ __('Please select employees first.') }}', 'danger');
                return;
            }

            document.getElementById('export_employee_ids').value = JSON.stringify(selected);
            const modalEl = document.getElementById('advancedExportModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

    document.querySelectorAll('.btn-submit-swal').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // ป้องกันการ submit ทันที
            const form = this.closest('form'); // หา form แม่
            if (!form) return;

            // ดึงข้อมูลจาก data attributes ที่เราตั้งไว้
            const title = this.dataset.swalTitle;
            const text = this.dataset.swalText;
            const icon = this.dataset.swalIcon;
            const confirmText = this.dataset.swalConfirmText;

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: (icon === 'danger' || icon === 'warning') ? '#d33' : '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText('{{ __('ยกเลิก') }}')
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // สั่ง submit form เมื่อผู้ใช้ยืนยัน
                }
            });
        });
    });
});
</script>
@endpush
