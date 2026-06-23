{{-- resources/views/workflow/partials/create_modal.blade.php --}}
@php
    // ใช้ slug ของ MOU import สำหรับ JS toggle (ตรวจทั้ง 'mou' และ 'mou_import' เพื่อรองรับข้อมูลเก่า)
    $mouSlugs = ['mou', 'mou_import'];
    // หา id ของ MOU tab จาก $tabs เพื่อให้ JS เปรียบเทียบ
    $mouTabIds = collect($tabs ?? [])
        ->filter(fn($t) => in_array($t->slug, $mouSlugs))
        ->pluck('id')
        ->values()
        ->all();

    // โหลด employers ทั้งหมดสำหรับ searchable dropdown (ลบ limit 200 ออก)
    $allEmployersForModal = \App\Models\Employer::select('id', 'employerNameTh', 'employerNameEn', 'employerId')
        ->orderBy('employerNameTh')
        ->get()
        ->map(fn($e) => [
            'id' => $e->id,
            'name_th' => $e->employerNameTh ?? '',
            'name_en' => $e->employerNameEn ?? '',
            'code' => $e->employerId ?? '',
            'search_str' => strtolower(($e->employerNameTh ?? '') . ' ' . ($e->employerNameEn ?? '') . ' ' . ($e->employerId ?? '')),
        ])
        ->values();
@endphp
<div class="modal fade" id="createJobModal" tabindex="-1" aria-hidden="true"
     x-data="workflowCreateJobModal({
         initialWorkTypeId: '{{ $activeTab->id ?? '' }}',
         mouTabIds: {{ json_encode($mouTabIds) }},
         employers: {{ $allEmployersForModal->toJson() }}
     })">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workflow.store') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="is_pre_production" id="create_job_is_pre_production" value="0">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span x-show="!isMou">{{ __('Create New Job') }}</span>
                    <span x-show="isMou" x-cloak><i class="bi bi-card-checklist me-1"></i> {{ __('Create Demand Card (MOU Import)') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Work Type') }}</label>
                    <select name="work_type_id" class="form-select" required x-model="workTypeId" @change="checkMou()">
                        @foreach($tabs as $tab)
                            <option value="{{ $tab->id }}" data-name="{{ $tab->name }}" data-slug="{{ $tab->slug }}" {{ isset($activeTab) && $activeTab->id == $tab->id ? 'selected' : '' }}>
                                {{ $tab->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Searchable Employer Dropdown --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('Employer') }} <span class="text-danger">*</span></label>
                    <input type="hidden" name="employer_id" :value="selectedEmployerId" required>
                    <div class="position-relative" @click.outside="empOpen = false">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text"
                                   class="form-control"
                                   placeholder="{{ __('พิมพ์เพื่อค้นหานายจ้าง (ชื่อไทย / EN / รหัส)') }}"
                                   x-model="empSearch"
                                   @focus="empOpen = true"
                                   @keydown.escape="empOpen = false"
                                   @keydown.arrow-down.prevent="empOpen = true"
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="empOpen = !empOpen"></button>
                            <button class="btn btn-outline-danger" type="button" @click="clearEmployer()" x-show="selectedEmployerId" style="display: none;" title="{{ __('Clear') }}">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div class="form-text text-success mt-1 small" x-show="selectedEmployerName" style="display: none;">
                            <i class="bi bi-check-circle-fill me-1"></i><span x-text="selectedEmployerName"></span>
                        </div>

                        <div class="card position-absolute w-100 shadow mt-1 border-0"
                             style="z-index: 1080; max-height: 280px; overflow-y: auto; display: none;"
                             x-show="empOpen"
                             x-transition>
                            <ul class="list-group list-group-flush">
                                <template x-for="emp in filteredEmployers" :key="emp.id">
                                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                        @click="selectEmployer(emp)"
                                        style="cursor: pointer;">
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-truncate" x-text="emp.name_th || emp.name_en"></div>
                                            <div class="small text-muted text-truncate">
                                                <span x-text="emp.name_en"></span>
                                                <span x-show="emp.code" class="ms-2"><i class="bi bi-hash"></i><span x-text="emp.code"></span></span>
                                            </div>
                                        </div>
                                        <i class="bi bi-check2 text-primary fs-5" x-show="selectedEmployerId == emp.id"></i>
                                    </li>
                                </template>
                                <li class="list-group-item text-muted text-center small" x-show="filteredEmployers.length === 0">
                                    {{ __('ไม่พบนายจ้างที่ค้นหา') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- ฟิลด์เฉพาะ MOU Import — สัญชาติ + จำนวนชาย/หญิง + ประเภท --}}
                <div x-show="isMou" x-cloak>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ __('MOU import is case-based — 1 employer can have multiple demand cards. Add employees inside each card.') }}
                    </div>

                    {{-- MOU Import Type Selection --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('ประเภท MOU นำเข้า') }}</label>
                        <input type="hidden" name="mou_import_type" :value="mouImportType">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100"
                                     :class="mouImportType === 'return' ? 'border-success bg-success bg-opacity-10' : 'border-secondary'"
                                     style="cursor: pointer;"
                                     @click="mouImportType = 'return'">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mou_import_type_radio" id="mouTypeReturn" value="return" x-model="mouImportType">
                                        <label class="form-check-label fw-bold text-success" for="mouTypeReturn">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Return') }}
                                        </label>
                                    </div>
                                    <div class="small text-muted mt-1">{{ __('ลูกจ้างอยู่ในไทยแล้ว — บันทึกข้อมูลลูกจ้างได้ทันที') }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100"
                                     :class="mouImportType === 'new' ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary'"
                                     style="cursor: pointer;"
                                     @click="mouImportType = 'new'">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="mou_import_type_radio" id="mouTypeNew" value="new" x-model="mouImportType">
                                        <label class="form-check-label fw-bold text-primary" for="mouTypeNew">
                                            <i class="bi bi-airplane me-1"></i>{{ __('New from Origin') }}
                                        </label>
                                    </div>
                                    <div class="small text-muted mt-1">{{ __('คนใหม่จากต้นทาง — ยังไม่มีข้อมูลลูกจ้าง รอ Demand → Name list') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-text small mt-1">
                            <i class="bi bi-info-circle me-1"></i>{{ __('ถ้ายังไม่แน่ใจ ปล่อยว่างได้ — กลับมาเลือกทีหลังจาก card บนหน้า Workflow ก็ได้') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Nationality') }} <span class="text-danger">*</span></label>
                        <select name="mou_nationality" class="form-select" :required="isMou">
                            <option value="">{{ __('Select Nationality...') }}</option>
                            <option value="myanmar">🇲🇲 {{ __('Myanmar') }}</option>
                            <option value="laos">🇱🇦 {{ __('Laos') }}</option>
                            <option value="cambodia">🇰🇭 {{ __('Cambodia') }}</option>
                            <option value="vietnam">🇻🇳 {{ __('Vietnam') }}</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Male Count') }}</label>
                            <input type="number" name="mou_male_count" class="form-control" min="0" max="9999" value="0" x-model="maleCount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Female Count') }}</label>
                            <input type="number" name="mou_female_count" class="form-control" min="0" max="9999" value="0" x-model="femaleCount">
                        </div>
                    </div>
                    <div class="mb-3 text-end small text-muted">
                        {{ __('Total') }}: <span class="fw-bold text-dark" x-text="(parseInt(maleCount) || 0) + (parseInt(femaleCount) || 0)"></span> {{ __('persons') }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Project Name / Batch Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                    <input type="text" name="project_name" class="form-control" placeholder="{{ __('e.g. Batch 1 - Jan 2024') }}">
                    <div class="form-text text-muted">{{ __('Use this to create multiple batches for MOU Import.') }}</div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary">
                    <span x-show="!isMou">{{ __('Create Job') }}</span>
                    <span x-show="isMou" x-cloak>{{ __('Create Demand Card') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('workflowCreateJobModal', (config) => ({
            workTypeId: config.initialWorkTypeId,
            mouTabIds: config.mouTabIds || [],
            isMou: false,
            maleCount: 0,
            femaleCount: 0,
            mouImportType: '',
            // Employer searchable dropdown
            employers: config.employers || [],
            empSearch: '',
            empOpen: false,
            selectedEmployerId: '',
            selectedEmployerName: '',

            init() {
                this.checkMou();
            },
            checkMou() {
                this.isMou = this.mouTabIds.includes(parseInt(this.workTypeId));
            },
            get filteredEmployers() {
                const term = (this.empSearch || '').toLowerCase().trim();
                if (!term) return this.employers.slice(0, 100);
                return this.employers
                    .filter(e => e.search_str.includes(term))
                    .slice(0, 100);
            },
            selectEmployer(emp) {
                this.selectedEmployerId = emp.id;
                this.selectedEmployerName = emp.name_th + (emp.name_en ? ' (' + emp.name_en + ')' : '') + (emp.code ? ' • ' + emp.code : '');
                this.empSearch = emp.name_th || emp.name_en;
                this.empOpen = false;
            },
            clearEmployer() {
                this.selectedEmployerId = '';
                this.selectedEmployerName = '';
                this.empSearch = '';
                this.empOpen = false;
            }
        }));
    });
</script>
@endonce
