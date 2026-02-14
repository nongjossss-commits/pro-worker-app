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

                    <form action="{{ route('admin.pdf-templates.generate.process') }}" method="POST" id="generateForm" @submit.prevent="handleSubmit">
                        @csrf

                        <!-- Hidden Employee IDs (Used for Sync Download) -->
                        @foreach($employees as $id)
                            <input type="hidden" name="employees[]" value="{{ $id }}" class="hidden-employee-id">
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

                            <div x-data="employerSelector()" @click.outside="open = false; search = selectedName">
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text"
                                               class="form-control"
                                               placeholder="Type to search employer or select Global..."
                                               x-model="search"
                                               @focus="open = true; search = ''"
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

                            <select name="slot_name" class="form-select" :required="outputType === 'save_to_slot'" x-model="slotName">
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

    {{-- Progress Modal (For Save to Slot) --}}
    <div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h5 class="modal-title fw-bold mb-3">Processing Documents...</h5>

                    <div class="progress mb-2" style="height: 20px;">
                        <div id="saveProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                    </div>

                    <p class="text-muted mb-0" id="saveProgressText">Preparing...</p>

                    <div class="mt-3 d-none" id="saveErrorDetails">
                        <div class="alert alert-danger text-start small">
                            <strong>Errors occurred:</strong>
                            <ul id="errorList" class="mb-0 ps-3"></ul>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">Reload Page</button>
                    </div>
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
            slotName: '',
            templates: @json($templates),
            selectedTemplateId: '',
            isLoadingTemplates: false,
            selectedEmployerId: 'global',

            init() {
                window.addEventListener('employer-selected', (e) => {
                    this.selectedEmployerId = e.detail.id;
                    this.fetchTemplates();
                });
            },

            fetchTemplates() {
                this.isLoadingTemplates = true;
                this.selectedTemplateId = '';
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
                    });
            },

            handleSubmit(e) {
                if (this.isProcessing) return;

                // 1. Download Mode: Submit form normally (synchronous)
                if (this.outputType === 'download') {
                    this.isProcessing = true;
                    e.target.submit();
                    // Reset button after delay since download doesn't reload page
                    setTimeout(() => this.isProcessing = false, 5000);
                    return;
                }

                // 2. Save to Slot Mode: AJAX Batch Processing
                if (this.outputType === 'save_to_slot') {
                    this.startBatchProcessing(e.target);
                }
            },

            async startBatchProcessing(form) {
                this.isProcessing = true;
                const employeeInputs = form.querySelectorAll('.hidden-employee-id');
                const employeeIds = Array.from(employeeInputs).map(i => i.value);
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                if (employeeIds.length === 0) {
                    alert('No employees selected.');
                    this.isProcessing = false;
                    return;
                }

                // Show Modal
                const modalEl = document.getElementById('progressModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                const progressBar = document.getElementById('saveProgressBar');
                const progressText = document.getElementById('saveProgressText');
                const errorDiv = document.getElementById('saveErrorDetails');
                const errorList = document.getElementById('errorList');

                // Reset UI
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressBar.classList.remove('bg-danger', 'bg-success');
                progressText.textContent = 'Starting...';
                errorDiv.classList.add('d-none');
                errorList.innerHTML = '';

                // Batch Config
                const BATCH_SIZE = 5;
                const total = employeeIds.length;
                let processed = 0;
                let failed = 0;

                for (let i = 0; i < total; i += BATCH_SIZE) {
                    const chunk = employeeIds.slice(i, i + BATCH_SIZE);

                    progressText.textContent = `Processing batch ${Math.ceil((i+1)/BATCH_SIZE)} of ${Math.ceil(total/BATCH_SIZE)}...`;

                    try {
                        const response = await fetch('{{ route("admin.pdf-templates.generate.process") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                employees: chunk,
                                template_id: this.selectedTemplateId,
                                output_type: 'save_to_slot',
                                slot_name: this.slotName
                            })
                        });

                        const result = await response.json();

                        // Check for partial errors in result array if backend returns array
                        // Or check if response was not OK
                        if (!response.ok) throw new Error('Network error');

                        // Backend returns array of results
                        if (Array.isArray(result)) {
                            result.forEach(r => {
                                if (r.status === 'error') {
                                    failed++;
                                    const li = document.createElement('li');
                                    li.textContent = `Emp ID ${r.employee}: ${r.message}`;
                                    errorList.appendChild(li);
                                }
                            });
                        }

                    } catch (err) {
                        console.error(err);
                        failed += chunk.length; // Assume whole chunk failed
                        const li = document.createElement('li');
                        li.textContent = `Batch error: ${err.message}`;
                        errorList.appendChild(li);
                    }

                    processed += chunk.length;
                    const percent = Math.round((processed / total) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressBar.textContent = `${percent}%`;
                }

                // Completion
                if (failed > 0) {
                    progressText.textContent = `Completed with ${failed} errors.`;
                    progressBar.classList.add('bg-danger');
                    errorDiv.classList.remove('d-none');
                    // Enable close
                    this.isProcessing = false;
                } else {
                    progressText.textContent = 'All documents generated successfully!';
                    progressBar.classList.add('bg-success');
                    setTimeout(() => {
                        window.location.href = '{{ route("employees.index") }}';
                    }, 1500);
                }
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
