{{-- resources/views/tickets/partials/_existing_employee_modal.blade.php --}}
<div class="modal fade" id="existingEmployeeModal" tabindex="-1" aria-labelledby="existingEmployeeModalLabel" aria-hidden="true">
    {{-- Use modal-dialog-scrollable for better UX with long lists --}}
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="existingEmployeeModalLabel">เลือกลูกจ้างที่มีอยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Loading State --}}
                <div x-show="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">กำลังโหลดข้อมูลลูกจ้าง...</p>
                </div>
                {{-- Content State --}}
                <div x-show="!isLoading">
                    <div class="mb-3">
                        {{-- Update placeholder text --}}
                        <input type="search" class="form-control" placeholder="ค้นหา ชื่อ (ไทย/อังกฤษ) หรือ Passport..." x-model.debounce.300ms="searchQuery">
                    </div>
                    <div class="list-group">
                        {{-- ... (Empty State Template remains the same) ... --}}
                        <template x-if="filteredEmployees().length === 0">
                            <div class="text-center text-muted py-3">
                                <span x-text="availableEmployees.length === 0 ? 'ไม่มีลูกจ้างในระบบ' : 'ไม่พบลูกจ้างที่ตรงกับคำค้นหา'"></span>
                            </div>
                        </template>
                        {{-- Employee List Iteration (V2.4-S6 Update: Richer Display) --}}
                        <template x-for="employee in filteredEmployees()" :key="employee.id">
                            <label class="list-group-item d-flex align-items-center gap-3 py-2">
                                {{-- Checkbox --}}
                                <input class="form-check-input me-1" type="checkbox" :value="employee.id" x-model="selectedEmployeeIds">
                                {{-- V2.4-S6: Employee Photo (using photo_url accessor from API) --}}
                                <img :src="employee.photo_url" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                {{-- V2.4-S6: Employee Details (Both Names) --}}
                                <span class="flex-grow-1">
                                    <strong x-text="employee.employeeNameTh"></strong>
                                    <span class="text-muted" x-text="employee.employeeNameEn ? '(' + employee.employeeNameEn + ')' : ''"></span>
                                    <small class="text-muted d-block" x-text="'Passport: ' + (employee.employeePassport || 'N/A')"></small>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto">เลือกแล้ว <strong x-text="selectedEmployeeIds.length"></strong> รายการ</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                {{-- Confirm Selection Button --}}
                <button type="button" class="btn btn-primary" @click="confirmSelection()">
                    <i class="bi bi-check-circle me-1"></i> ยืนยันการเลือก
                </button>
            </div>
        </div>
    </div>
</div>
