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
                    <div class="text-white text-sm cursor-pointer hover:underline" @click="if(capturedImages.length > 0) view = 'review'">
                        <div class="flex items-center gap-2">
                             <div class="relative" x-show="capturedImages.length > 0">
                                <img :src="capturedImages[capturedImages.length-1]?.cropped" class="w-10 h-10 rounded border border-white object-cover">
                                <span class="absolute -top-2 -right-2 badge bg-primary rounded-pill fs-7" x-text="capturedImages.length"></span>
                            </div>
                            <span x-show="capturedImages.length === 0">No images</span>
                        </div>
                    </div>

                    <button @click="captureImage()" :disabled="isProcessing" class="btn btn-light rounded-circle p-1 shadow-lg border-4 border-gray-300 relative" style="width: 70px; height: 70px;">
                         <div class="w-full h-full bg-danger rounded-circle flex items-center justify-center">
                             <span x-show="isProcessing" class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>
                         </div>
                    </button>

                    <button @click="finishCapture()" class="btn btn-success text-white fw-bold px-4 rounded-pill" x-show="capturedImages.length > 0">
                        เสร็จสิ้น <i class="bi bi-check-lg"></i>
                    </button>
                     <div style="width: 80px;" x-show="capturedImages.length === 0"></div> <!-- Spacer -->
                </div>
            </div>

            <!-- VIEW: REVIEW (Grid of taken images) -->
            <div x-show="view === 'review'" class="w-full h-full flex flex-col bg-gray-100">
                <div class="flex-grow overflow-y-auto p-3">
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
                                <div class="absolute top-1 left-1 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded" x-text="index + 1"></div>
                            </div>
                        </template>

                        <!-- Add More Button -->
                        <div @click="view = 'camera'; startCamera()" class="flex flex-col items-center justify-center h-40 border-2 border-dashed border-gray-300 rounded bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 cursor-pointer transition-colors">
                            <i class="bi bi-plus-lg text-3xl mb-1"></i>
                            <span class="text-sm">ถ่ายเพิ่ม</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-white border-t flex justify-between items-center">
                     <button @click="view = 'camera'; startCamera()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> กลับไปถ่ายภาพ
                    </button>
                    <button @click="finalizeProcess()" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> บันทึกข้อมูล (<span x-text="capturedImages.length"></span>)
                    </button>
                </div>
            </div>

            <!-- VIEW: CROP -->
            <div x-show="view === 'crop'" class="w-full h-full flex flex-col bg-dark relative">
                <div class="flex-grow relative overflow-hidden flex items-center justify-center bg-gray-900" x-ref="cropContainer">
                    <canvas x-ref="cropCanvas" class="max-w-full max-h-full shadow-2xl"></canvas>

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

                <div class="p-3 bg-black/80 flex justify-between items-center shrink-0">
                    <button @click="cancelCrop()" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> ยกเลิก
                    </button>
                     <div class="text-white text-sm opacity-75 hidden md:block">
                        ลากจุดทั้ง 4 มุมเพื่อปรับตำแหน่งเอกสาร
                    </div>
                    <div>
                         <button @click="resetToFull()" class="btn btn-outline-light me-2">
                            <i class="bi bi-arrows-fullscreen"></i> Reset
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
                if(this.view === 'camera') return 'ถ่ายภาพเอกสาร';
                if(this.view === 'review') return 'ตรวจสอบเอกสาร';
                if(this.view === 'crop') return 'ปรับมุมเอกสาร';
                return 'Document Scanner';
            },

            async openScanner(detail) {
                this.targetInputId = detail.inputId;
                this.targetPreviewId = detail.previewId || null;
                this.isOpen = true;
                this.capturedImages = [];
                this.view = 'camera';

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

            // --- SMART CAPTURE LOGIC ---

            captureImage() {
                if (this.isProcessing) return;
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
                        // 1. Detect Edges
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
                    // Optional: Brief success sound or haptic feedback
                }
            },

            detectDocument(src) {
                const dst = new cv.Mat();
                cv.cvtColor(src, dst, cv.COLOR_RGBA2GRAY, 0);
                cv.GaussianBlur(dst, dst, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
                cv.Canny(dst, dst, 75, 200);

                let contours = new cv.MatVector();
                let hierarchy = new cv.Mat();
                cv.findContours(dst, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

                let maxArea = 0;
                let biggestContour = null;
                let width = src.cols;
                let height = src.rows;
                let found = false;

                for(let i = 0; i < contours.size(); ++i) {
                    let cnt = contours.get(i);
                    let area = cv.contourArea(cnt);
                    // Filter: must be reasonably large (> 10% of image roughly)
                    if (area > (width * height * 0.1)) {
                        let peri = cv.arcLength(cnt, true);
                        let approx = new cv.Mat();
                        cv.approxPolyDP(cnt, approx, 0.02 * peri, true);

                        if (approx.rows === 4 && area > maxArea) {
                            maxArea = area;
                            biggestContour = approx;
                            found = true;
                        } else {
                            approx.delete();
                        }
                    }
                }

                let corners = [];
                if (biggestContour) {
                     const points = [];
                    for(let i=0; i<4; i++) {
                        points.push({
                            x: biggestContour.data32S[i*2],
                            y: biggestContour.data32S[i*2+1]
                        });
                    }
                    corners = this.sortPoints(points);
                    biggestContour.delete();
                } else {
                    corners = this.getDefaultCorners(width, height);
                }

                dst.delete();
                contours.delete();
                hierarchy.delete();

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
                const pad = 40; // Indent slightly so user sees handles
                return [
                    {x: pad, y: pad},
                    {x: w-pad, y: pad},
                    {x: w-pad, y: h-pad},
                    {x: pad, y: h-pad}
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

                    // Simple fit logic
                    const scale = Math.min(container.clientWidth / img.width, container.clientHeight / img.height) * 0.9;

                    this.canvasWidth = img.width * scale;
                    this.canvasHeight = img.height * scale;

                    canvas.width = this.canvasWidth;
                    canvas.height = this.canvasHeight;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, this.canvasWidth, this.canvasHeight);

                    this.scaleX = this.canvasWidth / this.imageWidth;
                    this.scaleY = this.canvasHeight / this.imageHeight;

                    // Restore corners (Deep copy to avoid modifying state directly until save)
                    // Scale saved corners (which are in original image coordinates) to canvas coordinates
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
                const pad = 20;
                this.corners = [
                    {x: pad, y: pad},
                    {x: w-pad, y: pad},
                    {x: w-pad, y: h-pad},
                    {x: pad, y: h-pad}
                ];
            },

            saveCropEdit() {
                if (typeof cv === 'undefined') return;
                const item = this.capturedImages[this.currentEditIndex];

                try {
                    // 1. Convert current canvas corners back to Original Image coordinates
                    const realCorners = this.corners.map(c => ({
                        x: c.x / this.scaleX,
                        y: c.y / this.scaleY
                    }));

                    // 2. Perform Warp on Original Image
                    // We need to load the original image into a Mat
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

                        // 3. Update State
                        this.capturedImages[this.currentEditIndex].cropped = newCroppedUrl;
                        this.capturedImages[this.currentEditIndex].corners = realCorners;

                        // 4. Return to review
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
                 // Sort top (y)
                points.sort((a,b) => a.y - b.y);
                const top = points.slice(0, 2).sort((a,b) => a.x - b.x);
                const bottom = points.slice(2, 4).sort((a,b) => b.x - a.x); // Note: BR is usually right-most
                // Re-sort bottom by x ascending for standard check (BL, BR)
                bottom.sort((a,b) => a.x - b.x);

                return [top[0], top[1], bottom[1], bottom[0]]; // TL, TR, BR, BL
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

                // Calculate position relative to canvas container
                const rect = this.$refs.cropContainer.getBoundingClientRect();
                const canvasRect = this.$refs.cropCanvas.getBoundingClientRect();

                // Offset inside the container
                let x = clientX - canvasRect.left;
                let y = clientY - canvasRect.top;

                // Clamp
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

                    if(this.capturedImages.length === 1) {
                        // Single Image -> JPG (Use the CROPPED version)
                        const file = await this.urlToFile(this.capturedImages[0].cropped, 'scanned_doc.jpg', 'image/jpeg');
                        dt.items.add(file);

                    } else if (this.capturedImages.length > 1) {
                        // Multiple Images -> PDF
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
                        // Trigger change event
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Handle Preview (Optional)
                    if (this.targetPreviewId && this.capturedImages.length === 1) {
                         const preview = document.getElementById(this.targetPreviewId);
                         if(preview) preview.src = this.capturedImages[0].cropped;
                    }

                    this.closeScanner();

                } catch (e) {
                    console.error("Finalize Error", e);
                    alert("Failed to save documents: " + e.message);
                } finally {
                    this.isLoading = false;
                }
            },

            async urlToFile(url, filename, mimeType) {
                const res = await fetch(url);
                const buf = await res.arrayBuffer();
                return new File([buf], filename, { type: mimeType });
            }
        }))
    });
</script>
