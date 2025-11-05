{{-- resources/views/tickets/partials/_existing_employee_modal.blade.php --}}
<div class="modal fade" id="existingEmployeeModal" tabindex="-1" aria-labelledby="existingEmployeeModalLabel" aria-hidden="true">
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
                    {{-- START: V2.4-S13 (PLAN B) - EMPLOYER FILTER --}}
                    {{-- Show this filter ONLY if the user is Admin/Staff (has permission) [cite: 18] --}}
                    @can('manage-tickets')
                    <div class="mb-3 p-3 border rounded bg-light">
                        <label for="employerFilterDropdown" class="form-label fw-bold">กรองตามนายจ้าง (สำหรับ Admin):</label>
                        <select x-model="selectedEmployerFilter" id="employerFilterDropdown" class="form-select"> [cite: 19]
                            <option value="">-- แสดงลูกจ้างทั้งหมด --</option> [cite: 19]
                            <template x-for="employer in employersList" :key="employer.id"> [cite: 20]
                                <option :value="employer.id" x-text="`${employer.employerNameTh} (${employer.employerId})`"></option> [cite: 20]
                            </template>
                        </select>
                    </div>
                    @endcan
                    {{-- END: V2.4-S13 (PLAN B) - EMPLOYER FILTER --}}
                    <div class="mb-3">
                        <input type="search" class="form-control" placeholder="ค้นหา ชื่อ (ไทย/อังกฤษ) หรือ Passport..." x-model.debounce.300ms="searchQuery"> [cite: 22]
                    </div>
                    <div class="list-group">
                        <template x-if="filteredEmployees().length === 0">
                            <div class="text-center text-muted py-3">
                                <span x-text="availableEmployees.length === 0 ? 'ไม่มีลูกจ้างในระบบ' : 'ไม่พบลูกจ้างที่ตรงกับคำค้นหา'"></span>
                            </div>
                        </template>
                        <template x-for="employee in filteredEmployees()" :key="employee.id"> [cite: 23]
                            <label class="list-group-item d-flex align-items-center gap-3 py-2">
                                {{-- Checkbox --}}
                                <input class="form-check-input me-1" type="checkbox" :value="employee.id.toString()" x-model="selectedEmployeeIds">
                                {{-- Photo --}}
                                <img :src="employee.photo_url" alt="Photo" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;"> [cite: 27]
                                <span class="flex-grow-1">
                                    {{-- Employee Details --}}
                                    <strong x-text="employee.employeeNameTh"></strong>
                                    <span class="text-muted" x-text="employee.employeeNameEn ? '(' + employee.employeeNameEn + ')' : ''"></span>
                                    <small class="text-muted d-block" x-text="'Passport: ' + (employee.employeePassport || 'N/A')"></small>
                                    {{-- Nationality --}}
                                    <div class="d-flex align-items-center" style="font-size: 0.85rem;">
                                        <span class="text-muted me-1">Nationality:</span>
                                        <template x-if="employee.flag_url">
                                            <img :src="employee.flag_url" class="me-1" style="width: 16px; height: 12px; object-fit: cover;" alt="Flag"> [cite: 31]
                                        </template>
                                        <strong x-text="employee.nationality || 'N/A'"></strong> [cite: 31]
                                    </div>
                                    {{-- Employer Name (V2.4-S13 Plan B) --}}
                                    @can('manage-tickets')
                                    <div class="d-flex align-items-center text-info" style="font-size: 0.85rem;">
                                        <i class="bi bi-building me-1"></i>
                                        <strong x-text="employee.employer_name || 'N/A'"></strong> [cite: 29]
                                    </div>
                                    @endcan
                                </span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto">เลือกแล้ว <strong x-text="selectedEmployeeIds.length"></strong> รายการ</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" @click="confirmSelection()">
                    <i class="bi bi-check-circle me-1"></i> ยืนยันการเลือก
                </button>
            </div>
        </div>
    </div>
</div>
