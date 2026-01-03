@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Generate Automated PDF</h5>
                </div>
                <div class="card-body p-4" x-data="pdfGenerator()">

                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        You have selected <strong>{{ count($employees) }}</strong> employees.
                    </div>

                    <form action="{{ route('admin.pdf-templates.generate.process') }}" method="POST" id="generateForm">
                        @csrf

                        <!-- Hidden Employee IDs -->
                        @foreach($employees as $id)
                            <input type="hidden" name="employees[]" value="{{ $id }}">
                        @endforeach

                        {{-- Section 1: Select Employer (For Filtering Templates) --}}
                        @if(isset($employers) && $employers->count() > 0)
                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Select Employer (Owner of Template)</label>

                            @php
                                // Reusing the same structure as the index filter
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

                            <div x-data="employerSelector()" @click.outside="open = false">
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text"
                                               class="form-control"
                                               placeholder="Type to search employer or select Global..."
                                               x-model="search"
                                               @focus="open = true"
                                               @keydown.escape="open = false"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="open = !open"></button>
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
                                                    <i class="bi bi-check2 text-primary" x-show="selectedEmployerId == opt.id"></i>
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
                        @endif

                        <!-- Template Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Select Template</label>
                            <div class="input-group">
                                <select name="template_id" class="form-select" required x-model="selectedTemplateId" :disabled="isLoadingTemplates">
                                    <option value="">-- Choose Template --</option>
                                    <template x-for="t in templates" :key="t.id">
                                        <option :value="t.id" x-text="t.name + (t.type === 'global' ? ' (Global)' : '')"></option>
                                    </template>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" @click="fetchTemplates()" title="Refresh">
                                    <i class="bi" :class="isLoadingTemplates ? 'bi-hourglass-split' : 'bi-arrow-clockwise'"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted" x-show="isLoadingTemplates">Loading templates...</div>
                        </div>

                        <!-- Output Option -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">3. Output Destination</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputDownload" value="download" x-model="outputType">
                                    <label class="form-check-label" for="outputDownload">
                                        Download immediately (Zip/PDF)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="output_type" id="outputSlot" value="save_to_slot" x-model="outputType">
                                    <label class="form-check-label" for="outputSlot">
                                        Save to Employee Record (Attachment Slot)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Slot Name Configuration -->
                        <div class="mb-4 p-3 bg-light rounded border" x-show="outputType === 'save_to_slot'" x-transition>
                            <label class="form-label fw-bold">Select Attachment Slot</label>
                            <p class="text-sm text-gray-500 mb-2">
                                Choose where to attach this document on the record.
                                Note: This will overwrite any existing file in the selected slot.
                            </p>

                            <select name="slot_name" class="form-select" :required="outputType === 'save_to_slot'">
                                <option value="">-- Select Slot --</option>
                                <optgroup label="Employee Documents (เอกสารลูกจ้าง)">
                                    {{-- Adjusted mapping based on user request and DB schema --}}
                                    {{-- Other Doc 1 starts at employee_doc_9 --}}
                                    @for($i = 1; $i <= 10; $i++)
                                        @php $dbIndex = $i + 8; @endphp
                                        <option value="employee_doc_{{ $dbIndex }}">Employee Other Document {{ $i }} (เอกสารอื่นๆ {{ $i }})</option>
                                    @endfor
                                </optgroup>
                                <optgroup label="Employer Documents (เอกสารนายจ้าง)">
                                    @for($i = 1; $i <= 3; $i++)
                                        <option value="employer_doc_other_{{ $i }}">Employer Other Document {{ $i }} (เอกสารอื่นๆ {{ $i }})</option>
                                    @endfor
                                </optgroup>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ url()->previous() }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="isProcessing">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Generate Documents
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pdfGenerator', () => ({
            outputType: 'download',
            isProcessing: false,
            // Templates loaded initially from server or empty
            templates: @json($templates),
            selectedTemplateId: '',
            isLoadingTemplates: false,

            // Global State for Employer Selection (shared with child component via $dispatch if needed, but here simple ref)
            selectedEmployerId: 'global', // Default

            init() {
                // Listen for employer selection event
                window.addEventListener('employer-selected', (e) => {
                    this.selectedEmployerId = e.detail.id;
                    this.fetchTemplates();
                });

                document.getElementById('generateForm').addEventListener('submit', () => {
                    this.isProcessing = true;
                    if (this.outputType === 'download') {
                        setTimeout(() => this.isProcessing = false, 3000);
                    }
                });
            },

            fetchTemplates() {
                this.isLoadingTemplates = true;
                this.selectedTemplateId = ''; // Reset selection

                const url = `{{ route('admin.pdf-templates.list') }}?employer_id=${this.selectedEmployerId}`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.templates = data;
                        this.isLoadingTemplates = false;
                    })
                    .catch(err => {
                        console.error(err);
                        this.isLoadingTemplates = false;
                        // Fallback or alert?
                    });
            }
        }));

        Alpine.data('employerSelector', () => ({
            search: '',
            open: false,
            selectedEmployerId: 'global', // Default to global
            selectedName: 'Global Templates Only (ส่วนกลาง)',
            options: @json($employerOptions ?? []),

            init() {
                this.search = this.selectedName;
            },

            get filteredOptions() {
                if (this.search === '') return this.options;
                const term = this.search.toLowerCase();
                return this.options.filter(o => o.search_str.includes(term));
            },

            selectOption(opt) {
                this.selectedEmployerId = opt.id;
                this.selectedName = opt.name_th + ' (' + opt.name_en + ')';
                this.search = opt.name_th;
                this.open = false;

                // Dispatch event to parent
                window.dispatchEvent(new CustomEvent('employer-selected', { detail: { id: opt.id } }));
            }
        }));
    });
</script>
@endpush
@endsection
