@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่')

@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่')
@section('content')
<div class="content-section" x-data="attachmentBasket()">
    {{-- ... (Header, Error Display, Form Start) ... --}}
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
                        {{-- Attachment Buttons (V2.4-S6: Enable the second button) --}}
                        <div class="d-grid gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal">
                                <i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่
                            </button>
                            {{-- ENABLE THIS BUTTON --}}
                            <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal">
                                <i class="bi bi-person-plus me-2"></i> แนบลูกจ้างใหม่/แจ้งเข้า
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="triggerFileInput" disabled>
                                <i class="bi bi-file-earmark-arrow-up me-2"></i> แนบไฟล์/รูปภาพ (V2.4-S7)
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
                        </div>
                        {{-- ... (HR, Submit Button) ... --}}
                        <hr>

                        {{-- Submit Button --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send-fill me-2"></i> ส่งคำขอ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    {{-- Include Modals --}}
    @include('tickets.partials._existing_employee_modal')
    @include('tickets.partials._new_employee_modal')
</div>
@endsection

@push('scripts')
<script>
// V2.4-S6: Comprehensive Alpine.js component for the Attachment Basket
function attachmentBasket() {
    return {
        // --- Core Basket State (Persistent) ---
        basket: {
            existing_employees: [],
            // S6: Stores form data objects, including temp file paths
            new_employees: [],
            files: [],
        },
        // --- Modal Instances (Bootstrap) ---
        modalInstances: {
            existing: null,
            new: null,
        },
        // --- V2.4-S5: Existing Employee Modal State (Transient) ---
        availableEmployees: [],
        selectedEmployeeIds: [],
        isLoading: false,
        searchQuery: '',
        // --- V2.4-S6: New Employee Modal State (Transient) ---
        // Define the default structure for the new employee form
        defaultNewEmployeeForm: {
            employeeTitleTh: 'นาย',
            employeeNameTh: '',
            employeeTitleEn: 'Mr.',
            employeeNameEn: '',
            employeeNationality: '',
            employeePassport: '',
            nature_of_work: '',
            // Fields for storing temporary file paths (from Temp Upload API)
            employeePhoto: null,
            document_1: null,
        },
        newEmployeeForm: {}, // Holds the current form data in the modal
        // V2.4-S6: Upload Status Tracking
        uploadStatus: {
            // Structure: { loading: false, error: null, url: null }
        },
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
        // V2.4-S6 Enhancement: Use SweetAlert2 for confirmation
        removeConfirm(type, index, itemName) {
            // Check if SweetAlert2 (Swal) is loaded (imported in app.blade.php)
            if (typeof Swal === 'undefined') {
                console.error("SweetAlert2 not loaded.");
                // Fallback to standard confirm
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
                confirmButtonColor: '#d33', // Red for delete
                cancelButtonColor: '#6c757d', // Gray for cancel
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Use $nextTick to ensure DOM updates correctly after the async Swal dialog closes
                    this.$nextTick(() => {
                        this.basket[type].splice(index, 1);
                    });
                }
            });
        },
        // --- V2.4-S5: Existing Employee Functions ---
        async fetchEmployees() {
            if (this.availableEmployees.length > 0) return;
            this.isLoading = true;
            try {
                // API response now includes appended 'photo_url'
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
            // Search across TH name, EN name, and Passport
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
        // --- V2.4-S6: New Employee Functions ---
        // 1. Reset Form State and Upload Status
        resetNewEmployeeForm() {
            // Deep copy the default structure to reset data
            this.newEmployeeForm = JSON.parse(JSON.stringify(this.defaultNewEmployeeForm));
            // Initialize/Reset upload statuses dynamically based on file-related fields
            this.uploadStatus = {};
            Object.keys(this.defaultNewEmployeeForm).forEach(key => {
                if (key === 'employeePhoto' || key.startsWith('document_')) {
                    this.uploadStatus[key] = { loading: false, error: null, url: null };
                }
            });
            // Reset HTML form element (clears file inputs and validation states)
            const formElement = document.getElementById('newEmployeeActualForm');
            if (formElement) {
                formElement.reset();
            }
        },
        // 2. Open the Modal
        openNewEmployeeModal() {
            this.resetNewEmployeeForm(); // Ensure form is clean
            if (this.modalInstances.new) this.modalInstances.new.show();
        },
        // 3. Handle File Uploads (Temporary Upload API Integration)
        async handleFileUpload(event, fieldName) {
            const file = event.target.files[0];
            if (!file) return;

            // Ensure status tracking exists for this field
            if (!this.uploadStatus[fieldName]) return;

            const status = this.uploadStatus[fieldName];
            status.loading = true;
            status.error = null;

            const formData = new FormData();
            formData.append('file', file);

            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('{{ route('api-web.temp_upload.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json', // Expect JSON response for errors/success
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    // Handle API errors (e.g., validation failure 422)
                    // The controller returns { error: 'message' }
                    throw new Error(data.error || 'Upload failed');
                }

                // Success: Store the temporary path (data.path) and the preview URL (data.url)
                this.newEmployeeForm[fieldName] = data.path;
                status.url = data.url;

            } catch (error) {
                console.error('Upload error:', error);
                status.error = error.message;
                // Clear the path in the form data if upload fails
                this.newEmployeeForm[fieldName] = null;
                // Reset the file input element value so the user can retry the same file
                event.target.value = null;
            } finally {
                status.loading = false;
            }
        },
        // 4. Submit the Modal Form (Add to Basket)
        submitNewEmployeeForm() {
            // HTML5 validation ('required' attributes) handles basic checks.

            // Check if any uploads are still in progress
            const isUploading = Object.values(this.uploadStatus).some(status => status.loading);
            if (isUploading) {
                // Use SweetAlert2 for a better user experience
                Swal.fire({
                    icon: 'warning',
                    title: 'รอสักครู่',
                    text: 'กรุณารอให้การอัปโหลดไฟล์เสร็จสิ้นก่อนเพิ่มเข้าตะกร้า',
                });
                return;
            }

            // Add the current form data (including temp file paths) to the basket
            // Use JSON.parse(JSON.stringify(...)) to create a deep copy
            this.basket.new_employees.push(JSON.parse(JSON.stringify(this.newEmployeeForm)));

            // Close modal and reset form
            if (this.modalInstances.new) {
                this.modalInstances.new.hide();
            }
            this.resetNewEmployeeForm();
        },
        // --- Placeholders for Future Steps (S7) ---
        triggerFileInput() {
            console.log('Placeholder: Trigger File Input');
        },
    }
}
</script>
@endpush
