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
                    <!-- Gallery Preview (Bottom Left) -->
                    <div class="text-white text-sm cursor-pointer hover:underline min-w-[80px]" @click="if(capturedImages.length > 0) view = 'review'">
                        <div class="flex items-center gap-2">
                             <div class="relative" x-show="capturedImages.length > 0">
                                <img :src="capturedImages[capturedImages.length-1]?.cropped" class="w-10 h-10 rounded border border-white object-cover">
                                <span class="absolute -top-2 -right-2 badge bg-primary rounded-pill fs-7" x-text="capturedImages.length"></span>
                            </div>
                            <span x-show="capturedImages.length === 0" class="opacity-70">No images</span>
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
            <div x-show="view === 'review'" class="w-full h-full flex flex-col bg-gray-100">
                <div class="flex-grow overflow-y-auto p-3">
                    <div class="text-center mb-3" x-show="scanMode === 'id_card'">
                         <span class="badge bg-primary fs-6">
                            <i class="bi bi-info-circle me-1"></i> ตรวจสอบภาพหน้าและหลังบัตรก่อนบันทึก
                         </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="(img, index) in capturedImages" :key="img.id">
                            <div class="relative group bg-white p-2 rounded shadow-sm hover:shadow-md transition-shadow">
                                <!-- Show CROPPED version -->
                                <img :src="img.cropped" class="w-full h-40 object-contain bg-gray-50 border rounded cursor-pointer" @click="startEdit(index)">

                                <div class="absolute top-1 right-1 flex gap-1">
                                    <button @click.stop="removeImage(index)" class="btn btn-sm btn-danger rounded-circle shadow-sm p-1 leading-none w-6 h-6 flex items-center justify-center">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <div class="absolute bottom-1 right-1">
                                     <button @click.stop="startEdit(index)" class="btn btn-sm btn-primary shadow-sm py-1 px-2 text-xs rounded-pill">
                                        <i class="bi bi-crop"></i> ปรับแต่ง
                                    </button>
                                </div>
                                <div class="absolute top-1 left-1 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded">
                                    <span x-show="scanMode === 'document'" x-text="index + 1"></span>
                                    <span x-show="scanMode === 'id_card' && index === 0">ด้านหน้า</span>
                                    <span x-show="scanMode === 'id_card' && index === 1">ด้านหลัง</span>
                                </div>
                            </div>
                        </template>

                        <!-- Add More Button (Only for Document Mode) -->
                        <div x-show="scanMode === 'document'" @click="view = 'camera'; startCamera()" class="flex flex-col items-center justify-center h-40 border-2 border-dashed border-gray-300 rounded bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 cursor-pointer transition-colors">
                            <i class="bi bi-plus-lg text-3xl mb-1"></i>
                            <span class="text-sm">ถ่ายเพิ่ม</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-white border-t flex justify-between items-center">
                     <button @click="view = 'camera'; startCamera()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> กลับไปถ่ายภาพ
                    </button>
                    <button @click="finalizeProcess()" class="btn btn-primary px-4" :disabled="!canFinish()">
                        <i class="bi bi-save"></i> บันทึกข้อมูล
                        <span x-show="scanMode === 'document'">(<span x-text="capturedImages.length"></span>)</span>
                        <span x-show="scanMode === 'id_card'">(รวมไฟล์ A4)</span>
                    </button>
                </div>
            </div>

            <!-- VIEW: CROP -->
            <div x-show="view === 'crop'" class="w-full h-full flex flex-col bg-dark relative">
                <div class="flex-grow relative overflow-hidden flex items-center justify-center bg-gray-900" x-ref="cropContainer">

                    <div x-ref="cropWrapper" class="relative shadow-2xl">
                        <canvas x-ref="cropCanvas" class="block"></canvas>

                        <!-- SVG Overlay for Handles -->
                        <svg class="absolute top-0 left-0 w-full h-full pointer-events-none" style="z-index: 10;">
                            <!-- Polygon Line -->
                            <polygon :points="getPolygonPoints()" fill="rgba(255, 255, 255, 0.2)" stroke="#0d6efd" stroke-width="2" />

                            <!-- Handles -->
                            <circle :cx="corners[0].x" :cy="corners[0].y" r="10" fill="#0d6efd" stroke="white" stroke-width="2" class="pointer-events-auto cursor-move" @mousedown="startDrag(0, $event)" @touchstart="startDrag(0, $event)" />
                            <circle :cx="corners[1].x" :cy="corners[1].y" r="10" fill="#0d6efd" stroke="white" stroke-width="2" class="pointer-events-auto cursor-move" @mousedown="startDrag(1, $event)" @touchstart="startDrag(1, $event)" />
                            <circle :cx="corners[2].x" :cy="corners[2].y" r="10" fill="#0d6efd" stroke="white" stroke-width="2" class="pointer-events-auto cursor-move" @mousedown="startDrag(2, $event)" @touchstart="startDrag(2, $event)" />
                            <circle :cx="corners[3].x" :cy="corners[3].y" r="10" fill="#0d6efd" stroke="white" stroke-width="2" class="pointer-events-auto cursor-move" @mousedown="startDrag(3, $event)" @touchstart="startDrag(3, $event)" />
                        </svg>
                    </div>
                </div>

                <div class="p-3 bg-black/80 flex justify-between items-center shrink-0">
                    <button @click="cancelCrop()" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> ยกเลิก
                    </button>
                     <div class="text-white text-sm opacity-75 hidden md:block">
                        ลากจุดทั้ง 4 มุมเพื่อปรับตำแหน่งเอกสาร
                    </div>
                    <div>
                         <button @click="resetToFull()" class="btn btn-outline-light me-2">
                            <i class="bi bi-arrows-fullscreen"></i> เต็มรูป
                        </button>
                        <button @click="saveCropEdit()" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> บันทึกแก้ไข
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Load Libraries (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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

            // Camera
            stream: null,

            // Data
            // Structure: { id, original: dataUrl, cropped: dataUrl, corners: [{x,y}...] }
            capturedImages: [],
            currentEditIndex: -1,

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

            init() {
                document.addEventListener('opencv-loaded', () => {
                    this.cvLoaded = true;
                    console.log('OpenCV Loaded');
                });

                // Mouse/Touch Move Handlers for Cropping
                window.addEventListener('mousemove', (e) => this.onDrag(e));
                window.addEventListener('mouseup', () => this.stopDrag());
                window.addEventListener('touchmove', (e) => this.onDrag(e), {passive: false});
                window.addEventListener('touchend', () => this.stopDrag());
            },

            getHeaderTitle() {
                if(this.view === 'camera') return this.scanMode === 'id_card' ? 'สแกนบัตรประชาชน (Scan ID Card)' : 'สแกนเอกสารทั่วไป (Scan Document)';
                if(this.view === 'review') return 'ตรวจสอบเอกสาร';
                if(this.view === 'crop') return 'ปรับมุมเอกสาร';
                return 'Document Scanner';
            },

            setMode(mode) {
                if(this.capturedImages.length > 0) {
                    if(!confirm('เปลี่ยนโหมดจะลบภาพที่ถ่ายไว้ คุณแน่ใจหรือไม่?')) return;
                }
                this.scanMode = mode;
                this.capturedImages = [];
            },

            async openScanner(detail) {
                this.targetInputId = detail.inputId;
                this.targetPreviewId = detail.previewId || null;
                this.isOpen = true;
                this.capturedImages = [];
                this.view = 'camera';
                this.scanMode = 'document'; // Default

                if(!this.cvLoaded) {
                     this.isLoading = true;
                     this.loadingMessage = 'กำลังโหลดระบบประมวลผลภาพ...';
                     // Check regularly if loaded
                     let checkInterval = setInterval(() => {
                         if(typeof cv !== 'undefined') {
                             this.cvLoaded = true;
                             this.isLoading = false;
                             clearInterval(checkInterval);
                             this.startCamera();
                         }
                     }, 500);

                     // Fallback timeout
                     setTimeout(() => {
                         if(!this.cvLoaded && this.isLoading) {
                             this.isLoading = false;
                             alert('Cannot load Image Processing Engine (OpenCV). Basic features only.');
                             this.startCamera();
                         }
                     }, 10000);
                } else {
                    this.startCamera();
                }
            },

            closeScanner() {
                this.stopCamera();
                this.isOpen = false;
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
                if (this.scanMode === 'id_card' && this.capturedImages.length >= 2) {
                    alert('ครบ 2 ด้านแล้ว กรุณากดเสร็จสิ้นเพื่อตรวจสอบ');
                    return;
                }

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
                const dst = new cv.Mat();
                const gray = new cv.Mat();
                const blurred = new cv.Mat();

                // 1. Grayscale
                cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY, 0);

                // 2. Gaussian Blur (Reduce noise)
                cv.GaussianBlur(gray, blurred, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);

                // 3. Canny Edge Detection
                // Lower threshold helps find faint edges, but might include noise
                cv.Canny(blurred, dst, 30, 150);

                // 4. Dilate to connect broken edges
                const M = cv.Mat.ones(3, 3, cv.CV_8U);
                const anchor = new cv.Point(-1, -1);
                cv.dilate(dst, dst, M, anchor, 1, cv.BORDER_CONSTANT, cv.morphologyDefaultBorderValue());

                // Find Contours
                let contours = new cv.MatVector();
                let hierarchy = new cv.Mat();
                cv.findContours(dst, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

                let maxQuadArea = 0;
                let bestQuad = null;
                let width = src.cols;
                let height = src.rows;
                let found = false;
                let minArea = width * height * 0.05; // 5% minimum area

                for(let i = 0; i < contours.size(); ++i) {
                    let cnt = contours.get(i);
                    let area = cv.contourArea(cnt);

                    if (area > minArea) {
                        let peri = cv.arcLength(cnt, true);
                        let approx = new cv.Mat();
                        cv.approxPolyDP(cnt, approx, 0.02 * peri, true);

                        if (cv.isContourConvex(approx) && approx.rows === 4) {
                            if (area > maxQuadArea) {
                                if (bestQuad) bestQuad.delete(); // Avoid leak
                                maxQuadArea = area;
                                bestQuad = approx;
                                found = true;
                            } else {
                                approx.delete();
                            }
                        } else {
                            approx.delete();
                        }
                    }
                }

                let corners = [];

                if (found && bestQuad) {
                    // Extract points from the perfect polygon
                    const points = [];
                    for(let i=0; i<4; i++) {
                        points.push({
                            x: bestQuad.data32S[i*2],
                            y: bestQuad.data32S[i*2+1]
                        });
                    }
                    corners = this.sortPoints(points);
                    bestQuad.delete();
                }
                else {
                    // Fallback to Full Image
                    corners = this.getDefaultCorners(width, height);
                    found = false;
                }

                // Cleanup
                gray.delete(); blurred.delete();
                dst.delete(); contours.delete(); hierarchy.delete(); M.delete();

                return { corners, found };
            },

            performWarp(src, corners, width, height) {
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

                const maxWidth = Math.max(wTop, wBot);
                const maxHeight = Math.max(hLeft, hRight);

                const dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    0, 0,
                    maxWidth, 0,
                    maxWidth, maxHeight,
                    0, maxHeight
                ]);

                const M = cv.getPerspectiveTransform(srcTri, dstTri);
                const dst = new cv.Mat();
                cv.warpPerspective(src, dst, M, new cv.Size(maxWidth, maxHeight), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());

                // Draw to temp canvas
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = maxWidth;
                tempCanvas.height = maxHeight;
                cv.imshow(tempCanvas, dst);
                const dataUrl = tempCanvas.toDataURL('image/jpeg', 0.95);

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
                this.capturedImages.splice(index, 1);
                if(this.capturedImages.length === 0) {
                    this.view = 'camera';
                    this.startCamera();
                }
            },

            // --- EDIT / CROP LOGIC ---

            startEdit(index) {
                this.currentEditIndex = index;
                this.view = 'crop';
                const item = this.capturedImages[index];

                this.$nextTick(() => {
                    this.loadImageForCrop(item.original, item.corners);
                });
            },

            loadImageForCrop(src, savedCorners) {
                const img = new Image();
                img.onload = () => {
                    this.imageWidth = img.width;
                    this.imageHeight = img.height;

                    const canvas = this.$refs.cropCanvas;
                    const container = this.$refs.cropContainer;
                    const wrapper = this.$refs.cropWrapper;

                    // Simple fit logic
                    const scale = Math.min(container.clientWidth / img.width, container.clientHeight / img.height) * 0.9;

                    this.canvasWidth = img.width * scale;
                    this.canvasHeight = img.height * scale;

                    // Set wrapper size to match calculated canvas size
                    wrapper.style.width = this.canvasWidth + 'px';
                    wrapper.style.height = this.canvasHeight + 'px';

                    canvas.width = this.canvasWidth;
                    canvas.height = this.canvasHeight;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, this.canvasWidth, this.canvasHeight);

                    this.scaleX = this.canvasWidth / this.imageWidth;
                    this.scaleY = this.canvasHeight / this.imageHeight;

                    // Restore corners
                    this.corners = savedCorners.map(c => ({
                        x: c.x * this.scaleX,
                        y: c.y * this.scaleY
                    }));
                };
                img.src = src;
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
                    const realCorners = this.corners.map(c => ({
                        x: c.x / this.scaleX,
                        y: c.y / this.scaleY
                    }));

                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);

                        const src = cv.imread(canvas);
                        const newCroppedUrl = this.performWarp(src, realCorners, img.width, img.height);
                        src.delete();

                        this.capturedImages[this.currentEditIndex].cropped = newCroppedUrl;
                        this.capturedImages[this.currentEditIndex].corners = realCorners;
                        this.view = 'review';
                    };
                    img.src = item.original;

                } catch(e) {
                    console.error("Save Edit Error", e);
                    alert("Failed to process crop.");
                }
            },

            cancelCrop() {
                this.view = 'review';
            },

            // --- UTILS ---

            sortPoints(points) {
                // Reliable sorting for TL, TR, BR, BL
                // Find Center
                const center = points.reduce((acc, p) => ({x: acc.x + p.x, y: acc.y + p.y}), {x:0, y:0});
                center.x /= 4;
                center.y /= 4;

                const top = [], bottom = [];
                points.forEach(p => {
                    if (p.y < center.y) top.push(p);
                    else bottom.push(p);
                });

                // Sort top by x (TL, TR)
                top.sort((a,b) => a.x - b.x);
                // Sort bottom by x (BL, BR) -> We need BR, BL usually, but let's stick to standard order order: TL, TR, BR, BL
                bottom.sort((a,b) => b.x - a.x); // Right first for standard "circle" order?
                // Perspective Transform Expects: TL, TR, BR, BL?
                // Actually my performWarp uses: corners[0], corners[1], corners[2], corners[3]
                // Mapping to: 0,0 (TL) -> maxW,0 (TR) -> maxW,maxH (BR) -> 0,maxH (BL)
                // So order must be TL, TR, BR, BL.

                // Refined Sort:
                // Sort by Y first
                points.sort((a,b) => a.y - b.y);

                // Top 2 are top
                const t = points.slice(0, 2).sort((a,b) => a.x - b.x);
                // Bottom 2 are bottom
                const b = points.slice(2, 4).sort((a,b) => a.x - b.x);

                return [t[0], t[1], b[1], b[0]];
            },

            getPolygonPoints() {
                return this.corners.map(c => `${c.x},${c.y}`).join(' ');
            },

            startDrag(index, e) {
                e.preventDefault();
                this.activeDragIndex = index;
            },

            onDrag(e) {
                if (this.activeDragIndex === -1) return;
                e.preventDefault();

                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                const rect = this.$refs.cropContainer.getBoundingClientRect();
                const canvasRect = this.$refs.cropCanvas.getBoundingClientRect();

                let x = clientX - canvasRect.left;
                let y = clientY - canvasRect.top;

                x = Math.max(0, Math.min(x, this.canvasWidth));
                y = Math.max(0, Math.min(y, this.canvasHeight));

                this.corners[this.activeDragIndex] = {x, y};
            },

            stopDrag() {
                this.activeDragIndex = -1;
            },

            // --- FINALIZATION ---

            async finalizeProcess() {
                this.isLoading = true;
                this.loadingMessage = 'Generating Output...';

                try {
                    const dt = new DataTransfer();

                    // --- ID CARD COMBINATION LOGIC ---
                    if (this.scanMode === 'id_card' && this.capturedImages.length >= 2) {
                        const front = await this.loadImage(this.capturedImages[0].cropped);
                        const back = await this.loadImage(this.capturedImages[1].cropped);

                        // A4 Dimensions (High Res - 150 DPI approx)
                        const a4Width = 1240;
                        const a4Height = 1754;

                        const canvas = document.createElement('canvas');
                        canvas.width = a4Width;
                        canvas.height = a4Height;
                        const ctx = canvas.getContext('2d');

                        // Fill White
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, a4Width, a4Height);

                        // Layout: Center Front in Top Half, Center Back in Bottom Half
                        const cardWidth = a4Width * 0.6; // 60% of page width
                        const cardHeight = cardWidth * (front.height / front.width); // maintain aspect

                        const centerX = (a4Width - cardWidth) / 2;
                        const topY = (a4Height / 4) - (cardHeight / 2); // Center of top half
                        const bottomY = (a4Height * 3 / 4) - (cardHeight / 2); // Center of bottom half

                        // Draw Front
                        ctx.drawImage(front, centerX, topY, cardWidth, cardHeight);
                        // Label Front
                        ctx.font = '30px Arial';
                        ctx.fillStyle = '#333';
                        ctx.textAlign = 'center';
                        ctx.fillText('ด้านหน้า (Front)', a4Width/2, topY - 20);

                        // Draw Back
                        ctx.drawImage(back, centerX, bottomY, cardWidth, cardHeight);
                        // Label Back
                        ctx.fillText('ด้านหลัง (Back)', a4Width/2, bottomY - 20);

                        const finalDataUrl = canvas.toDataURL('image/jpeg', 0.85);
                        const file = await this.urlToFile(finalDataUrl, 'id_card_scan.jpg', 'image/jpeg');
                        dt.items.add(file);

                    }
                    // --- NORMAL DOCUMENT LOGIC ---
                    else if(this.capturedImages.length === 1) {
                        const file = await this.urlToFile(this.capturedImages[0].cropped, 'scanned_doc.jpg', 'image/jpeg');
                        dt.items.add(file);

                    } else if (this.capturedImages.length > 1) {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF();

                        for (let i = 0; i < this.capturedImages.length; i++) {
                            const imgData = this.capturedImages[i].cropped;
                            if (i > 0) doc.addPage();

                            const props = doc.getImageProperties(imgData);
                            const pdfWidth = doc.internal.pageSize.getWidth();
                            const pdfHeight = (props.height * pdfWidth) / props.width;

                            doc.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
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
