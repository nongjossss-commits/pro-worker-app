@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col" x-data="pdfBuilder()">
    <!-- Toolbar -->
    <div class="bg-white border-b px-4 py-3 flex justify-between items-center shadow-sm z-10 sticky top-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.pdf-templates.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="text-lg font-bold text-gray-800">{{ $template->name }} <span class="text-sm font-normal text-gray-500">(Builder Mode)</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <!-- Page Navigation (Jumping) -->
            <div class="flex items-center gap-2" x-show="totalPages > 1">
                <label class="text-sm text-gray-600">Go to Page:</label>
                <select x-model="currentPage" @change="scrollToPage(currentPage)" class="form-select form-select-sm w-20">
                    <template x-for="p in totalPages" :key="p">
                        <option :value="p" x-text="p"></option>
                    </template>
                </select>
                <span class="text-sm text-gray-500">of <span x-text="totalPages"></span></span>
            </div>

            <div class="border-l h-6 mx-2"></div>

            <button @click="saveMapping()" class="btn btn-primary btn-sm flex items-center gap-2" :disabled="isSaving">
                <span x-show="isSaving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <i class="bi bi-save" x-show="!isSaving"></i> Save Template
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden h-[calc(100vh-64px)]">
        <!-- Sidebar -->
        <div class="w-80 bg-gray-50 border-r flex flex-col overflow-y-auto z-20 shadow-lg">
            <div class="p-4 border-b bg-white">
                <h3 class="font-bold text-gray-700 mb-2">Data Fields</h3>
                <input type="text" x-model="searchQuery" placeholder="Search fields..." class="form-control form-control-sm">
            </div>

            <div class="p-3 space-y-2 flex-1">
                <!-- Static Text Tool -->
                <div class="mb-4">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Tools</h4>
                    <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex items-center gap-2"
                         draggable="true"
                         @dragstart="dragStart($event, {type: 'static', label: 'Static Text'})">
                        <i class="bi bi-type text-lg text-gray-600"></i>
                        <span class="text-sm font-medium">Add Static Text</span>
                    </div>
                </div>

                <!-- Database Fields -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Employee Data</h4>
                    <template x-for="field in filteredFields" :key="field.key">
                        <div class="bg-white p-2 mb-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex items-center justify-between"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'db', key: field.key, label: field.label})">
                            <span class="text-sm" x-text="field.label"></span>
                            <i class="bi bi-grip-vertical text-gray-400"></i>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Canvas Area (Vertical Scroll) -->
        <div class="flex-1 bg-gray-200 overflow-y-auto flex justify-center p-8 relative" id="main-scroll-container">
            <div class="flex flex-col gap-8 pb-20">
                <!-- Loop through pages -->
                <template x-for="pageNum in totalPages" :key="pageNum">
                    <div class="relative shadow-lg bg-white"
                         :id="'page-container-' + pageNum"
                         :style="pageDimensions[pageNum] ? `width: ${pageDimensions[pageNum].width}px; height: ${pageDimensions[pageNum].height}px;` : 'min-height: 200px;'">

                        <!-- Page Label -->
                        <div class="absolute -top-6 left-0 text-sm font-bold text-gray-500">
                            Page <span x-text="pageNum"></span>
                        </div>

                        <!-- PDF Canvas -->
                        <canvas :id="'canvas-page-' + pageNum" class="block bg-white"></canvas>

                        <!-- Drop Overlay -->
                        <div class="absolute inset-0 z-10"
                             @dragover.prevent
                             @drop.prevent="drop($event, pageNum)">

                            <!-- Placed Items for this Page -->
                            <template x-for="(item, index) in items" :key="index">
                                <div x-show="item.page === pageNum"
                                     class="absolute border border-blue-500 bg-blue-100/50 hover:bg-blue-100/80 cursor-move group flex items-center px-1"
                                     :style="`left: ${item.x}%; top: ${item.y}%; width: ${item.w ?? 15}%; height: ${item.h ?? 3}%; font-size: ${item.fontSize ?? 12}px;`"
                                     @mousedown="startMove($event, index, pageNum)">

                                    <!-- Content -->
                                    <span class="truncate w-full select-none"
                                          :class="{'text-blue-800 font-bold': item.type === 'db', 'text-gray-800': item.type === 'static'}"
                                          x-text="item.type === 'static' ? (item.text || 'Static Text') : item.label"></span>

                                    <!-- Controls -->
                                    <div class="absolute -top-8 right-0 bg-white shadow rounded border flex gap-1 p-1 hidden group-hover:flex z-50">
                                        <template x-if="item.type === 'static'">
                                            <button @click.stop="editStaticText(index)" class="p-1 hover:bg-gray-100 rounded text-blue-600" title="Edit Text">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </template>
                                        <button @click.stop="deleteItem(index)" class="p-1 hover:bg-gray-100 rounded text-red-600" title="Remove">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Edit Static Text Modal -->
    <div class="modal fade" id="staticTextModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Text</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="staticTextInput" class="form-control" placeholder="Enter text here...">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStaticTextBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Initialize PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('alpine:init', () => {
        Alpine.data('pdfBuilder', () => ({
            pdfDoc: null,
            currentPage: 1, // Currently viewed page (for jump)
            totalPages: 1,
            scale: 1.5,
            pageDimensions: {}, // Store width/height for each page index
            items: @json($template->field_mapping ?? []),
            searchQuery: '',
            isSaving: false,
            currentEditIndex: null,

            // Available Fields - Updated Comprehensive List
            fields: [
                // --- Employee Personal ---
                { key: 'employeeNameTh', label: 'Employee Name (TH)' },
                { key: 'employeeNameEn', label: 'Employee Name (EN)' },
                { key: 'employeeTitleTh', label: 'Title (TH)' },
                { key: 'employeeTitleEn', label: 'Title (EN)' },
                { key: 'employeeGender', label: 'Gender' },
                { key: 'employeeDob', label: 'Date of Birth' },
                { key: 'age', label: 'Age' },
                { key: 'employeeNationality', label: 'Nationality' },
                { key: 'employeePhone', label: 'Phone' },
                { key: 'email', label: 'Email' },
                { key: 'father_name', label: 'Father Name' },
                { key: 'mother_name', label: 'Mother Name' },

                // --- Passport & Visa ---
                { key: 'employeePassport', label: 'Passport No' },
                { key: 'passportIssueDate', label: 'Passport Issue Date' },
                { key: 'passportExpiryDate', label: 'Passport Expiry Date' },
                { key: 'passportType', label: 'Passport Type (MM)' },
                { key: 'passport_type_cambodia', label: 'Passport Type (KH)' },
                { key: 'visaType', label: 'Visa Type' },
                { key: 'visaExpiryDate', label: 'Visa Expiry Date' },
                { key: 'pinkCardNo', label: 'Pink Card No' },

                // --- Work Permit ---
                { key: 'employeeWorkPermit', label: 'Work Permit No' },
                { key: 'workPermitExpiryDate', label: 'Work Permit Expiry' },
                { key: 'workPermitMOUGroup', label: 'MOU Group' },

                // --- Job ---
                { key: 'job_title', label: 'Job Title' },
                { key: 'startDate', label: 'Start Date' },
                { key: 'employee_id_number', label: 'Personal ID' },

                // --- Insurance ---
                { key: 'social_security_number', label: 'Social Security No' },
                { key: 'insurance_detail', label: 'Hospital (SS Rights)' },
                { key: 'insurance_detail_hospital', label: 'Hospital Name (Insurance)' },
                { key: 'insurance_detail_private', label: 'Private Ins. Company' },

                // --- Employer Data ---
                { key: 'employer.employerNameTh', label: 'Employer Name (TH)' },
                { key: 'employer.employerNameEn', label: 'Employer Name (EN)' },
                { key: 'employer.employerPhone', label: 'Employer Phone' },
                { key: 'employer.employerTaxId', label: 'Employer Tax ID' },
                { key: 'employer.businessType', label: 'Business Type' },
                { key: 'employer.regCapital', label: 'Registered Capital' },
                { key: 'employer.signerNameTh', label: 'Signer Name' },
                { key: 'employer.address_th', label: 'Employer Address (TH)' },
                { key: 'employer.address_en', label: 'Employer Address (EN)' },
            ],

            get filteredFields() {
                if (!this.searchQuery) return this.fields;
                const q = this.searchQuery.toLowerCase();
                return this.fields.filter(f => f.label.toLowerCase().includes(q));
            },

            async init() {
                // Use the direct route to avoid Storage URL / CORS issues
                const url = '{{ route("admin.pdf-templates.file", $template) }}';

                try {
                    const loadingTask = pdfjsLib.getDocument(url);
                    this.pdfDoc = await loadingTask.promise;
                    this.totalPages = this.pdfDoc.numPages;

                    // Render ALL pages sequentially
                    await this.renderAllPages();

                } catch (error) {
                    console.error('Error loading PDF:', error);
                    // alert('Failed to load PDF file. Please check if the file is valid.'); // Squelch alert for better UX in case of minor glitches
                    showToast('Failed to load PDF preview. Please check file validity.', 'danger');
                }

                // Setup Modal Listener
                document.getElementById('saveStaticTextBtn').addEventListener('click', () => {
                    if (this.currentEditIndex !== null) {
                        this.items[this.currentEditIndex].text = document.getElementById('staticTextInput').value;
                        bootstrap.Modal.getInstance(document.getElementById('staticTextModal')).hide();
                        this.currentEditIndex = null;
                    }
                });
            },

            async renderAllPages() {
                // No global dimension setting here anymore
                // Wait for Alpine to render the DOM loops
                await this.$nextTick();

                for (let i = 1; i <= this.totalPages; i++) {
                    await this.renderPage(i);
                }
            },

            async renderPage(num) {
                const page = await this.pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: this.scale });

                // Store dimensions for this specific page
                this.pageDimensions[num] = {
                    width: viewport.width,
                    height: viewport.height
                };

                // Wait for Alpine to update DOM with new dimensions
                await this.$nextTick();

                const canvas = document.getElementById('canvas-page-' + num);

                if (!canvas) return;

                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                await page.render(renderContext).promise;
            },

            scrollToPage(pageNum) {
                const el = document.getElementById('page-container-' + pageNum);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            },

            dragStart(event, data) {
                event.dataTransfer.setData('text/plain', JSON.stringify(data));
                event.dataTransfer.effectAllowed = 'copy';
            },

            drop(event, pageNum) {
                const dims = this.pageDimensions[pageNum];
                if (!dims) return;

                const rect = event.target.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                // Convert to percentage relative to this page's dimensions
                const xPct = (x / dims.width) * 100;
                const yPct = (y / dims.height) * 100;

                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                    this.items.push({
                        type: data.type,
                        key: data.key || null,
                        label: data.label,
                        text: data.type === 'static' ? 'Double click to edit' : null,
                        x: xPct,
                        y: yPct,
                        w: 15,
                        h: 3,
                        page: pageNum, // Assign to correct page
                        fontSize: 12
                    });

                    if (data.type === 'static') {
                        this.editStaticText(this.items.length - 1);
                    }

                } catch (e) {
                    console.error('Drop error', e);
                }
            },

            startMove(event, index, pageNum) {
                const item = this.items[index];
                const dims = this.pageDimensions[pageNum];

                if (!dims) return; // Should not happen

                const startX = event.clientX;
                const startY = event.clientY;
                // Calculate initial pixel position
                const startLeft = (item.x / 100) * dims.width;
                const startTop = (item.y / 100) * dims.height;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;

                    let newLeft = startLeft + dx;
                    let newTop = startTop + dy;

                    // Boundaries
                    newLeft = Math.max(0, Math.min(newLeft, dims.width - 20));
                    newTop = Math.max(0, Math.min(newTop, dims.height - 10));

                    item.x = (newLeft / dims.width) * 100;
                    item.y = (newTop / dims.height) * 100;
                };

                const onMouseUp = () => {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            },

            deleteItem(index) {
                this.items.splice(index, 1);
            },

            editStaticText(index) {
                this.currentEditIndex = index;
                const input = document.getElementById('staticTextInput');
                input.value = this.items[index].text || '';
                const modal = new bootstrap.Modal(document.getElementById('staticTextModal'));
                modal.show();
            },

            async saveMapping() {
                this.isSaving = true;
                try {
                    const response = await fetch('{{ route("admin.pdf-templates.update", $template) }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ field_mapping: this.items })
                    });

                    if (response.ok) {
                        showToast('Template saved successfully!', 'success');
                    } else {
                        throw new Error('Save failed');
                    }
                } catch (error) {
                    showToast('Error saving template.', 'danger');
                    console.error(error);
                } finally {
                    this.isSaving = false;
                }
            }
        }));
    });
</script>

<style>
    [x-cloak] { display: none !important; }
    .cursor-grab { cursor: grab; }
    .cursor-move { cursor: move; }
</style>
@endpush
@endsection
