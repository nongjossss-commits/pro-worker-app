@extends('labor.layout')

@section('title', __('Contract Template Builder'))

@section('content')
<div class="pwct-builder d-flex flex-column" x-data="proworkerContractBuilder()">
    <!-- Toolbar -->
    <div class="bg-white border-bottom px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('labor.contract-templates.index') }}" class="text-secondary text-decoration-none">
                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
            </a>
            <h5 class="mb-0 fw-bold"><span x-text="templateName"></span> <span class="small fw-normal text-muted">({{ __('Builder Mode') }})</span></h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-2" x-show="totalPages > 1">
                <label class="small text-muted mb-0">{{ __('Go to Page') }}:</label>
                <select x-model="currentPage" @change="scrollToPage(currentPage)" class="form-select form-select-sm" style="width: 80px;">
                    <template x-for="p in totalPages" :key="p">
                        <option :value="p" x-text="p"></option>
                    </template>
                </select>
                <span class="small text-muted">{{ __('of') }} <span x-text="totalPages"></span></span>
            </div>
            <button @click="saveMapping()" class="btn btn-primary btn-sm" :disabled="isSaving">
                <span x-show="isSaving" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                <i class="bi bi-save me-1" x-show="!isSaving"></i>{{ __('Save Template') }}
            </button>
        </div>
    </div>

    <div class="pwct-body d-flex flex-grow-1 overflow-hidden">
        <!-- Sidebar: Tools -->
        <div class="pwct-sidebar bg-light border-end d-flex flex-column overflow-auto flex-shrink-0">
            <div class="p-3 border-bottom bg-white">
                <h6 class="fw-bold mb-1">{{ __('Tools') }}</h6>
                <p class="small text-muted mb-0">{{ __('Drag a tool onto the document. For a text field, you\'ll be asked to name what it is for right away.') }}</p>
            </div>
            <div class="p-2">
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'text'})">
                    <i class="bi bi-input-cursor-text text-primary"></i>
                    <span class="small fw-semibold">{{ __('Text Field') }} (ช่องข้อความ)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'static_text'})">
                    <i class="bi bi-fonts text-secondary"></i>
                    <span class="small fw-semibold">{{ __('Fixed Text') }} (ข้อความคงที่)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'address'})">
                    <i class="bi bi-geo-alt text-success"></i>
                    <span class="small fw-semibold">{{ __('Address') }} (ที่อยู่ ไทย+อังกฤษ)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'worker_count'})">
                    <i class="bi bi-people text-primary"></i>
                    <span class="small fw-semibold">{{ __('Worker Count') }} (จำนวนแรงงานนำเข้า)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'issue_date', dateFormat: 'full'})">
                    <i class="bi bi-calendar-date text-primary"></i>
                    <span class="small fw-semibold">{{ __('Document Issue Date') }} (วันที่สร้างเอกสาร)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" draggable="true"
                     @dragstart="dragStart($event, {type: 'mark', markShape: 'check', color: '#16a34a'})">
                    <i class="bi bi-check2-square text-success"></i>
                    <span class="small fw-semibold">{{ __('Mark') }} (เครื่องหมาย)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" role="button"
                     @click="openImageUploader('image')">
                    <i class="bi bi-image text-primary"></i>
                    <span class="small fw-semibold">{{ __('Image') }} (รูปภาพ)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" role="button"
                     @click="openImageUploader('stamp')">
                    <i class="bi bi-record-circle text-danger"></i>
                    <span class="small fw-semibold">{{ __('Stamp') }} (ตราประทับ)</span>
                </div>
                <div class="pwct-tool bg-white border rounded mb-2 d-flex align-items-center gap-2 p-2" role="button"
                     @click="openImageUploader('signature')">
                    <i class="bi bi-pen text-secondary"></i>
                    <span class="small fw-semibold">{{ __('Company Signature') }} (ลายเซ็นกรรมการ)</span>
                </div>
            </div>
            <div class="p-2 border-top mt-1">
                <h6 class="small fw-bold text-uppercase text-muted mb-2">{{ __('Placed Fields') }} (<span x-text="items.length"></span>)</h6>
                <template x-for="(item, index) in items" :key="index">
                    <div class="d-flex justify-content-between align-items-center bg-white border rounded p-2 mb-1 small">
                        <span x-text="item.label" class="text-truncate" style="max-width: 140px;"></span>
                        <div class="d-flex gap-1">
                            <button type="button" @click="copyItem(index)" class="btn btn-sm btn-link text-secondary p-0" title="{{ __('Copy — links to the same value') }}"><i class="bi bi-copy"></i></button>
                            <button type="button" @click="openSettings(index)" class="btn btn-sm btn-link text-secondary p-0"><i class="bi bi-gear"></i></button>
                            <button type="button" @click="removeItem(index)" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="pwct-canvas-area flex-grow-1 overflow-auto d-flex justify-content-center p-4">
            <div class="d-flex flex-column gap-4 pb-5">
                <template x-for="pageNum in totalPages" :key="pageNum">
                    <div class="position-relative shadow bg-white"
                         :id="'page-container-' + pageNum"
                         :style="pageDimensions[pageNum] ? `width: ${pageDimensions[pageNum].width}px; height: ${pageDimensions[pageNum].height}px;` : 'min-height: 200px;'">

                        <div class="position-absolute small fw-bold text-muted" style="top: -1.5rem; left: 0;">{{ __('Page') }} <span x-text="pageNum"></span></div>

                        <canvas :id="'canvas-page-' + pageNum" class="d-block bg-white"></canvas>

                        <div class="position-absolute" style="top:0; right:0; bottom:0; left:0; z-index: 10;" @dragover.prevent @drop.prevent="drop($event, pageNum)">
                            <template x-for="[item, index] in itemsForPage(pageNum)" :key="pageNum + '-' + index">
                                <div class="pwct-item position-absolute border d-flex px-1"
                                     :class="{
                                        'pwct-item-text': item.type === 'text' || item.type === 'worker_count' || item.type === 'issue_date' || item.type === 'static_text',
                                        'pwct-item-address': item.type === 'address_th' || item.type === 'address_en',
                                        'pwct-item-mark': item.type === 'mark',
                                        'pwct-item-media': item.type === 'image' || item.type === 'stamp' || item.type === 'signature'
                                     }"
                                     :style="`left: ${item.x}%; top: ${item.y}%; width: ${item.w}%; height: ${item.h}%;`"
                                     @mousedown.self="startMove($event, index, pageNum)">

                                    <template x-if="item.type === 'image' || item.type === 'stamp' || item.type === 'signature'">
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center position-relative" style="pointer-events:none;">
                                            <img :src="item.url" class="mw-100 mh-100" style="object-fit: contain;">
                                            <span class="position-absolute bottom-0 end-0 bg-white bg-opacity-75 px-1" style="font-size: 9px;" x-text="item.label"></span>
                                        </div>
                                    </template>

                                    <template x-if="item.type === 'mark'">
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="pointer-events:none;">
                                            <svg viewBox="0 0 100 100" class="w-100 h-100" preserveAspectRatio="xMidYMid meet">
                                                <template x-if="item.markShape === 'check'">
                                                    <path d="M20,55 L40,75 L80,25" fill="none" :stroke="item.color" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" />
                                                </template>
                                                <template x-if="item.markShape === 'cross'">
                                                    <g :stroke="item.color" stroke-width="10" stroke-linecap="round">
                                                        <line x1="20" y1="20" x2="80" y2="80" />
                                                        <line x1="80" y1="20" x2="20" y2="80" />
                                                    </g>
                                                </template>
                                                <template x-if="item.markShape === 'circle'">
                                                    <circle cx="50" cy="50" r="38" fill="none" :stroke="item.color" stroke-width="8" />
                                                </template>
                                            </svg>
                                        </div>
                                    </template>

                                    <template x-if="item.type === 'text' || item.type === 'worker_count' || item.type === 'issue_date' || item.type === 'address_th' || item.type === 'address_en' || item.type === 'static_text'">
                                        <div class="w-100 h-100 d-flex flex-column justify-content-end overflow-hidden position-relative"
                                             :style="`pointer-events:none; font-family: 'THSarabunNew', sans-serif; font-size: ${item.fontSize || 16}pt; text-align: ${item.align || 'left'}; color:#000;`">
                                            <span class="d-block text-nowrap" x-text="item.type === 'static_text' ? (item.text || '{{ __('(empty fixed text)') }}') : item.label" style="line-height:1;"></span>
                                        </div>
                                    </template>

                                    <div class="pwct-resize-handle" style="top:0; left:0; cursor: nwse-resize;" @mousedown.stop="startResize($event, index, pageNum, 'nw')"></div>
                                    <div class="pwct-resize-handle" style="top:0; right:0; cursor: nesw-resize;" @mousedown.stop="startResize($event, index, pageNum, 'ne')"></div>
                                    <div class="pwct-resize-handle" style="bottom:0; right:0; cursor: nwse-resize;" @mousedown.stop="startResize($event, index, pageNum, 'se')"></div>
                                    <div class="pwct-resize-handle" style="bottom:0; left:0; cursor: nesw-resize;" @mousedown.stop="startResize($event, index, pageNum, 'sw')"></div>

                                    <div class="pwct-item-controls position-absolute bg-white shadow-sm rounded border d-flex gap-1 p-1">
                                        <button type="button" @click.stop="copyItem(index)" class="btn btn-sm btn-link text-secondary p-0" title="{{ __('Copy — links to the same value') }}"><i class="bi bi-copy small"></i></button>
                                        <button type="button" @click.stop="openSettings(index)" class="btn btn-sm btn-link text-secondary p-0" title="{{ __('Settings') }}"><i class="bi bi-gear-fill small"></i></button>
                                        <button type="button" @click.stop="removeItem(index)" class="btn btn-sm btn-link text-danger p-0" title="{{ __('Delete') }}"><i class="bi bi-trash-fill small"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Item Settings Modal -->
    <div class="modal fade" id="itemSettingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <template x-if="editingIndex !== null">
                    <div>
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Field Settings') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <template x-if="items[editingIndex].type !== 'static_text'">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Label shown to whoever fills this in') }} *</label>
                                    <input type="text" class="form-control" x-model="items[editingIndex].label" placeholder="{{ __('e.g. Employer Name (Thai)') }}">
                                </div>
                            </template>

                            <template x-if="items[editingIndex].type === 'static_text'">
                                <div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Internal name (for your reference only, not shown on the document)') }}</label>
                                        <input type="text" class="form-control" x-model="items[editingIndex].label" placeholder="{{ __('e.g. Company Address Line') }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Fixed text') }} *</label>
                                        <textarea class="form-control" rows="3" x-model="items[editingIndex].text" placeholder="{{ __('This exact text is printed on every issued document — the issuer cannot edit it.') }}"></textarea>
                                    </div>
                                </div>
                            </template>

                            <template x-if="items[editingIndex].type === 'issue_date'">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Date Format') }}</label>
                                    <select class="form-select" x-model="items[editingIndex].dateFormat">
                                        <option value="day">{{ __('Day') }}</option>
                                        <option value="month">{{ __('Month') }}</option>
                                        <option value="year">{{ __('Year (B.E.)') }}</option>
                                        <option value="full">{{ __('Full Date') }}</option>
                                        <option value="month_en">{{ __('Month (English)') }}</option>
                                        <option value="year_ce">{{ __('Year (C.E.)') }}</option>
                                        <option value="full_en">{{ __('Full Date (English)') }}</option>
                                    </select>
                                </div>
                            </template>

                            <template x-if="items[editingIndex].type === 'text' || items[editingIndex].type === 'worker_count' || items[editingIndex].type === 'issue_date' || items[editingIndex].type === 'address_th' || items[editingIndex].type === 'address_en' || items[editingIndex].type === 'static_text'">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">{{ __('Font Size') }}</label>
                                        <input type="number" class="form-control" x-model.number="items[editingIndex].fontSize" min="6" max="72">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">{{ __('Align') }}</label>
                                        <select class="form-select" x-model="items[editingIndex].align">
                                            <option value="left">{{ __('Left') }}</option>
                                            <option value="center">{{ __('Center') }}</option>
                                            <option value="right">{{ __('Right') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </template>

                            <template x-if="items[editingIndex].type === 'mark'">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">{{ __('Shape') }}</label>
                                        <select class="form-select" x-model="items[editingIndex].markShape">
                                            <option value="check">{{ __('Check') }} (✓)</option>
                                            <option value="cross">{{ __('Cross') }} (✗)</option>
                                            <option value="circle">{{ __('Circle') }} (○)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">{{ __('Color') }}</label>
                                        <input type="color" class="form-control form-control-color" x-model="items[editingIndex].color">
                                    </div>
                                </div>
                            </template>

                            <template x-if="items[editingIndex].type === 'image' || items[editingIndex].type === 'stamp' || items[editingIndex].type === 'signature'">
                                <div>
                                    <img :src="items[editingIndex].url" class="mb-2 border rounded" style="max-height: 100px; max-width: 100%;">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="replaceImage(editingIndex)">
                                            <i class="bi bi-arrow-repeat me-1"></i>{{ __('Replace Image') }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('Done') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Image/Stamp/Signature Upload Modal -->
    <div class="modal fade" id="imageUploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="uploadModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">{{ __('Select Image (JPG/PNG)') }}</label>
                    <input type="file" x-ref="uploadFileInput" class="form-control" accept="image/png, image/jpeg">
                    <div class="form-text">{{ __('You can drag and resize it on the document afterwards.') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" @click="uploadImage()" :disabled="isUploadingImage">
                        <span x-show="isUploadingImage" class="spinner-border spinner-border-sm me-1"></span>{{ __('Upload & Add') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    document.addEventListener('alpine:init', () => {
        Alpine.data('proworkerContractBuilder', () => {
            let _pdfDoc = null;

            return {
                templateName: @json($template->name ?? ''),
                currentPage: 1,
                totalPages: 1,
                scale: 1.5,
                pageDimensions: {},
                items: @json($template->field_mapping ?? []),
                metaData: @json($template->meta_data ?? []),
                isSaving: false,
                isUploadingImage: false,
                editingIndex: null,
                uploadModalTitle: '{{ __('Upload Image') }}',
                pendingUploadType: 'image',

                async init() {
                    if (!Array.isArray(this.items)) this.items = [];
                    this.items = this.items.map(item => ({ ...item, page: item.page ? parseInt(item.page) : 1 }));

                    const url = '{{ route("labor.contract-templates.file", $template) }}';
                    try {
                        const loadingTask = pdfjsLib.getDocument(url);
                        _pdfDoc = await loadingTask.promise;
                        this.totalPages = _pdfDoc.numPages;
                        await this.renderAllPages();
                    } catch (error) {
                        console.error('Error loading PDF:', error);
                    }
                },

                async renderAllPages() {
                    await this.$nextTick();
                    for (let i = 1; i <= this.totalPages; i++) {
                        await this.renderPage(i);
                    }
                },

                async renderPage(num) {
                    if (!_pdfDoc) return;
                    const page = await _pdfDoc.getPage(num);
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

                genKey() {
                    return 'field_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
                },

                // Returns only the items that belong to this page, each
                // paired with its ORIGINAL index into this.items (needed
                // so startMove()/openSettings()/removeItem() still mutate
                // the right entry). Previously every page rendered ALL
                // items and just toggled display:none on the ones that
                // didn't belong there — a field placed on page 1 would
                // still show at the same coordinates on every other page
                // whenever it didn't cleanly re-hide. Filtering here means
                // a page's DOM never contains another page's fields at
                // all, so there's nothing left to leak through.
                itemsForPage(pageNum) {
                    return this.items
                        .map((item, index) => [item, index])
                        .filter(([item]) => parseInt(item.page) === pageNum);
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
                    const xPct = (x / dims.width) * 100;
                    const yPct = (y / dims.height) * 100;

                    try {
                        const data = JSON.parse(event.dataTransfer.getData('text/plain'));

                        if (data.type === 'text') {
                            this.items.push({
                                type: 'text',
                                key: this.genKey(),
                                label: '{{ __('New Field') }}',
                                x: xPct, y: yPct, w: 20, h: 3, page: pageNum,
                                fontSize: 16, align: 'left', autoFit: false,
                            });
                            this.openSettings(this.items.length - 1);
                        } else if (data.type === 'static_text') {
                            this.items.push({
                                type: 'static_text',
                                key: this.genKey(),
                                label: '{{ __('Fixed Text') }}',
                                text: '',
                                x: xPct, y: yPct, w: 20, h: 3, page: pageNum,
                                fontSize: 16, align: 'left', autoFit: false,
                            });
                            this.openSettings(this.items.length - 1);
                        } else if (data.type === 'worker_count') {
                            this.items.push({
                                type: 'worker_count',
                                key: this.genKey(),
                                label: '{{ __('Worker Count') }}',
                                x: xPct, y: yPct, w: 20, h: 3, page: pageNum,
                                fontSize: 16, align: 'left', autoFit: false,
                            });
                            this.openSettings(this.items.length - 1);
                        } else if (data.type === 'issue_date') {
                            this.items.push({
                                type: 'issue_date',
                                key: this.genKey(),
                                label: '{{ __('Document Issue Date') }}',
                                dateFormat: data.dateFormat || 'full',
                                x: xPct, y: yPct, w: 20, h: 3, page: pageNum,
                                fontSize: 16, align: 'left', autoFit: false,
                            });
                            this.openSettings(this.items.length - 1);
                        } else if (data.type === 'address') {
                            const groupId = 'addr_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
                            this.items.push({
                                type: 'address_th',
                                key: groupId + '_th',
                                addressGroup: groupId,
                                label: '{{ __('Address (Thai)') }}',
                                x: xPct, y: yPct, w: 40, h: 3, page: pageNum,
                                fontSize: 14, align: 'left', autoFit: false,
                            });
                            this.items.push({
                                type: 'address_en',
                                key: groupId + '_en',
                                addressGroup: groupId,
                                label: '{{ __('Address (English)') }}',
                                x: xPct, y: Math.min(yPct + 4, 95), w: 40, h: 3, page: pageNum,
                                fontSize: 14, align: 'left', autoFit: false,
                            });
                        } else if (data.type === 'mark') {
                            this.items.push({
                                type: 'mark',
                                key: this.genKey(),
                                label: '{{ __('Mark') }}',
                                markShape: data.markShape || 'check',
                                color: data.color || '#16a34a',
                                x: xPct, y: yPct, w: 5, h: 5, page: pageNum,
                            });
                        }
                    } catch (e) {
                        console.error('Drop error', e);
                    }
                },

                openImageUploader(kind) {
                    this.pendingUploadType = kind;
                    this.uploadModalTitle = kind === 'stamp'
                        ? '{{ __('Stamp') }}'
                        : (kind === 'signature' ? '{{ __('Company Signature') }}' : '{{ __('Upload Image') }}');
                    if (this.$refs.uploadFileInput) this.$refs.uploadFileInput.value = '';
                    this._replaceTargetIndex = null;
                    new bootstrap.Modal(document.getElementById('imageUploadModal')).show();
                },

                replaceImage(index) {
                    this.pendingUploadType = this.items[index].type;
                    this.uploadModalTitle = '{{ __('Replace Image') }}';
                    if (this.$refs.uploadFileInput) this.$refs.uploadFileInput.value = '';
                    this._replaceTargetIndex = index;
                    bootstrap.Modal.getInstance(document.getElementById('itemSettingsModal'))?.hide();
                    new bootstrap.Modal(document.getElementById('imageUploadModal')).show();
                },

                async uploadImage() {
                    const fileInput = this.$refs.uploadFileInput;
                    if (!fileInput.files.length) return;

                    this.isUploadingImage = true;
                    const formData = new FormData();
                    formData.append('image', fileInput.files[0]);
                    formData.append('kind', this.pendingUploadType);

                    try {
                        const response = await fetch('{{ route("labor.contract-templates.upload-image") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: formData,
                        });
                        const data = await response.json();

                        if (response.ok && data.success) {
                            if (this._replaceTargetIndex !== null && this._replaceTargetIndex !== undefined) {
                                this.items[this._replaceTargetIndex].path = data.path;
                                this.items[this._replaceTargetIndex].url = data.url;
                            } else {
                                const sizes = {
                                    image: { w: 20, h: 20, label: '{{ __('Image') }}' },
                                    stamp: { w: 8, h: 8, label: '{{ __('Stamp') }}' },
                                    signature: { w: 10, h: 6, label: '{{ __('Company Signature') }}' },
                                };
                                const s = sizes[this.pendingUploadType] || sizes.image;
                                this.items.push({
                                    type: this.pendingUploadType,
                                    key: this.genKey(),
                                    label: s.label,
                                    url: data.url,
                                    path: data.path,
                                    x: 40, y: 40, w: s.w, h: s.h, page: this.currentPage,
                                });
                            }
                            bootstrap.Modal.getInstance(document.getElementById('imageUploadModal'))?.hide();
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.isUploadingImage = false;
                    }
                },

                startMove(event, index, pageNum) {
                    if (event.target.classList.contains('pwct-resize-handle')) return;
                    const item = this.items[index];
                    let dims = this.pageDimensions[pageNum];
                    let refX = event.clientX;
                    let refY = event.clientY;
                    let refLeft = (item.x / 100) * dims.width;
                    let refTop = (item.y / 100) * dims.height;

                    // Any field — text, image, stamp, signature, mark, address —
                    // can be dragged onto a different page mid-move, not just
                    // repositioned within the page it started on. Detected by
                    // checking which page-container the cursor is over on every
                    // move; when it changes, the item switches pages and the
                    // drag reference point resets so the motion stays smooth
                    // instead of jumping.
                    const onMouseMove = (e) => {
                        const hoverEl = document.elementFromPoint(e.clientX, e.clientY);
                        const hoverContainer = hoverEl ? hoverEl.closest('[id^="page-container-"]') : null;

                        if (hoverContainer) {
                            const hoverPage = parseInt(hoverContainer.id.replace('page-container-', ''), 10);
                            if (hoverPage !== item.page && this.pageDimensions[hoverPage]) {
                                item.page = hoverPage;
                                dims = this.pageDimensions[hoverPage];
                                const rect = hoverContainer.getBoundingClientRect();
                                refLeft = Math.max(0, Math.min(e.clientX - rect.left - ((item.w / 100) * dims.width) / 2, dims.width - ((item.w / 100) * dims.width)));
                                refTop = Math.max(0, Math.min(e.clientY - rect.top - ((item.h / 100) * dims.height) / 2, dims.height - ((item.h / 100) * dims.height)));
                                item.x = (refLeft / dims.width) * 100;
                                item.y = (refTop / dims.height) * 100;
                                refX = e.clientX;
                                refY = e.clientY;
                                return;
                            }
                        }

                        const dx = e.clientX - refX;
                        const dy = e.clientY - refY;
                        let newLeft = refLeft + dx;
                        let newTop = refTop + dy;
                        newLeft = Math.max(0, Math.min(newLeft, dims.width - ((item.w / 100) * dims.width)));
                        newTop = Math.max(0, Math.min(newTop, dims.height - ((item.h / 100) * dims.height)));
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

                startResize(event, index, pageNum, direction) {
                    const item = this.items[index];
                    const dims = this.pageDimensions[pageNum];
                    const startX = event.clientX;
                    const startY = event.clientY;
                    const currentLeftPx = (item.x / 100) * dims.width;
                    const currentTopPx = (item.y / 100) * dims.height;
                    const currentWidthPx = (item.w / 100) * dims.width;
                    const currentHeightPx = (item.h / 100) * dims.height;

                    const onMouseMove = (e) => {
                        const dx = e.clientX - startX;
                        const dy = e.clientY - startY;
                        let newLeft = currentLeftPx, newTop = currentTopPx, newWidth = currentWidthPx, newHeight = currentHeightPx;

                        if (direction.includes('e')) newWidth = Math.max(10, currentWidthPx + dx);
                        if (direction.includes('s')) newHeight = Math.max(8, currentHeightPx + dy);
                        if (direction.includes('w')) { newWidth = Math.max(10, currentWidthPx - dx); newLeft = currentLeftPx + dx; }
                        if (direction.includes('n')) { newHeight = Math.max(8, currentHeightPx - dy); newTop = currentTopPx + dy; }

                        item.x = (newLeft / dims.width) * 100;
                        item.y = (newTop / dims.height) * 100;
                        item.w = (newWidth / dims.width) * 100;
                        item.h = (newHeight / dims.height) * 100;
                    };
                    const onMouseUp = () => {
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    };
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                },

                openSettings(index) {
                    this.editingIndex = index;
                    new bootstrap.Modal(document.getElementById('itemSettingsModal')).show();
                },

                // Duplicates a placed field at a new position, keeping the
                // SAME key (and addressGroup, for address_th/address_en)
                // as the original — the issuance form only ever shows one
                // input per unique key (see _fields.blade.php), so
                // whatever the person filling out the contract types once
                // renders at every position that shares its key. Offset
                // so the copy is visibly distinct from the original;
                // opens settings immediately so it can be repositioned
                // right away, same as a freshly-dropped field.
                copyItem(index) {
                    const source = this.items[index];
                    const copy = JSON.parse(JSON.stringify(source));
                    copy.x = Math.min(90, source.x + 4);
                    copy.y = Math.min(90, source.y + 4);
                    this.items.push(copy);
                    this.openSettings(this.items.length - 1);
                },

                removeItem(index) {
                    const item = this.items[index];
                    if (item.addressGroup) {
                        this.items = this.items.filter(i => i.addressGroup !== item.addressGroup);
                    } else {
                        this.items.splice(index, 1);
                    }
                },

                async saveMapping() {
                    this.isSaving = true;
                    try {
                        const itemsToSave = JSON.parse(JSON.stringify(this.items));
                        const response = await fetch('{{ route("labor.contract-templates.update", $template) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ name: this.templateName, field_mapping: itemsToSave, meta_data: this.metaData }),
                        });
                        if (!response.ok) throw new Error('Save failed');
                    } catch (error) {
                        console.error(error);
                        alert('{{ __('Error saving template') }}: ' + error.message);
                    } finally {
                        this.isSaving = false;
                    }
                },
            };
        });
    });
</script>

@push('styles')
<style>
    .pwct-builder { height: calc(100vh - 200px); min-height: 520px; }
    .pwct-body { min-height: 0; }
    .pwct-sidebar { width: 280px; }
    .pwct-canvas-area { background-color: #e2e8f0; }
    .pwct-tool[draggable="true"] { cursor: grab; }
    .pwct-tool[role="button"] { cursor: pointer; }
    .pwct-tool:hover { background-color: #eff6ff !important; }

    .pwct-item { cursor: move; }
    .pwct-item-text { border-color: #3b82f6 !important; background-color: rgba(59,130,246,.08); }
    .pwct-item-address { border-color: #16a34a !important; background-color: rgba(22,163,74,.08); }
    .pwct-item-mark { border-color: #16a34a !important; background-color: rgba(22,163,74,.08); }
    .pwct-item-media { border-color: #dc2626 !important; background-color: rgba(220,38,38,.06); }

    .pwct-resize-handle { display: none; position: absolute; width: 8px; height: 8px; background: #fff; border: 1px solid #94a3b8; border-radius: 50%; z-index: 20; transform: translate(-50%, -50%); }
    .pwct-item:hover .pwct-resize-handle { display: block; }
    .pwct-item-controls { display: none; top: -2rem; left: 50%; transform: translateX(-50%); z-index: 50; }
    .pwct-item:hover .pwct-item-controls { display: flex; }
</style>
@endpush
@endsection
