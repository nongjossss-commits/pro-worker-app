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
                        {{-- Attachment Buttons (Placeholders - Disabled for now) --}}
                        <div class="d-grid gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary" @click="openExistingEmployeeModal" disabled>
                                <i class="bi bi-person-check me-2"></i> แนบลูกจ้างที่มีอยู่ (V2.4-S5)
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

                            {{-- Dynamic Display & Hidden Inputs Generation (Crucial for Hybrid Form) --}}
                            {{-- Display Existing Employees --}}
                            <template x-for="(item, index) in basket.existing_employees" :key="'e-' + item.id">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-person-check me-2 text-primary"></i> <span x-text="item.name"></span></span>
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
</div>
@endsection

@push('scripts')
<script>
    // Define the Alpine.js component for the Attachment Basket
    function attachmentBasket() {
        return {
            // Initialize basket state.
            basket: {
                // Structure for storing attached items
                existing_employees: [], // Format: {id: 1, name: 'John Doe'}
                new_employees: [],    // Format: Full form data object {employeeNameTh: '...', ...}
                files: [],            // File objects
            },

            totalItemsCount() {
                return this.basket.existing_employees.length + this.basket.new_employees.length + this.basket.files.length;
            },

            // Placeholder functions (To be implemented in V2.4-S5/S6/S7)
            openExistingEmployeeModal() {
                console.log('Placeholder: Open Existing Employee Modal');
                // Example Add (for testing UI):
                // this.basket.existing_employees.push({id: Date.now(), name: 'Test Employee ' + Date.now()});
            },
            openNewEmployeeModal() {
                console.log('Placeholder: Open New Employee Modal');
            },
            triggerFileInput() {
                console.log('Placeholder: Trigger File Input');
            },

            removeFromBasket(type, index) {
                if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้ออกจากตะกร้า?')) {
                    this.basket[type].splice(index, 1);
                }
            },
        }
    }
</script>
@endpush
