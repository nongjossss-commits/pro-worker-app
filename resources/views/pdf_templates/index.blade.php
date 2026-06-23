@extends('layouts.app')

@section('content')
<x-help-button manual="pdf_templates" title="{{ __('PDF Templates') }}" />
<div class="container-fluid py-4" x-data="witnessManager()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">PDF Templates</h2>
        <div class="d-flex gap-2">
            @can('view-pdf-templates')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#quickPrintModal">
                <i class="bi bi-printer me-2"></i>พิมพ์เอกสาร
            </button>
            @endcan
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

    <!-- Quick Print Modal -->
    <div class="modal fade" id="quickPrintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" x-data="quickPrintManager()">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-printer me-2"></i>พิมพ์เอกสาร (Quick Print)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">1. เลือก Template <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="selectedId" @change="onTemplateChange()">
                            <option value="">-- เลือก Template --</option>
                            <template x-for="t in templates" :key="t.id">
                                <option :value="t.id" x-text="t.name + ' (' + (t.type === 'global' ? 'Global' : (t.employer_name || 'Employer')) + ')'"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Field analysis --}}
                    <div x-show="selectedId" x-transition style="display: none;">
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-body py-2 px-3">
                                <div class="small fw-bold text-muted mb-1">
                                    <i class="bi bi-diagram-3 me-1"></i> ข้อมูลที่ template นี้ต้องใช้
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-warning text-dark" x-show="analysis.employee.count > 0" style="display: none;">
                                        <i class="bi bi-person-fill me-1"></i> ลูกจ้าง: <span x-text="analysis.employee.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-primary" x-show="analysis.employer.count > 0" style="display: none;">
                                        <i class="bi bi-building me-1"></i> นายจ้าง: <span x-text="analysis.employer.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-info" x-show="analysis.delegate.count > 0" style="display: none;">
                                        <i class="bi bi-person-badge me-1"></i> ผู้รับมอบอำนาจ: <span x-text="analysis.delegate.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-success" x-show="analysis.importer.count > 0" style="display: none;">
                                        <i class="bi bi-box-seam me-1"></i> บริษัทนำเข้า: <span x-text="analysis.importer.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-secondary" x-show="analysis.witness.count > 0" style="display: none;">
                                        <i class="bi bi-people me-1"></i> พยาน: <span x-text="analysis.witness.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-light text-dark border" x-show="analysis.static.count > 0" style="display: none;">
                                        <i class="bi bi-type me-1"></i> ข้อความคงที่: <span x-text="analysis.static.count"></span> ช่อง
                                    </span>
                                    <span class="badge bg-light text-dark border" x-show="!analysis.employee.count && !analysis.employer.count && !analysis.delegate.count && !analysis.importer.count && !analysis.witness.count && !analysis.static.count">
                                        — ไม่มีฟิลด์ตั้งค่าใน template นี้ —
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Warning: needs employee data --}}
                        <div class="alert alert-warning py-2" x-show="analysis.employee.count > 0" style="display: none;">
                            <div class="d-flex">
                                <div class="me-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                                <div class="flex-grow-1 small">
                                    <div class="fw-bold mb-1">Template นี้มีช่องข้อมูลลูกจ้าง</div>
                                    <div class="mb-2">ไม่สามารถพิมพ์เปล่าได้ — ต้องเลือกลูกจ้างก่อน เพื่อให้ระบบเติมข้อมูลจริง</div>
                                    <div class="small text-muted mb-1">ช่องลูกจ้างที่จะเติม:</div>
                                    <ul class="small mb-0 ps-3" style="max-height: 120px; overflow-y: auto;">
                                        <template x-for="key in analysis.employee.keys" :key="key">
                                            <li x-text="key"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                            <div class="text-end mt-2">
                                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-people me-1"></i> ไปเลือกลูกจ้าง
                                </a>
                            </div>
                        </div>

                        {{-- Target selectors (only when no employee data needed) --}}
                        <div x-show="!analysis.employee.count" style="display: none;">
                            <div class="mb-3" x-show="analysis.employer.count > 0" style="display: none;">
                                <label class="form-label">2. เลือกนายจ้าง (Target Employer)</label>
                                <select class="form-select" x-model="targetEmployerId">
                                    <option value="">-- เว้นว่าง --</option>
                                    @foreach($quickPrintEmployers ?? [] as $e)
                                        <option value="{{ $e->id }}">{{ $e->employerNameTh }} ({{ $e->employerNameEn }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3" x-show="analysis.delegate.count > 0" style="display: none;">
                                <label class="form-label">เลือกผู้รับมอบอำนาจ (Delegate)</label>
                                <select class="form-select" x-model="targetDelegateId">
                                    <option value="">-- เว้นว่าง --</option>
                                    @foreach($quickPrintDelegates ?? [] as $d)
                                        <option value="{{ $d->id }}">{{ $d->delegateNameTh }} ({{ $d->delegateNameEn }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3" x-show="analysis.importer.count > 0" style="display: none;">
                                <label class="form-label">เลือกบริษัทนำเข้า (Importer)</label>
                                <select class="form-select" x-model="targetImporterId">
                                    <option value="">-- เว้นว่าง --</option>
                                    @foreach($quickPrintImporters ?? [] as $i)
                                        <option value="{{ $i->id }}">{{ $i->importerNameTh }} ({{ $i->importerNameEn }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Template นี้ไม่ต้องใช้ข้อมูลลูกจ้าง สามารถเลือกข้อมูลที่จะเติม (ถ้ามี) แล้วกดพิมพ์ได้เลย
                            </div>
                        </div>
                    </div>

                    {{-- Initial hint --}}
                    <div class="alert alert-secondary py-2 small mb-0" x-show="!selectedId">
                        <i class="bi bi-info-circle me-1"></i> เลือก template เพื่อดูว่าต้องใช้ข้อมูลอะไรบ้าง
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button"
                            class="btn btn-outline-primary"
                            :disabled="!canPrint"
                            @click="doPrint('download')"
                            x-show="selectedId && !analysis.employee.count" style="display: none;">
                        <i class="bi bi-download me-1"></i> ดาวน์โหลด PDF
                    </button>
                    <button type="button"
                            class="btn btn-success"
                            :disabled="!canPrint"
                            @click="doPrint('preview')"
                            x-show="selectedId && !analysis.employee.count" style="display: none;">
                        <i class="bi bi-printer me-1"></i> พิมพ์ / Preview
                    </button>
                </div>
            </div>
        </div>
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

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
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

    document.addEventListener('alpine:init', () => {
        Alpine.data('quickPrintManager', () => ({
            templates: @json($quickPrintTemplates ?? collect()),
            selectedId: '',
            targetEmployerId: '',
            targetDelegateId: '',
            targetImporterId: '',
            analysis: {
                employee: { count: 0, keys: [] },
                employer: { count: 0, keys: [] },
                delegate: { count: 0, keys: [] },
                importer: { count: 0, keys: [] },
                witness: { count: 0, keys: [] },
                static: { count: 0, keys: [] },
            },

            get canPrint() {
                return this.selectedId && !this.analysis.employee.count;
            },

            classifyKey(key) {
                if (!key) return 'employee';
                if (key.startsWith('employer.')) return 'employer';
                if (key.startsWith('importer.')) return 'importer';
                if (key.startsWith('delegate.')) return 'delegate';
                if (key.startsWith('witness_')) return 'witness';
                return 'employee';
            },

            classifySignatureGroup(group) {
                if (!group) return 'employee';
                if (group === 'employer' || group === 'employer_2' || group === 'employer_stamp') return 'employer';
                if (group === 'delegate') return 'delegate';
                if (group === 'importer_1' || group === 'importer_2' || group === 'importer_stamp') return 'importer';
                if (group.startsWith('witness_')) return 'witness';
                return 'employee';
            },

            onTemplateChange() {
                this.targetEmployerId = '';
                this.targetDelegateId = '';
                this.targetImporterId = '';
                this.analyzeFields();
            },

            analyzeFields() {
                const reset = () => ({
                    employee: { count: 0, keys: [] },
                    employer: { count: 0, keys: [] },
                    delegate: { count: 0, keys: [] },
                    importer: { count: 0, keys: [] },
                    witness: { count: 0, keys: [] },
                    static: { count: 0, keys: [] },
                });
                this.analysis = reset();
                if (!this.selectedId) return;

                const t = this.templates.find(x => x.id == this.selectedId);
                if (!t || !Array.isArray(t.field_mapping)) return;

                const seen = { employee: new Set(), employer: new Set(), delegate: new Set(), importer: new Set(), witness: new Set(), static: new Set() };

                t.field_mapping.forEach(item => {
                    if (!item || !item.type) return;
                    if (item.type === 'static') {
                        const label = item.text ? ('"' + item.text.substring(0, 30) + '"') : 'static';
                        if (!seen.static.has(label)) {
                            seen.static.add(label);
                            this.analysis.static.keys.push(label);
                            this.analysis.static.count++;
                        }
                        return;
                    }
                    if (item.type === 'db' && item.key) {
                        const group = this.classifyKey(item.key);
                        if (!seen[group].has(item.key)) {
                            seen[group].add(item.key);
                            this.analysis[group].keys.push(item.key);
                            this.analysis[group].count++;
                        }
                        return;
                    }
                    if (item.type === 'signature' || item.type === 'stamp') {
                        const group = this.classifySignatureGroup(item.signatureGroup || (item.type === 'stamp' ? 'employer_stamp' : 'employee'));
                        const label = '[' + (item.signatureGroup || item.type) + ']';
                        if (!seen[group].has(label)) {
                            seen[group].add(label);
                            this.analysis[group].keys.push(label);
                            this.analysis[group].count++;
                        }
                    }
                });
            },

            doPrint(mode) {
                if (!this.canPrint) return;
                const params = new URLSearchParams();
                if (this.targetEmployerId) params.append('target_employer_id', this.targetEmployerId);
                if (this.targetDelegateId) params.append('target_delegate_id', this.targetDelegateId);
                if (this.targetImporterId) params.append('target_importer_id', this.targetImporterId);
                params.append('mode', mode);
                const url = '/admin/pdf-templates/' + this.selectedId + '/quick-print?' + params.toString();
                window.open(url, '_blank');
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
