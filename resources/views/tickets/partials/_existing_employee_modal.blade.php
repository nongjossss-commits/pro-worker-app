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
                    {{-- V2.4-S19 (Plan B): Employer Search (Admin/Staff Only) --}}
                    @can('manage-tickets')
                        <div class="px-3 pt-3">
                            <label for="employerSearchInput" class="form-label fw-bold">1. ค้นหานายจ้าง (สำหรับ Admin/Staff)</label>
                            {{-- If an employer is selected, show their name and a clear button --}}
                            <template x-if="selectedEmployer">
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light" x-text="selectedEmployer.employerNameTh"></span>
                                    <button class="btn btn-outline-danger" type="button" @click="clearEmployerSelection">
                                        <i class="bi bi-x-lg"></i> ล้าง
                                    </button>
                                </div>
                            </template>
                            {{-- Show search box ONLY if no employer is selected --}}
                            <template x-if="!selectedEmployer">
                                <div>
                                    <input type="text" id="employerSearchInput" class="form-control" placeholder="พิมพ์ชื่อ หรือ รหัสนายจ้าง..."
                                           x-model="employerSearchQuery" @input.debounce.500ms="fetchEmployersList">
                                    {{-- Loading indicator for employer search --}}
                                    <div x-show="isLoadingEmployers" class="text-muted small mt-1">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        กำลังค้นหานายจ้าง...
                                    </div>
                                    {{-- Employer Search Results List --}}
                                    <template x-if="employerSearchQuery && availableEmployersList.length > 0">
                                        <div class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">
                                            <template x-for="employer in availableEmployersList" :key="employer.id">
                                                <button type="button" class="list-group-item list-group-item-action" @click="selectEmployer(employer)">
                                                    <span x-text="employer.employerNameTh"></span> (<span x-text="employer.employerId"></span>)
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    @endcan
                    {{-- V2.4-S19 (Plan B) END --}}

                    {{-- Employee Search (Filter within selected Employer) --}}
                    <div class="p-3">
                        @can('manage-tickets')
                             <label for="employeeSearchInput" class="form-label fw-bold">2. ค้นหาลูกจ้าง</label>
                        @endcan
                        <input type="text" id="employeeSearchInput" class="form-control"
                               placeholder="พิมพ์ชื่อ, Passport, หรือรหัสลูกจ้าง..." x-model="searchQuery">
                    </div>

                    <hr class="my-0">

                    {{-- Employee List Container --}}
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
                                    {{-- V2.5-S2: Nationality with Flag --}}
                                    <div class="d-flex align-items-center" style="font-size: 0.85rem;">
                                        <span class="text-muted me-1">Nationality:</span>
                                        <template x-if="employee.flag_url">
                                            <img :src="employee.flag_url" class="me-1" style="width: 16px; height: 12px; object-fit: cover;" alt="Flag">
                                        </template>
                                        <strong x-text="employee.nationality || 'N/A'"></strong>
                                    </div>
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
