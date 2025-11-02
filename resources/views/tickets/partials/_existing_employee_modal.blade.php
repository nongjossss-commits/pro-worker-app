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
                        {{-- Use debounce for better search performance --}}
                        <input type="search" class="form-control" placeholder="ค้นหา ชื่อ หรือ Passport..." x-model.debounce.300ms="searchQuery">
                    </div>
                    <div class="list-group">
                        <template x-if="filteredEmployees().length === 0">
                            <div class="text-center text-muted py-3">
                                <span x-text="availableEmployees.length === 0 ? 'ไม่มีลูกจ้างในระบบ' : 'ไม่พบลูกจ้างที่ตรงกับคำค้นหา'"></span>
                            </div>
                        </template>
                        {{-- Employee List Iteration --}}
                        <template x-for="employee in filteredEmployees()" :key="employee.id">
                            <label class="list-group-item d-flex align-items-center gap-3">
                                {{-- Checkbox bound to the temporary selection state (selectedEmployeeIds) --}}
                                <input class="form-check-input me-1" type="checkbox" :value="employee.id" x-model="selectedEmployeeIds">
                                <span class="flex-grow-1">
                                    <strong x-text="employee.employeeNameTh"></strong>
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
