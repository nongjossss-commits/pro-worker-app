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
            <button type="submit" form="pdfTemplateExportForm" id="pdfTemplateExportBtn" class="btn btn-outline-secondary" disabled
                    title="{{ __('Tick templates in the list below first') }}">
                <i class="bi bi-download me-2"></i>{{ __('Export Selected') }}
            </button>
            @endcan
            @can('create-pdf-templates')
            <button type="button" @click="openModal()" class="btn btn-outline-info">
                <i class="bi bi-people me-2"></i>ตั้งค่ารายชื่อพยาน
            </button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importTemplateModal">
                <i class="bi bi-upload me-2"></i>{{ __('Import') }}
            </button>
            <a href="{{ route('admin.pdf-templates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Create New Template
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="alert alert-danger">{{ session('danger') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Export/Import — settings-portability feature: lets an admin download
         the selected templates' field positions (+ background PDF) as one
         JSON file to hand off/re-upload into another install of this
         program, instead of rebuilding the same layout from scratch there.
         The checkboxes below are `form="pdfTemplateExportForm"`-linked to
         this GET form rather than nested inside it, since they live inside
         the results table further down the page. --}}
    @can('view-pdf-templates')
    <form id="pdfTemplateExportForm" method="GET" action="{{ route('admin.pdf-templates.export') }}"></form>
    @endcan

    {{-- Filter Section --}}
    @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff') || auth()->user()->hasRole('caretaker'))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded">
            <form action="{{ route('admin.pdf-templates.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">ค้นหาชื่อ Template</label>
                    <input type="text" name="name" class="form-control" placeholder="พิมพ์ชื่อ template..." value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
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
                            @can('view-pdf-templates')
                            <th class="ps-4" style="width: 1%;">
                                <input type="checkbox" class="form-check-input" id="pdfTemplateSelectAll" title="{{ __('Select all') }}">
                            </th>
                            @endcan
                            <th class="{{ auth()->user()->can('view-pdf-templates') ? '' : 'ps-4' }}">Template Name</th>
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
                            @can('view-pdf-templates')
                            <td class="ps-4">
                                <input type="checkbox" class="form-check-input pdf-template-checkbox" name="ids[]" value="{{ $template->id }}" form="pdfTemplateExportForm">
                            </td>
                            @endcan
                            <td>
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
                            <td colspan="{{ auth()->user()->can('view-pdf-templates') ? 7 : 6 }}" class="text-center py-5 text-muted">
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
                        {{-- Searchable template picker — shows ALL templates (not the
                             page-limited slice) so operators don't have to paginate
                             the underlying list first. --}}
                        <div class="position-relative" @click.outside="tplOpen = false">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                       class="form-control border-start-0 ps-0"
                                       :placeholder="selectedId ? selectedLabel('template') : 'พิมพ์ค้นหา template หรือคลิกเพื่อดูรายการ...'"
                                       x-model="tplSearch"
                                       @focus="tplOpen = true"
                                       @keydown.escape="tplOpen = false"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button"
                                        @click="tplOpen = !tplOpen; tplSearch = ''"
                                        title="ล้าง/เปิดรายการ">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="form-text text-primary fw-bold mt-1" x-show="selectedId" style="display: none;">
                                <i class="bi bi-check-circle-fill me-1"></i> เลือกแล้ว: <span x-text="selectedLabel('template')"></span>
                            </div>
                            <div class="card position-absolute w-100 shadow mt-1 border-0"
                                 style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;"
                                 x-show="tplOpen" x-transition>
                                <ul class="list-group list-group-flush">
                                    <template x-for="t in filteredTemplates" :key="t.id">
                                        <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            style="cursor: pointer;"
                                            @click="selectedId = t.id; tplOpen = false; tplSearch = ''; onTemplateChange()">
                                            <div>
                                                <div class="fw-bold" x-text="t.name"></div>
                                                <div class="small">
                                                    <span class="badge" :class="t.type === 'global' ? 'bg-success' : 'bg-info'"
                                                          x-text="t.type === 'global' ? 'Global' : (t.employer_name || 'Employer')"></span>
                                                </div>
                                            </div>
                                            <i class="bi bi-check2 text-primary" x-show="selectedId == t.id"></i>
                                        </li>
                                    </template>
                                    <li class="list-group-item text-muted text-center" x-show="filteredTemplates.length === 0">
                                        ไม่พบ template ที่ค้นหา
                                    </li>
                                </ul>
                            </div>
                        </div>
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

                        {{-- Info: has employee fields — pick one below, or leave blank --}}
                        <div class="alert alert-warning py-2" x-show="analysis.employee.count > 0" style="display: none;">
                            <div class="d-flex">
                                <div class="me-2"><i class="bi bi-person-fill fs-5"></i></div>
                                <div class="flex-grow-1 small">
                                    <div class="fw-bold mb-1">Template นี้มีช่องข้อมูลลูกจ้าง</div>
                                    <div class="mb-2">เลือกลูกจ้างด้านล่างเพื่อเติมข้อมูลจริง หรือเว้นว่างไว้แล้วพิมพ์ได้เลย (ช่องลูกจ้างจะว่างเปล่าในเอกสาร)</div>
                                    <div class="small text-muted mb-1">ช่องลูกจ้างที่จะเติม:</div>
                                    <ul class="small mb-0 ps-3" style="max-height: 120px; overflow-y: auto;">
                                        <template x-for="key in analysis.employee.keys" :key="key">
                                            <li x-text="key"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Target selectors — all searchable so the operator
                             doesn't have to scroll a long native select. --}}
                        <div>
                            {{-- Employee (AJAX search — too many to preload) --}}
                            <div class="mb-3" x-show="analysis.employee.count > 0" style="display: none;">
                                <label class="form-label">เลือกลูกจ้าง (Target Employee)</label>
                                <div class="position-relative" @click.outside="eeOpen = false">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text"
                                               class="form-control border-start-0 ps-0"
                                               :placeholder="targetEmployeeId ? eeSelectedLabel : 'พิมพ์ค้นหาลูกจ้าง (ชื่อ/พาสปอร์ต)...'"
                                               x-model="eeSearch"
                                               @input="searchEmployees()"
                                               @focus="eeOpen = true"
                                               @keydown.escape="eeOpen = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" @click="eeOpen = !eeOpen; eeSearch = ''">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="card position-absolute w-100 shadow mt-1 border-0"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none;"
                                         x-show="eeOpen" x-transition>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item list-group-item-action" style="cursor:pointer;" @click="clearEmployee()">
                                                <em class="text-muted">— เว้นว่าง —</em>
                                            </li>
                                            <li class="list-group-item text-muted text-center" x-show="eeLoading">
                                                <span class="spinner-border spinner-border-sm me-1"></span> กำลังค้นหา...
                                            </li>
                                            <template x-for="opt in eeResults" :key="opt.id">
                                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    style="cursor:pointer;" @click="selectEmployee(opt)">
                                                    <div>
                                                        <div class="fw-bold" x-text="opt.employeeNameTh"></div>
                                                        <div class="small text-muted" x-text="opt.text"></div>
                                                    </div>
                                                    <i class="bi bi-check2 text-primary" x-show="targetEmployeeId == opt.id"></i>
                                                </li>
                                            </template>
                                            <li class="list-group-item text-muted text-center" x-show="!eeLoading && eeSearch.trim().length >= 2 && eeResults.length === 0">
                                                ไม่พบลูกจ้าง
                                            </li>
                                            <li class="list-group-item text-muted text-center" x-show="eeSearch.trim().length < 2 && !eeLoading">
                                                พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-text text-primary fw-bold mt-1" x-show="targetEmployeeId">
                                    <i class="bi bi-check-circle-fill me-1"></i> เลือกแล้ว: <span x-text="eeSelectedLabel"></span>
                                </div>
                            </div>

                            {{-- Employer --}}
                            <div class="mb-3" x-show="analysis.employer.count > 0" style="display: none;">
                                <label class="form-label">2. เลือกนายจ้าง (Target Employer)</label>
                                <div class="position-relative" @click.outside="empOpen = false">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text"
                                               class="form-control border-start-0 ps-0"
                                               :placeholder="targetEmployerId ? selectedLabel('employer') : 'พิมพ์ค้นหานายจ้าง...'"
                                               x-model="empSearch"
                                               @focus="empOpen = true"
                                               @keydown.escape="empOpen = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button"
                                                @click="empOpen = !empOpen; empSearch = ''">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="card position-absolute w-100 shadow mt-1 border-0"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none;"
                                         x-show="empOpen" x-transition>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item list-group-item-action" style="cursor:pointer;"
                                                @click="targetEmployerId = ''; empOpen = false; empSearch = ''">
                                                <em class="text-muted">— เว้นว่าง —</em>
                                            </li>
                                            <template x-for="opt in filteredEmployers" :key="opt.id">
                                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    style="cursor:pointer;"
                                                    @click="targetEmployerId = opt.id; empOpen = false; empSearch = ''">
                                                    <div>
                                                        <div class="fw-bold" x-text="opt.name_th"></div>
                                                        <div class="small text-muted" x-text="opt.name_en"></div>
                                                    </div>
                                                    <i class="bi bi-check2 text-primary" x-show="targetEmployerId == opt.id"></i>
                                                </li>
                                            </template>
                                            <li class="list-group-item text-muted text-center" x-show="filteredEmployers.length === 0">
                                                ไม่พบนายจ้าง
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {{-- Delegate --}}
                            <div class="mb-3" x-show="analysis.delegate.count > 0" style="display: none;">
                                <label class="form-label">เลือกผู้รับมอบอำนาจ (Delegate)</label>
                                <div class="position-relative" @click.outside="delOpen = false">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text"
                                               class="form-control border-start-0 ps-0"
                                               :placeholder="targetDelegateId ? selectedLabel('delegate') : 'พิมพ์ค้นหาผู้รับมอบอำนาจ...'"
                                               x-model="delSearch"
                                               @focus="delOpen = true"
                                               @keydown.escape="delOpen = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button"
                                                @click="delOpen = !delOpen; delSearch = ''">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="card position-absolute w-100 shadow mt-1 border-0"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none;"
                                         x-show="delOpen" x-transition>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item list-group-item-action" style="cursor:pointer;"
                                                @click="targetDelegateId = ''; delOpen = false; delSearch = ''">
                                                <em class="text-muted">— เว้นว่าง —</em>
                                            </li>
                                            <template x-for="opt in filteredDelegates" :key="opt.id">
                                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    style="cursor:pointer;"
                                                    @click="targetDelegateId = opt.id; delOpen = false; delSearch = ''">
                                                    <div>
                                                        <div class="fw-bold" x-text="opt.name_th"></div>
                                                        <div class="small text-muted" x-text="opt.name_en"></div>
                                                    </div>
                                                    <i class="bi bi-check2 text-primary" x-show="targetDelegateId == opt.id"></i>
                                                </li>
                                            </template>
                                            <li class="list-group-item text-muted text-center" x-show="filteredDelegates.length === 0">
                                                ไม่พบผู้รับมอบอำนาจ
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {{-- Importer --}}
                            <div class="mb-3" x-show="analysis.importer.count > 0" style="display: none;">
                                <label class="form-label">เลือกบริษัทนำเข้า (Importer)</label>
                                <div class="position-relative" @click.outside="impOpen = false">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text"
                                               class="form-control border-start-0 ps-0"
                                               :placeholder="targetImporterId ? selectedLabel('importer') : 'พิมพ์ค้นหาบริษัทนำเข้า...'"
                                               x-model="impSearch"
                                               @focus="impOpen = true"
                                               @keydown.escape="impOpen = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button"
                                                @click="impOpen = !impOpen; impSearch = ''">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="card position-absolute w-100 shadow mt-1 border-0"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto; display: none;"
                                         x-show="impOpen" x-transition>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item list-group-item-action" style="cursor:pointer;"
                                                @click="targetImporterId = ''; impOpen = false; impSearch = ''">
                                                <em class="text-muted">— เว้นว่าง —</em>
                                            </li>
                                            <template x-for="opt in filteredImporters" :key="opt.id">
                                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    style="cursor:pointer;"
                                                    @click="targetImporterId = opt.id; impOpen = false; impSearch = ''">
                                                    <div>
                                                        <div class="fw-bold" x-text="opt.name_th"></div>
                                                        <div class="small text-muted" x-text="opt.name_en"></div>
                                                    </div>
                                                    <i class="bi bi-check2 text-primary" x-show="targetImporterId == opt.id"></i>
                                                </li>
                                            </template>
                                            <li class="list-group-item text-muted text-center" x-show="filteredImporters.length === 0">
                                                ไม่พบบริษัทนำเข้า
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> เลือกข้อมูลที่จะเติม (ถ้ามี) แล้วกดพิมพ์ได้เลย — ช่องที่เว้นว่างไว้จะว่างเปล่าในเอกสาร
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
                            x-show="selectedId" style="display: none;">
                        <i class="bi bi-download me-1"></i> ดาวน์โหลด PDF
                    </button>
                    <button type="button"
                            class="btn btn-success"
                            :disabled="!canPrint"
                            @click="doPrint('preview')"
                            x-show="selectedId" style="display: none;">
                        <i class="bi bi-printer me-1"></i> พิมพ์ / Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Template Modal — uploads a JSON file produced by "Export
         Selected" above (from this install or another one running this
         same program) and recreates the templates it contains. --}}
    @can('create-pdf-templates')
    <div class="modal fade" id="importTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.pdf-templates.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>{{ __('Import Templates') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">{{ __('Export file (.json)') }}</label>
                    <input type="file" name="file" accept=".json" class="form-control" required>
                    <div class="form-text">
                        {{ __('This creates new templates — it never overwrites an existing one. A template whose name is already used here is skipped. Any image fields (logo/watermark) need to be re-uploaded manually afterward in the builder.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> {{ __('Import') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

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
            employers: (@json($quickPrintEmployers ?? collect())).map(e => ({
                id: e.id,
                name_th: e.employerNameTh || '',
                name_en: e.employerNameEn || '',
                _search: ((e.employerNameTh || '') + ' ' + (e.employerNameEn || '')).toLowerCase(),
            })),
            delegates: (@json($quickPrintDelegates ?? collect())).map(d => ({
                id: d.id,
                name_th: d.delegateNameTh || '',
                name_en: d.delegateNameEn || '',
                _search: ((d.delegateNameTh || '') + ' ' + (d.delegateNameEn || '')).toLowerCase(),
            })),
            importers: (@json($quickPrintImporters ?? collect())).map(i => ({
                id: i.id,
                name_th: i.importerNameTh || '',
                name_en: i.importerNameEn || '',
                _search: ((i.importerNameTh || '') + ' ' + (i.importerNameEn || '')).toLowerCase(),
            })),
            selectedId: '',
            targetEmployerId: '',
            targetDelegateId: '',
            targetImporterId: '',
            targetEmployeeId: '',
            // Searchable dropdown state — one search string + one open flag per selector.
            tplSearch: '', tplOpen: false,
            empSearch: '', empOpen: false,
            delSearch: '', delOpen: false,
            impSearch: '', impOpen: false,
            // Employee picker is AJAX-backed (too many employees to preload
            // like employer/delegate/importer above), so it needs its own
            // result list + loading flag + a remembered label instead of
            // looking the selection up in a preloaded array.
            eeSearch: '', eeOpen: false, eeResults: [], eeLoading: false, eeSelectedLabel: '', eeSearchTimer: null,
            analysis: {
                employee: { count: 0, keys: [] },
                employer: { count: 0, keys: [] },
                delegate: { count: 0, keys: [] },
                importer: { count: 0, keys: [] },
                witness: { count: 0, keys: [] },
                static: { count: 0, keys: [] },
            },

            get canPrint() {
                return !!this.selectedId;
            },

            // Filtered lists — case-insensitive contains match against the
            // pre-computed lowercase _search string, or full list when the
            // search box is empty.
            get filteredTemplates() {
                const term = (this.tplSearch || '').toLowerCase().trim();
                if (!term) return this.templates;
                return this.templates.filter(t => {
                    const label = (t.name || '') + ' ' + (t.employer_name || '') + ' ' + (t.type || '');
                    return label.toLowerCase().includes(term);
                });
            },
            get filteredEmployers() {
                const term = (this.empSearch || '').toLowerCase().trim();
                if (!term) return this.employers;
                return this.employers.filter(o => o._search.includes(term));
            },
            get filteredDelegates() {
                const term = (this.delSearch || '').toLowerCase().trim();
                if (!term) return this.delegates;
                return this.delegates.filter(o => o._search.includes(term));
            },
            get filteredImporters() {
                const term = (this.impSearch || '').toLowerCase().trim();
                if (!term) return this.importers;
                return this.importers.filter(o => o._search.includes(term));
            },

            // Resolve the display label for the currently selected id in each dropdown.
            selectedLabel(kind) {
                if (kind === 'template') {
                    const t = this.templates.find(x => x.id == this.selectedId);
                    if (!t) return '';
                    return t.name + ' (' + (t.type === 'global' ? 'Global' : (t.employer_name || 'Employer')) + ')';
                }
                if (kind === 'employer') {
                    const o = this.employers.find(x => x.id == this.targetEmployerId);
                    return o ? (o.name_th + (o.name_en ? ' (' + o.name_en + ')' : '')) : '';
                }
                if (kind === 'delegate') {
                    const o = this.delegates.find(x => x.id == this.targetDelegateId);
                    return o ? (o.name_th + (o.name_en ? ' (' + o.name_en + ')' : '')) : '';
                }
                if (kind === 'importer') {
                    const o = this.importers.find(x => x.id == this.targetImporterId);
                    return o ? (o.name_th + (o.name_en ? ' (' + o.name_en + ')' : '')) : '';
                }
                return '';
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
                this.targetEmployeeId = '';
                this.eeSelectedLabel = '';
                this.eeResults = [];
                this.analyzeFields();
            },

            // Debounced AJAX search against the existing employees.search
            // endpoint (used elsewhere via Select2 — here it's plain fetch
            // to match this modal's hand-rolled Alpine dropdown styling).
            searchEmployees() {
                clearTimeout(this.eeSearchTimer);
                const term = this.eeSearch.trim();
                if (term.length < 2) {
                    this.eeResults = [];
                    return;
                }
                this.eeSearchTimer = setTimeout(() => {
                    this.eeLoading = true;
                    fetch('{{ route('employees.search') }}?q=' + encodeURIComponent(term))
                        .then(res => res.json())
                        .then(data => { this.eeResults = data; })
                        .catch(() => { this.eeResults = []; })
                        .finally(() => { this.eeLoading = false; });
                }, 300);
            },

            selectEmployee(emp) {
                this.targetEmployeeId = emp.id;
                this.eeSelectedLabel = emp.text || emp.employeeNameEn || emp.employeeNameTh || ('ID:' + emp.id);
                this.eeOpen = false;
                this.eeSearch = '';
                this.eeResults = [];
            },

            clearEmployee() {
                this.targetEmployeeId = '';
                this.eeSelectedLabel = '';
                this.eeOpen = false;
                this.eeSearch = '';
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
                if (this.targetEmployeeId) params.append('target_employee_id', this.targetEmployeeId);
                params.append('mode', mode);
                const url = '/admin/pdf-templates/' + this.selectedId + '/quick-print?' + params.toString();
                window.open(url, '_blank');
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Export Selected — enable the button only once at least one row is
        // ticked, and keep the header "select all" checkbox in sync both ways.
        const exportBtn = document.getElementById('pdfTemplateExportBtn');
        const selectAll = document.getElementById('pdfTemplateSelectAll');
        const rowCheckboxes = document.querySelectorAll('.pdf-template-checkbox');

        function refreshExportBtn() {
            if (!exportBtn) return;
            exportBtn.disabled = ![...rowCheckboxes].some(cb => cb.checked);
        }

        rowCheckboxes.forEach(cb => cb.addEventListener('change', refreshExportBtn));

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(cb => { cb.checked = selectAll.checked; });
                refreshExportBtn();
            });
        }

        refreshExportBtn();
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
