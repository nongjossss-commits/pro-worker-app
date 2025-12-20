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
                         :style="`width: ${canvasWidth}px; height: ${canvasHeight}px;`">

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
            canvasWidth: 0,
            canvasHeight: 0,
            items: @json($template->field_mapping ?? []),
            searchQuery: '',
            isSaving: false,
            currentEditIndex: null,

            // Available Fields
            fields: [
                { key: 'employeeNameTh', label: 'ชื่อ (ไทย)' },
                { key: 'employeeNameEn', label: 'Name (Eng)' },
                { key: 'employeeTitleTh', label: 'คำนำหน้า (ไทย)' },
                { key: 'gender', label: 'เพศ' },
                { key: 'employeeDob', label: 'วันเกิด (YYYY-MM-DD)' },
                { key: 'age', label: 'อายุ' },
                { key: 'employeeNationality', label: 'สัญชาติ' },
                { key: 'employeePassport', label: 'Passport No' },
                { key: 'passportExpiryDate', label: 'Passport Expiry' },
                { key: 'passportIssueDate', label: 'Passport Issue' },
                { key: 'employeeWorkPermit', label: 'Work Permit No' },
                { key: 'workPermitExpiryDate', label: 'Work Permit Expiry' },
                { key: 'pinkCardNo', label: 'Pink Card No' },
                { key: 'visaType', label: 'Visa Type' },
                { key: 'visaExpiryDate', label: 'Visa Expiry' },
                { key: 'employer.employerNameTh', label: 'นายจ้าง (ไทย)' },
                { key: 'employer.employerNameEn', label: 'Employer (Eng)' },
                { key: 'employer.employerPhone', label: 'เบอร์นายจ้าง' },
                { key: 'employer.employerTaxId', label: 'เลขผู้เสียภาษีนายจ้าง' },
                { key: 'employer.employerAddress', label: 'ที่อยู่นายจ้าง' }
            ],

            get filteredFields() {
                if (!this.searchQuery) return this.fields;
                return this.fields.filter(f => f.label.toLowerCase().includes(this.searchQuery.toLowerCase()));
            },

            async init() {
                // Use the direct route to avoid Storage URL / CORS issues
                const url = '{{ route("admin.pdf-templates.file", $template) }}';

                try {
                    const loadingTask = pdfjsLib.getDocument(url);
                    this.pdfDoc = await loadingTask.promise;
                    this.totalPages = this.pdfDoc.numPages;

                    // Render ALL pages sequentially
                    // We wait for the first page to determine dimensions, then render rest
                    await this.renderAllPages();

                } catch (error) {
                    console.error('Error loading PDF:', error);
                    alert('Failed to load PDF file. Please check if the file is valid.');
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
                // Get first page to set global dimensions (assuming uniform page size for simplicity)
                const page1 = await this.pdfDoc.getPage(1);
                const viewport = page1.getViewport({ scale: this.scale });

                this.canvasWidth = viewport.width;
                this.canvasHeight = viewport.height;

                // Wait for Alpine to render the DOM loops
                await this.$nextTick();

                for (let i = 1; i <= this.totalPages; i++) {
                    await this.renderPage(i);
                }
            },

            async renderPage(num) {
                const page = await this.pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: this.scale });
                const canvas = document.getElementById('canvas-page-' + num);

                if (!canvas) return; // Should not happen if loop matches

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
                const rect = event.target.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                // Convert to percentage relative to canvas
                const xPct = (x / this.canvasWidth) * 100;
                const yPct = (y / this.canvasHeight) * 100;

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
                const startX = event.clientX;
                const startY = event.clientY;
                // Calculate initial pixel position
                const startLeft = (item.x / 100) * this.canvasWidth;
                const startTop = (item.y / 100) * this.canvasHeight;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;

                    let newLeft = startLeft + dx;
                    let newTop = startTop + dy;

                    // Boundaries
                    newLeft = Math.max(0, Math.min(newLeft, this.canvasWidth - 20));
                    newTop = Math.max(0, Math.min(newTop, this.canvasHeight - 10));

                    item.x = (newLeft / this.canvasWidth) * 100;
                    item.y = (newTop / this.canvasHeight) * 100;
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
