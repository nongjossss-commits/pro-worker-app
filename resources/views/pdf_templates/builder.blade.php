@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col" x-data="pdfBuilder()">
    <!-- Toolbar -->
    <div class="bg-white border-b px-4 py-3 flex justify-between items-center shadow-sm z-30 sticky top-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.pdf-templates.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="text-lg font-bold text-gray-800">{{ $template->name }} <span class="text-sm font-normal text-gray-500">(Builder Mode)</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <!-- Page Navigation -->
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

            <div class="p-3 space-y-4 flex-1">
                <!-- Tools Section -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1">Tools</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Static Text -->
                        <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex flex-col items-center justify-center gap-1 text-center"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'static', label: 'Static Text'})">
                            <i class="bi bi-type text-xl text-gray-600"></i>
                            <span class="text-xs font-medium">Text</span>
                        </div>
                        <!-- Signature -->
                        <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex flex-col items-center justify-center gap-1 text-center"
                             draggable="true"
                             @dragstart="dragStart($event, {type: 'signature', label: 'Signature Box'})">
                            <i class="bi bi-pen text-xl text-gray-600"></i>
                            <span class="text-xs font-medium">Signature</span>
                        </div>
                    </div>
                </div>

                <!-- Data Fields Groups -->
                <template x-for="(group, groupName) in filteredGroups" :key="groupName">
                    <div class="mb-2">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2 border-b pb-1" x-text="groupName"></h4>
                        <div class="space-y-1">
                            <template x-for="field in group" :key="field.key">
                                <div class="bg-white p-2 border rounded shadow-sm cursor-grab hover:bg-orange-50 transition-colors flex items-center justify-between"
                                     draggable="true"
                                     @dragstart="dragStart($event, {type: 'db', key: field.key, label: field.label})">
                                    <span class="text-sm" x-text="field.label"></span>
                                    <i class="bi bi-grip-vertical text-gray-400"></i>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Canvas Area -->
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

                            <!-- Placed Items -->
                            <template x-for="(item, index) in items" :key="index">
                                <div x-show="parseInt(item.page) === pageNum"
                                     class="absolute border cursor-move group flex items-center px-1 overflow-hidden"
                                     :class="{
                                        'border-blue-500 bg-blue-100/50 hover:bg-blue-100/80': item.type === 'db',
                                        'border-gray-500 bg-gray-100/50 hover:bg-gray-100/80': item.type === 'static',
                                        'border-purple-500 bg-purple-100/50 hover:bg-purple-100/80': item.type === 'signature'
                                     }"
                                     :style="`left: ${item.x}%; top: ${item.y}%; width: ${item.w}%; height: ${item.h}%; font-size: ${item.fontSize ?? 12}px;`"
                                     @mousedown.self="startMove($event, index, pageNum)">

                                    <!-- Content -->
                                    <div class="w-full h-full flex items-center pointer-events-none select-none">
                                        <!-- Signature Icon/Preview -->
                                        <template x-if="item.type === 'signature'">
                                            <div class="w-full text-center text-purple-800">
                                                <i class="bi bi-pen"></i>
                                                <span class="text-xs block" x-text="item.signatureGroup === 'employer' ? '(Employer)' : '(Employee)'"></span>
                                            </div>
                                        </template>

                                        <!-- Text Content -->
                                        <template x-if="item.type !== 'signature'">
                                            <span class="truncate w-full"
                                                  :class="{'text-blue-800 font-bold': item.type === 'db', 'text-gray-800': item.type === 'static'}"
                                                  x-text="item.type === 'static' ? (item.text || 'Static Text') : item.label"></span>
                                        </template>
                                    </div>

                                    <!-- Resize Handle -->
                                    <div class="resize-handle absolute bottom-0 right-0 w-3 h-3 bg-white border border-gray-400 cursor-nwse-resize z-20"
                                         @mousedown.stop="startResize($event, index, pageNum)"></div>

                                    <!-- Controls -->
                                    <div class="absolute -top-8 right-0 bg-white shadow rounded border flex gap-1 p-1 hidden group-hover:flex z-50">
                                        <!-- Settings Button (Context Aware) -->
                                        <button @click.stop="openSettings(index)" class="p-1 hover:bg-gray-100 rounded text-gray-600" title="Settings">
                                            <i class="bi bi-gear"></i>
                                        </button>

                                        <!-- Delete Button -->
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

    <!-- Settings Modal -->
    <div class="modal fade" id="itemSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" x-data="{ currentItem: {} }">
                <div class="modal-header">
                    <h5 class="modal-title">Item Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" x-show="editingIndex !== null">
                    <!-- Static Text Edit -->
                    <template x-if="items[editingIndex]?.type === 'static'">
                        <div class="mb-3">
                            <label class="form-label">Text Content</label>
                            <input type="text" x-model="items[editingIndex].text" class="form-control">
                        </div>
                    </template>

                    <!-- Signature Settings -->
                    <template x-if="items[editingIndex]?.type === 'signature'">
                        <div>
                            <div class="mb-3">
                                <label class="form-label">Signature Group</label>
                                <select x-model="items[editingIndex].signatureGroup" class="form-select">
                                    <option value="employee">Employee (Unique per person)</option>
                                    <option value="employer">Employer (Consistent)</option>
                                </select>
                                <div class="form-text text-xs text-muted mt-1">
                                    'Employee' will generate a unique signature for each employee.<br>
                                    'Employer' will use the single stored employer signature.
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Text Field Settings (DB & Static) -->
                    <template x-if="items[editingIndex]?.type === 'db' || items[editingIndex]?.type === 'static'">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoFitToggle" x-model="items[editingIndex].autoFit">
                                <label class="form-check-label" for="autoFitToggle">Auto-fit Text</label>
                            </div>
                            <div class="form-text text-xs text-muted">
                                If enabled, text font size will shrink to fit within the box width.
                            </div>
                        </div>
                    </template>
                     <template x-if="items[editingIndex]?.type === 'db' || items[editingIndex]?.type === 'static'">
                        <div class="mb-3">
                             <label class="form-label">Font Size (px)</label>
                             <input type="number" x-model="items[editingIndex].fontSize" class="form-control" min="8" max="72">
                        </div>
                    </template>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('alpine:init', () => {
        Alpine.data('pdfBuilder', () => ({
            _pdfDoc: null,
            currentPage: 1,
            totalPages: 1,
            scale: 1.5,
            pageDimensions: {},
            items: @json($template->field_mapping ?? []),
            searchQuery: '',
            isSaving: false,
            editingIndex: null,

            // Raw Fields Data
            rawFields: [
                // Employee Personal
                { group: 'Employee Personal', key: 'employeeNameTh', label: 'Name (TH)' },
                { group: 'Employee Personal', key: 'employeeNameEn', label: 'Name (EN)' },
                { group: 'Employee Personal', key: 'employeeTitleTh', label: 'Title (TH)' },
                { group: 'Employee Personal', key: 'employeeGender', label: 'Gender' },
                { group: 'Employee Personal', key: 'age', label: 'Age' },
                { group: 'Employee Personal', key: 'employeeDob', label: 'Date of Birth' },
                { group: 'Employee Personal', key: 'employeeNationality', label: 'Nationality' },
                { group: 'Employee Personal', key: 'father_name', label: 'Father Name' },
                { group: 'Employee Personal', key: 'mother_name', label: 'Mother Name' },
                { group: 'Employee Personal', key: 'employeePhone', label: 'Phone' },

                // Employee Documents
                { group: 'Employee Documents', key: 'employeePassport', label: 'Passport No' },
                { group: 'Employee Documents', key: 'passportIssueDate', label: 'Passport Issue' },
                { group: 'Employee Documents', key: 'passportExpiryDate', label: 'Passport Expiry' },
                { group: 'Employee Documents', key: 'visaType', label: 'Visa Type' },
                { group: 'Employee Documents', key: 'visaExpiryDate', label: 'Visa Expiry' },
                { group: 'Employee Documents', key: 'employeeWorkPermit', label: 'Work Permit No' },
                { group: 'Employee Documents', key: 'workPermitExpiryDate', label: 'Work Permit Expiry' },
                { group: 'Employee Documents', key: 'pinkCardNo', label: 'Pink Card No' },

                // Job & Insurance
                { group: 'Job & Insurance', key: 'job_title', label: 'Job Title' },
                { group: 'Job & Insurance', key: 'startDate', label: 'Start Date' },
                { group: 'Job & Insurance', key: 'social_security_number', label: 'Social Security No' },
                { group: 'Job & Insurance', key: 'insurance_detail', label: 'Hospital (SS)' },

                // Employer
                { group: 'Employer Data', key: 'employer.employerNameTh', label: 'Company Name (TH)' },
                { group: 'Employer Data', key: 'employer.employerNameEn', label: 'Company Name (EN)' },
                { group: 'Employer Data', key: 'employer.employerTaxId', label: 'Tax ID' },
                { group: 'Employer Data', key: 'employer.signerNameTh', label: 'Authorized Signer' },
                { group: 'Employer Data', key: 'employer.address_th', label: 'Address (TH)' },
                { group: 'Employer Data', key: 'employer.address_en', label: 'Address (EN)' },
            ],

            get filteredGroups() {
                const q = this.searchQuery.toLowerCase();
                const filtered = this.rawFields.filter(f =>
                    f.label.toLowerCase().includes(q) || f.group.toLowerCase().includes(q)
                );

                // Group by 'group' key
                return filtered.reduce((acc, field) => {
                    if (!acc[field.group]) acc[field.group] = [];
                    acc[field.group].push(field);
                    return acc;
                }, {});
            },

            async init() {
                // Ensure page numbers are integers for correct comparison
                this.items = this.items.map(item => ({ ...item, page: parseInt(item.page) }));

                const url = '{{ route("admin.pdf-templates.file", $template) }}';
                try {
                    const loadingTask = pdfjsLib.getDocument(url);
                    this._pdfDoc = await loadingTask.promise;
                    this.totalPages = this._pdfDoc.numPages;
                    await this.renderAllPages();
                } catch (error) {
                    console.error('Error loading PDF:', error);
                    showToast('Failed to load PDF.', 'danger');
                }
            },

            async renderAllPages() {
                await this.$nextTick();
                for (let i = 1; i <= this.totalPages; i++) {
                    await this.renderPage(i);
                }
            },

            async renderPage(num) {
                if (!this._pdfDoc) return;
                const page = await this._pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: this.scale });

                this.pageDimensions[num] = { width: viewport.width, height: viewport.height };
                await this.$nextTick();

                const canvas = document.getElementById('canvas-page-' + num);
                if (canvas) {
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    await page.render({ canvasContext: context, viewport: viewport }).promise;
                }
            },

            scrollToPage(pageNum) {
                const el = document.getElementById('page-container-' + pageNum);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

                // Defaults
                let wPct = 15;
                let hPct = 3;

                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                    if (data.type === 'signature') {
                        wPct = 10;
                        hPct = 6; // Signatures are taller
                    }

                    this.items.push({
                        type: data.type,
                        key: data.key || null,
                        label: data.label,
                        text: data.type === 'static' ? 'Double click to edit' : null,
                        x: (x / dims.width) * 100,
                        y: (y / dims.height) * 100,
                        w: wPct,
                        h: hPct,
                        page: pageNum,
                        fontSize: 12,
                        autoFit: true, // Default to true
                        signatureGroup: data.type === 'signature' ? 'employee' : null
                    });

                    // Auto-open settings for new static text
                    if (data.type === 'static') {
                        this.openSettings(this.items.length - 1);
                    }

                } catch (e) {
                    console.error('Drop error', e);
                }
            },

            startMove(event, index, pageNum) {
                // Prevent drag if clicking resize handle
                if (event.target.classList.contains('resize-handle')) return;

                const item = this.items[index];
                const dims = this.pageDimensions[pageNum];
                const startX = event.clientX;
                const startY = event.clientY;
                const startLeft = (item.x / 100) * dims.width;
                const startTop = (item.y / 100) * dims.height;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    let newLeft = startLeft + dx;
                    let newTop = startTop + dy;

                    // Boundaries
                    newLeft = Math.max(0, Math.min(newLeft, dims.width - ((item.w/100)*dims.width)));
                    newTop = Math.max(0, Math.min(newTop, dims.height - ((item.h/100)*dims.height)));

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

            startResize(event, index, pageNum) {
                const item = this.items[index];
                const dims = this.pageDimensions[pageNum];
                const startX = event.clientX;
                const startY = event.clientY;
                const startW = (item.w / 100) * dims.width;
                const startH = (item.h / 100) * dims.height;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    let newW = startW + dx;
                    let newH = startH + dy;

                    // Minimum sizes
                    newW = Math.max(20, newW);
                    newH = Math.max(10, newH);

                    item.w = (newW / dims.width) * 100;
                    item.h = (newH / dims.height) * 100;
                };

                const onMouseUp = () => {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            },

            deleteItem(index) {
                if(confirm('Remove this field?')) {
                    this.items.splice(index, 1);
                    this.editingIndex = null;
                }
            },

            openSettings(index) {
                this.editingIndex = index;
                new bootstrap.Modal(document.getElementById('itemSettingsModal')).show();
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
    .resize-handle { display: none; }
    .group:hover .resize-handle { display: block; }
</style>
@endpush
@endsection
