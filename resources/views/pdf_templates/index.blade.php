@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="witnessManager()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">PDF Templates</h2>
        <div class="d-flex gap-2 align-items-center justify-content-center flex-grow-1" style="max-width: fit-content; margin: 0 auto;">
            <button type="button" class="btn btn-warning shadow-sm fw-bold px-4" data-bs-toggle="modal" data-bs-target="#quickGenerateModal">
                <i class="bi bi-printer-fill me-2"></i>สร้างการพิมพ์เอกสารอัตโนมัติ (Quick Print)
            </button>
        </div>
        <div class="d-flex gap-2">
            @can('create-pdf-templates')
            <button type="button" @click="openModal()" class="btn btn-outline-info">
                <i class="bi bi-people me-2"></i>ตั้งค่ารายชื่อพยาน
            </button>
            <a href="{{ route('admin.pdf-templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Create New Template
            </a>
            @endcan
        </div>
    </div>

    {{-- Filter Section --}}
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff') || auth()->user()->hasRole('caretaker'))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded">
            <form action="{{ route('admin.pdf-templates.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Filter by Employer / Type</label>
                    {{-- Reusing the searchable dropdown pattern --}}
                    @php
                        $selectedId = request('employer_id');
                        $selectedName = '';
                        if($selectedId === 'global') {
                            $selectedName = 'Global Templates Only';
                        } elseif($selectedId && $employer = $employers->firstWhere('id', $selectedId)) {
                            $selectedName = $employer->employerNameTh . ' (' . $employer->employerNameEn . ')';
                        }

                        // Prepare options: Global + Employers
                        // We structure options to include a specialized 'Global' entry
                        $employerOptions = collect([
                            [
                                'id' => 'global',
                                'name_th' => 'Global Templates Only (ส่วนกลาง)',
                                'name_en' => 'Global',
                                'search_str' => 'global templates only ส่วนกลาง'
                            ]
                        ])->merge(
                            $employers->map(fn($e) => [
                                'id' => $e->id,
                                'name_th' => $e->employerNameTh,
                                'name_en' => $e->employerNameEn,
                                'search_str' => strtolower($e->employerNameTh . ' ' . $e->employerNameEn)
                            ])
                        );
                    @endphp

                    <div x-data="filterEmployerSelector()" @click.outside="open = false">
                        <input type="hidden" name="employer_id" :value="selectedId">

                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Type to search employer or select Global..."
                                       x-model="search"
                                       @focus="open = true"
                                       @keydown.escape="open = false"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                            </div>

                            <div class="form-text text-primary fw-bold mt-1" x-show="selectedName" style="display: none;">
                                <i class="bi bi-check-circle-fill me-1"></i> Filtering: <span x-text="selectedName"></span>
                            </div>

                            <div class="card position-absolute w-100 shadow mt-1 border-0"
                                 style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"
                                 x-show="open"
                                 x-transition>
                                <ul class="list-group list-group-flush">
                                    <template x-for="opt in filteredOptions" :key="opt.id">
                                        <li class="list-group-item list-group-item-action cursor-pointer d-flex justify-content-between align-items-center"
                                            @click="selectOption(opt)">
                                            <div>
                                                <div class="fw-bold" x-text="opt.name_th"></div>
                                                <div class="small text-muted" x-text="opt.name_en"></div>
                                            </div>
                                            <i class="bi bi-check2 text-primary" x-show="selectedId == opt.id"></i>
                                        </li>
                                    </template>
                                    <li class="list-group-item text-muted text-center" x-show="filteredOptions.length === 0">
                                        No results found
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.pdf-templates.index') }}" class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function filterEmployerSelector() {
            return {
                search: '',
                open: false,
                selectedId: '{{ $selectedId }}',
                selectedName: '{{ addslashes($selectedName) }}',
                options: @json($employerOptions),

                init() {
                    // Pre-fill search box if value selected
                    if(this.selectedName) {
                        this.search = this.selectedName;
                    }
                },

                get filteredOptions() {
                    if (this.search === '') return this.options;
                    const term = this.search.toLowerCase();
                    return this.options.filter(o => o.search_str.includes(term));
                },

                selectOption(opt) {
                    this.selectedId = opt.id;
                    this.selectedName = opt.name_th + ' (' + opt.name_en + ')';
                    this.search = opt.name_th; // Show Thai name in input
                    this.open = false;
                }
            }
        }
    </script>
    @endpush
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Template Name</th>
                            <th>Type</th>
                            <th>Owner (Employer)</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-gray-700">{{ $template->name }}</div>
                            </td>
                            <td>
                                @if($template->type === 'global')
                                    <span class="badge bg-success">Global</span>
                                @else
                                    <span class="badge bg-info">Employer</span>
                                @endif
                            </td>
                            <td>
                                {{ optional($template->employer)->employerNameTh ?? '-' }}
                            </td>
                            <td>{{ optional($template->creator)->name }}</td>
                            <td>{{ $template->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.pdf-templates.builder', $template) }}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-pencil-square"></i> Builder
                                </a>

                                <a href="{{ route('admin.pdf-templates.preview', $template->id) }}" class="btn btn-sm btn-outline-info me-2" title="Download Preview">
                                    <i class="bi bi-eye"></i> Preview
                                </a>

                                <a href="{{ route('admin.pdf-templates.file', ['pdf_template' => $template->id, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary me-2" title="Download Original">
                                    <i class="bi bi-download"></i> Original
                                </a>

                                @can('delete-pdf-templates', $template)
                                <form action="{{ route('admin.pdf-templates.destroy', $template) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-submit-swal"
                                            data-swal-title="Are you sure?"
                                            data-swal-text="This template will be deleted permanently.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No templates found. Create one to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $templates->links() }}
        </div>
        @endif
    </div>

    <!-- Witness Management Modal -->
    <div class="modal fade" id="witnessModal" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตั้งค่ารายชื่อพยาน (Manage Witnesses)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <!-- Form to add/edit witness -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3" x-text="isEditing ? 'แก้ไขพยาน (Edit Witness)' : 'เพิ่มพยานใหม่ (Add New Witness)'"></h6>
                            <form @submit.prevent="saveWitness" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อภาษาไทย (Name TH) <span class="text-danger">*</span></label>
                                        <input type="text" x-model="form.name_th" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อภาษาอังกฤษ (Name EN) <span class="text-danger">*</span></label>
                                        <input type="text" x-model="form.name_en" class="form-control" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">อัปโหลดลายเซ็น (Signature Image)</label>
                                        <input type="file" x-ref="signatureFile" class="form-control" accept="image/png, image/jpeg" @change="handleFileChange">
                                        <div class="text-muted small mt-1">อัปโหลดไฟล์ JPG, PNG หากต้องการบันทึกลายเซ็น (หากไม่มี ระบบจะสุ่มลายเซ็นให้ตอนสร้างเอกสาร)</div>
                                    </div>

                                    <div class="col-md-12" x-show="form.signature_preview">
                                        <label class="form-label text-muted small">Current Signature / Preview</label>
                                        <div class="p-2 border rounded bg-white" style="max-width: 200px;">
                                            <img :src="form.signature_preview" class="img-fluid" style="max-height: 80px;">
                                        </div>
                                    </div>

                                    <div class="col-md-12 text-end mt-3">
                                        <button type="button" x-show="isEditing" @click="resetForm()" class="btn btn-outline-secondary me-2">Cancel Edit</button>
                                        <button type="submit" class="btn btn-primary" :disabled="isSaving">
                                            <span x-show="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                                            <span x-text="isEditing ? 'อัปเดต (Update)' : 'บันทึก (Save)'"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- List of witnesses -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 300px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-white sticky-top">
                                        <tr>
                                            <th>ชื่อ (Name)</th>
                                            <th>ลายเซ็น (Signature)</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="isLoading">
                                            <tr><td colspan="3" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary"></span> Loading...</td></tr>
                                        </template>
                                        <template x-if="!isLoading && witnesses.length === 0">
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No witnesses found. Add one above.</td></tr>
                                        </template>
                                        <template x-for="witness in witnesses" :key="witness.id">
                                            <tr>
                                                <td>
                                                    <div class="fw-bold" x-text="witness.name_th"></div>
                                                    <div class="small text-muted" x-text="witness.name_en"></div>
                                                </td>
                                                <td>
                                                    <template x-if="witness.signature_path">
                                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> มีลายเซ็น</span>
                                                    </template>
                                                    <template x-if="!witness.signature_path">
                                                        <span class="badge bg-secondary">ไม่มี (No Signature)</span>
                                                    </template>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" @click="editWitness(witness)" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" @click="deleteWitness(witness.id)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Generate Modal -->
<div class="modal fade" id="quickGenerateModal" tabindex="-1" aria-labelledby="quickGenerateModalLabel" aria-hidden="true" x-data="quickGenerateModal()">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-bottom-0 py-3">
                <h5 class="modal-title fw-bold" id="quickGenerateModalLabel">
                    <i class="bi bi-printer-fill me-2"></i>สร้างการพิมพ์เอกสารอัตโนมัติ (Quick Print)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form action="{{ route('admin.pdf-templates.quick-generate') }}" method="POST" id="quickGenerateForm" @submit="handleSubmit">
                    @csrf

                    <!-- Select Template -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                        <label class="form-label fw-bold text-primary mb-3"><i class="bi bi-file-earmark-pdf me-2"></i>1. เลือกเทมเพลต (Select Template)</label>
                        <select name="template_id" class="form-select form-select-lg" required x-model="form.template_id">
                            <option value="">-- กรุณาเลือกเทมเพลต --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }}
                                    ({{ $template->type === 'global' ? 'ส่วนกลาง/Global' : 'ส่วนตัวนายจ้าง' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Target Employer Selection -->
                    @if(isset($employers) && $employers->count() > 0)
                    <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                        <label class="form-label fw-bold mb-3"><i class="bi bi-building me-2"></i>2. เลือกข้อมูลนายจ้าง (Target Employer)</label>
                        <div class="alert alert-light border small text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i> หากไม่ต้องการระบุข้อมูลนายจ้าง ให้เลือก "เว้นว่าง / ไม่มีนายจ้าง"
                        </div>

                        <div x-data="searchableDropdown({
                            options: [
                                {id: '', name_th: 'เว้นว่าง / ไม่มีนายจ้าง (Empty)', name_en: 'None', search_str: 'เว้นว่าง ไม่มีนายจ้าง empty none'},
                                @foreach($employers as $e)
                                {id: '{{ $e->id }}', name_th: '{{ addslashes($e->employerNameTh) }}', name_en: '{{ addslashes($e->employerNameEn) }}', search_str: '{{ strtolower(addslashes($e->employerNameTh . ' ' . $e->employerNameEn)) }}'},
                                @endforeach
                            ],
                            value: ''
                        })" @click.outside="open = false; syncSearch()">

                            <input type="hidden" name="target_employer_id" :value="value" x-model="form.target_employer_id">

                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" placeholder="ค้นหานายจ้าง หรือเลือกเว้นว่าง..."
                                        x-model="search" @focus="open = true; search = ''" @keydown.escape="open = false">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                </div>

                                <div class="card position-absolute w-100 shadow mt-1 border-0" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;" x-show="open" x-transition>
                                    <ul class="list-group list-group-flush">
                                        <template x-for="opt in filteredOptions" :key="opt.id">
                                            <li class="list-group-item list-group-item-action cursor-pointer" @click="selectOption(opt)">
                                                <div class="fw-bold" x-text="opt.name_th"></div>
                                                <div class="small text-muted" x-show="opt.name_en !== 'None'" x-text="opt.name_en"></div>
                                            </li>
                                        </template>
                                        <li class="list-group-item text-muted text-center" x-show="filteredOptions.length === 0">ไม่พบข้อมูล</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-4">
                        <!-- Target Importer -->
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded shadow-sm border h-100">
                                <label class="form-label fw-bold mb-3"><i class="bi bi-truck me-2"></i>3. เลือกบริษัทนำเข้า</label>
                                <div x-data="searchableDropdown({
                                    options: [
                                        {id: '', name_th: 'เว้นว่าง / ไม่มีบริษัทนำเข้า', name_en: 'None', search_str: 'เว้นว่าง none'},
                                        @foreach($importers as $i)
                                        {id: '{{ $i->id }}', name_th: '{{ addslashes($i->importerNameTh) }}', name_en: '{{ addslashes($i->importerNameEn) }}', search_str: '{{ strtolower(addslashes($i->importerNameTh . ' ' . $i->importerNameEn)) }}'},
                                        @endforeach
                                    ], value: ''
                                })" @click.outside="open = false; syncSearch()">
                                    <input type="hidden" name="target_importer_id" :value="value">
                                    <div class="position-relative">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" placeholder="ค้นหาบริษัทนำเข้า..." x-model="search" @focus="open = true; search = ''" @keydown.escape="open = false">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                        </div>
                                        <div class="card position-absolute w-100 shadow mt-1 border-0" style="z-index: 1050; max-height: 200px; overflow-y: auto; display: none;" x-show="open">
                                            <ul class="list-group list-group-flush">
                                                <template x-for="opt in filteredOptions" :key="opt.id">
                                                    <li class="list-group-item list-group-item-action cursor-pointer p-2" @click="selectOption(opt)">
                                                        <div class="fw-bold small" x-text="opt.name_th"></div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Target Delegate -->
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded shadow-sm border h-100">
                                <label class="form-label fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>4. เลือกผู้รับมอบอำนาจ</label>
                                <div x-data="searchableDropdown({
                                    options: [
                                        {id: '', name_th: 'เว้นว่าง / ไม่มีผู้รับมอบอำนาจ', name_en: 'None', search_str: 'เว้นว่าง none'},
                                        @foreach($delegates as $d)
                                        {id: '{{ $d->id }}', name_th: '{{ addslashes($d->delegateNameTh) }}', name_en: '{{ addslashes($d->delegateNameEn) }}', search_str: '{{ strtolower(addslashes($d->delegateNameTh . ' ' . $d->delegateNameEn)) }}'},
                                        @endforeach
                                    ], value: ''
                                })" @click.outside="open = false; syncSearch()">
                                    <input type="hidden" name="target_delegate_id" :value="value">
                                    <div class="position-relative">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" placeholder="ค้นหาผู้รับมอบอำนาจ..." x-model="search" @focus="open = true; search = ''" @keydown.escape="open = false">
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
                                        </div>
                                        <div class="card position-absolute w-100 shadow mt-1 border-0" style="z-index: 1050; max-height: 200px; overflow-y: auto; display: none;" x-show="open">
                                            <ul class="list-group list-group-flush">
                                                <template x-for="opt in filteredOptions" :key="opt.id">
                                                    <li class="list-group-item list-group-item-action cursor-pointer p-2" @click="selectOption(opt)">
                                                        <div class="fw-bold small" x-text="opt.name_th"></div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Employee -->
                    <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                        <label class="form-label fw-bold mb-3"><i class="bi bi-person me-2"></i>5. เลือกลูกจ้าง (Target Employee)</label>
                        <div class="alert alert-light border small text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i> หากไม่ต้องการระบุข้อมูลลูกจ้าง ให้เลือก "เว้นว่าง / ไม่มีลูกจ้าง"
                        </div>

                        <div x-data="employeeSearchDropdown()" @click.outside="open = false">
                            <input type="hidden" name="employee_id" :value="selectedId">

                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-white" x-show="!isSearching"><i class="bi bi-search"></i></span>
                                    <span class="input-group-text bg-white" x-show="isSearching"><i class="spinner-border spinner-border-sm text-primary"></i></span>
                                    <input type="text" class="form-control" placeholder="พิมพ์ชื่อลูกจ้าง พาสปอร์ต หรือเว้นว่าง..."
                                        x-model="search" @input.debounce.500ms="fetchEmployees" @focus="open = true; search = ''" @keydown.escape="open = false">
                                    <button class="btn btn-outline-danger" type="button" @click="clearSelection()" x-show="selectedId" title="Clear Selection"><i class="bi bi-x"></i></button>
                                </div>

                                <div class="form-text text-primary fw-bold mt-1" x-show="selectedName" style="display: none;">
                                    <i class="bi bi-check-circle-fill me-1"></i> เลือกลูกจ้าง: <span x-text="selectedName"></span>
                                </div>

                                <div class="card position-absolute w-100 shadow mt-1 border-0" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;" x-show="open" x-transition>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item list-group-item-action cursor-pointer text-muted bg-light" @click="selectEmpty()">
                                            <div class="fw-bold">เว้นว่าง / ไม่มีลูกจ้าง (Empty)</div>
                                        </li>
                                        <template x-for="emp in results" :key="emp.id">
                                            <li class="list-group-item list-group-item-action cursor-pointer" @click="selectOption(emp)">
                                                <div class="fw-bold" x-text="(emp.employeeNameTh || '') + ' ' + (emp.employeeNameEn || '')"></div>
                                                <div class="small text-muted">
                                                    <span x-show="emp.employeePassport">PP: <span x-text="emp.employeePassport"></span></span>
                                                    <span x-show="emp.employeeWorkPermit" class="ms-2">WP: <span x-text="emp.employeeWorkPermit"></span></span>
                                                </div>
                                            </li>
                                        </template>
                                        <li class="list-group-item text-muted text-center" x-show="search.length > 0 && results.length === 0 && !isSearching">ไม่พบข้อมูล</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก (Cancel)</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" :disabled="isSubmitting">
                            <span x-show="!isSubmitting"><i class="bi bi-download me-2"></i>สร้างและดาวน์โหลด (Generate PDF)</span>
                            <span x-show="isSubmitting"><span class="spinner-border spinner-border-sm me-2"></span>กำลังสร้าง...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {

        Alpine.data('quickGenerateModal', () => ({
            isSubmitting: false,
            form: {
                template_id: '',
                target_employer_id: ''
            },
            handleSubmit(e) {
                if(!this.form.template_id) {
                    alert('กรุณาเลือกเทมเพลต (Please select a template)');
                    e.preventDefault();
                    return;
                }

                // Optional Warning Message
                if(!this.form.target_employer_id && !document.querySelector('input[name="employee_id"]').value) {
                    if(!confirm('คุณกำลังจะสร้าง PDF โดยไม่ระบุทั้ง "นายจ้าง" และ "ลูกจ้าง" ข้อมูลใน PDF จะว่างเปล่าในส่วนนั้น ยืนยันที่จะทำต่อหรือไม่?')) {
                        e.preventDefault();
                        return;
                    }
                }

                this.isSubmitting = true;
                // Automatically restore button after short delay because browser will trigger download
                setTimeout(() => {
                    this.isSubmitting = false;
                }, 3000);
            }
        }));

        Alpine.data('searchableDropdown', (config) => ({
            open: false,
            search: '',
            value: config.value,
            options: config.options,
            selectedName: '',

            get filteredOptions() {
                if (this.search === '') {
                    return this.options;
                }
                const term = this.search.toLowerCase();
                return this.options.filter(opt => opt.search_str.includes(term));
            },

            selectOption(opt) {
                this.value = opt.id;
                this.selectedName = opt.name_th;
                this.search = opt.name_th;
                this.open = false;
            },

            syncSearch() {
                if (this.value === '') {
                    this.search = 'เว้นว่าง / ไม่มีข้อมูล';
                } else {
                    const opt = this.options.find(o => o.id === this.value);
                    if(opt) this.search = opt.name_th;
                }
            },

            init() {
                this.syncSearch();
            }
        }));

        Alpine.data('employeeSearchDropdown', () => ({
            open: false,
            search: '',
            selectedId: '',
            selectedName: '',
            results: [],
            isSearching: false,

            async fetchEmployees() {
                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }

                this.isSearching = true;
                try {
                    // The EmployeeController search expects '?q='
                    const res = await fetch(`{{ route('employees.search') }}?q=${encodeURIComponent(this.search)}`);
                    if(res.ok) {
                        const data = await res.json();
                        // Select2 format returns { results: [{id, text, employee: {}}] }
                        if (data.results) {
                            this.results = data.results.map(item => item.employee || item);
                        } else {
                            this.results = data.data || data;
                        }
                    }
                } catch(e) {
                    console.error('Failed to search employees', e);
                } finally {
                    this.isSearching = false;
                    this.open = true;
                }
            },

            selectOption(emp) {
                this.selectedId = emp.id;
                this.selectedName = (emp.employeeNameTh || '') + ' ' + (emp.employeeNameEn || '');
                this.search = this.selectedName;
                this.open = false;
            },

            selectEmpty() {
                this.selectedId = '';
                this.selectedName = '';
                this.search = 'เว้นว่าง / ไม่มีลูกจ้าง (Empty)';
                this.open = false;
            },

            clearSelection() {
                this.selectedId = '';
                this.selectedName = '';
                this.search = '';
                this.results = [];
            }
        }));

        Alpine.data('witnessManager', () => ({
            witnesses: [],
            isLoading: false,
            isSaving: false,
            isEditing: false,
            modalInstance: null,
            form: {
                id: null,
                name_th: '',
                name_en: '',
                signature_file: null,
                signature_preview: null
            },

            init() {
                this.$nextTick(() => {
                    this.modalInstance = new bootstrap.Modal(document.getElementById('witnessModal'));
                });
            },

            openModal() {
                this.loadWitnesses();
                this.resetForm();
                this.modalInstance.show();
            },

            async loadWitnesses() {
                this.isLoading = true;
                try {
                    const res = await fetch('{{ route("admin.pdf-templates.witnesses.index") }}');
                    if(res.ok) {
                        this.witnesses = await res.json();
                    }
                } catch(e) {
                    console.error('Failed to load witnesses', e);
                } finally {
                    this.isLoading = false;
                }
            },

            handleFileChange(e) {
                const file = e.target.files[0];
                this.form.signature_file = file;
                if (file) {
                    this.form.signature_preview = URL.createObjectURL(file);
                } else {
                    this.form.signature_preview = null;
                }
            },

            resetForm() {
                this.isEditing = false;
                this.form = { id: null, name_th: '', name_en: '', signature_file: null, signature_preview: null };
                if(this.$refs.signatureFile) this.$refs.signatureFile.value = '';
            },

            editWitness(witness) {
                this.isEditing = true;
                this.form.id = witness.id;
                this.form.name_th = witness.name_th;
                this.form.name_en = witness.name_en;
                this.form.signature_file = null;
                this.form.signature_preview = witness.signature_path ? '/storage/' + witness.signature_path : null;
                if(this.$refs.signatureFile) this.$refs.signatureFile.value = '';

                // Scroll to top of modal body smoothly
                const modalBody = document.querySelector('#witnessModal .modal-body');
                if(modalBody) modalBody.scrollTo({top: 0, behavior: 'smooth'});
            },

            async saveWitness() {
                this.isSaving = true;

                let formData = new FormData();
                formData.append('name_th', this.form.name_th);
                formData.append('name_en', this.form.name_en);
                if (this.form.signature_file) {
                    formData.append('signature_file', this.form.signature_file);
                }

                let url = '{{ route("admin.pdf-templates.witnesses.store") }}';
                if (this.isEditing) {
                    url = '/admin/pdf-templates/witnesses/' + this.form.id;
                    // We need to use POST method spoofing or just POST for FormData
                }

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        showToast(data.message, 'success');
                        this.resetForm();
                        this.loadWitnesses();
                    } else {
                        showToast(data.message || 'Error saving witness', 'danger');
                    }
                } catch(e) {
                    console.error(e);
                    showToast('Network error while saving', 'danger');
                } finally {
                    this.isSaving = false;
                }
            },

            async deleteWitness(id) {
                if(!confirm('Are you sure you want to delete this witness?')) return;

                try {
                    const res = await fetch('/admin/pdf-templates/witnesses/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        showToast(data.message, 'success');
                        this.loadWitnesses();
                    } else {
                        showToast(data.message || 'Error deleting witness', 'danger');
                    }
                } catch(e) {
                    console.error(e);
                    showToast('Network error while deleting', 'danger');
                }
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-submit-swal').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const title = this.dataset.swalTitle || 'Are you sure?';
                const text = this.dataset.swalText || '';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
@endsection
