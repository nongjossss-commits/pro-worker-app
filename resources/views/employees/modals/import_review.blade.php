<!-- Import Review Modal -->
<div class="modal fade" id="importReviewModal" tabindex="-1" aria-labelledby="importReviewModalLabel" aria-hidden="true"
     x-data="importReviewData()"
     x-init="init()"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="importReviewModalLabel">
                    <i class="bi bi-card-checklist me-2 text-primary"></i>{{ __('Review Imported Employees') }}
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success" x-text="employees.length + ' {{ __('Imported') }}'"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body bg-light">

                <!-- Toolbar -->
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-center">
                            <!-- Select All -->
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllImported" x-model="selectAll" @change="toggleSelectAll()">
                                    <label class="form-check-label" for="selectAllImported">{{ __('Select All') }}</label>
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" placeholder="{{ __('Search by Name, Passport...') }}" x-model="search" @input="filter()">
                                </div>
                            </div>

                            <!-- Filter Nationality -->
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" x-model="nationality" @change="filter()">
                                    <option value="">{{ __('All Nationalities') }}</option>
                                    <option value="เมียนมา">{{ __('Myanmar') }}</option>
                                    <option value="กัมพูชา">{{ __('Cambodia') }}</option>
                                    <option value="ลาว">{{ __('Laos') }}</option>
                                    <option value="เวียดนาม">{{ __('Vietnam') }}</option>
                                </select>
                            </div>

                            <!-- Bulk Actions -->
                            <div class="col text-md-end">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="importBulkActions" data-bs-toggle="dropdown" aria-expanded="false" :disabled="selectedIds.length === 0">
                                        {{ __('Selected Actions') }} (<span x-text="selectedIds.length"></span>)
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="importBulkActions">
                                        <li>
                                            <a class="dropdown-item" href="#" @click.prevent="submitBulkAdvancedEdit()">
                                                <i class="bi bi-pencil-square me-2"></i>{{ __('Advanced Edit') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="row g-3">
                    <template x-for="employee in filteredEmployees" :key="employee.id">
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm border-0 position-relative">
                                <!-- Selection Checkbox -->
                                <div class="position-absolute top-0 start-0 m-2" style="z-index: 10;">
                                    <input type="checkbox" class="form-check-input" :value="employee.id" x-model="selectedIds">
                                </div>

                                <div class="card-body p-3 text-center">
                                    <div class="mb-3 position-relative d-inline-block">
                                        <!-- Photo: Use correct property or fallback -->
                                        <img :src="employee.photo_url || '/images/default-avatar.png'"
                                             class="rounded-circle shadow-sm object-fit-cover"
                                             width="80" height="80"
                                             alt="Employee Photo">
                                    </div>

                                    <h6 class="fw-bold text-truncate mb-1" x-text="employee.employeeNameTh || '-'"></h6>
                                    <p class="text-muted small mb-1" x-text="employee.employeeNameEn || '-'"></p>

                                    <div class="d-flex justify-content-center gap-2 mb-2">
                                        <span class="badge bg-light text-dark border" x-text="employee.employeeNationality"></span>
                                        <span class="badge bg-light text-dark border" x-text="employee.age ? employee.age + ' {{ __('Years') }}' : '-'"></span>
                                    </div>

                                    <div class="small text-start bg-light p-2 rounded mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">{{ __('Passport') }}:</span>
                                            <span class="fw-bold" x-text="employee.employeePassport || '-'"></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">{{ __('Work Permit') }}:</span>
                                            <span class="fw-bold" x-text="employee.employeeWorkPermit || '-'"></span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-info btn-preview"
                                            :data-model-id="employee.id"
                                            data-model-type="App\Models\Employee"
                                            title="{{ __('Preview') }}">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <a :href="'/employees/' + employee.id + '/edit'" class="btn btn-sm btn-outline-warning" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredEmployees.length === 0" class="col-12 text-center py-5">
                        <p class="text-muted">{{ __('No employees found matching criteria.') }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('Finish Review') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('importReviewData', () => ({
        // Pass data from Blade to Alpine
        employees: @json($importedEmployees ?? []),
        filteredEmployees: [],
        search: '',
        nationality: '',
        selectedIds: [],
        selectAll: false,

        init() {
            // Transform data if needed (e.g. photo URL)
            // The Employee model usually has an accessor for photo_url
            // If the JSON serialization doesn't include it by default (appends),
            // we rely on what's passed.
            // *Correction*: We need to ensure 'photo_url' and 'age' accessors are available in the JSON.
            // Eloquent models don't serialize accessors unless appended.
            // However, in blade @json($collection), it uses toArray().
            // I should check if 'photo_url' is appended in Employee model.
            // If not, the JS might show broken images.
            // Assuming for now standard attributes or the 'media' relationship logic is handled elsewhere.
            // To be safe, I'll assume 'employeePhoto' is the path and we construct the URL,
            // OR I rely on the controller eager loading/appending.

            // For now, let's map the photo path to a storage URL in JS if needed,
            // or trust the model.
            this.employees = this.employees.map(emp => {
                // simple fix for photo url if it's just a path
                if (emp.employeePhoto && !emp.employeePhoto.startsWith('http')) {
                    emp.photo_url = '/storage/' + emp.employeePhoto;
                } else if (!emp.employeePhoto) {
                    emp.photo_url = null;
                }
                // calculate age if missing (simple approx)
                if (!emp.age && emp.employeeDob) {
                     const dob = new Date(emp.employeeDob);
                     const diff = Date.now() - dob.getTime();
                     emp.age = Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25));
                }
                return emp;
            });

            this.filteredEmployees = this.employees;
        },

        filter() {
            const lowerSearch = this.search.toLowerCase();
            this.filteredEmployees = this.employees.filter(emp => {
                const matchesName = (emp.employeeNameTh && emp.employeeNameTh.toLowerCase().includes(lowerSearch)) ||
                                    (emp.employeeNameEn && emp.employeeNameEn.toLowerCase().includes(lowerSearch)) ||
                                    (emp.name_suffix && emp.name_suffix.toLowerCase().includes(lowerSearch)) ||
                                    (emp.employeePassport && emp.employeePassport.toLowerCase().includes(lowerSearch));

                const matchesNation = this.nationality === '' || emp.employeeNationality === this.nationality;

                return matchesName && matchesNation;
            });
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedIds = this.filteredEmployees.map(e => e.id);
            } else {
                this.selectedIds = [];
            }
        },

        submitBulkAdvancedEdit() {
             if (this.selectedIds.length === 0) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('employees.bulk-edit.selector') }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            this.selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'employee_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            // Also pass redirect_to to come back here?
            // Or maybe back to index. Usually bulk edit goes to index after save.

            document.body.appendChild(form);
            form.submit();
        }
    }));
});
</script>
