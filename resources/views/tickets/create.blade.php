@extends('layouts.app')
@section('title', 'สร้างคำขอใหม่')

@section('content')
{{-- Initialize Alpine.js Component for the entire form area --}}
<div class="content-section" x-data="attachmentBasket()">
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
            {{-- Column 1: Main Information (Left Side) --}}
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
                {{-- Make the basket sticky for better UX --}}
                <div class="card mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header">สิ่งที่แนบมา (Attachment Basket)</div>
                    <div class="card-body">
                        {{-- Attachment Buttons (V2.4-S5: Enable the first button) --}}
                        <div class="d-grid gap-2 mb-3">
                            {{-- ENABLE THIS BUTTON and link to Alpine function --}}
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal">
                                <i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่
                            </button>
                            <button type="button" class="btn btn-outline-success" @click="openNewEmployeeModal" disabled>
                                <i class="bi bi-person-plus me-2"></i> แนบลูกจ้างใหม่/แจ้งเข้า (V2.4-S6)
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

                            {{-- Dynamic Display & Hidden Inputs Generation --}}
                            {{-- Display Existing Employees --}}
                            <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    {{-- CRITICAL UPDATE: Use item.employeeNameTh (as fetched from API) --}}
                                    <span><i class="bi bi-person-check me-2 text-primary"></i> <span x-text="item.employeeNameTh"></span></span>
                                    <button type="button" class="btn btn-sm btn-danger" @click="removeFromBasket('existing_employees', index)">ลบ</button>
                                    {{-- Hidden input for backend processing (Array of IDs) --}}
                                    <input type="hidden" :name="'attachments[existing_employees][' + index + ']'" :value="item.id">
                                </div>
                            </template>

                            {{-- Display New Employees --}}
                            <template x-for="(item, index) in basket.new_employees" :key="'n-' + index">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-person-plus me-2 text-success"></i> ใหม่: <span x-text="item.employeeNameTh"></span></span>
                                    <button type="button" class="btn btn-sm btn-danger" @click="removeFromBasket('new_employees', index)">ลบ</button>
                                    {{-- Hidden input for backend processing (Array of JSON strings) --}}
                                    <input type="hidden" :name="'attachments[new_employees][' + index + ']'" :value="JSON.stringify(item)">
                                </div>
                            </template>
                        </div>

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

    {{-- V2.4-S5: Include the Modal Partial --}}
    @include('tickets.partials._existing_employee_modal')
</div>
@endsection

@push('scripts')
<script>
// V2.4-S5: Enhanced Alpine.js component for the Attachment Basket
function attachmentBasket() {
    return {
        // --- Core Basket State (Persistent) ---
        basket: {
            // Format: {id: 1, employeeNameTh: '...', ...} - Stores full objects from API
            existing_employees: [],
            new_employees: [],
            files: [],
        },

        // --- V2.4-S5: Existing Employee Modal State (Transient) ---
        availableEmployees: [], // Stores the list fetched from API
        // Stores IDs currently checked in the modal. (Transient State)
        selectedEmployeeIds: [],
        isLoading: false,
        searchQuery: '',
        modalInstance: null, // To hold the Bootstrap Modal instance

        // Initialize the component
        init() {
            // Get the Bootstrap Modal instance safely after DOM is ready
            this.$nextTick(() => {
                // Ensure Bootstrap's JS is loaded globally
                if (typeof bootstrap !== 'undefined') {
                    this.modalInstance = new bootstrap.Modal(document.getElementById('existingEmployeeModal'));
                } else {
                    console.error("Bootstrap JS not loaded.");
                }
            });
        },

        // --- Core Basket Functions ---
        totalItemsCount() {
            return this.basket.existing_employees.length + this.basket.new_employees.length + this.basket.files.length;
        },
        removeFromBasket(type, index) {
            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้ออกจากตะกร้า?')) {
                this.basket[type].splice(index, 1);
            }
        },

        // --- V2.4-S5: Existing Employee Functions (Transient State + Confirm Pattern) ---
        // 1. Fetch data from API
        async fetchEmployees() {
            if (this.availableEmployees.length > 0) return; // Data already loaded (Caching)

            this.isLoading = true;
            try {
                // Use the named route generated by Laravel
                const response = await fetch('{{ route('api-web.employer.employees.index') }}');
                if (!response.ok) throw new Error('Failed to fetch employees');
                this.availableEmployees = await response.json();
            } catch (error) {
                console.error(error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูลลูกจ้าง กรุณาลองใหม่อีกครั้ง');
            } finally {
                this.isLoading = false;
            }
        },

        // 2. Open the Modal
        async openExistingEmployeeModal() {
            // A. Fetch data (if needed)
            await this.fetchEmployees();

            // B. STATE SYNCHRONIZATION (Basket -> Modal):
            // Initialize transient state based on persistent state.
            // We map IDs to strings here to ensure reliable binding with x-model on checkboxes.
            this.selectedEmployeeIds = this.basket.existing_employees.map(e => e.id.toString());

            // C. Show the modal
            if (this.modalInstance) {
                this.modalInstance.show();
            }
        },

        // 3. Filter employees in the modal (Client-side search)
        filteredEmployees() {
            if (!this.searchQuery) return this.availableEmployees;
            const query = this.searchQuery.toLowerCase();
            return this.availableEmployees.filter(employee => {
                return (employee.employeeNameTh && employee.employeeNameTh.toLowerCase().includes(query)) ||
                       (employee.employeePassport && employee.employeePassport.toLowerCase().includes(query));
            });
        },

        // 4. Confirm Selection (Save changes from Modal to Basket)
        confirmSelection() {
            // STATE SYNCHRONIZATION (Modal -> Basket):
            // Reconstruct the basket list based on the final selected IDs in the modal.
            // CRITICAL: Ensure selected IDs are parsed back to integers for robust comparison,
            // as x-model binds them as strings from the checkboxes.
            const transientIds = new Set(this.selectedEmployeeIds.map(id => parseInt(id)));
            this.basket.existing_employees = this.availableEmployees.filter(employee => {
                return transientIds.has(employee.id);
            });

            // Close the modal
            if (this.modalInstance) {
                this.modalInstance.hide();
            }
            this.searchQuery = ''; // Reset search
        },

        // --- Placeholders for Future Steps (S6/S7) ---
        openNewEmployeeModal() {
            console.log('Placeholder: Open New Employee Modal');
        },
        triggerFileInput() {
            console.log('Placeholder: Trigger File Input');
        },
    }
}
</script>
@endpush
