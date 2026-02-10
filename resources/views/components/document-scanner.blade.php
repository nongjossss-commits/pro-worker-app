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
                <video x-ref="video" class="w-full h-full object-contain bg-black" autoplay playsinline></video>

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
                <div class="flex-grow relative overflow-hidden flex items-center justify-center bg-gray-900" x-ref="cropContainer">
                    <div class="relative shadow-2xl max-w-full max-h-full flex items-center justify-center" x-ref="cropWrapper">
                        <canvas x-ref="cropCanvas" class="block max-w-full max-h-full object-contain"></canvas>

                        <!-- SVG Overlay for Handles -->
                        <svg class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 10;">
                            <!-- Polygon Line -->
                            <polygon :points="getPolygonPoints()" fill="rgba(255, 255, 255, 0.2)" stroke="#0d6efd" stroke-width="2" />

                            <!-- Edge Click Areas (Transparent Thick Lines) -->
                            <!-- Top (0-1) -->
                            <line :x1="corners[0].x" :y1="corners[0].y" :x2="corners[1].x" :y2="corners[1].y" stroke="transparent" stroke-width="30" class="pointer-events-auto cursor-move" @mousedown="startDrag('edge', 0, $event)" @touchstart="startDrag('edge', 0, $event)" />
                            <!-- Right (1-2) -->
                            <line :x1="corners[1].x" :y1="corners[1].y" :x2="corners[2].x" :y2="corners[2].y" stroke="transparent" stroke-width="30" class="pointer-events-auto cursor-move" @mousedown="startDrag('edge', 1, $event)" @touchstart="startDrag('edge', 1, $event)" />
                            <!-- Bottom (2-3) -->
                            <line :x1="corners[2].x" :y1="corners[2].y" :x2="corners[3].x" :y2="corners[3].y" stroke="transparent" stroke-width="30" class="pointer-events-auto cursor-move" @mousedown="startDrag('edge', 2, $event)" @touchstart="startDrag('edge', 2, $event)" />
                            <!-- Left (3-0) -->
                            <line :x1="corners[3].x" :y1="corners[3].y" :x2="corners[0].x" :y2="corners[0].y" stroke="transparent" stroke-width="30" class="pointer-events-auto cursor-move" @mousedown="startDrag('edge', 3, $event)" @touchstart="startDrag('edge', 3, $event)" />

                            <!-- Handles (Larger) -->
                            <circle :cx="corners[0].x" :cy="corners[0].y" r="15" fill="#0d6efd" stroke="white" stroke-width="3" class="pointer-events-auto cursor-move shadow-sm" @mousedown="startDrag('corner', 0, $event)" @touchstart="startDrag('corner', 0, $event)" />
                            <circle :cx="corners[1].x" :cy="corners[1].y" r="15" fill="#0d6efd" stroke="white" stroke-width="3" class="pointer-events-auto cursor-move shadow-sm" @mousedown="startDrag('corner', 1, $event)" @touchstart="startDrag('corner', 1, $event)" />
                            <circle :cx="corners[2].x" :cy="corners[2].y" r="15" fill="#0d6efd" stroke="white" stroke-width="3" class="pointer-events-auto cursor-move shadow-sm" @mousedown="startDrag('corner', 2, $event)" @touchstart="startDrag('corner', 2, $event)" />
                            <circle :cx="corners[3].x" :cy="corners[3].y" r="15" fill="#0d6efd" stroke="white" stroke-width="3" class="pointer-events-auto cursor-move shadow-sm" @mousedown="startDrag('corner', 3, $event)" @touchstart="startDrag('corner', 3, $event)" />
                        </svg>
                    </div>
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
<script async src="https://docs.opencv.org/4.x/opencv.js" onload="document.dispatchEvent(new Event('opencv-loaded'))"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('documentScanner', () => ({
            isOpen: false,
            isLoading: false,
            isProcessing: false,
            loadingMessage: '',
            view: 'camera', // camera, review, crop
            scanMode: 'document', // 'document' or 'id_card'
            targetInputId: null,
            targetPreviewId: null,
            flash: false,

            // Reordering State
            isSorting: false,
            sortOrder: [], // Array of IDs in selected order

            // Camera
            stream: null,

            // Data
            // Structure: { id, original: dataUrl, cropped: dataUrl, corners: [{x,y}...] }
            capturedImages: [],
            selectedIndices: [], // Indices of selected images for layout
            currentEditIndex: -1,

            // Layout Editor State
            layoutSourceImages: [], // { src, originalIndex }
            layoutPreviewImage: null,
            layoutType: null,

            // Cropping State
            cvLoaded: false,
            corners: [{x:0, y:0}, {x:0, y:0}, {x:0, y:0}, {x:0, y:0}],
            canvasWidth: 0,
            canvasHeight: 0,
            imageWidth: 0,
            imageHeight: 0,
            scaleX: 1,
            scaleY: 1,
            activeDragIndex: -1,
            activeDragEdge: -1,
            previousMousePosition: { x: 0, y: 0 },
            rotation: 0, // Current rotation in degrees (0, 90, 180, 270)
            activeFilter: 'original', // original, bw, magic, gray
            editorSourceCanvas: null, // Store rotated source for preview

            init() {
                // Initialize PDF.js worker
                if (typeof pdfjsLib !== 'undefined') {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                }

                document.addEventListener('opencv-loaded', () => {
                    this.cvLoaded = true;
                    console.log('OpenCV Loaded');
                });

                // Mouse/Touch Move Handlers for Cropping
                window.addEventListener('mousemove', (e) => this.onDrag(e));
                window.addEventListener('mouseup', () => this.stopDrag());
                window.addEventListener('touchmove', (e) => this.onDrag(e), {passive: false});
                window.addEventListener('touchend', () => this.stopDrag());

                // Watch activeFilter for live preview
                this.$watch('activeFilter', () => {
                     if (this.view === 'crop') {
                         this.renderEditorCanvas();
                     }
                });
            },

            getHeaderTitle() {
                if(this.view === 'camera') return this.scanMode === 'id_card' ? 'สแกนบัตรประชาชน (Scan ID Card)' : 'สแกนเอกสารทั่วไป (Scan Document)';
                if(this.view === 'review') return 'ตรวจสอบเอกสาร';
                if(this.view === 'crop') return 'ปรับมุมเอกสาร';
                return 'Document Scanner';
            },

            setMode(mode) {
                // Simplified Mode Switching - No data loss
                this.scanMode = mode;
            },

            async openScanner(detail) {
                this.targetInputId = detail.inputId;
                this.targetPreviewId = detail.previewId || null;
                this.isOpen = true;
                this.capturedImages = [];
                this.view = 'camera';
                this.scanMode = 'document'; // Default

                // Handle Edit Mode (Initial File)
                if (detail.initialUrl) {
                    await this.loadInitialFile(detail.initialUrl);
                }

                if(!this.cvLoaded) {
                     this.isLoading = true;
                     this.loadingMessage = 'กำลังโหลดระบบประมวลผลภาพ...';
                     // Check regularly if loaded
                     let checkInterval = setInterval(() => {
                         if(typeof cv !== 'undefined') {
                             this.cvLoaded = true;
                             this.isLoading = false;
                             clearInterval(checkInterval);
                             if (!detail.initialUrl) this.startCamera();
                         }
                     }, 500);

                     // Fallback timeout
                     setTimeout(() => {
                         if(!this.cvLoaded && this.isLoading) {
                             this.isLoading = false;
                             alert('Cannot load Image Processing Engine (OpenCV). Basic features only.');
                             if (!detail.initialUrl) this.startCamera();
                         }
                     }, 10000);
                } else {
                    if (!detail.initialUrl) this.startCamera();
                }
            },

            async loadInitialFile(url) {
                this.isLoading = true;
                this.loadingMessage = 'Loading file...';
                try {
                    const response = await fetch(url);
                    const blob = await response.blob();
                    const mimeType = blob.type;
                    const filename = url.split('/').pop() || 'file';
                    const file = new File([blob], filename, { type: mimeType });

                    if (mimeType.startsWith('image/')) {
                        await this.processImageFile(file);
                    } else if (mimeType === 'application/pdf') {
                        await this.processPdfFile(file);
                    } else {
                         // Try image as fallback if mime is generic binary
                         await this.processImageFile(file);
                    }
                    this.view = 'review';
                } catch (e) {
                    console.error("Load Initial File Error", e);
                    alert("Cannot load file for editing: " + e.message);
                } finally {
                    this.isLoading = false;
                }
            },

            closeScanner() {
                this.stopCamera();
                this.isOpen = false;
            },

            async handleImport(event) {
                const files = event.target.files;
                if (!files || files.length === 0) return;

                this.isLoading = true;
                this.loadingMessage = 'กำลังนำเข้าไฟล์...';

                try {
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];

                        if (file.type.startsWith('image/')) {
                            await this.processImageFile(file);
                        } else if (file.type === 'application/pdf') {
                            await this.processPdfFile(file);
                        }
                    }

                    this.stopCamera();
                    this.view = 'review';

                    // Reset input
                    event.target.value = '';
                } catch (err) {
                    console.error("Import Error:", err);
                    alert("เกิดข้อผิดพลาดในการนำเข้าไฟล์: " + err.message);
                } finally {
                    this.isLoading = false;
                }
            },

            async processImageFile(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = async (e) => {
                        const dataUrl = e.target.result;
                        const img = await this.loadImage(dataUrl);

                        this.capturedImages.push({
                            id: Date.now() + Math.random(),
                            original: dataUrl,
                            cropped: dataUrl,
                            corners: this.getDefaultCorners(img.width, img.height),
                            isFound: false
                        });
                        resolve();
                    };
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            },

            async processPdfFile(file) {
                if (typeof pdfjsLib === 'undefined') {
                    throw new Error('ระบบยังไม่พร้อมสำหรับการนำเข้า PDF กรุณารอสักครู่หรือรีเฟรชหน้าเว็บ');
                }

                const arrayBuffer = await file.arrayBuffer();
                const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
                const pdf = await loadingTask.promise;

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    this.loadingMessage = `กำลังแปลง PDF หน้าที่ ${pageNum}/${pdf.numPages}...`;

                    const page = await pdf.getPage(pageNum);
                    const viewport = page.getViewport({ scale: 2.0 }); // High quality

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    await page.render({ canvasContext: context, viewport: viewport }).promise;

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);

                    this.capturedImages.push({
                        id: Date.now() + Math.random(),
                        original: dataUrl,
                        cropped: dataUrl,
                        corners: this.getDefaultCorners(canvas.width, canvas.height),
                        isFound: false
                    });
                }
            },

            async startCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'environment',
                            width: { ideal: 1920 },
                            height: { ideal: 1080 }
                        },
                        audio: false
                    });
                    this.$refs.video.srcObject = this.stream;
                } catch (err) {
                    console.error("Camera Error:", err);
                    alert("Cannot access camera: " + err.message);
                }
            },

            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
            },

            canFinish() {
                if (this.capturedImages.length === 0) return false;
                if (this.scanMode === 'id_card' && this.capturedImages.length < 2) return false;
                return true;
            },

            // --- SMART CAPTURE LOGIC ---

            captureImage() {
                if (this.isProcessing) return;
                // Removed ID Card Limit Check to allow flexible scanning

                this.isProcessing = true;
                this.flash = true;
                setTimeout(() => this.flash = false, 150);

                const video = this.$refs.video;
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);

                const originalDataUrl = canvas.toDataURL('image/jpeg', 0.9);

                // PROCESS IMAGE
                try {
                    if (typeof cv !== 'undefined') {
                        // 1. Detect Edges (SMARTER)
                        const src = cv.imread(canvas);
                        const { corners, found } = this.detectDocument(src);

                        // 2. Warp (Crop)
                        const croppedDataUrl = this.performWarp(src, corners, canvas.width, canvas.height);

                        // 3. Store
                        this.capturedImages.push({
                            id: Date.now(),
                            original: originalDataUrl,
                            cropped: croppedDataUrl,
                            corners: corners, // Store scaled to original image
                            isFound: found
                        });

                        src.delete();
                    } else {
                        // Fallback
                        this.capturedImages.push({
                            id: Date.now(),
                            original: originalDataUrl,
                            cropped: originalDataUrl,
                            corners: this.getDefaultCorners(canvas.width, canvas.height),
                            isFound: false
                        });
                    }

                    // Auto-advance for ID card
                    if(this.scanMode === 'id_card' && this.capturedImages.length === 2) {
                        setTimeout(() => {
                            this.finishCapture();
                        }, 500);
                    }

                } catch (e) {
                    console.error("Capture Processing Error:", e);
                    // Fallback on error
                    this.capturedImages.push({
                        id: Date.now(),
                        original: originalDataUrl,
                        cropped: originalDataUrl,
                        corners: this.getDefaultCorners(canvas.width, canvas.height),
                        isFound: false
                    });
                } finally {
                    this.isProcessing = false;
                }
            },

            detectDocument(src) {
                const width = src.cols;
                const height = src.rows;
                let bestCorners = [];
                let isFound = false;

                try {
                    // 1. Preprocessing (Resize for speed & noise reduction)
                    let processingSrc = src;
                    let scale = 1;
                    const maxDim = 800;

                    if (Math.max(width, height) > maxDim) {
                        scale = maxDim / Math.max(width, height);
                        processingSrc = new cv.Mat();
                        cv.resize(src, processingSrc, new cv.Size(0, 0), scale, scale, cv.INTER_AREA);
                    }

                    const pWidth = processingSrc.cols;
                    const pHeight = processingSrc.rows;

                    const gray = new cv.Mat();
                    cv.cvtColor(processingSrc, gray, cv.COLOR_RGBA2GRAY, 0);

                    // 2. Denoise (Gaussian Blur)
                    const blurred = new cv.Mat();
                    cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);

                    // 3. Edge Detection (Canny is generally more structural than simple thresholding)
                    const edges = new cv.Mat();
                    // Thresholds: Lower 75, Upper 200 is a standard starting point
                    cv.Canny(blurred, edges, 75, 200);

                    // 4. Dilate to connect broken edges
                    const kernel = cv.Mat.ones(3, 3, cv.CV_8U);
                    const dilated = new cv.Mat();
                    cv.dilate(edges, dilated, kernel);

                    // 5. Find Contours
                    const contours = new cv.MatVector();
                    const hierarchy = new cv.Mat();
                    cv.findContours(dilated, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

                    // 6. Find Best Quadrilateral
                    let maxArea = 0;
                    let bestCandidate = null;
                    const minArea = pWidth * pHeight * 0.10; // Must be at least 10%

                    for (let i = 0; i < contours.size(); ++i) {
                        const cnt = contours.get(i);
                        const area = cv.contourArea(cnt);

                        if (area < minArea) continue;

                        let peri = cv.arcLength(cnt, true);
                        let approx = new cv.Mat();
                        cv.approxPolyDP(cnt, approx, 0.02 * peri, true);

                        // Looking for 4 corners and convexity
                        if (approx.rows === 4 && cv.isContourConvex(approx)) {
                            if (area > maxArea) {
                                maxArea = area;
                                bestCandidate = [];
                                for(let j=0; j<4; j++) {
                                    bestCandidate.push({
                                        x: approx.data32S[j*2] / scale,
                                        y: approx.data32S[j*2+1] / scale
                                    });
                                }
                            }
                        }

                        approx.delete();
                    }

                    // Clean up
                    if (scale !== 1) processingSrc.delete();
                    gray.delete(); blurred.delete(); edges.delete(); dilated.delete();
                    kernel.delete(); contours.delete(); hierarchy.delete();

                    if (bestCandidate) {
                        bestCorners = this.sortPoints(bestCandidate);
                        isFound = true;
                    } else {
                        bestCorners = this.getDefaultCorners(width, height);
                        isFound = false;
                    }

                } catch(e) {
                    console.error("Detection Logic Error:", e);
                    bestCorners = this.getDefaultCorners(width, height);
                    isFound = false;
                }

                return { corners: bestCorners, found: isFound };
            },

            performWarp(src, corners, width, height, filterType = 'original') {
                 // Convert corners array to flat array for OpenCV
                 const srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    corners[0].x, corners[0].y,
                    corners[1].x, corners[1].y,
                    corners[2].x, corners[2].y,
                    corners[3].x, corners[3].y
                ]);

                // Calculate dimensions of the new cropped image
                const wTop = Math.hypot(corners[1].x - corners[0].x, corners[1].y - corners[0].y);
                const wBot = Math.hypot(corners[2].x - corners[3].x, corners[2].y - corners[3].y);
                const hLeft = Math.hypot(corners[3].x - corners[0].x, corners[3].y - corners[0].y);
                const hRight = Math.hypot(corners[2].x - corners[1].x, corners[2].y - corners[1].y);

                const maxWidth = Math.round(Math.max(wTop, wBot));
                const maxHeight = Math.round(Math.max(hLeft, hRight));

                const dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    0, 0,
                    maxWidth, 0,
                    maxWidth, maxHeight,
                    0, maxHeight
                ]);

                const M = cv.getPerspectiveTransform(srcTri, dstTri);
                const dst = new cv.Mat();
                cv.warpPerspective(src, dst, M, new cv.Size(maxWidth, maxHeight), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());

                // Apply Filter
                let finalDst = dst;
                if (filterType && filterType !== 'original') {
                    finalDst = this.applyFilter(dst, filterType);
                }

                // Draw to temp canvas
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = maxWidth;
                tempCanvas.height = maxHeight;
                cv.imshow(tempCanvas, finalDst);
                const dataUrl = tempCanvas.toDataURL('image/jpeg', 0.95);

                // Clean up
                if (finalDst !== dst) finalDst.delete();
                srcTri.delete(); dstTri.delete(); M.delete(); dst.delete();
                return dataUrl;
            },

            getDefaultCorners(w, h) {
                // Default to FULL IMAGE (0,0 to w,h)
                // This ensures that if detection fails, we don't crop out important edges.
                return [
                    {x: 0, y: 0},
                    {x: w, y: 0},
                    {x: w, y: h},
                    {x: 0, y: h}
                ];
            },

            finishCapture() {
                this.stopCamera();
                this.view = 'review';
            },

            removeImage(index) {
                // Update selection indices before removing
                // If removed index is selected, deselect it
                // If removed index < selected index, shift selected index down
                this.selectedIndices = this.selectedIndices.filter(i => i !== index).map(i => i > index ? i - 1 : i);

                this.capturedImages.splice(index, 1);
                if(this.capturedImages.length === 0) {
                    this.view = 'camera';
                    this.startCamera();
                }
            },

            toggleSelection(index) {
                if (this.selectedIndices.includes(index)) {
                    this.selectedIndices = this.selectedIndices.filter(i => i !== index);
                } else {
                    this.selectedIndices.push(index);
                }
            },

            // --- SORTING LOGIC ---
            startSorting() {
                this.isSorting = true;
                this.sortOrder = [];
                this.selectedIndices = []; // Clear layout selection to avoid confusion
            },

            cancelSorting() {
                this.isSorting = false;
                this.sortOrder = [];
            },

            resetSorting() {
                this.sortOrder = [];
            },

            handleImageClick(index, id) {
                if (this.isSorting) {
                    this.toggleSort(id);
                } else {
                    this.toggleSelection(index);
                }
            },

            toggleSort(id) {
                const idx = this.sortOrder.indexOf(id);
                if (idx === -1) {
                    // Add to end
                    this.sortOrder.push(id);
                } else {
                    // Only allow removing if it's the LAST item
                    if (idx === this.sortOrder.length - 1) {
                        this.sortOrder.pop();
                    } else {
                        // Optional: Feedback that you can't remove middle items
                        // alert("Can only undo the last selection");
                    }
                }
            },

            getSortIndex(id) {
                const idx = this.sortOrder.indexOf(id);
                return idx !== -1 ? idx + 1 : '';
            },

            applySorting() {
                if (this.sortOrder.length === 0) {
                    this.cancelSorting();
                    return;
                }

                // Create new array based on sortOrder
                const newImages = [];

                // 1. Add sorted items
                this.sortOrder.forEach(id => {
                    const img = this.capturedImages.find(i => i.id === id);
                    if (img) newImages.push(img);
                });

                // 2. Append remaining items (that weren't selected)
                this.capturedImages.forEach(img => {
                    if (!this.sortOrder.includes(img.id)) {
                        newImages.push(img);
                    }
                });

                this.capturedImages = newImages;
                this.cancelSorting();
            },

            async generateLayout(type) {
                this.isLoading = true;
                this.loadingMessage = 'Preparing Layout...';

                try {
                    // 1. Prepare Source Images
                    this.layoutSourceImages = [];
                    for(const idx of this.selectedIndices) {
                        // We store the data URL directly in layoutSourceImages
                        this.layoutSourceImages.push({
                            src: this.capturedImages[idx].cropped,
                            originalIndex: idx
                        });
                    }
                    this.layoutType = type;

                    // 2. Decide Flow
                    // If multiple images, go to Layout Editor to allow swapping
                    // If single image, we can arguably skip editor, but maybe user wants to see the preview (e.g. passport placement)?
                    // Let's ALWAYS show editor for consistency, or at least for complex ones.
                    // User request: "User must be able to move... in case they want to swap".
                    // This implies >1 image.

                    if (this.layoutSourceImages.length > 1) {
                         this.view = 'layout_editor';
                         this.updateLayoutPreview();
                    } else {
                        // Direct Save for single image (faster workflow)
                         const img = await this.loadImage(this.layoutSourceImages[0].src);
                         const layoutDataUrl = this.renderLayoutToCanvas(type, [img]);
                         this.saveLayoutResult(layoutDataUrl);
                    }

                } catch(e) {
                    console.error("Layout Error", e);
                    alert("Error generating layout: " + e.message);
                } finally {
                    this.isLoading = false;
                }
            },

            async updateLayoutPreview() {
                // Convert source objects to Image elements
                const imgs = [];
                for(const item of this.layoutSourceImages) {
                    const i = await this.loadImage(item.src);
                    imgs.push(i);
                }

                this.layoutPreviewImage = this.renderLayoutToCanvas(this.layoutType, imgs);
            },

            moveLayoutItem(index, direction) {
                const newIndex = index + direction;
                if (newIndex < 0 || newIndex >= this.layoutSourceImages.length) return;

                // Swap
                const temp = this.layoutSourceImages[index];
                this.layoutSourceImages[index] = this.layoutSourceImages[newIndex];
                this.layoutSourceImages[newIndex] = temp;

                this.updateLayoutPreview();
            },

            cancelLayout() {
                this.view = 'review';
                this.layoutSourceImages = [];
                this.layoutPreviewImage = null;
            },

            confirmLayout() {
                if (this.layoutPreviewImage) {
                    this.saveLayoutResult(this.layoutPreviewImage);
                    this.cancelLayout(); // Back to review
                }
            },

            saveLayoutResult(dataUrl) {
                this.capturedImages.push({
                    id: Date.now(),
                    original: dataUrl,
                    cropped: dataUrl,
                    corners: this.getDefaultCorners(1240, 1754),
                    isFound: true
                });
                // Optional: Scroll to bottom
            },

            renderLayoutToCanvas(type, images) {
                const a4w = 1240;
                const a4h = 1754;
                const canvas = document.createElement('canvas');
                canvas.width = a4w;
                canvas.height = a4h;
                const ctx = canvas.getContext('2d');

                // Fill White Background
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, a4w, a4h);

                const margin = 40;

                // Helper to draw image fitting within a box
                const drawFit = (img, x, y, w, h) => {
                    const scale = Math.min(w / img.width, h / img.height);
                    const drawW = img.width * scale;
                    const drawH = img.height * scale;
                    const drawX = x + (w - drawW) / 2;
                    const drawY = y + (h - drawH) / 2;
                    ctx.drawImage(img, drawX, drawY, drawW, drawH);
                };

                if (type === 'full') {
                    // 1 Image Full Page (with margin)
                    if(images[0]) drawFit(images[0], margin, margin, a4w - 2*margin, a4h - 2*margin);
                }
                else if (type === '70') {
                    // 1 Image 70% Scale (Relative to A4 width)
                    if(images[0]) {
                        const targetW = a4w * 0.7;
                        const targetH = a4h * 0.7; // Bound by 70% height too?
                        // "70% of page" usually means 70% size.
                        drawFit(images[0], (a4w - targetW)/2, (a4h - targetH)/2, targetW, targetH);
                    }
                }
                else if (type === 'passport') {
                    // Passport (Spread/Open ID-3): 176mm x 125mm @ 150DPI
                    // W: (176 / 25.4) * 150 = 1039 px
                    // H: (125 / 25.4) * 150 = 738 px
                    const pw = 1039;
                    const ph = 738;
                    // Center it
                    if(images[0]) drawFit(images[0], (a4w - pw)/2, (a4h - ph)/2, pw, ph);
                }
                else if (type === 'card') {
                    // Credit Card (ID-1): 85.6mm x 54mm @ 150DPI
                    // W: (85.6 / 25.4) * 150 = 506 px
                    // H: (54 / 25.4) * 150 = 319 px
                    const cw = 506;
                    const ch = 319;
                    if(images[0]) drawFit(images[0], (a4w - cw)/2, (a4h - ch)/2, cw, ch);
                }
                else if (type === 'half_v') {
                    // Top / Bottom
                    const hHalf = a4h / 2;
                    if(images[0]) drawFit(images[0], margin, margin, a4w - 2*margin, hHalf - 2*margin);
                    if(images[1]) drawFit(images[1], margin, hHalf + margin, a4w - 2*margin, hHalf - 2*margin);

                    // Divider Line (Optional - Visual Aid)
                    ctx.beginPath();
                    ctx.moveTo(0, hHalf);
                    ctx.lineTo(a4w, hHalf);
                    ctx.strokeStyle = '#e5e7eb';
                    ctx.lineWidth = 2;
                    // ctx.stroke(); // Maybe don't draw lines on the document itself
                }
                else if (type === 'half_h') {
                    // Left / Right
                    const wHalf = a4w / 2;
                    if(images[0]) drawFit(images[0], margin, margin, wHalf - 2*margin, a4h - 2*margin);
                    if(images[1]) drawFit(images[1], wHalf + margin, margin, wHalf - 2*margin, a4h - 2*margin);
                }
                else if (type === 'id_card_pair') {
                    // Specific ID Card Layout (Center Top / Center Bottom)
                    // Standard ID-1 Size: 506 x 319 px
                    const cardW = 506;
                    const cardH = 319;

                    const topY = a4h/4 - cardH/2;
                    const botY = a4h*3/4 - cardH/2;

                    if(images[0]) drawFit(images[0], (a4w - cardW)/2, topY, cardW, cardH);
                    if(images[1]) drawFit(images[1], (a4w - cardW)/2, botY, cardW, cardH);

                    // Labels
                    ctx.font = '30px Arial';
                    ctx.fillStyle = '#333';
                    ctx.textAlign = 'center';
                    if(images[0]) ctx.fillText('ด้านหน้า (Front)', a4w/2, topY - 20);
                    if(images[1]) ctx.fillText('ด้านหลัง (Back)', a4w/2, botY - 20);
                }
                else if (type === 'grid') {
                    // 2x2 Grid for 3 or 4 images
                    // 3 images -> 2 top, 1 bottom center? Or 3 vertical?
                    // "3 รูปต่อ 1 หน้า"
                    // Common: Top-Left, Top-Right, Bottom-Left, (Bottom-Right)
                    const wHalf = a4w / 2;
                    const hHalf = a4h / 2;

                    // Grid 1 (TL)
                    if(images[0]) drawFit(images[0], margin, margin, wHalf - 2*margin, hHalf - 2*margin);
                    // Grid 2 (TR)
                    if(images[1]) drawFit(images[1], wHalf + margin, margin, wHalf - 2*margin, hHalf - 2*margin);
                    // Grid 3 (BL)
                    if(images[2]) drawFit(images[2], margin, hHalf + margin, wHalf - 2*margin, hHalf - 2*margin);
                    // Grid 4 (BR)
                    if(images[3]) drawFit(images[3], wHalf + margin, hHalf + margin, wHalf - 2*margin, hHalf - 2*margin);
                }

                return canvas.toDataURL('image/jpeg', 0.9);
            },

            // --- EDIT / CROP LOGIC ---

            startEdit(index) {
                this.currentEditIndex = index;
                this.view = 'crop';
                this.rotation = 0; // Reset rotation
                this.activeFilter = 'original'; // Reset filter
                const item = this.capturedImages[index];

                this.$nextTick(() => {
                    this.loadImageForCrop(item.original, item.corners);
                });
            },

            rotateImage(degrees) {
                // 1. Get current normalized corners before rotation
                const oldW = this.canvasWidth;
                const oldH = this.canvasHeight;
                const normCorners = this.corners.map(c => ({
                    u: c.x / oldW,
                    v: c.y / oldH
                }));

                // 2. Update Rotation
                this.rotation = (this.rotation + degrees) % 360;
                if(this.rotation < 0) this.rotation += 360;

                // 3. Transform Normalized Corners
                // 90 deg CW: (u, v) -> (1-v, u)
                // -90 deg CCW: (u, v) -> (v, 1-u)
                let newNormCorners = [];
                if (degrees === 90) {
                    newNormCorners = normCorners.map(c => ({ u: 1 - c.v, v: c.u }));
                    // FIX: Shift array to maintain TL, TR, BR, BL order
                    // Old TL (0) -> New TR. We need New TL to be at index 0.
                    // The point that became New TL is Old BL (3).
                    // So we move last element to first.
                    if (newNormCorners.length === 4) newNormCorners.unshift(newNormCorners.pop());
                } else if (degrees === -90) {
                    newNormCorners = normCorners.map(c => ({ u: c.v, v: 1 - c.u }));
                    // FIX: Shift array to maintain TL, TR, BR, BL order
                    // Old TL (0) -> New BL. We need New TL to be at index 0.
                    // The point that became New TL is Old TR (1).
                    // So we move first element to last.
                    if (newNormCorners.length === 4) newNormCorners.push(newNormCorners.shift());
                } else {
                    newNormCorners = normCorners;
                }

                // Re-render
                const item = this.capturedImages[this.currentEditIndex];
                this.loadImageForCrop(item.original, newNormCorners, false, true);
            },

            loadImageForCrop(src, savedCorners, forceFull = false, isNormalized = false) {
                const img = new Image();
                img.onload = () => {
                    // Handle Rotation Logic (Virtual Canvas)
                    // If rotation is not 0, we must draw the image rotated onto a temporary canvas
                    // and use that as our source "imageWidth/Height"

                    let srcWidth = img.width;
                    let srcHeight = img.height;

                    // Create an intermediate canvas for rotation if needed
                    const rotCanvas = document.createElement('canvas');
                    const rotCtx = rotCanvas.getContext('2d');

                    if (this.rotation % 180 !== 0) {
                        rotCanvas.width = srcHeight;
                        rotCanvas.height = srcWidth;
                    } else {
                        rotCanvas.width = srcWidth;
                        rotCanvas.height = srcHeight;
                    }

                    rotCtx.translate(rotCanvas.width/2, rotCanvas.height/2);
                    rotCtx.rotate(this.rotation * Math.PI / 180);
                    rotCtx.drawImage(img, -srcWidth/2, -srcHeight/2);

                    // Now use rotCanvas as the source
                    this.imageWidth = rotCanvas.width;
                    this.imageHeight = rotCanvas.height;

                    // SAVE THIS for preview updates
                    this.editorSourceCanvas = rotCanvas;

                    const canvas = this.$refs.cropCanvas;
                    const container = this.$refs.cropContainer;

                    // Simple fit logic
                    const scale = Math.min(container.clientWidth / this.imageWidth, container.clientHeight / this.imageHeight) * 0.9;

                    this.canvasWidth = this.imageWidth * scale;
                    this.canvasHeight = this.imageHeight * scale;

                    canvas.width = this.canvasWidth;
                    canvas.height = this.canvasHeight;

                    // Render with filter check
                    this.renderEditorCanvas();

                    this.scaleX = this.canvasWidth / this.imageWidth;
                    this.scaleY = this.canvasHeight / this.imageHeight;

                    if (forceFull) {
                        this.resetToFull();
                    } else {
                        if (isNormalized) {
                            // Map normalized (0..1) to New Canvas Dimensions
                            this.corners = savedCorners.map(c => ({
                                x: c.u * this.canvasWidth,
                                y: c.v * this.canvasHeight
                            }));
                        } else {
                            // Map Original Image Coordinates to Canvas
                            this.corners = savedCorners.map(c => ({
                                x: c.x * this.scaleX,
                                y: c.y * this.scaleY
                            }));
                        }
                    }
                };
                img.src = src;
            },

            renderEditorCanvas() {
                if (!this.editorSourceCanvas) return;

                const canvas = this.$refs.cropCanvas;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });

                // 1. Draw Original (Scaled) - effectively resetting the view
                ctx.drawImage(this.editorSourceCanvas, 0, 0, this.canvasWidth, this.canvasHeight);

                // 2. Apply Filter (if not original)
                if (this.activeFilter !== 'original' && typeof cv !== 'undefined' && this.cvLoaded) {
                    try {
                        const srcMat = cv.imread(canvas);
                        const dstMat = this.applyFilter(srcMat, this.activeFilter);
                        cv.imshow(canvas, dstMat);
                        srcMat.delete();
                        if (dstMat !== srcMat) dstMat.delete();
                    } catch (e) {
                        console.error("Preview Filter Error:", e);
                    }
                }
            },

            resetToFull() {
                const w = this.canvasWidth;
                const h = this.canvasHeight;
                // Full image (no padding)
                this.corners = [
                    {x: 0, y: 0},
                    {x: w, y: 0},
                    {x: w, y: h},
                    {x: 0, y: h}
                ];
            },

            saveCropEdit() {
                if (typeof cv === 'undefined') return;
                const item = this.capturedImages[this.currentEditIndex];

                try {
                    // Fix: Ensure corners are sorted (TL, TR, BR, BL) to prevent twisting
                    this.corners = this.sortPoints(this.corners);

                    const realCorners = this.corners.map(c => ({
                        x: c.x / this.scaleX,
                        y: c.y / this.scaleY
                    }));

                    const img = new Image();
                    img.onload = () => {
                        try {
                            // Apply Rotation to Source
                            const canvas = document.createElement('canvas');
                            // Swap dims if 90/270
                            if (this.rotation % 180 !== 0) {
                                canvas.width = img.height;
                                canvas.height = img.width;
                            } else {
                                canvas.width = img.width;
                                canvas.height = img.height;
                            }

                            const ctx = canvas.getContext('2d');
                            ctx.translate(canvas.width/2, canvas.height/2);
                            ctx.rotate(this.rotation * Math.PI / 180);
                            ctx.drawImage(img, -img.width/2, -img.height/2);

                            // Read from rotated canvas
                            const src = cv.imread(canvas);
                            const newCroppedUrl = this.performWarp(src, realCorners, canvas.width, canvas.height, this.activeFilter);
                            src.delete();

                            // Create updated object (Deep Copy)
                            const updatedItem = {
                                ...this.capturedImages[this.currentEditIndex],
                                id: Date.now(), // Force reactivity
                                cropped: newCroppedUrl,
                                corners: realCorners,
                                original: canvas.toDataURL('image/jpeg', 0.9)
                            };

                            // Update State with Splice to ensure Alpine detects change
                            this.capturedImages.splice(this.currentEditIndex, 1, updatedItem);

                            // Reset rotation because 'original' is now the rotated image
                            this.rotation = 0;
                            this.view = 'review';
                        } catch (innerError) {
                            console.error("Processing Error during Save:", innerError);
                            alert("Failed to process image rotation: " + innerError.message);
                        }
                    };
                    img.onerror = (err) => {
                        console.error("Image Load Error:", err);
                        alert("Failed to load source image for processing.");
                    };
                    img.src = item.original;

                } catch(e) {
                    console.error("Save Edit Error", e);
                    alert("Failed to start save process.");
                }
            },

            cancelCrop() {
                this.view = 'review';
            },

            // --- FILTERS ---

            applyFilter(src, type) {
                const dst = new cv.Mat();
                try {
                    if (type === 'original' || type === 'photo') {
                        src.copyTo(dst);
                    }
                    else if (type === 'scan_doc') {
                        // NEW: Scan Document (Clean White Background)
                        const gray = new cv.Mat();
                        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);

                        // Mild blur to reduce noise specks
                        cv.GaussianBlur(gray, gray, new cv.Size(3, 3), 0, 0, cv.BORDER_DEFAULT);

                        // Block Size 31 (large area), C 15 (aggressive cutoff for white background)
                        cv.adaptiveThreshold(gray, dst, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY, 31, 15);

                        gray.delete();
                    }
                    else if (type === 'high_contrast') {
                         // NEW: High Contrast (Color Enhancement)
                         const rgb = new cv.Mat();
                         cv.cvtColor(src, rgb, cv.COLOR_RGBA2RGB);

                         const lab = new cv.Mat();
                         cv.cvtColor(rgb, lab, cv.COLOR_RGB2Lab);

                         const planes = new cv.MatVector();
                         cv.split(lab, planes);

                         const l = planes.get(0);
                         const a = planes.get(1);
                         const b = planes.get(2);

                         // CLAHE (Contrast Limited Adaptive Histogram Equalization)
                         const clahe = new cv.CLAHE(3.0, new cv.Size(8, 8));
                         clahe.apply(l, l);

                         // Merge
                         const mergedPlanes = new cv.MatVector();
                         mergedPlanes.push_back(l);
                         mergedPlanes.push_back(a);
                         mergedPlanes.push_back(b);

                         cv.merge(mergedPlanes, lab);

                         // Convert back to RGBA
                         const resultRGB = new cv.Mat();
                         cv.cvtColor(lab, resultRGB, cv.COLOR_Lab2RGB);
                         cv.cvtColor(resultRGB, dst, cv.COLOR_RGB2RGBA);

                         // Cleanup
                         rgb.delete(); lab.delete(); planes.delete(); mergedPlanes.delete();
                         l.delete(); a.delete(); b.delete(); clahe.delete(); resultRGB.delete();
                    }
                    else if (type === 'gray') {
                        cv.cvtColor(src, dst, cv.COLOR_RGBA2GRAY);
                    }
                    else if (type === 'bw') {
                        const gray = new cv.Mat();
                        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
                        // Block Size: 15, C: 10 (High contrast)
                        cv.adaptiveThreshold(gray, dst, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY, 15, 10);
                        gray.delete();
                    }
                    else if (type === 'magic') {
                         // Magic Color: Shadow Removal + Contrast
                         const rgb = new cv.Mat();
                         cv.cvtColor(src, rgb, cv.COLOR_RGBA2RGB);

                         const hsv = new cv.Mat();
                         cv.cvtColor(rgb, hsv, cv.COLOR_RGB2HSV);

                         const planes = new cv.MatVector();
                         cv.split(hsv, planes);

                         const h = planes.get(0);
                         const s = planes.get(1);
                         const v = planes.get(2);

                         const bg = new cv.Mat();
                         cv.GaussianBlur(v, bg, new cv.Size(51, 51), 0, 0, cv.BORDER_DEFAULT);

                         const diff = new cv.Mat();
                         cv.divide(v, bg, diff, 255);

                         const newPlanes = new cv.MatVector();
                         newPlanes.push_back(h);
                         newPlanes.push_back(s);
                         newPlanes.push_back(diff);

                         cv.merge(newPlanes, hsv);

                         const resultRGB = new cv.Mat();
                         cv.cvtColor(hsv, resultRGB, cv.COLOR_HSV2RGB);
                         cv.cvtColor(resultRGB, dst, cv.COLOR_RGB2RGBA);

                         // Cleanup
                         rgb.delete(); hsv.delete(); planes.delete(); newPlanes.delete();
                         h.delete(); s.delete(); v.delete();
                         bg.delete(); diff.delete(); resultRGB.delete();
                    }
                    else {
                        src.copyTo(dst);
                    }
                } catch (e) {
                    console.error("Filter Error (" + type + "):", e);
                    src.copyTo(dst); // Fallback to original
                }
                return dst;
            },

            // --- UTILS ---

            sortPoints(points) {
                // Sort by Y to separate Top/Bottom pairs
                points.sort((a,b) => a.y - b.y);

                // Top 2 (TL, TR) - Sort by X
                const top = points.slice(0, 2).sort((a,b) => a.x - b.x);

                // Bottom 2 (BL, BR) - Sort by X (BL is left, BR is right)
                // My performWarp expects [TL, TR, BR, BL] based on the destination mapping.
                // Dest: [0,0] (TL), [w,0] (TR), [w,h] (BR), [0,h] (BL)
                // So index 2 must be BR (largest X in bottom pair), index 3 must be BL (smallest X in bottom pair).

                const bottom = points.slice(2, 4).sort((a,b) => a.x - b.x);

                // top[0]=TL, top[1]=TR
                // bottom[0]=BL, bottom[1]=BR
                // Return Order: TL, TR, BR, BL
                return [top[0], top[1], bottom[1], bottom[0]];
            },

            getPolygonPoints() {
                return this.corners.map(c => `${c.x},${c.y}`).join(' ');
            },

            startDrag(type, index, e) {
                e.preventDefault();
                e.stopPropagation();

                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                this.previousMousePosition = { x: clientX, y: clientY };

                if (type === 'corner') {
                    this.activeDragIndex = index;
                    this.activeDragEdge = -1;
                } else if (type === 'edge') {
                    this.activeDragEdge = index;
                    this.activeDragIndex = -1;
                }
            },

            onDrag(e) {
                if (this.activeDragIndex === -1 && this.activeDragEdge === -1) return;
                e.preventDefault();

                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                // Calculate Delta
                const dx = clientX - this.previousMousePosition.x;
                const dy = clientY - this.previousMousePosition.y;

                // Update prev position for next frame
                this.previousMousePosition = { x: clientX, y: clientY };

                // Get Canvas Rect to check bounds relative to canvas
                const canvasRect = this.$refs.cropCanvas.getBoundingClientRect();

                // Helper to move a point with bounds check
                const movePoint = (idx, deltaX, deltaY) => {
                     let newX = this.corners[idx].x + deltaX;
                     let newY = this.corners[idx].y + deltaY;

                     // Clamping
                     newX = Math.max(0, Math.min(newX, this.canvasWidth));
                     newY = Math.max(0, Math.min(newY, this.canvasHeight));

                     this.corners[idx] = { x: newX, y: newY };
                };

                if (this.activeDragIndex !== -1) {
                    // Moving a Corner: Absolute position calculation is smoother for corners than delta,
                    // but delta is consistent with edge dragging.
                    // Let's switch corner dragging to absolute position like before to prevent drift?
                    // Actually, "snap to mouse" is better for corners.

                    let x = clientX - canvasRect.left;
                    let y = clientY - canvasRect.top;
                    x = Math.max(0, Math.min(x, this.canvasWidth));
                    y = Math.max(0, Math.min(y, this.canvasHeight));
                    this.corners[this.activeDragIndex] = {x, y};

                } else if (this.activeDragEdge !== -1) {
                    // Moving an Edge: Apply delta to both endpoints
                    const edgeIdx = this.activeDragEdge;
                    // Edge 0: 0-1, Edge 1: 1-2, Edge 2: 2-3, Edge 3: 3-0
                    const p1 = edgeIdx;
                    const p2 = (edgeIdx + 1) % 4;

                    movePoint(p1, dx, dy);
                    movePoint(p2, dx, dy);
                }
            },

            stopDrag() {
                this.activeDragIndex = -1;
                this.activeDragEdge = -1;
            },

            // --- FINALIZATION ---

            async finalizeProcess() {
                this.isLoading = true;
                this.loadingMessage = 'Generating Output...';

                try {
                    const dt = new DataTransfer();

                    // Standard Logic: Bundle everything into the input
                    // If 1 image -> JPG
                    // If > 1 image -> PDF
                    // Note: If user created a Layout, it is just another image in the list.
                    // The user is expected to delete source images if they only want the layout.

                    if(this.capturedImages.length === 1) {
                        // Check if we should generate PDF for single image to enforce A4 placement
                        // For now, we continue to return JPG for single images unless they are specifically from a layout.
                        // However, to ensure "Fit to A4" behavior is consistent, we might want to consider PDF.
                        // But sticking to JPG for single image is standard for file inputs.
                        const file = await this.urlToFile(this.capturedImages[0].cropped, 'scanned_doc.jpg', 'image/jpeg');
                        dt.items.add(file);

                    } else if (this.capturedImages.length > 1) {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF(); // A4 Portrait by default

                        const pageWidth = doc.internal.pageSize.getWidth();
                        const pageHeight = doc.internal.pageSize.getHeight();

                        for (let i = 0; i < this.capturedImages.length; i++) {
                            const imgData = this.capturedImages[i].cropped;
                            if (i > 0) doc.addPage();

                            const props = doc.getImageProperties(imgData);

                            // "Fit to Page" Logic (Aspect Fit)
                            // Calculate scale factors for both dimensions
                            const scaleW = pageWidth / props.width;
                            const scaleH = pageHeight / props.height;

                            // Use the smaller scale to ensure the image fits entirely within the page
                            const scale = Math.min(scaleW, scaleH);

                            const finalW = props.width * scale;
                            const finalH = props.height * scale;

                            // Center the image on the page
                            const x = (pageWidth - finalW) / 2;
                            const y = (pageHeight - finalH) / 2;

                            doc.addImage(imgData, 'JPEG', x, y, finalW, finalH);
                        }

                        const pdfBlob = doc.output('blob');
                        const file = new File([pdfBlob], "scanned_document.pdf", { type: "application/pdf" });
                        dt.items.add(file);
                    }

                    // Assign to Input
                    const input = document.getElementById(this.targetInputId);
                    if (input) {
                        input.files = dt.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Handle Preview
                    if (this.targetPreviewId && dt.files.length > 0) {
                         const preview = document.getElementById(this.targetPreviewId);
                         if(preview) {
                             const reader = new FileReader();
                             reader.onload = (e) => preview.src = e.target.result;
                             reader.readAsDataURL(dt.files[0]);
                         }
                    }

                    this.closeScanner();

                } catch (e) {
                    console.error("Finalize Error", e);
                    alert("Failed to save documents: " + e.message);
                } finally {
                    this.isLoading = false;
                }
            },

            loadImage(src) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                    img.src = src;
                });
            },

            async urlToFile(url, filename, mimeType) {
                const res = await fetch(url);
                const buf = await res.arrayBuffer();
                return new File([buf], filename, { type: mimeType });
            }
        }))
    });
</script>
