<!-- resources/views/components/document-scanner.blade.php -->
<div x-data="documentScanner()"
     x-show="isOpen"
     @open-document-scanner.document="openScanner($event.detail)"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-90"
     style="display: none;"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <!-- Loading Overlay for OpenCV -->
    <div x-show="isLoading" class="absolute inset-0 z-[10000] flex flex-col items-center justify-center bg-black bg-opacity-80 text-white">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div x-text="loadingMessage">Loading Scanner Resources...</div>
    </div>

    <div class="bg-white w-full h-full md:w-[90%] md:h-[90%] md:rounded-lg shadow-xl flex flex-col relative overflow-hidden">

        <!-- Header -->
        <div class="bg-dark text-white p-3 flex justify-between items-center shrink-0">
            <h5 class="m-0 flex items-center gap-2">
                <i class="bi bi-camera-fill"></i>
                <span x-text="getHeaderTitle()">Document Scanner</span>
            </h5>
            <button @click="closeScanner()" class="btn btn-sm btn-outline-light border-0">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <!-- Main Content Area -->
        <div class="flex-grow bg-gray-100 relative overflow-hidden flex flex-col items-center justify-center p-0">

            <!-- Hidden File Input -->
            <input type="file"
                   x-ref="fileInput"
                   class="hidden"
                   accept="image/*,application/pdf"
                   multiple
                   @change="handleImport($event)">

            <!-- VIEW: CAMERA -->
            <div x-show="view === 'camera'" class="w-full h-full relative bg-black flex flex-col">
                <div class="relative w-full h-full flex items-center justify-center overflow-hidden">
                    <video x-ref="video" class="w-full h-full object-contain bg-black" autoplay playsinline></video>
                    <!-- Live Detection Overlay -->
                    <svg x-show="liveCorners.length === 4" class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 30;">
                        <polygon :points="getLivePolygonPoints()"
                                 :fill="isDocumentStable ? 'rgba(25, 135, 84, 0.2)' : 'rgba(255, 193, 7, 0.2)'"
                                 :stroke="isDocumentStable ? '#198754' : '#ffc107'"
                                 stroke-width="4"
                                 stroke-dasharray="5,5" />
                    </svg>
                </div>

                <!-- Mode Switcher -->
                <div class="absolute top-4 left-0 right-0 flex justify-center z-50">
                    <div class="bg-black/50 rounded-full p-1 flex shadow-lg border border-white/20">
                         <button @click="setMode('document')"
                                 :class="scanMode === 'document' ? 'bg-primary text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                                 class="px-4 py-1.5 rounded-full text-sm font-medium transition-all">
                            เอกสารทั่วไป
                         </button>
                         <button @click="setMode('id_card')"
                                 :class="scanMode === 'id_card' ? 'bg-primary text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                                 class="px-4 py-1.5 rounded-full text-sm font-medium transition-all flex items-center gap-1">
                            <i class="bi bi-person-badge-fill"></i> บัตรประชาชน
                         </button>
                    </div>
                </div>

                <!-- ID Card Prompt -->
                <div x-show="scanMode === 'id_card'"
                     class="absolute top-20 left-0 right-0 text-center pointer-events-none z-40 transition-opacity duration-300"
                     x-transition:enter="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <span class="bg-black/60 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-bold border border-white/20 shadow-lg">
                        <span x-show="capturedImages.length === 0"><i class="bi bi-person-bounding-box me-1"></i> ถ่ายด้านหน้า (Front)</span>
                        <span x-show="capturedImages.length === 1"><i class="bi bi-card-text me-1"></i> ถ่ายด้านหลัง (Back)</span>
                    </span>
                </div>

                <!-- Flash Effect -->
                <div x-show="flash"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-80"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-80"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 bg-white pointer-events-none z-50"></div>

                <!-- Camera Controls -->
                <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent flex justify-between items-center z-40">
                    <!-- Gallery Preview & Import (Bottom Left) -->
                    <div class="flex items-center gap-3 min-w-[120px]">
                        <!-- Import Button -->
                        <button @click="$refs.fileInput.click()"
                                class="btn btn-dark rounded-circle p-0 flex flex-col items-center justify-center shadow-lg border border-white/20 hover:bg-gray-800 transition-colors group"
                                style="width: 50px; height: 50px;"
                                title="นำเข้าไฟล์ (Images/PDF)">
                            <i class="bi bi-file-earmark-plus text-xl mb-0"></i>
                            <span class="text-[10px] leading-none opacity-80 group-hover:opacity-100">นำเข้า</span>
                        </button>

                        <div class="text-white text-sm cursor-pointer hover:underline" @click="if(capturedImages.length > 0) view = 'review'">
                            <div class="flex items-center gap-2">
                                <div class="relative" x-show="capturedImages.length > 0">
                                    <img :src="capturedImages[capturedImages.length-1]?.cropped" class="w-10 h-10 rounded border border-white object-cover">
                                    <span class="absolute -top-2 -right-2 badge bg-primary rounded-pill fs-7" x-text="capturedImages.length"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Capture Button -->
                    <button @click="captureImage()" :disabled="isProcessing"
                            class="btn btn-light rounded-circle p-1 shadow-lg border-4 border-gray-300 relative transform active:scale-95 transition-transform"
                            style="width: 70px; height: 70px;">
                         <div class="w-full h-full bg-danger rounded-circle flex items-center justify-center">
                             <span x-show="isProcessing" class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>
                         </div>
                    </button>

                    <!-- Finish Button (Bottom Right) -->
                    <div class="min-w-[80px] flex justify-end">
                        <button @click="finishCapture()"
                                class="btn btn-success text-white fw-bold px-4 rounded-pill shadow-lg border border-white/20"
                                x-show="canFinish()">
                            เสร็จสิ้น <i class="bi bi-check-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VIEW: REVIEW (Grid of taken images) -->
            <div x-show="view === 'review'" class="w-full h-full flex flex-col bg-gray-100 relative">

                <!-- Action Bar for Layouts -->
                <div x-show="selectedIndices.length > 0"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="translate-y-full opacity-0"
                     x-transition:enter-end="translate-y-0 opacity-100"
                     class="absolute bottom-[70px] left-0 right-0 z-20 px-3 flex justify-center pointer-events-none">

                     <div class="bg-white rounded-full shadow-xl border p-2 pointer-events-auto flex items-center gap-2 overflow-x-auto max-w-full">
                        <span class="text-sm font-bold px-2 text-gray-600 whitespace-nowrap">
                            <span x-text="selectedIndices.length"></span> รายการ:
                        </span>

                        <!-- 1 Image Options -->
                        <template x-if="selectedIndices.length === 1">
                            <div class="flex gap-1">
                                <button @click="generateLayout('full')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-arrows-fullscreen"></i> เต็ม A4</button>
                                <button @click="generateLayout('70')" class="btn btn-sm btn-outline-primary whitespace-nowrap">70%</button>
                                <button @click="generateLayout('passport')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-person-bounding-box"></i> Passport</button>
                                <button @click="generateLayout('card')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-credit-card"></i> ขนาดบัตร</button>
                            </div>
                        </template>

                        <!-- 2 Image Options -->
                        <template x-if="selectedIndices.length === 2">
                            <div class="flex gap-1">
                                <button @click="generateLayout('half_v')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-layout-split"></i> บน-ล่าง</button>
                                <button @click="generateLayout('half_h')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-layout-sidebar"></i> ซ้าย-ขวา</button>
                                <button @click="generateLayout('id_card_pair')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-person-badge"></i> หน้า-หลังบัตร</button>
                            </div>
                        </template>

                        <!-- 3+ Options -->
                        <template x-if="selectedIndices.length >= 3">
                            <button @click="generateLayout('grid')" class="btn btn-sm btn-outline-primary whitespace-nowrap"><i class="bi bi-grid-3x3"></i> Grid Layout</button>
                        </template>

                        <div class="w-px h-6 bg-gray-300 mx-1"></div>
                        <button @click="selectedIndices = []" class="btn btn-sm btn-light text-muted hover:text-dark"><i class="bi bi-x-lg"></i></button>
                     </div>
                </div>

                <div class="flex-grow overflow-y-auto p-3 pb-24">
                    <!-- Top Controls for Review Mode -->
                    <div class="flex justify-between items-center mb-3 px-1">
                        <div x-show="scanMode === 'id_card'">
                             <span class="badge bg-primary fs-6">
                                <i class="bi bi-info-circle me-1"></i> เลือกรูปภาพเพื่อจัดวางรูปแบบ
                             </span>
                        </div>
                        <div x-show="scanMode !== 'id_card'" class="flex gap-2 w-full justify-end">
                            <button x-show="!isSorting" @click="startSorting()" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-sort-numeric-down"></i> จัดลำดับหน้า
                            </button>

                            <div x-show="isSorting" class="flex gap-2 items-center bg-white p-1 rounded-pill shadow-sm border">
                                <span class="text-xs font-bold text-primary px-2">โหมดจัดลำดับ</span>
                                <button @click="resetSorting()" class="btn btn-xs btn-light rounded-circle" title="รีเซ็ต">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button @click="applySorting()" class="btn btn-xs btn-success text-white rounded-pill px-3">
                                    <i class="bi bi-check-lg"></i> ยืนยัน
                                </button>
                                <button @click="cancelSorting()" class="btn btn-xs btn-secondary rounded-pill px-2">
                                    ยกเลิก
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="(img, index) in capturedImages" :key="img.id">
                            <div class="relative group bg-white p-2 rounded shadow-sm hover:shadow-md transition-all duration-200"
                                 :class="(isSorting && sortOrder.includes(img.id)) ? 'ring-2 ring-success bg-green-50' : (selectedIndices.includes(index) ? 'ring-2 ring-primary bg-blue-50' : '')">

                                <!-- Normal Mode: Selection Checkbox -->
                                <div class="absolute top-2 left-2 z-10" x-show="!isSorting">
                                    <input type="checkbox"
                                           :checked="selectedIndices.includes(index)"
                                           @change="toggleSelection(index)"
                                           class="form-check-input w-5 h-5 cursor-pointer shadow-sm border-gray-300">
                                </div>

                                <!-- Sort Mode: Sequence Badge -->
                                <div class="absolute top-2 left-2 z-10" x-show="isSorting">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold shadow-md transition-all transform scale-100"
                                         :class="sortOrder.includes(img.id) ? 'bg-success' : 'bg-gray-300 scale-90 opacity-50'"
                                         @click.stop="toggleSort(img.id)">
                                        <span x-text="getSortIndex(img.id)"></span>
                                    </div>
                                </div>

                                <!-- Image -->
                                <img :src="img.cropped"
                                     class="w-full h-40 object-contain bg-gray-50 border rounded cursor-pointer"
                                     :class="isSorting ? (sortOrder.includes(img.id) ? '' : 'opacity-60 grayscale') : ''"
                                     @click="handleImageClick(index, img.id)">

                                <!-- Actions (Hidden during sort) -->
                                <div class="absolute top-1 right-1 flex gap-1 z-10" x-show="!isSorting">
                                    <button @click.stop="removeImage(index)" class="btn btn-sm btn-danger rounded-circle shadow-sm p-1 leading-none w-6 h-6 flex items-center justify-center">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div class="absolute bottom-1 right-1 z-10" x-show="!isSorting">
                                     <button @click.stop="startEdit(index)" class="btn btn-sm btn-primary shadow-sm py-1 px-2 text-xs rounded-pill">
                                        <i class="bi bi-crop"></i> ปรับแต่ง
                                    </button>
                                </div>

                                <!-- Sequence Number Display (Normal Mode) -->
                                <div class="absolute top-1 left-8 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded pointer-events-none transition-opacity" x-show="!isSorting">
                                    <span x-text="index + 1"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Add More Button (Universal - Hidden in sort mode) -->
                        <div x-show="!isSorting" @click="view = 'camera'; startCamera()" class="static-item flex flex-col items-center justify-center h-40 border-2 border-dashed border-gray-300 rounded bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 cursor-pointer transition-colors">
                            <i class="bi bi-plus-lg text-3xl mb-1"></i>
                            <span class="text-sm">ถ่ายเพิ่ม</span>
                        </div>

                        <!-- Import Button (Universal - Hidden in sort mode) -->
                        <div x-show="!isSorting" @click="$refs.fileInput.click()" class="static-item flex flex-col items-center justify-center h-40 border-2 border-dashed border-gray-300 rounded bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 cursor-pointer transition-colors">
                            <i class="bi bi-file-earmark-plus text-3xl mb-1"></i>
                            <span class="text-sm">นำเข้าไฟล์</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-white border-t flex justify-between items-center z-30 relative">
                     <button @click="view = 'camera'; startCamera()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> กลับไปถ่ายภาพ
                    </button>
                    <button @click="finalizeProcess()" class="btn btn-primary px-4" :disabled="!canFinish()">
                        <i class="bi bi-save"></i> บันทึกข้อมูล
                        <span>(<span x-text="capturedImages.length"></span>)</span>
                    </button>
                </div>
            </div>

            <!-- VIEW: LAYOUT EDITOR (Preview & Swap) -->
            <div x-show="view === 'layout_editor'" class="w-full h-full flex flex-col md:flex-row bg-gray-100 overflow-hidden">

                <!-- Sidebar: Order Controls -->
                <div class="w-full md:w-80 bg-white border-r flex flex-col shadow-lg z-10">
                    <div class="p-3 border-b bg-gray-50">
                        <h6 class="m-0 font-bold text-gray-700"><i class="bi bi-sort-numeric-down"></i> จัดลำดับรูปภาพ</h6>
                        <small class="text-gray-500">ลากหรือกดลูกศรเพื่อย้ายตำแหน่ง</small>
                    </div>

                    <div class="flex-grow overflow-y-auto p-2 space-y-2">
                        <template x-for="(item, index) in layoutSourceImages" :key="index">
                            <div class="flex items-center gap-2 p-2 bg-gray-50 border rounded hover:bg-white transition-colors">
                                <span class="badge bg-secondary rounded-pill" x-text="index + 1"></span>
                                <img :src="item.src" class="w-12 h-12 object-cover rounded border bg-white">

                                <div class="flex-grow"></div>

                                <div class="flex flex-col gap-1">
                                    <button @click="moveLayoutItem(index, -1)" :disabled="index === 0" class="btn btn-xs btn-outline-secondary py-0" title="ย้ายขึ้น">
                                        <i class="bi bi-chevron-up"></i>
                                    </button>
                                    <button @click="moveLayoutItem(index, 1)" :disabled="index === layoutSourceImages.length - 1" class="btn btn-xs btn-outline-secondary py-0" title="ย้ายลง">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-3 border-t bg-gray-50 flex justify-between">
                         <button @click="cancelLayout()" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> กลับ
                        </button>
                        <button @click="confirmLayout()" class="btn btn-success text-white">
                            <i class="bi bi-check-circle"></i> ยืนยัน
                        </button>
                    </div>
                </div>

                <!-- Main: Preview -->
                <div class="flex-grow bg-gray-200 relative flex flex-col">
                    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-auto">
                        <div class="bg-white shadow-2xl relative transition-all duration-300">
                             <img :src="layoutPreviewImage" class="max-w-full max-h-[80vh] border border-gray-300 block" style="min-width: 200px;">
                             <div class="absolute top-0 right-0 bg-primary text-white text-xs px-2 py-1 shadow-sm">
                                Preview (A4)
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: CROP -->
            <div x-show="view === 'crop'" class="w-full h-full flex flex-col bg-dark relative">
                <div class="flex-grow relative bg-gray-900" x-ref="cropContainer"
                     :style="isDragging ? 'overflow: hidden; touch-action: none;' : 'overflow: auto;'"
                     @wheel="handleCropZoomWheel($event)">
                    <div style="display: flex; align-items: center; justify-content: center; min-width: 100%; min-height: 100%; padding: 8px;">
                        <div x-ref="cropWrapper" style="position: relative; flex-shrink: 0;">
                            <canvas x-ref="cropCanvas" style="display: block; image-rendering: high-quality;"></canvas>

                            <!-- SVG Overlay for Handles -->
                            <svg x-ref="cropSvg"
                                 style="position: absolute; top: 0; left: 0; z-index: 10; pointer-events: none; overflow: visible; touch-action: none;">

                                <!-- Dim area outside crop region -->
                                <defs>
                                    <mask id="cropMask">
                                        <rect x="0" y="0" :width="canvasWidth" :height="canvasHeight" fill="white" />
                                        <polygon :points="getPolygonPoints()" fill="black" />
                                    </mask>
                                </defs>
                                <rect x="0" y="0" :width="canvasWidth" :height="canvasHeight" fill="rgba(0,0,0,0.4)" mask="url(#cropMask)" />

                                <!-- Border Lines (green) -->
                                <line :x1="corners[0].x" :y1="corners[0].y" :x2="corners[1].x" :y2="corners[1].y" stroke="#22c55e" stroke-width="5" />
                                <line :x1="corners[1].x" :y1="corners[1].y" :x2="corners[2].x" :y2="corners[2].y" stroke="#22c55e" stroke-width="5" />
                                <line :x1="corners[2].x" :y1="corners[2].y" :x2="corners[3].x" :y2="corners[3].y" stroke="#22c55e" stroke-width="5" />
                                <line :x1="corners[3].x" :y1="corners[3].y" :x2="corners[0].x" :y2="corners[0].y" stroke="#22c55e" stroke-width="5" />

                                <!-- Invisible thick edge hit areas for dragging (Edge 0-3) -->
                                <line :x1="corners[0].x" :y1="corners[0].y" :x2="corners[1].x" :y2="corners[1].y" stroke="transparent" stroke-width="30" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('edge', 0, $event)" @touchstart.prevent="startDrag('edge', 0, $event)" />
                                <line :x1="corners[1].x" :y1="corners[1].y" :x2="corners[2].x" :y2="corners[2].y" stroke="transparent" stroke-width="30" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('edge', 1, $event)" @touchstart.prevent="startDrag('edge', 1, $event)" />
                                <line :x1="corners[2].x" :y1="corners[2].y" :x2="corners[3].x" :y2="corners[3].y" stroke="transparent" stroke-width="30" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('edge', 2, $event)" @touchstart.prevent="startDrag('edge', 2, $event)" />
                                <line :x1="corners[3].x" :y1="corners[3].y" :x2="corners[0].x" :y2="corners[0].y" stroke="transparent" stroke-width="30" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('edge', 3, $event)" @touchstart.prevent="startDrag('edge', 3, $event)" />

                                <!-- Midpoint Square Handles (center of each edge) - invisible hit area + visible handle -->
                                <rect :x="(corners[0].x + corners[1].x) / 2 - 28" :y="(corners[0].y + corners[1].y) / 2 - 28" width="56" height="56" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('midpoint', 0, $event)" @touchstart.prevent="startDrag('midpoint', 0, $event)" />
                                <rect :x="(corners[0].x + corners[1].x) / 2 - 16" :y="(corners[0].y + corners[1].y) / 2 - 16" width="32" height="32" rx="5" :fill="activeDragMidpoint === 0 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="3.5" style="pointer-events: none;" />

                                <rect :x="(corners[1].x + corners[2].x) / 2 - 28" :y="(corners[1].y + corners[2].y) / 2 - 28" width="56" height="56" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('midpoint', 1, $event)" @touchstart.prevent="startDrag('midpoint', 1, $event)" />
                                <rect :x="(corners[1].x + corners[2].x) / 2 - 16" :y="(corners[1].y + corners[2].y) / 2 - 16" width="32" height="32" rx="5" :fill="activeDragMidpoint === 1 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="3.5" style="pointer-events: none;" />

                                <rect :x="(corners[2].x + corners[3].x) / 2 - 28" :y="(corners[2].y + corners[3].y) / 2 - 28" width="56" height="56" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('midpoint', 2, $event)" @touchstart.prevent="startDrag('midpoint', 2, $event)" />
                                <rect :x="(corners[2].x + corners[3].x) / 2 - 16" :y="(corners[2].y + corners[3].y) / 2 - 16" width="32" height="32" rx="5" :fill="activeDragMidpoint === 2 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="3.5" style="pointer-events: none;" />

                                <rect :x="(corners[3].x + corners[0].x) / 2 - 28" :y="(corners[3].y + corners[0].y) / 2 - 28" width="56" height="56" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('midpoint', 3, $event)" @touchstart.prevent="startDrag('midpoint', 3, $event)" />
                                <rect :x="(corners[3].x + corners[0].x) / 2 - 16" :y="(corners[3].y + corners[0].y) / 2 - 16" width="32" height="32" rx="5" :fill="activeDragMidpoint === 3 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="3.5" style="pointer-events: none;" />

                                <!-- Corner 0 (TL) -->
                                <circle :cx="corners[0].x" :cy="corners[0].y" r="50" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('corner', 0, $event)" @touchstart.prevent="startDrag('corner', 0, $event)" />
                                <circle x-show="activeDragIndex === 0" :cx="corners[0].x" :cy="corners[0].y" r="42" fill="rgba(34, 197, 94, 0.25)" stroke="#22c55e" stroke-width="2" style="pointer-events: none;" />
                                <circle :cx="corners[0].x" :cy="corners[0].y" :r="activeDragIndex === 0 ? 30 : 26" :fill="activeDragIndex === 0 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="4" style="pointer-events: none;" />

                                <!-- Corner 1 (TR) -->
                                <circle :cx="corners[1].x" :cy="corners[1].y" r="50" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('corner', 1, $event)" @touchstart.prevent="startDrag('corner', 1, $event)" />
                                <circle x-show="activeDragIndex === 1" :cx="corners[1].x" :cy="corners[1].y" r="42" fill="rgba(34, 197, 94, 0.25)" stroke="#22c55e" stroke-width="2" style="pointer-events: none;" />
                                <circle :cx="corners[1].x" :cy="corners[1].y" :r="activeDragIndex === 1 ? 30 : 26" :fill="activeDragIndex === 1 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="4" style="pointer-events: none;" />

                                <!-- Corner 2 (BR) -->
                                <circle :cx="corners[2].x" :cy="corners[2].y" r="50" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('corner', 2, $event)" @touchstart.prevent="startDrag('corner', 2, $event)" />
                                <circle x-show="activeDragIndex === 2" :cx="corners[2].x" :cy="corners[2].y" r="42" fill="rgba(34, 197, 94, 0.25)" stroke="#22c55e" stroke-width="2" style="pointer-events: none;" />
                                <circle :cx="corners[2].x" :cy="corners[2].y" :r="activeDragIndex === 2 ? 30 : 26" :fill="activeDragIndex === 2 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="4" style="pointer-events: none;" />

                                <!-- Corner 3 (BL) -->
                                <circle :cx="corners[3].x" :cy="corners[3].y" r="50" fill="transparent" style="pointer-events: auto; cursor: move; touch-action: none;" @mousedown="startDrag('corner', 3, $event)" @touchstart.prevent="startDrag('corner', 3, $event)" />
                                <circle x-show="activeDragIndex === 3" :cx="corners[3].x" :cy="corners[3].y" r="42" fill="rgba(34, 197, 94, 0.25)" stroke="#22c55e" stroke-width="2" style="pointer-events: none;" />
                                <circle :cx="corners[3].x" :cy="corners[3].y" :r="activeDragIndex === 3 ? 30 : 26" :fill="activeDragIndex === 3 ? '#16a34a' : '#22c55e'" stroke="white" stroke-width="4" style="pointer-events: none;" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Magnifier Loupe (canvas-based, fixed top-left, shows when dragging corner) -->
                <div x-show="isDragging && activeDragIndex !== -1"
                     x-ref="loupeContainer"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-75"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-75"
                     style="position: absolute; top: 12px; left: 12px; z-index: 9999; pointer-events: none; border-radius: 16px; overflow: hidden; border: 3px solid white; box-shadow: 0 6px 30px rgba(0,0,0,0.6), 0 0 0 2px rgba(34, 197, 94, 0.5); width: 160px; height: 160px;">
                    <canvas x-ref="loupeCanvas" width="320" height="320" style="width: 160px; height: 160px;"></canvas>
                    <!-- Crosshair overlay drawn on loupeCanvas via JS -->
                </div>

                <!-- Zoom Controls (floating) -->
                <div class="absolute top-16 right-3 z-20 flex flex-col gap-1 bg-black/70 rounded-lg p-1 shadow-lg">
                    <button @click="setCropZoom(cropZoom + 0.5)" class="btn btn-sm btn-dark border-0 text-white px-2" title="ซูมเข้า">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <div class="text-center text-white text-xs py-1 font-bold" x-text="Math.round(cropZoom * 100) + '%'"></div>
                    <button @click="setCropZoom(cropZoom - 0.5)" class="btn btn-sm btn-dark border-0 text-white px-2" title="ซูมออก">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <hr class="border-gray-600 my-0.5">
                    <button @click="setCropZoom(1)" class="btn btn-sm btn-dark border-0 text-white px-2" title="พอดีจอ">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <button @click="setCropZoom(2)" class="btn btn-sm btn-dark border-0 text-white px-2" title="ซูม 200%">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <!-- Filter Toolbar -->
                <div class="bg-black/90 p-2 flex justify-center gap-2 overflow-x-auto shrink-0 border-b border-gray-700">
                    <button @click="activeFilter = 'original'" :class="activeFilter === 'original' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-image"></i> ต้นฉบับ
                    </button>
                    <button @click="activeFilter = 'magic'" :class="activeFilter === 'magic' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-magic"></i> สแกนสี (Magic)
                    </button>
                    <button @click="activeFilter = 'scan_doc'" :class="activeFilter === 'scan_doc' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-file-earmark-check"></i> สแกนเอกสาร
                    </button>
                    <button @click="activeFilter = 'high_contrast'" :class="activeFilter === 'high_contrast' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-brightness-high"></i> เพิ่มความคมชัด
                    </button>
                    <button @click="activeFilter = 'bw'" :class="activeFilter === 'bw' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-file-earmark-text"></i> ขาวดำ (B/W)
                    </button>
                    <button @click="activeFilter = 'gray'" :class="activeFilter === 'gray' ? 'bg-primary text-white' : 'bg-dark text-gray-400 border-secondary'" class="btn btn-sm border flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-circle-half"></i> เทา
                    </button>
                </div>

                <div class="p-3 bg-black/80 flex justify-between items-center shrink-0 gap-2">
                    <button @click="cancelCrop()" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> ยกเลิก
                    </button>

                    <!-- Rotation Controls -->
                     <div class="flex items-center gap-2">
                        <button @click="rotateImage(-90)" class="btn btn-dark border-secondary text-white" title="หมุนซ้าย">
                             <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button @click="rotateImage(90)" class="btn btn-dark border-secondary text-white" title="หมุนขวา">
                             <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                         <button @click="resetToFull()" class="btn btn-outline-light">
                            <i class="bi bi-arrows-fullscreen"></i> <span class="hidden sm:inline">เต็มรูป</span>
                        </button>
                        <button @click="saveCropEdit()" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> <span class="hidden sm:inline">บันทึก</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Load Libraries (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script async src="https://docs.opencv.org/4.8.0/opencv.js" onload="document.dispatchEvent(new Event('opencv-loaded'))"></script>

<script src="{{ asset('js/document-scanner.js') }}?v={{ @filemtime(public_path('js/document-scanner.js')) }}"></script>
