@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col" x-data="pdfBuilder()">
    <!-- Toolbar -->
    <div class="bg-white border-b px-4 py-3 flex justify-between items-center shadow-sm z-10">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.pdf-templates.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h1 class="text-lg font-bold text-gray-800">{{ $template->name }} <span class="text-sm font-normal text-gray-500">(Builder Mode)</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-sm text-gray-500">
                Page <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
            </div>
            <button @click="prevPage()" class="btn btn-sm btn-light border" :disabled="currentPage <= 1">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button @click="nextPage()" class="btn btn-sm btn-light border" :disabled="currentPage >= totalPages">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="border-l h-6 mx-2"></div>
            <button @click="saveMapping()" class="btn btn-primary btn-sm flex items-center gap-2" :disabled="isSaving">
                <span x-show="isSaving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <i class="bi bi-save" x-show="!isSaving"></i> Save Template
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden h-[calc(100vh-64px)]">
        <!-- Sidebar -->
        <div class="w-80 bg-gray-50 border-r flex flex-col overflow-y-auto">
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

        <!-- Canvas Area -->
        <div class="flex-1 bg-gray-200 overflow-auto flex justify-center p-8 relative" id="canvas-container">
            <div class="relative shadow-lg" :style="`width: ${canvasWidth}px; height: ${canvasHeight}px;`">
                <!-- PDF Canvas -->
                <canvas id="the-canvas" class="block bg-white"></canvas>

                <!-- Drop Overlay -->
                <div class="absolute inset-0 z-10"
                     @dragover.prevent
                     @drop.prevent="drop($event)">

                    <!-- Placed Items -->
                    <template x-for="(item, index) in items" :key="index">
                        <div x-show="item.page === currentPage"
                             class="absolute border border-blue-500 bg-blue-100/50 hover:bg-blue-100/80 cursor-move group flex items-center px-1"
                             :style="`left: ${item.x}%; top: ${item.y}%; width: ${item.w ?? 15}%; height: ${item.h ?? 3}%; font-size: ${item.fontSize ?? 12}px;`"
                             @mousedown="startMove($event, index)">

                            <!-- Resize Handles (Corners) -->
                            <!-- Simplified: Just move for now, maybe resize later if requested -->

                            <!-- Content -->
                            <span class="truncate w-full select-none"
                                  :class="{'text-blue-800 font-bold': item.type === 'db', 'text-gray-800': item.type === 'static'}"
                                  x-text="item.type === 'static' ? (item.text || 'Static Text') : item.label"></span>

                            <!-- Controls (Show on Hover) -->
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
            currentPage: 1,
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
                // Add more fields as needed
            ],

            get filteredFields() {
                if (!this.searchQuery) return this.fields;
                return this.fields.filter(f => f.label.toLowerCase().includes(this.searchQuery.toLowerCase()));
            },

            async init() {
                // Load PDF
                const url = '{{ Storage::disk("public")->url($template->file_path) }}';
                try {
                    this.pdfDoc = await pdfjsLib.getDocument(url).promise;
                    this.totalPages = this.pdfDoc.numPages;
                    this.renderPage(this.currentPage);
                } catch (error) {
                    console.error('Error loading PDF:', error);
                    alert('Failed to load PDF file.');
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

            async renderPage(num) {
                const page = await this.pdfDoc.getPage(num);
                const viewport = page.getViewport({ scale: this.scale });
                const canvas = document.getElementById('the-canvas');
                const context = canvas.getContext('2d');

                this.canvasWidth = viewport.width;
                this.canvasHeight = viewport.height;
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                await page.render(renderContext).promise;
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.renderPage(this.currentPage);
                }
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.renderPage(this.currentPage);
                }
            },

            dragStart(event, data) {
                event.dataTransfer.setData('text/plain', JSON.stringify(data));
                event.dataTransfer.effectAllowed = 'copy';
            },

            drop(event) {
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
                        key: data.key || null, // Only for DB fields
                        label: data.label,
                        text: data.type === 'static' ? 'Double click to edit' : null,
                        x: xPct,
                        y: yPct,
                        w: 15, // Default width %
                        h: 3,  // Default height %
                        page: this.currentPage,
                        fontSize: 12
                    });

                    // If static, open edit immediately
                    if (data.type === 'static') {
                        this.editStaticText(this.items.length - 1);
                    }

                } catch (e) {
                    // Moving existing item logic could go here if using dragstart on existing items
                    console.error('Drop error', e);
                }
            },

            startMove(event, index) {
                // Simple drag implementation for placed items
                const item = this.items[index];
                const startX = event.clientX;
                const startY = event.clientY;
                const startLeft = (item.x / 100) * this.canvasWidth;
                const startTop = (item.y / 100) * this.canvasHeight;

                const onMouseMove = (e) => {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;

                    let newLeft = startLeft + dx;
                    let newTop = startTop + dy;

                    // Boundaries
                    newLeft = Math.max(0, Math.min(newLeft, this.canvasWidth - 20)); // Approximate width
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
