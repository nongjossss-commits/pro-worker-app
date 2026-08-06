    // Global Interceptor Function
    window.interceptFileSelect = function(event) {
        const input = event.target;
        // Convert FileList to a static Array immediately so clearing the input later doesn't destroy the list during async processing
        const files = Array.from(input.files);

        // 1. Check if this change was triggered by the scanner itself
        if (input.dataset.scannerSource === 'true') {
            delete input.dataset.scannerSource; // Reset flag
            return; // Allow normal processing
        }

        if (!files || files.length === 0) return;

        // 2. Check if files are supported (Image or PDF)
        let isSupported = true;
        for (let i = 0; i < files.length; i++) {
            const type = files[i].type;
            if (!type.startsWith('image/') && type !== 'application/pdf') {
                isSupported = false;
                break;
            }
        }

        // 3. If supported, intercept and open scanner
        if (isSupported) {
            // Stop other listeners (e.g. immediate upload)
            event.stopImmediatePropagation();
            // event.preventDefault(); // change event is not cancellable usually, but good practice

            // Dispatch event with files
            document.dispatchEvent(new CustomEvent('open-document-scanner', {
                detail: {
                    inputId: input.id,
                    files: files
                }
            }));

            // Clear the input so it's "empty" while scanning
            // Note: This might not be strictly necessary if we stopped propagation,
            // but keeps UI clean.
            input.value = '';
        }
    };

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
            canvasWidth: 1,
            canvasHeight: 1,
            imageWidth: 0,
            imageHeight: 0,
            scaleX: 1,
            scaleY: 1,
            activeDragIndex: -1,
            activeDragEdge: -1,
            activeDragMidpoint: -1,
            previousMousePosition: { x: 0, y: 0 },
            isDragging: false,
            dragPosition: { x: 0, y: 0 },
            loupeScreenPos: { x: 0, y: 0 },
            rotation: 0, // Current rotation in degrees (0, 90, 180, 270)
            activeFilter: 'original', // original, bw, magic, gray
            editorSourceCanvas: null, // Store rotated source for preview
            cropZoom: 1, // 1 = fit to screen, 2 = 200%, etc.
            cropZoomFitScale: 1, // The base fit scale (stored for reference)

            // Live Detection State
            liveCorners: [],
            liveCornersHistory: [],
            isDocumentStable: false,
            liveDetectionInterval: null,
            videoDisplayScale: { x: 1, y: 1 },
            videoOffset: { x: 0, y: 0 },

            init() {
                // Initialize PDF.js worker
                if (typeof pdfjsLib !== 'undefined') {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                }

                // Fix Race Condition: Check if OpenCV is already loaded
                if (typeof cv !== 'undefined' && cv.Mat) {
                    this.cvLoaded = true;
                    console.log('OpenCV Already Loaded');
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
                console.log('Opening Scanner. Files:', detail.files ? detail.files.length : 0, 'URL:', detail.initialUrl);
                this.targetInputId = detail.inputId;
                this.targetPreviewId = detail.previewId || null;
                // Remember where the caller was scrolled to (page or modal/offcanvas
                // body) so closeScanner() can put it back — otherwise finishing one
                // attachment (e.g. a passport page) jumps the form to the top,
                // breaking the flow of attaching several documents in a row.
                this._scrollRestore = this.captureScrollPosition(detail.inputId);
                this.isOpen = true;
                this.capturedImages = [];
                this.view = 'camera';
                this.scanMode = 'document'; // Default

                // Handle Edit Mode (Initial File or Files Object)
                if (detail.files && detail.files.length > 0) {
                     console.log('Importing files directly');
                     await this.handleImport({ target: { files: detail.files, value: '' } }, true); // Pass true to indicate direct file loading
                } else if (detail.initialUrl) {
                    await this.loadInitialFile(detail.initialUrl);
                }

                const fileCount = detail.files ? detail.files.length : 0;
                const hasFiles = fileCount > 0;
                const hasInitialContent = !!detail.initialUrl || hasFiles;
                console.log('Debug Calc:', { url: detail.initialUrl, fileCount, hasFiles, hasInitialContent });

                if(!this.cvLoaded) {
                     this.isLoading = true;
                     this.loadingMessage = 'กำลังโหลดระบบประมวลผลภาพ...';
                     // Check regularly if loaded
                     let checkInterval = setInterval(() => {
                         if(typeof cv !== 'undefined') {
                             this.cvLoaded = true;
                             this.isLoading = false;
                             clearInterval(checkInterval);
                             console.log('Interval check. hasInitialContent:', hasInitialContent);
                             if (!hasInitialContent) this.startCamera();
                         }
                     }, 500);

                     // Fallback timeout
                     setTimeout(() => {
                         if(!this.cvLoaded && this.isLoading) {
                             this.isLoading = false;
                             alert('Cannot load Image Processing Engine (OpenCV). Basic features only.');
                             if (!hasInitialContent) this.startCamera();
                         }
                     }, 10000);
                } else {
                    if (!hasInitialContent) this.startCamera();
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
                // Restore the scroll position captured in openScanner(). Runs after
                // the overlay is removed from layout (requestAnimationFrame), since
                // restoring immediately can race with the browser's own reflow.
                const restore = this._scrollRestore;
                this._scrollRestore = null;
                if (restore) {
                    requestAnimationFrame(() => {
                        if (restore.container) {
                            restore.container.scrollTop = restore.top;
                        } else {
                            window.scrollTo(0, restore.top);
                        }
                    });
                }
            },

            // Finds the nearest scrollable ancestor of the target input (e.g. a
            // .modal-body or .offcanvas-body when the form is embedded in one),
            // falling back to the page itself for plain, non-modal forms.
            captureScrollPosition(inputId) {
                const input = inputId ? document.getElementById(inputId) : null;
                const container = input ? this.findScrollContainer(input) : null;
                return {
                    container,
                    top: container ? container.scrollTop : (window.scrollY || document.documentElement.scrollTop),
                };
            },

            findScrollContainer(el) {
                let node = el.parentElement;
                while (node && node !== document.body) {
                    const style = window.getComputedStyle(node);
                    const canScroll = /(auto|scroll)/.test(style.overflowY) && node.scrollHeight > node.clientHeight;
                    if (canScroll) return node;
                    node = node.parentElement;
                }
                return null;
            },

            async handleImport(event, directLoad = false) {
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

                    // Reset input only if event came from actual input
                    if (!directLoad && event.target) {
                        event.target.value = '';
                    }
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

                        let finalCorners = this.getDefaultCorners(img.width, img.height);
                        let isFound = false;
                        let finalCropped = dataUrl;

                        // Auto-detect on import if OpenCV is ready
                        if (typeof cv !== 'undefined' && this.cvLoaded) {
                            try {
                                const canvas = document.createElement('canvas');
                                canvas.width = img.width;
                                canvas.height = img.height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0);
                                const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                                const src = cv.matFromImageData(imgData);

                                const result = this.detectDocument(src, true);
                                if (result.found) {
                                    // Expand corners slightly outward to avoid cutting document edges
                                    finalCorners = this.expandCorners(result.corners, img.width, img.height, 0.01);
                                    isFound = true;
                                    finalCropped = this.performWarp(src, finalCorners, canvas.width, canvas.height);
                                } else {
                                    // Use fallback corners but don't auto-warp if not found confidently
                                    finalCorners = result.corners;
                                }
                                src.delete();
                            } catch (err) {
                                console.error("Auto-detect on import failed", err);
                            }
                        }

                        this.capturedImages.push({
                            id: Date.now() + Math.random(),
                            original: dataUrl,
                            cropped: finalCropped,
                            corners: finalCorners,
                            isFound: isFound
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
                    const viewport = page.getViewport({ scale: 3.0 }); // Ultra High quality (300DPI+)

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    await page.render({ canvasContext: context, viewport: viewport }).promise;

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.98);

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
                            width: { ideal: 4096 },
                            height: { ideal: 2160 },
                            focusMode: { ideal: "continuous" }
                        },
                        audio: false
                    });
                    this.$refs.video.srcObject = this.stream;
                    this.$refs.video.onloadedmetadata = () => {
                        this.startLiveDetection();
                    };
                } catch (err) {
                    console.error("Camera Error:", err);
                    alert("Cannot access camera: " + err.message);
                }
            },

            stopCamera() {
                this.stopLiveDetection();
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

            // --- LIVE DETECTION LOGIC ---
            startLiveDetection() {
                if (this.liveDetectionInterval) clearInterval(this.liveDetectionInterval);

                // Downscale for performance
                const processWidth = 400;

                this.liveDetectionInterval = setInterval(() => {
                    if (!this.cvLoaded || typeof cv === 'undefined' || this.view !== 'camera' || !this.$refs.video || this.$refs.video.readyState !== 4) return;

                    const video = this.$refs.video;

                    // Update display scale & offset mapping (since object-fit: contain is used)
                    const videoRatio = video.videoWidth / video.videoHeight;
                    const displayRatio = video.clientWidth / video.clientHeight;

                    let drawWidth = video.clientWidth;
                    let drawHeight = video.clientHeight;
                    let offsetX = 0;
                    let offsetY = 0;

                    if (videoRatio > displayRatio) {
                        drawHeight = video.clientWidth / videoRatio;
                        offsetY = (video.clientHeight - drawHeight) / 2;
                    } else {
                        drawWidth = video.clientHeight * videoRatio;
                        offsetX = (video.clientWidth - drawWidth) / 2;
                    }

                    this.videoDisplayScale = {
                        x: drawWidth / video.videoWidth,
                        y: drawHeight / video.videoHeight
                    };
                    this.videoOffset = { x: offsetX, y: offsetY };

                    // Process frame
                    try {
                        const processHeight = (video.videoHeight / video.videoWidth) * processWidth;
                        const canvas = document.createElement('canvas');
                        canvas.width = processWidth;
                        canvas.height = processHeight;
                        const ctx = canvas.getContext('2d', { willReadFrequently: true });
                        ctx.drawImage(video, 0, 0, processWidth, processHeight);

                        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const src = cv.matFromImageData(imgData);
                        const { corners, found } = this.detectDocument(src, false); // Fast mode

                        if (found) {
                            // Scale corners back to original video dimensions
                            const scaleX = video.videoWidth / processWidth;
                            const scaleY = video.videoHeight / processHeight;

                            this.liveCorners = corners.map(c => ({
                                x: c.x * scaleX,
                                y: c.y * scaleY
                            }));
                            this.updateStability(this.liveCorners);
                        } else {
                            this.liveCorners = [];
                            this.liveCornersHistory = [];
                            this.isDocumentStable = false;
                        }
                        src.delete();
                    } catch (e) {
                        // Silent fail for live detection
                    }
                }, 150); // Increased FPS for better stability tracking
            },

            updateStability(corners) {
                // Keep history of last 5 frames
                this.liveCornersHistory.push(corners);
                if (this.liveCornersHistory.length > 5) {
                    this.liveCornersHistory.shift();
                }

                if (this.liveCornersHistory.length < 5) {
                    this.isDocumentStable = false;
                    return;
                }

                // Calculate variance of corners over history
                let isStable = true;
                const tolerance = 20; // Maximum pixel movement allowed across 5 frames

                for (let i = 0; i < 4; i++) {
                    let minX = this.liveCornersHistory[0][i].x;
                    let maxX = this.liveCornersHistory[0][i].x;
                    let minY = this.liveCornersHistory[0][i].y;
                    let maxY = this.liveCornersHistory[0][i].y;

                    for (let j = 1; j < this.liveCornersHistory.length; j++) {
                        minX = Math.min(minX, this.liveCornersHistory[j][i].x);
                        maxX = Math.max(maxX, this.liveCornersHistory[j][i].x);
                        minY = Math.min(minY, this.liveCornersHistory[j][i].y);
                        maxY = Math.max(maxY, this.liveCornersHistory[j][i].y);
                    }

                    if ((maxX - minX) > tolerance || (maxY - minY) > tolerance) {
                        isStable = false;
                        break;
                    }
                }

                this.isDocumentStable = isStable;
            },

            stopLiveDetection() {
                if (this.liveDetectionInterval) {
                    clearInterval(this.liveDetectionInterval);
                    this.liveDetectionInterval = null;
                }
                this.liveCorners = [];
                this.liveCornersHistory = [];
                this.isDocumentStable = false;
            },

            getLivePolygonPoints() {
                if (this.liveCorners.length !== 4) return '';
                // Map from video resolution to display resolution considering object-fit
                return this.liveCorners.map(c => {
                    const dispX = (c.x * this.videoDisplayScale.x) + this.videoOffset.x;
                    const dispY = (c.y * this.videoDisplayScale.y) + this.videoOffset.y;
                    return `${dispX},${dispY}`;
                }).join(' ');
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

                const originalDataUrl = canvas.toDataURL('image/jpeg', 0.98);

                // PROCESS IMAGE
                try {
                    if (typeof cv !== 'undefined') {
                        // 1. Detect Edges (SMARTER)
                        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const src = cv.matFromImageData(imgData);
                        let cornersToUse = [];
                        let isFound = false;

                        // Use live corners if they exist and are valid, otherwise detect from the high-res snapshot
                        if (this.liveCorners.length === 4) {
                            cornersToUse = this.expandCorners([...this.liveCorners], canvas.width, canvas.height, 0.01);
                            isFound = true;
                        } else {
                            const result = this.detectDocument(src);
                            cornersToUse = result.found ? this.expandCorners(result.corners, canvas.width, canvas.height, 0.01) : result.corners;
                            isFound = result.found;
                        }

                        // 2. Warp (Crop)
                        const croppedDataUrl = this.performWarp(src, cornersToUse, canvas.width, canvas.height);

                        // 3. Store
                        this.capturedImages.push({
                            id: Date.now(),
                            original: originalDataUrl,
                            cropped: croppedDataUrl,
                            corners: cornersToUse, // Store scaled to original image
                            isFound: isFound
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

            detectDocument(src, isHighRes = true) {
                const width = src.cols;
                const height = src.rows;
                let bestCorners = [];
                let isFound = false;

                const matsToDelete = [];
                const trackMat = (mat) => { if (mat) matsToDelete.push(mat); return mat; };

                try {
                    // 1. Preprocessing - Resize for speed
                    let processingSrc = src;
                    let scale = 1;
                    const maxDim = 800;

                    if (Math.max(width, height) > maxDim) {
                        scale = maxDim / Math.max(width, height);
                        processingSrc = trackMat(new cv.Mat());
                        cv.resize(src, processingSrc, new cv.Size(0, 0), scale, scale, cv.INTER_AREA);
                    }

                    const pWidth = processingSrc.cols;
                    const pHeight = processingSrc.rows;

                    const gray = trackMat(new cv.Mat());
                    cv.cvtColor(processingSrc, gray, cv.COLOR_RGBA2GRAY, 0);

                    // 2. Multi-strategy detection - try multiple approaches
                    let bestCandidate = null;
                    let maxArea = 0;
                    const minArea = pWidth * pHeight * 0.15;

                    // Helper: find quads from edge mat
                    const findQuadsFromEdges = (edgeMat) => {
                        const morphKernel = trackMat(cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(5, 5)));
                        const closed = trackMat(new cv.Mat());
                        cv.morphologyEx(edgeMat, closed, cv.MORPH_CLOSE, morphKernel);

                        // Also dilate to connect nearby edges
                        const dilateKernel = trackMat(cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(3, 3)));
                        const dilated = trackMat(new cv.Mat());
                        cv.dilate(closed, dilated, dilateKernel);

                        const contours = trackMat(new cv.MatVector());
                        const hierarchy = trackMat(new cv.Mat());
                        cv.findContours(dilated, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

                        for (let i = 0; i < contours.size(); ++i) {
                            const cnt = contours.get(i);
                            const area = cv.contourArea(cnt);
                            if (area < minArea) continue;

                            // Try multiple epsilon values for approxPolyDP
                            for (const epsFactor of [0.015, 0.02, 0.03, 0.04, 0.05]) {
                                const peri = cv.arcLength(cnt, true);
                                const approx = trackMat(new cv.Mat());
                                cv.approxPolyDP(cnt, approx, epsFactor * peri, true);

                                if (approx.rows === 4 && cv.isContourConvex(approx)) {
                                    // Score: prefer larger area and more rectangular shape
                                    const pts = [];
                                    for (let j = 0; j < 4; j++) {
                                        pts.push({ x: approx.data32S[j * 2], y: approx.data32S[j * 2 + 1] });
                                    }
                                    const sorted = this.sortPoints(pts);

                                    // Calculate angles - prefer close to 90 degrees
                                    const angleScore = this._calcRectScore(sorted);
                                    const score = area * angleScore;

                                    if (score > maxArea) {
                                        maxArea = score;
                                        bestCandidate = sorted.map(p => ({
                                            x: p.x / scale,
                                            y: p.y / scale
                                        }));
                                    }
                                }
                            }
                        }
                    };

                    // Strategy 1: Bilateral filter + adaptive Canny thresholds
                    const blurred1 = trackMat(new cv.Mat());
                    cv.bilateralFilter(gray, blurred1, 9, 75, 75, cv.BORDER_DEFAULT);

                    // Calculate Otsu threshold for adaptive Canny
                    const otsuThresh = trackMat(new cv.Mat());
                    const otsuVal = cv.threshold(gray, otsuThresh, 0, 255, cv.THRESH_BINARY | cv.THRESH_OTSU);
                    const cannyLow1 = Math.max(10, otsuVal * 0.33);
                    const cannyHigh1 = Math.min(255, otsuVal * 0.67);

                    const edges1 = trackMat(new cv.Mat());
                    cv.Canny(blurred1, edges1, cannyLow1, cannyHigh1, 3, true);
                    findQuadsFromEdges(edges1);

                    // Strategy 2: Gaussian blur + different Canny thresholds
                    if (!bestCandidate) {
                        const blurred2 = trackMat(new cv.Mat());
                        cv.GaussianBlur(gray, blurred2, new cv.Size(5, 5), 0);

                        // Live mode: fewer thresholds for speed
                        const thresholds = isHighRes
                            ? [[20, 80], [30, 120], [50, 200], [75, 250]]
                            : [[30, 120], [50, 200]];

                        for (const [low, high] of thresholds) {
                            const edges2 = trackMat(new cv.Mat());
                            cv.Canny(blurred2, edges2, low, high, 3, false);
                            findQuadsFromEdges(edges2);
                            if (bestCandidate) break;
                        }
                    }

                    // Strategy 3: Adaptive threshold + contour (skip for live mode)
                    if (!bestCandidate && isHighRes) {
                        const blurred3 = trackMat(new cv.Mat());
                        cv.GaussianBlur(gray, blurred3, new cv.Size(11, 11), 0);

                        const thresh = trackMat(new cv.Mat());
                        cv.adaptiveThreshold(blurred3, thresh, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY_INV, 15, 8);

                        findQuadsFromEdges(thresh);
                    }

                    // Strategy 4: Color-based detection (skip for live mode)
                    if (!bestCandidate && isHighRes && processingSrc.channels() >= 3) {
                        try {
                            const rgb = trackMat(new cv.Mat());
                            cv.cvtColor(processingSrc, rgb, cv.COLOR_RGBA2RGB);
                            const hsv = trackMat(new cv.Mat());
                            cv.cvtColor(rgb, hsv, cv.COLOR_RGB2HSV);

                            // Detect white/light areas (paper) using scalar bounds
                            const lowerBound = new cv.Scalar(0, 0, 160);
                            const upperBound = new cv.Scalar(180, 60, 255);
                            const mask = trackMat(new cv.Mat());
                            // Create bound mats matching HSV type
                            const low = trackMat(new cv.Mat(hsv.rows, hsv.cols, hsv.type(), lowerBound));
                            const high = trackMat(new cv.Mat(hsv.rows, hsv.cols, hsv.type(), upperBound));
                            cv.inRange(hsv, low, high, mask);

                            const morphK = trackMat(cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(7, 7)));
                            const morphed = trackMat(new cv.Mat());
                            cv.morphologyEx(mask, morphed, cv.MORPH_CLOSE, morphK);
                            cv.morphologyEx(morphed, morphed, cv.MORPH_OPEN, morphK);

                            const edgesFromMask = trackMat(new cv.Mat());
                            cv.Canny(morphed, edgesFromMask, 50, 150);
                            findQuadsFromEdges(edgesFromMask);
                        } catch(colorErr) {
                            console.warn("Color detection strategy failed:", colorErr);
                        }
                    }

                    if (bestCandidate) {
                        bestCorners = bestCandidate;
                        isFound = true;
                    } else {
                        // Fallback: place crop at 5% inset
                        const marginW = width * 0.05;
                        const marginH = height * 0.05;
                        bestCorners = [
                            {x: marginW, y: marginH},
                            {x: width - marginW, y: marginH},
                            {x: width - marginW, y: height - marginH},
                            {x: marginW, y: height - marginH}
                        ];
                        isFound = false;
                    }

                } catch(e) {
                    console.error("Detection Logic Error:", e);
                    bestCorners = this.getDefaultCorners(width, height);
                    isFound = false;
                } finally {
                    for (let i = matsToDelete.length - 1; i >= 0; i--) {
                        try {
                            const mat = matsToDelete[i];
                            if (mat && typeof mat.delete === 'function') {
                                mat.delete();
                            }
                        } catch(e) { /* ignore cleanup errors */ }
                    }
                }

                return { corners: bestCorners, found: isFound };
            },

            _calcRectScore(sortedPts) {
                // Score how rectangular a quadrilateral is (1.0 = perfect rectangle)
                const angles = [];
                for (let i = 0; i < 4; i++) {
                    const p1 = sortedPts[i];
                    const p2 = sortedPts[(i + 1) % 4];
                    const p3 = sortedPts[(i + 2) % 4];
                    const v1 = { x: p1.x - p2.x, y: p1.y - p2.y };
                    const v2 = { x: p3.x - p2.x, y: p3.y - p2.y };
                    const dot = v1.x * v2.x + v1.y * v2.y;
                    const mag = Math.sqrt(v1.x * v1.x + v1.y * v1.y) * Math.sqrt(v2.x * v2.x + v2.y * v2.y);
                    if (mag === 0) return 0;
                    const angle = Math.acos(Math.min(1, Math.max(-1, dot / mag))) * (180 / Math.PI);
                    angles.push(angle);
                }
                // Average deviation from 90 degrees
                const avgDev = angles.reduce((s, a) => s + Math.abs(a - 90), 0) / 4;
                // Score: 1.0 for perfect rectangle, lower for skewed shapes
                return Math.max(0, 1 - avgDev / 45);
            },

            performWarp(src, corners, width, height, filterType = 'original') {
                let srcTri = null;
                let dstTri = null;
                let M = null;
                let dst = null;
                let finalDst = null;

                try {
                     // Validate Corners for OpenCV
                     const safeCorners = [
                        Number(corners[0]?.x) || 0, Number(corners[0]?.y) || 0,
                        Number(corners[1]?.x) || 0, Number(corners[1]?.y) || 0,
                        Number(corners[2]?.x) || 0, Number(corners[2]?.y) || 0,
                        Number(corners[3]?.x) || 0, Number(corners[3]?.y) || 0
                     ];

                     // Convert corners array to flat array for OpenCV
                     srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, safeCorners);

                    // Calculate dimensions of the new cropped image
                    const wTop = Math.hypot(corners[1].x - corners[0].x, corners[1].y - corners[0].y);
                    const wBot = Math.hypot(corners[2].x - corners[3].x, corners[2].y - corners[3].y);
                    const hLeft = Math.hypot(corners[3].x - corners[0].x, corners[3].y - corners[0].y);
                    const hRight = Math.hypot(corners[2].x - corners[1].x, corners[2].y - corners[1].y);

                    let maxWidth = Math.round(Math.max(wTop, wBot));
                    let maxHeight = Math.round(Math.max(hLeft, hRight));

                    // Validation: Ensure valid dimensions to prevent WASM table index errors
                    if (maxWidth < 1) maxWidth = 1;
                    if (maxHeight < 1) maxHeight = 1;

                    dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                        0, 0,
                        maxWidth, 0,
                        maxWidth, maxHeight,
                        0, maxHeight
                    ]);

                    M = cv.getPerspectiveTransform(srcTri, dstTri);
                    dst = new cv.Mat();
                    cv.warpPerspective(src, dst, M, new cv.Size(maxWidth, maxHeight), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar(0, 0, 0, 0));

                    // Apply Filter
                    if (filterType && filterType !== 'original') {
                        finalDst = this.applyFilter(dst, filterType);
                    } else {
                        finalDst = dst;
                    }

                    // Draw to temp canvas
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = maxWidth;
                    tempCanvas.height = maxHeight;
                    cv.imshow(tempCanvas, finalDst);
                    return tempCanvas.toDataURL('image/jpeg', 0.98);

                } catch (e) {
                    console.error("Warp Error:", e);
                    throw e;
                } finally {
                    if (finalDst && finalDst !== dst) finalDst.delete();
                    if (srcTri) srcTri.delete();
                    if (dstTri) dstTri.delete();
                    if (M) M.delete();
                    if (dst) dst.delete();
                }
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

            expandCorners(corners, imgW, imgH, ratio) {
                // Expand each corner outward from center by ratio (e.g. 0.01 = 1%)
                const cx = corners.reduce((s, c) => s + c.x, 0) / 4;
                const cy = corners.reduce((s, c) => s + c.y, 0) / 4;
                return corners.map(c => ({
                    x: Math.max(0, Math.min(imgW, c.x + (c.x - cx) * ratio)),
                    y: Math.max(0, Math.min(imgH, c.y + (c.y - cy) * ratio))
                }));
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
                    corners: this.getDefaultCorners(2480, 3508),
                    isFound: true
                });
                // Optional: Scroll to bottom
            },

            renderLayoutToCanvas(type, images) {
                const a4w = 2480; // 300 DPI A4
                const a4h = 3508; // 300 DPI A4
                const canvas = document.createElement('canvas');
                canvas.width = a4w;
                canvas.height = a4h;
                const ctx = canvas.getContext('2d');

                // Fill White Background
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, a4w, a4h);

                const margin = 80; // Scaled for 300 DPI

                // Helper to draw image fitting within a box
                const drawFit = (img, x, y, w, h) => {
                    const scale = Math.min(w / img.width, h / img.height);
                    const drawW = img.width * scale;
                    const drawH = img.height * scale;
                    const drawX = x + (w - drawW) / 2;
                    const drawY = y + (h - drawH) / 2;
                    ctx.drawImage(img, drawX, drawY, drawW, drawH);
                };

                // Helper to draw image with rounded corners (card-like)
                const drawFitRounded = (img, x, y, w, h, radius) => {
                    const scale = Math.min(w / img.width, h / img.height);
                    const drawW = img.width * scale;
                    const drawH = img.height * scale;
                    const drawX = x + (w - drawW) / 2;
                    const drawY = y + (h - drawH) / 2;

                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(drawX + radius, drawY);
                    ctx.lineTo(drawX + drawW - radius, drawY);
                    ctx.quadraticCurveTo(drawX + drawW, drawY, drawX + drawW, drawY + radius);
                    ctx.lineTo(drawX + drawW, drawY + drawH - radius);
                    ctx.quadraticCurveTo(drawX + drawW, drawY + drawH, drawX + drawW - radius, drawY + drawH);
                    ctx.lineTo(drawX + radius, drawY + drawH);
                    ctx.quadraticCurveTo(drawX, drawY + drawH, drawX, drawY + drawH - radius);
                    ctx.lineTo(drawX, drawY + radius);
                    ctx.quadraticCurveTo(drawX, drawY, drawX + radius, drawY);
                    ctx.closePath();
                    ctx.clip();
                    ctx.drawImage(img, drawX, drawY, drawW, drawH);
                    ctx.restore();
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
                    const pw = 2078; // 300 DPI Passport
                    const ph = 1476; // 300 DPI Passport
                    // Center it
                    if(images[0]) drawFit(images[0], (a4w - pw)/2, (a4h - ph)/2, pw, ph);
                }
                else if (type === 'card') {
                    // Credit Card (ID-1): 85.6mm x 54mm @ 300DPI
                    const cw = 1164; // 300 DPI ID Card
                    const ch = 734; // 300 DPI ID Card
                    // Real card corner radius ~3mm = (3/25.4)*300 ≈ 35px
                    const cardRadius = 35;
                    if(images[0]) drawFitRounded(images[0], (a4w - cw)/2, (a4h - ch)/2, cw, ch, cardRadius);
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
                    const cardW = 1164; // 300 DPI ID Card
                    const cardH = 734; // 300 DPI ID Card
                    // Real card corner radius ~3mm = (3/25.4)*300 ≈ 35px
                    const cardRadius = 35;

                    const topY = a4h/4 - cardH/2;
                    const botY = a4h*3/4 - cardH/2;

                    if(images[0]) drawFitRounded(images[0], (a4w - cardW)/2, topY, cardW, cardH, cardRadius);
                    if(images[1]) drawFitRounded(images[1], (a4w - cardW)/2, botY, cardW, cardH, cardRadius);
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

                return canvas.toDataURL('image/jpeg', 0.98);
            },

            // --- EDIT / CROP LOGIC ---

            startEdit(index) {
                this.currentEditIndex = index;
                this.view = 'crop';
                this.rotation = 0;
                this.activeFilter = 'original';
                this.cropZoom = 1;
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
                    const wrapper = this.$refs.cropWrapper;
                    const svg = this.$refs.cropSvg;
                    const container = this.$refs.cropContainer;

                    // Preserve HIGH RESOLUTION for cropping so text isn't blurry.
                    const maxCropRes = 3000;
                    let scale = 1;
                    if (this.imageWidth > maxCropRes || this.imageHeight > maxCropRes) {
                        scale = Math.min(maxCropRes / this.imageWidth, maxCropRes / this.imageHeight);
                    }

                    this.canvasWidth = Math.max(1, Math.floor(this.imageWidth * scale));
                    this.canvasHeight = Math.max(1, Math.floor(this.imageHeight * scale));

                    // Set canvas internal resolution = logical size (full quality)
                    canvas.width = this.canvasWidth;
                    canvas.height = this.canvasHeight;

                    // Calculate base fit scale
                    const containerRect = container.getBoundingClientRect();
                    const availW = containerRect.width - 16;
                    const availH = containerRect.height - 16;
                    this.cropZoomFitScale = Math.min(availW / this.canvasWidth, availH / this.canvasHeight);

                    // Apply zoom display
                    this._applyCropZoom();

                    // Render image to canvas
                    this.renderEditorCanvas();

                    this.scaleX = this.canvasWidth / this.imageWidth;
                    this.scaleY = this.canvasHeight / this.imageHeight;

                    if (forceFull) {
                        this.resetToFull();
                    } else {
                        if (isNormalized) {
                            this.corners = savedCorners.map(c => ({
                                x: c.u * this.canvasWidth,
                                y: c.v * this.canvasHeight
                            }));
                        } else {
                            this.corners = savedCorners.map(c => ({
                                x: c.x * this.scaleX,
                                y: c.y * this.scaleY
                            }));
                        }
                    }

                    console.log('CropEditor loaded:', {
                        canvasW: this.canvasWidth, canvasH: this.canvasHeight,
                        fitScale: this.cropZoomFitScale, zoom: this.cropZoom,
                        corners: JSON.parse(JSON.stringify(this.corners))
                    });
                };
                img.src = src;
            },

            _applyCropZoom() {
                const wrapper = this.$refs.cropWrapper;
                const canvas = this.$refs.cropCanvas;
                const svg = this.$refs.cropSvg;
                if (!wrapper || !canvas) return;

                const displayW = Math.max(1, Math.floor(this.canvasWidth * this.cropZoomFitScale * this.cropZoom));
                const displayH = Math.max(1, Math.floor(this.canvasHeight * this.cropZoomFitScale * this.cropZoom));

                canvas.style.width = displayW + 'px';
                canvas.style.height = displayH + 'px';
                wrapper.style.width = displayW + 'px';
                wrapper.style.height = displayH + 'px';
                if (svg) {
                    svg.setAttribute('width', displayW);
                    svg.setAttribute('height', displayH);
                    svg.setAttribute('viewBox', `0 0 ${this.canvasWidth} ${this.canvasHeight}`);
                }
            },

            setCropZoom(level) {
                this.cropZoom = Math.max(0.5, Math.min(4, level));
                this._applyCropZoom();
            },

            handleCropZoomWheel(e) {
                // Ctrl+wheel = zoom, plain wheel = normal scroll
                if (!e.ctrlKey) return;
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.25 : 0.25;
                this.setCropZoom(this.cropZoom + delta);
            },

            renderEditorCanvas() {
                if (!this.editorSourceCanvas || this.canvasWidth < 1 || this.canvasHeight < 1) return;

                const canvas = this.$refs.cropCanvas;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });

                // Draw Original at full canvas resolution
                ctx.drawImage(this.editorSourceCanvas, 0, 0, canvas.width, canvas.height);

                // 2. Apply Filter (if not original)
                if (this.activeFilter !== 'original' && typeof cv !== 'undefined' && this.cvLoaded) {
                    let srcMat = null;
                    let dstMat = null;
                    try {
                        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        srcMat = cv.matFromImageData(imgData);
                        dstMat = this.applyFilter(srcMat, this.activeFilter);
                        cv.imshow(canvas, dstMat);
                    } catch (e) {
                        console.error("Preview Filter Error:", e);
                    } finally {
                        if (srcMat) srcMat.delete();
                        // applyFilter usually returns a NEW Mat, so we must delete it.
                        // If it returned srcMat (unlikely per implementation), we shouldn't double delete.
                        // But my applyFilter always returns a new Mat 'dst'.
                        if (dstMat && dstMat !== srcMat) dstMat.delete();
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
                if (typeof cv === 'undefined') {
                    alert("Image Processing Engine (OpenCV) is not loaded. Cannot save edits.");
                    return;
                }
                const item = this.capturedImages[this.currentEditIndex];

                try {
                    // Fix: Ensure corners are sorted (TL, TR, BR, BL) to prevent twisting
                    this.corners = this.sortPoints(this.corners);

                    // VALIDATION: Check Scaling Factors
                    if (!Number.isFinite(this.scaleX) || this.scaleX <= 0) this.scaleX = 1;
                    if (!Number.isFinite(this.scaleY) || this.scaleY <= 0) this.scaleY = 1;

                    const realCorners = this.corners.map(c => ({
                        x: Number.isFinite(c.x) ? (c.x / this.scaleX) : 0,
                        y: Number.isFinite(c.y) ? (c.y / this.scaleY) : 0
                    }));

                    const img = new Image();
                    img.onload = () => {
                        let src = null;
                        try {
                            // Apply Rotation to Source
                            const canvas = document.createElement('canvas');
                            // Swap dims if 90/270
                            if (this.rotation % 180 !== 0) {
                                canvas.width = Math.max(1, img.height);
                                canvas.height = Math.max(1, img.width);
                            } else {
                                canvas.width = Math.max(1, img.width);
                                canvas.height = Math.max(1, img.height);
                            }

                            const ctx = canvas.getContext('2d');
                            ctx.translate(canvas.width/2, canvas.height/2);
                            ctx.rotate(this.rotation * Math.PI / 180);
                            ctx.drawImage(img, -img.width/2, -img.height/2);

                            // Read from rotated canvas
                            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            src = cv.matFromImageData(imgData);
                            const newCroppedUrl = this.performWarp(src, realCorners, canvas.width, canvas.height, this.activeFilter);

                            // Create updated object (Deep Copy)
                            const updatedItem = {
                                ...this.capturedImages[this.currentEditIndex],
                                id: Date.now(), // Force reactivity
                                cropped: newCroppedUrl,
                                corners: realCorners,
                                original: canvas.toDataURL('image/jpeg', 0.98)
                            };

                            // Update State with Splice to ensure Alpine detects change
                            this.capturedImages.splice(this.currentEditIndex, 1, updatedItem);

                            // Reset rotation because 'original' is now the rotated image
                            this.rotation = 0;
                            this.view = 'review';
                        } catch (innerError) {
                            console.error("Processing Error during Save:", innerError);
                            alert("Failed to process image rotation: " + innerError.message);
                        } finally {
                            if (src) src.delete();
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
                if (!src || src.isDeleted() || src.empty()) return src;
                const dst = new cv.Mat();
                try {
                    if (type === 'original' || type === 'photo') {
                        src.copyTo(dst);
                    }
                    else if (type === 'scan_doc') {
                        // Check image size for kernel
                        if (src.cols < 105 || src.rows < 105) {
                             src.copyTo(dst);
                             return dst;
                        }

                        // Smart Grayscale
                        const gray = new cv.Mat();
                        const bg = new cv.Mat();
                        const blurred = new cv.Mat();
                        try {
                            cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);

                            // 1. Estimate Illumination
                            cv.GaussianBlur(gray, bg, new cv.Size(101, 101), 0, 0, cv.BORDER_DEFAULT);

                            // 2. Division Normalization
                            cv.divide(gray, bg, dst, 255);

                            // 3. Sharpen
                            cv.GaussianBlur(dst, blurred, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
                            cv.addWeighted(dst, 1.5, blurred, -0.5, 0, dst);
                        } finally {
                            gray.delete(); bg.delete(); blurred.delete();
                        }
                    }
                    else if (type === 'high_contrast') {
                         const rgb = new cv.Mat();
                         const lab = new cv.Mat();
                         const planes = new cv.MatVector();
                         const mergedPlanes = new cv.MatVector();
                         const resultRGB = new cv.Mat();
                         let l = null, a = null, b = null, clahe = null;

                         try {
                             cv.cvtColor(src, rgb, cv.COLOR_RGBA2RGB);
                             cv.cvtColor(rgb, lab, cv.COLOR_RGB2Lab);
                             cv.split(lab, planes);

                             l = planes.get(0);
                             a = planes.get(1);
                             b = planes.get(2);

                             // CLAHE Constructor Check
                             if (cv.CLAHE) {
                                 clahe = new cv.CLAHE(3.0, new cv.Size(8, 8));
                                 clahe.apply(l, l);
                             } else {
                                 // Fallback if CLAHE missing
                                 console.warn("CLAHE not available");
                             }

                             mergedPlanes.push_back(l);
                             mergedPlanes.push_back(a);
                             mergedPlanes.push_back(b);

                             cv.merge(mergedPlanes, lab);
                             cv.cvtColor(lab, resultRGB, cv.COLOR_Lab2RGB);
                             cv.cvtColor(resultRGB, dst, cv.COLOR_RGB2RGBA);
                         } finally {
                             if(rgb) rgb.delete();
                             if(lab) lab.delete();
                             if(planes) planes.delete();
                             if(mergedPlanes) mergedPlanes.delete();
                             if(l) l.delete();
                             if(a) a.delete();
                             if(b) b.delete();
                             if(clahe) clahe.delete();
                             if(resultRGB) resultRGB.delete();
                         }
                    }
                    else if (type === 'gray') {
                        cv.cvtColor(src, dst, cv.COLOR_RGBA2GRAY);
                    }
                    else if (type === 'bw') {
                        const gray = new cv.Mat();
                        try {
                            cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
                            cv.adaptiveThreshold(gray, dst, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY, 15, 10);
                        } finally {
                            gray.delete();
                        }
                    }
                    else if (type === 'magic') {
                         if (src.cols < 105 || src.rows < 105) {
                             src.copyTo(dst);
                             return dst;
                         }

                         const rgb = new cv.Mat();
                         const hsv = new cv.Mat();
                         const planes = new cv.MatVector();
                         const newPlanes = new cv.MatVector();
                         const bg = new cv.Mat();
                         const blurredV = new cv.Mat();
                         const resultRGB = new cv.Mat();
                         let h = null, s = null, v = null;

                         try {
                             cv.cvtColor(src, rgb, cv.COLOR_RGBA2RGB);
                             cv.cvtColor(rgb, hsv, cv.COLOR_RGB2HSV);
                             cv.split(hsv, planes);

                             h = planes.get(0);
                             s = planes.get(1);
                             v = planes.get(2);

                             // 1. Illumination Normalization
                             cv.GaussianBlur(v, bg, new cv.Size(101, 101), 0, 0, cv.BORDER_DEFAULT);
                             cv.divide(v, bg, v, 255);

                             // 2. Sharpening
                             cv.GaussianBlur(v, blurredV, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
                             cv.addWeighted(v, 1.5, blurredV, -0.5, 0, v);

                             newPlanes.push_back(h);
                             newPlanes.push_back(s);
                             newPlanes.push_back(v);

                             cv.merge(newPlanes, hsv);
                             cv.cvtColor(hsv, resultRGB, cv.COLOR_HSV2RGB);
                             cv.cvtColor(resultRGB, dst, cv.COLOR_RGB2RGBA);
                         } finally {
                             if(rgb) rgb.delete();
                             if(hsv) hsv.delete();
                             if(planes) planes.delete();
                             if(newPlanes) newPlanes.delete();
                             if(bg) bg.delete();
                             if(blurredV) blurredV.delete();
                             if(resultRGB) resultRGB.delete();
                             if(h) h.delete();
                             if(s) s.delete();
                             if(v) v.delete();
                         }
                    }
                    else {
                        src.copyTo(dst);
                    }
                } catch (e) {
                    console.error("Filter Error (" + type + "):", e);
                    try {
                        if (dst && !dst.isDeleted()) {
                             src.copyTo(dst); // Fallback to original
                        }
                    } catch (err) {
                        console.error("Fallback failed:", err);
                    }
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

            getLoupePosition() {
                // Fixed top-left position — no longer follows finger
                return '';
            },

            startDrag(type, index, e) {
                e.preventDefault();
                e.stopPropagation();

                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                this.previousMousePosition = { x: clientX, y: clientY };
                this.isDragging = true;

                if (type === 'corner') {
                    this.activeDragIndex = index;
                    this.activeDragEdge = -1;
                    this.activeDragMidpoint = -1;
                    this.dragPosition = { ...this.corners[index] };
                    this.loupeScreenPos = { x: clientX, y: clientY };
                    this.$nextTick(() => this._renderLoupe(this.corners[index].x, this.corners[index].y));
                } else if (type === 'edge') {
                    this.activeDragEdge = index;
                    this.activeDragIndex = -1;
                    this.activeDragMidpoint = -1;
                } else if (type === 'midpoint') {
                    this.activeDragMidpoint = index;
                    this.activeDragIndex = -1;
                    this.activeDragEdge = -1;
                }
            },

            onDrag(e) {
                if (this.activeDragIndex === -1 && this.activeDragEdge === -1 && this.activeDragMidpoint === -1) return;
                e.preventDefault();

                const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                const dx = clientX - this.previousMousePosition.x;
                const dy = clientY - this.previousMousePosition.y;
                this.previousMousePosition = { x: clientX, y: clientY };

                const canvasRect = this.$refs.cropCanvas.getBoundingClientRect();
                const scaleX = this.canvasWidth / canvasRect.width;
                const scaleY = this.canvasHeight / canvasRect.height;

                if (this.activeDragIndex !== -1) {
                    // Corner drag: absolute position
                    let x = (clientX - canvasRect.left) * scaleX;
                    let y = (clientY - canvasRect.top) * scaleY;
                    x = Math.max(0, Math.min(x, this.canvasWidth));
                    y = Math.max(0, Math.min(y, this.canvasHeight));

                    const newCorners = [...this.corners];
                    newCorners[this.activeDragIndex] = { x, y };
                    this.corners = newCorners;
                    this.dragPosition = { x, y };

                    // Update loupe position (raw screen coords, getLoupePosition() handles offset)
                    this.loupeScreenPos = { x: clientX, y: clientY };
                    this._renderLoupe(x, y);

                } else if (this.activeDragEdge !== -1) {
                    // Edge drag: move both endpoints by delta
                    const scaledDx = dx * scaleX;
                    const scaledDy = dy * scaleY;
                    const p1 = this.activeDragEdge;
                    const p2 = (this.activeDragEdge + 1) % 4;
                    const newCorners = [...this.corners];

                    [p1, p2].forEach(idx => {
                        newCorners[idx] = {
                            x: Math.max(0, Math.min(newCorners[idx].x + scaledDx, this.canvasWidth)),
                            y: Math.max(0, Math.min(newCorners[idx].y + scaledDy, this.canvasHeight))
                        };
                    });
                    this.corners = newCorners;

                } else if (this.activeDragMidpoint !== -1) {
                    // Midpoint drag: move both endpoints of that edge
                    const scaledDx = dx * scaleX;
                    const scaledDy = dy * scaleY;
                    const p1 = this.activeDragMidpoint;
                    const p2 = (this.activeDragMidpoint + 1) % 4;
                    const newCorners = [...this.corners];

                    // Determine movement axis based on edge orientation
                    // Edge 0 (top) & Edge 2 (bottom): primarily vertical
                    // Edge 1 (right) & Edge 3 (left): primarily horizontal
                    [p1, p2].forEach(idx => {
                        newCorners[idx] = {
                            x: Math.max(0, Math.min(newCorners[idx].x + scaledDx, this.canvasWidth)),
                            y: Math.max(0, Math.min(newCorners[idx].y + scaledDy, this.canvasHeight))
                        };
                    });
                    this.corners = newCorners;
                }
            },

            _renderLoupe(canvasX, canvasY) {
                const loupeCanvas = this.$refs.loupeCanvas;
                const srcCanvas = this.$refs.cropCanvas;
                if (!loupeCanvas || !srcCanvas) return;

                const ctx = loupeCanvas.getContext('2d');
                const loupeSize = 320; // canvas internal size (matches canvas element)
                const zoomFactor = 3.5; // 3.5x magnification for better precision
                const srcSize = loupeSize / zoomFactor;

                // Map from logical coords to actual canvas pixels
                const pixelX = (canvasX / this.canvasWidth) * srcCanvas.width;
                const pixelY = (canvasY / this.canvasHeight) * srcCanvas.height;
                const srcPixelSize = (srcSize / this.canvasWidth) * srcCanvas.width;

                // Clamp source rect to stay within canvas bounds (prevents black bars)
                let sx = pixelX - srcPixelSize / 2;
                let sy = pixelY - srcPixelSize / 2;
                let sw = srcPixelSize;
                let sh = srcPixelSize;
                let dx = 0, dy = 0, dw = loupeSize, dh = loupeSize;

                // Fill background with the image's edge color instead of black
                ctx.fillStyle = '#374151'; // dark gray background
                ctx.fillRect(0, 0, loupeSize, loupeSize);

                // Clamp: if source goes out of bounds, adjust destination accordingly
                if (sx < 0) {
                    const overflow = -sx;
                    const ratio = overflow / sw;
                    dx = ratio * dw;
                    dw -= ratio * dw;
                    sw -= overflow;
                    sx = 0;
                }
                if (sy < 0) {
                    const overflow = -sy;
                    const ratio = overflow / sh;
                    dy = ratio * dh;
                    dh -= ratio * dh;
                    sh -= overflow;
                    sy = 0;
                }
                if (sx + sw > srcCanvas.width) {
                    const overflow = (sx + sw) - srcCanvas.width;
                    const ratio = overflow / srcPixelSize;
                    dw -= ratio * loupeSize;
                    sw = srcCanvas.width - sx;
                }
                if (sy + sh > srcCanvas.height) {
                    const overflow = (sy + sh) - srcCanvas.height;
                    const ratio = overflow / srcPixelSize;
                    dh -= ratio * loupeSize;
                    sh = srcCanvas.height - sy;
                }

                if (sw > 0 && sh > 0 && dw > 0 && dh > 0) {
                    ctx.drawImage(srcCanvas, sx, sy, sw, sh, dx, dy, dw, dh);
                }

                // Draw crop border lines that pass through this region
                // Convert each corner from canvas logical coords to loupe pixel coords
                const cornerIdx = this.activeDragIndex;
                const prevIdx = (cornerIdx + 3) % 4; // previous corner
                const nextIdx = (cornerIdx + 1) % 4; // next corner

                // Center of loupe = current corner position
                const centerLoupeX = loupeSize / 2;
                const centerLoupeY = loupeSize / 2;
                const scale = loupeSize / srcSize; // pixels per canvas-logical-unit

                // Helper: convert canvas logical coord to loupe coord
                const toLoupe = (cx, cy) => ({
                    lx: centerLoupeX + (cx - canvasX) * scale,
                    ly: centerLoupeY + (cy - canvasY) * scale
                });

                // Draw the two crop lines meeting at the active corner
                ctx.strokeStyle = '#22c55e';
                ctx.lineWidth = 3;
                ctx.shadowColor = 'rgba(0,0,0,0.4)';
                ctx.shadowBlur = 3;

                // Line from previous corner to active corner
                const prev = toLoupe(this.corners[prevIdx].x, this.corners[prevIdx].y);
                const curr = toLoupe(this.corners[cornerIdx].x, this.corners[cornerIdx].y);
                const next = toLoupe(this.corners[nextIdx].x, this.corners[nextIdx].y);

                ctx.beginPath();
                ctx.moveTo(prev.lx, prev.ly);
                ctx.lineTo(curr.lx, curr.ly);
                ctx.lineTo(next.lx, next.ly);
                ctx.stroke();

                // Reset shadow
                ctx.shadowColor = 'transparent';
                ctx.shadowBlur = 0;

                // Draw crosshair at center
                ctx.strokeStyle = '#22c55e';
                ctx.lineWidth = 2;
                const chSize = 18;
                // Horizontal
                ctx.beginPath();
                ctx.moveTo(centerLoupeX - chSize, centerLoupeY);
                ctx.lineTo(centerLoupeX + chSize, centerLoupeY);
                ctx.stroke();
                // Vertical
                ctx.beginPath();
                ctx.moveTo(centerLoupeX, centerLoupeY - chSize);
                ctx.lineTo(centerLoupeX, centerLoupeY + chSize);
                ctx.stroke();
                // Center dot
                ctx.fillStyle = '#22c55e';
                ctx.beginPath();
                ctx.arc(centerLoupeX, centerLoupeY, 3, 0, Math.PI * 2);
                ctx.fill();
            },

            stopDrag() {
                this.activeDragIndex = -1;
                this.activeDragEdge = -1;
                this.activeDragMidpoint = -1;
                this.isDragging = false;
            },

            // --- FINALIZATION ---

            async finalizeProcess() {
                this.isLoading = true;
                this.loadingMessage = 'Generating Output...';

                try {
                    // 1. Validate Target Input
                    if (!this.targetInputId) {
                        throw new Error("Target input ID is missing.");
                    }
                    const input = document.getElementById(this.targetInputId);
                    if (!input) {
                        throw new Error(`Target input element (#${this.targetInputId}) not found.`);
                    }

                    const dt = new DataTransfer();

                    // Standard Logic: Bundle everything into the input
                    // Whether 1 or multiple images, always generate a PDF.
                    // Note: If user created a Layout, it is just another image in the list.
                    // The user is expected to delete source images if they only want the layout.

                    if (this.capturedImages.length >= 1) {
                        // Check if jsPDF is loaded
                        if (!window.jspdf) {
                            throw new Error("PDF generation library (jsPDF) is not loaded. Please check your internet connection.");
                        }

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
                    // Flag the input so the interceptor knows this change comes from the scanner
                    input.dataset.scannerSource = 'true';
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));

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
