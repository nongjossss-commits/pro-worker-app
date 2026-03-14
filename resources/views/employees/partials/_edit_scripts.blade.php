<script>
    // --- Global Cropper State & Logic ---
    // This ensures we only attach listeners to the global modal ONCE,
    // preventing the "stacking listeners" bug which caused "Canvas creation failed".
    window.cropperManager = {
        initialized: false,
        instance: null,
        originalFile: null,
        editedFile: null, // Track manually edited/refined file
        mimeType: null, // Track mime type for transparency support
        targetInputId: null,
        targetPreviewId: null
    };

    window.openCropperWithUrl = async function(url, targetInputId, targetPreviewId) {
        // 1. Ensure global cropper logic is ready
        window.initCropperGlobal();

        const imageToCrop = document.getElementById('imageToCrop');
        const cropperModalEl = document.getElementById('cropperModal');

        if (!imageToCrop || !cropperModalEl) {
             console.error('Cropper elements not found');
             return;
        }

        try {
            // Fetch the image
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok');
            const blob = await response.blob();

            // Create a File object
            // Try to guess extension from blob type
            let extension = 'jpg';
            if (blob.type === 'image/png') extension = 'png';
            else if (blob.type === 'image/webp') extension = 'webp';

            const file = new File([blob], `existing-image.${extension}`, { type: blob.type });

            // Update global state
            window.cropperManager.originalFile = file;
            window.cropperManager.editedFile = null; // Reset
            window.cropperManager.mimeType = blob.type;
            window.cropperManager.targetInputId = targetInputId;
            window.cropperManager.targetPreviewId = targetPreviewId;

            // Update Image Source
            imageToCrop.src = URL.createObjectURL(blob);

            // Open Modal
            const modal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);
            modal.show();

        } catch (error) {
            console.error('Error loading image for cropping:', error);
            alert('{{ __('ไม่สามารถโหลดรูปภาพได้: ') }}') + error.message);
        }
    };

    window.initCropperGlobal = function() {
        if (window.cropperManager.initialized) return;

        const cropperModalEl = document.getElementById('cropperModal');
        if (!cropperModalEl) return;

        // Mark as initialized so this block runs only once per page load
        window.cropperManager.initialized = true;

        const imageToCrop = document.getElementById('imageToCrop');
        const cropImageBtn = document.getElementById('cropImageBtn');

        // --- New Elements for Review ---
        const cropperContainer = document.getElementById('cropperContainer');
        const cropperReviewContainer = document.getElementById('cropperReviewContainer');
        const reviewImage = document.getElementById('reviewImage');
        const cropToolbar = document.getElementById('cropToolbar');
        const reviewToolbar = document.getElementById('reviewToolbar');
        const enhanceBtn = document.getElementById('enhanceBtn');
        const confirmSaveBtn = document.getElementById('confirmSaveBtn');
        const backToCropBtn = document.getElementById('backToCropBtn');
        const bgToolbarContainer = document.getElementById('bgToolbarContainer');

        // Retrieve or create bootstrap modal instance
        let cropperModal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);

        // --- Helper: Init Cropper Instance ---
        function initCropperInstance() {
            if (typeof Cropper === 'undefined') {
                alert('{{ __('ไม่สามารถโหลดเครื่องมือตัดภาพได้ (Cropper.js) กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต') }}'));
                return;
            }

            try {
                window.cropperManager.instance = new Cropper(imageToCrop, {
                    aspectRatio: 150 / 180,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 0.8,
                    movable: true,
                    zoomable: true,
                    rotatable: true,
                    scalable: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    minCropBoxWidth: 50,
                    minCropBoxHeight: 50,
                    checkCrossOrigin: false,
                    ready: function () {
                        if(cropImageBtn) cropImageBtn.disabled = false;
                    },
                });
            } catch (err) {
                console.error(err);
                alert('{{ __('เกิดข้อผิดพลาดในการเริ่มทำงาน Cropper: ') }}') + err.message);
            }
        }

        // --- Background Removal Logic ---
        const bgToolbar = document.getElementById('bgToolbar');
        const loadingOverlay = document.getElementById('cropperLoadingOverlay');
        const loadingText = document.getElementById('cropperLoadingText');
        const cancelBtn = document.getElementById('cancelProcessingBtn'); // New button

        let currentCancellationToken = null;

        if (bgToolbar) {
            bgToolbar.addEventListener('click', async function(e) {
                const btn = e.target.closest('button[data-bg-action]');
                if (!btn) return;

                const action = btn.dataset.bgAction;

                // Determine source file: use edited file if available, unless reverting to original
                let fileToProcess = window.cropperManager.originalFile;
                let isEdited = false;

                if (action === 'original') {
                    // Explicitly revert to original
                    window.cropperManager.editedFile = null;
                } else if (window.cropperManager.editedFile) {
                    // Use the edited version (e.g. manually erased)
                    fileToProcess = window.cropperManager.editedFile;
                    isEdited = true;
                }

                if (!fileToProcess) {
                    alert('No image selected');
                    return;
                }

                // Disable UI
                const allButtons = cropperModalEl.querySelectorAll('button');
                allButtons.forEach(b => b.disabled = true);
                if(cancelBtn) cancelBtn.disabled = false;

                try {
                    // Show Loading
                    if (loadingOverlay) loadingOverlay.classList.remove('d-none');
                    if (loadingText) loadingText.textContent = 'Processing...';

                    // Create Token
                    currentCancellationToken = { cancelled: false, onCancel: null };

                    // Process
                    const processedBlob = await window.backgroundRemoval.process(fileToProcess, action, (active, text) => {
                        if (loadingText && text) loadingText.textContent = text;
                    }, currentCancellationToken, isEdited);

                    // Check Cancellation
                    if (currentCancellationToken.cancelled) return;

                    // Update Mime Type for Saving
                    if (action === 'transparent') {
                        window.cropperManager.mimeType = 'image/png';
                    } else {
                        // For colored backgrounds or original, default to JPEG for efficiency
                        // unless original was something else we want to keep?
                        // For now, forcing JPEG for non-transparent ensures small file size.
                        window.cropperManager.mimeType = 'image/jpeg';
                    }

                    // Replace Image
                    const newUrl = URL.createObjectURL(processedBlob);
                    imageToCrop.src = newUrl;

                    // Re-init Cropper
                    if (window.cropperManager.instance) window.cropperManager.instance.destroy();
                    initCropperInstance();

                } catch (err) {
                    if (err.message === 'Cancelled by user') {
                        console.log('Processing cancelled');
                    } else {
                        console.error(err);
                        alert('Failed to process image: ' + err.message);
                    }
                } finally {
                    // Hide Loading
                    if (loadingOverlay) loadingOverlay.classList.add('d-none');
                    // Enable UI
                    const allButtons = cropperModalEl.querySelectorAll('button');
                    allButtons.forEach(b => b.disabled = false);
                    currentCancellationToken = null;
                }
            });
        }

        // Cancel Button Listener
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                if (currentCancellationToken) {
                    currentCancellationToken.cancelled = true;
                    if (currentCancellationToken.onCancel) currentCancellationToken.onCancel();
                    if (loadingText) loadingText.textContent = 'Cancelling...';
                }
            });
        }

        // --- Event: Modal Shown ---
        cropperModalEl.addEventListener('shown.bs.modal', function () {
            if (cropImageBtn) cropImageBtn.disabled = true;

            // Reset View to Crop Mode
            if(cropperContainer) cropperContainer.classList.remove('d-none');
            if(cropToolbar) cropToolbar.classList.remove('d-none');
            if(cropperReviewContainer) cropperReviewContainer.classList.add('d-none');
            if(reviewToolbar) reviewToolbar.classList.add('d-none');
            if(bgToolbarContainer) bgToolbarContainer.classList.remove('d-none');

            // Destroy existing cropper if any to be safe
            if (window.cropperManager.instance) {
                window.cropperManager.instance.destroy();
                window.cropperManager.instance = null;
            }

            // Ensure image is loaded
            if (imageToCrop.complete) {
                initCropperInstance();
            } else {
                imageToCrop.onload = function() {
                    initCropperInstance();
                };
            }
        });

        // --- Event: Modal Hidden ---
        cropperModalEl.addEventListener('hidden.bs.modal', function () {
            if (window.cropperManager.instance) {
                window.cropperManager.instance.destroy();
                window.cropperManager.instance = null;
            }
            // Clear image src to prevent flashing old content next time
            imageToCrop.src = '';
            if(reviewImage) reviewImage.src = '';
            // Note: We do NOT clear window.cropperManager.originalFile here because
            // the save logic might need it (though save happens before hide).
            // Input value clearing is handled in handleFileSelect.
        });

        // --- Event: Crop & Review Button Click ---
        cropImageBtn.addEventListener('click', function () {
            const cropper = window.cropperManager.instance;
            const originalFile = window.cropperManager.originalFile;

            if (!cropper) {
                alert('{{ __('กรุณารอให้เครื่องมือตัดภาพทำงาน หรือลองเลือกไฟล์ใหม่') }}'));
                return;
            }

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 360,
                minWidth: 200,
                minHeight: 200,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                alert('{{ __('เกิดข้อผิดพลาดในการตัดภาพ (Canvas creation failed). กรุณาลองใหม่อีกครั้ง') }}'));
                return;
            }

            // Show Review
            const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
            if(reviewImage) reviewImage.src = dataUrl;

            // Switch UI
            if(cropperContainer) cropperContainer.classList.add('d-none');
            if(cropToolbar) cropToolbar.classList.add('d-none');
            if(bgToolbarContainer) bgToolbarContainer.classList.add('d-none');

            if(cropperReviewContainer) cropperReviewContainer.classList.remove('d-none');
            if(reviewToolbar) reviewToolbar.classList.remove('d-none');
        });

        // --- Event: Back to Crop ---
        if(backToCropBtn) {
            backToCropBtn.addEventListener('click', function() {
                if(cropperContainer) cropperContainer.classList.remove('d-none');
                if(cropToolbar) cropToolbar.classList.remove('d-none');
                if(bgToolbarContainer) bgToolbarContainer.classList.remove('d-none');

                if(cropperReviewContainer) cropperReviewContainer.classList.add('d-none');
                if(reviewToolbar) reviewToolbar.classList.add('d-none');
            });
        }

        // --- Event: Enhance Button ---
        if(enhanceBtn) {
            enhanceBtn.addEventListener('click', async function() {
                if(!reviewImage.src) return;

                try {
                    // Show Loading
                    if (loadingOverlay) loadingOverlay.classList.remove('d-none');
                    if (loadingText) loadingText.textContent = 'กำลังปรับใบหน้าให้ชัด...';

                    // Convert src to Image object to draw on canvas
                    const img = new Image();
                    img.crossOrigin = 'Anonymous';
                    await new Promise((resolve, reject) => {
                        img.onload = resolve;
                        img.onerror = reject;
                        img.src = reviewImage.src;
                    });

                    // 1. Ensure MediaPipe Body Segmentation is loaded dynamically
                    if (!window.ImageSegmenter) {
                         if (loadingText) loadingText.textContent = 'กำลังโหลด AI Models...';
                         try {
                             // Use native dynamic import
                             const mediapipe = await import("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/vision_bundle.mjs");
                             window.ImageSegmenter = mediapipe.ImageSegmenter;
                             window.FilesetResolver = mediapipe.FilesetResolver;
                         } catch (error) {
                             console.error("Failed to load Mediapipe via dynamic import:", error);
                             throw new Error("ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์เพื่อโหลด AI Model (CDN Error)");
                         }
                    }

                    if (!window.ImageSegmenter) {
                         throw new Error("ไม่พบโมเดล AI กรุณาลองใหม่อีกครั้ง");
                    }

                    if (loadingText) loadingText.textContent = 'กำลังแยกส่วนบุคคล...';

                    // 2. Initialize Segmenter
                    const vision = await window.FilesetResolver.forVisionTasks(
                        "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/wasm"
                    );

                    const segmenter = await window.ImageSegmenter.createFromOptions(vision, {
                        baseOptions: {
                            modelAssetPath: "https://storage.googleapis.com/mediapipe-models/image_segmenter/selfie_segmenter/float16/latest/selfie_segmenter.tflite",
                            delegate: "GPU"
                        },
                        runningMode: "IMAGE",
                        outputCategoryMask: true,
                        outputConfidenceMasks: false
                    });

                    // 3. Run Segmentation
                    const segmentationResult = segmenter.segment(img);
                    let mask = segmentationResult.categoryMask.getAsUint8Array();
                    const maskWidth = segmentationResult.categoryMask.width;
                    const maskHeight = segmentationResult.categoryMask.height;

                    if (loadingText) loadingText.textContent = 'กำลังประมวลผล...';

                    // 4. Upscale (2x) using Canvas
                    const upscaleFactor = 2;
                    const c = document.createElement('canvas');
                    c.width = img.width * upscaleFactor;
                    c.height = img.height * upscaleFactor;
                    const ctx = c.getContext('2d');

                    // Use High Quality Smoothing for upscaling original
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, c.width, c.height);

                    // Create mask canvas to upscale the mask smoothly
                    const maskCanvas = document.createElement('canvas');
                    maskCanvas.width = maskWidth;
                    maskCanvas.height = maskHeight;
                    const maskCtx = maskCanvas.getContext('2d');
                    const maskImgData = maskCtx.createImageData(maskWidth, maskHeight);
                    // selfie_segmenter: 255 is background, 0 is person (sometimes varies, but usually 0 is background if only 1 class).
                    // Actually, category mask for selfie returns 0 for background, 1-255 for person classes.
                    for(let i=0; i<mask.length; i++) {
                        const isPerson = mask[i] > 0;
                        const val = isPerson ? 255 : 0;

                        maskImgData.data[i*4] = val; // R
                        maskImgData.data[i*4+1] = val; // G
                        maskImgData.data[i*4+2] = val; // B
                        maskImgData.data[i*4+3] = 255; // Alpha
                    }
                    maskCtx.putImageData(maskImgData, 0, 0);

                    // Upscale mask to match working canvas
                    const upscaledMaskCanvas = document.createElement('canvas');
                    upscaledMaskCanvas.width = c.width;
                    upscaledMaskCanvas.height = c.height;
                    const upscaledMaskCtx = upscaledMaskCanvas.getContext('2d');
                    upscaledMaskCtx.imageSmoothingEnabled = true;
                    upscaledMaskCtx.drawImage(maskCanvas, 0, 0, c.width, c.height);

                    const upscaledMaskData = upscaledMaskCtx.getImageData(0, 0, c.width, c.height).data;

                    // 5. Apply Sharpening Filter ONLY to the Person
                    const imageData = ctx.getImageData(0, 0, c.width, c.height);
                    const data = imageData.data;
                    const width = c.width;
                    const height = c.height;

                    // Sharpen matrix (Unsharp Mask style)
                    const weights = [
                         0, -1,  0,
                        -1,  5, -1,
                         0, -1,  0
                    ];

                    const side = Math.round(Math.sqrt(weights.length));
                    const halfSide = Math.floor(side/2);
                    const srcData = new Uint8ClampedArray(data); // Copy original data
                    const w = width;
                    const h = height;

                    for (let y = 0; y < h; y++) {
                        for (let x = 0; x < w; x++) {
                            const dstOff = (y * w + x) * 4;

                            // Check mask: is this pixel part of the person? (Check Red channel of upscaled mask)
                            // If mask value is > 128, it's person.
                            const isPerson = upscaledMaskData[dstOff] > 128;

                            if (isPerson) {
                                let r = 0, g = 0, b = 0;

                                for (let cy = 0; cy < side; cy++) {
                                    for (let cx = 0; cx < side; cx++) {
                                        const scy = y + cy - halfSide;
                                        const scx = x + cx - halfSide;

                                        if (scy >= 0 && scy < h && scx >= 0 && scx < w) {
                                            const srcOff = (scy * w + scx) * 4;
                                            const wt = weights[cy * side + cx];
                                            r += srcData[srcOff] * wt;
                                            g += srcData[srcOff + 1] * wt;
                                            b += srcData[srcOff + 2] * wt;
                                        } else {
                                            const wt = weights[cy * side + cx];
                                            r += srcData[dstOff] * wt;
                                            g += srcData[dstOff + 1] * wt;
                                            b += srcData[dstOff + 2] * wt;
                                        }
                                    }
                                }

                                // Update RGB with clamping
                                data[dstOff] = Math.max(0, Math.min(255, r));
                                data[dstOff + 1] = Math.max(0, Math.min(255, g));
                                data[dstOff + 2] = Math.max(0, Math.min(255, b));
                            }
                            // If it's background (!isPerson), we leave the pixel as the original smoothed upscale.
                        }
                    }

                    ctx.putImageData(imageData, 0, 0);

                    // Output back to reviewImage
                    const newUrl = c.toDataURL('image/jpeg', 0.95);
                    reviewImage.src = newUrl;

                } catch (err) {
                    console.error("Enhancement Error:", err);
                    alert("{{ __('การปรับความชัดล้มเหลว: ') }}") + err.message);
                } finally {
                    if (loadingOverlay) loadingOverlay.classList.add('d-none');
                }
            });
        }

        // --- Event: Confirm Save ---
        if(confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', async function() {
                if(!reviewImage.src) return;

                const originalFile = window.cropperManager.originalFile;
                const targetInputId = window.cropperManager.targetInputId;
                const targetPreviewId = window.cropperManager.targetPreviewId;

                // Convert src to Blob
                const res = await fetch(reviewImage.src);
                const blob = await res.blob();

                const croppedImageUrl = URL.createObjectURL(blob);

                // Update Preview Image
                if (targetPreviewId) {
                    const employeePhotoPreview = document.getElementById(targetPreviewId);
                    if(employeePhotoPreview) employeePhotoPreview.src = croppedImageUrl;
                }

                // Create a new File object
                // Use explicitly set mimeType or default to jpeg
                let outputType = window.cropperManager.mimeType;
                if (!outputType) {
                    outputType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
                }

                const fileName = originalFile ? originalFile.name : 'cropped-image.jpg';
                // Adjust extension
                let finalName = fileName;
                if (outputType === 'image/png' && !finalName.toLowerCase().endsWith('.png')) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".png";
                } else if (outputType === 'image/jpeg' && !finalName.toLowerCase().match(/\.(jpg|jpeg)$/)) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".jpg";
                }

                const processedFile = new File([blob], finalName, {
                    type: outputType,
                    lastModified: Date.now()
                });

                // Update Input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(processedFile);

                if (targetInputId) {
                    const actualInput = document.getElementById(targetInputId);
                    if(actualInput) {
                        actualInput.files = dataTransfer.files;
                    }
                }

                // Hide Modal
                cropperModal.hide();
            });
        }
    };

    // --- Refine / Mask Editor Manager ---
    window.refineManager = {
        initialized: false,
        isActive: false,

        // State
        originalImage: null,  // The full resolution original
        workCanvas: null,     // The canvas we are editing (RGBA)
        displayCanvas: null,  // The visible canvas
        ctx: null,            // Context of displayCanvas

        // History (Simple Undo)
        history: [],
        maxHistory: 10,

        // Tools
        currentTool: 'eraser', // eraser, restore, smart_erase
        brushSize: 20,
        isDrawing: false,
        lastPos: { x: 0, y: 0 },

        // Smart Erase State
        tolerance: 30, // Default tolerance for Magic Wand

        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            // DOM Elements
            this.container = document.getElementById('refineEditorContainer');
            this.displayCanvas = document.getElementById('refineCanvas');
            this.ctx = this.displayCanvas.getContext('2d', { willReadFrequently: true });

            this.btnStart = document.getElementById('btnStartRefine');
            this.btnSave = document.getElementById('refineBtnSave');
            this.btnCancel = document.getElementById('refineBtnCancel');
            this.btnUndo = document.getElementById('refineBtnUndo');

            this.toolEraser = document.getElementById('refineToolEraser');
            this.toolRestore = document.getElementById('refineToolRestore');
            this.toolSmart = document.getElementById('refineToolSmart');
            this.rangeSize = document.getElementById('refineBrushSize');

            // Attach Listeners
            if(this.btnStart) this.btnStart.addEventListener('click', () => this.start());
            if(this.btnSave) this.btnSave.addEventListener('click', () => this.save());
            if(this.btnCancel) this.btnCancel.addEventListener('click', () => this.cancel());
            if(this.btnUndo) this.btnUndo.addEventListener('click', () => this.undo());

            if(this.toolEraser) this.toolEraser.addEventListener('click', () => this.setTool('eraser'));
            if(this.toolRestore) this.toolRestore.addEventListener('click', () => this.setTool('restore'));
            if(this.toolSmart) this.toolSmart.addEventListener('click', () => this.setTool('smart_erase'));

            if(this.rangeSize) {
                this.rangeSize.addEventListener('input', (e) => {
                    if (this.currentTool === 'smart_erase') {
                        this.tolerance = parseInt(e.target.value); // Use brush size as tolerance (1-100)
                    } else {
                        this.brushSize = parseInt(e.target.value);
                    }
                });
            }

            // Canvas Interaction
            this.displayCanvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
            this.displayCanvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
            window.addEventListener('mouseup', () => this.onMouseUp());

            // Touch Support
            this.displayCanvas.addEventListener('touchstart', (e) => { e.preventDefault(); this.onMouseDown(e.touches[0]); }, { passive: false });
            this.displayCanvas.addEventListener('touchmove', (e) => { e.preventDefault(); this.onMouseMove(e.touches[0]); }, { passive: false });
            this.displayCanvas.addEventListener('touchend', (e) => { e.preventDefault(); this.onMouseUp(); });
        },

        async start() {
            if (this.isActive) return;

            const imageToCrop = document.getElementById('imageToCrop');
            if (!imageToCrop || !imageToCrop.src) {
                alert('No image to refine.');
                return;
            }

            // 1. Prepare UI
            this.isActive = true;
            document.querySelector('.img-container').style.display = 'none'; // Hide Cropper
            // Hide Main Modal Footer to prevent confusion
            const footer = document.querySelector('.modal-footer');
            if(footer) footer.classList.add('d-none');

            this.container.classList.remove('d-none');

            // Show Loading
            this.toggleLoading(true, 'Preparing Editor...');

            try {
                // 2. Load Images
                // Current Result (starting point)
                const currentSrc = imageToCrop.src;
                const currentImg = await this.loadImage(currentSrc);

                // Original Image (for Restore/Smart logic)
                // Try to get from global manager, fallback to current if not available
                let originalSrc = currentSrc;
                if (window.cropperManager.originalFile) {
                    originalSrc = URL.createObjectURL(window.cropperManager.originalFile);
                }
                this.originalImage = await this.loadImage(originalSrc);

                // 3. Setup Canvases
                // We use the dimensions of the Original Image to maintain quality
                // But if it's huge, performance will suffer. Limit to 2000px?
                // For now, keep original size for best quality.

                const w = this.originalImage.width;
                const h = this.originalImage.height;

                this.displayCanvas.width = w;
                this.displayCanvas.height = h;

                // Create Offscreen Work Canvas
                this.workCanvas = document.createElement('canvas');
                this.workCanvas.width = w;
                this.workCanvas.height = h;
                const workCtx = this.workCanvas.getContext('2d');

                // Initialize Temp Canvas (Used for Restore/Smart Erase)
                this.tempCanvas = document.createElement('canvas');
                this.tempCanvas.width = w;
                this.tempCanvas.height = h;

                // Initialize Work Canvas with Current Result
                // Note: Current Result might be resized/different aspect if it came from Cropper output?
                // Ideally we are refining the Pre-Crop image (the one IN the cropper).
                // Yes, imageToCrop is the source FOR the cropper.
                // But check if imageToCrop is different size than originalFile due to previous processing?
                // Usually bg removal replaces imageToCrop with a new blob.

                // Draw current image (which has transparency) onto work canvas
                workCtx.drawImage(currentImg, 0, 0, w, h);

                // 4. Initial Render
                this.render();

                // 5. Save Initial State for Undo
                this.pushHistory();

            } catch (e) {
                console.error(e);
                alert('Failed to start refine mode: ' + e.message);
                this.cancel();
            } finally {
                this.toggleLoading(false);
            }
        },

        save() {
            if (!this.workCanvas) return;

            this.toggleLoading(true, 'Saving...');

            this.workCanvas.toBlob((blob) => {
                if (blob) {
                    // Update Main Image
                    const newUrl = URL.createObjectURL(blob);
                    const imageToCrop = document.getElementById('imageToCrop');
                    imageToCrop.src = newUrl;

                    // Update MimeType to PNG to support transparency
                    window.cropperManager.mimeType = 'image/png';

                    // Update Edited File State so Background Tools use this version
                    // Create a File object
                    const originalName = window.cropperManager.originalFile ? window.cropperManager.originalFile.name : 'image.png';
                    const fileName = originalName.replace(/\.[^/.]+$/, "") + ".png"; // Force png extension

                    window.cropperManager.editedFile = new File([blob], fileName, {
                        type: 'image/png',
                        lastModified: Date.now()
                    });

                    // Re-init Cropper
                    if (window.cropperManager.instance) {
                        window.cropperManager.instance.replace(newUrl);
                    } else {
                        // Or trigger init
                         const modalEl = document.getElementById('cropperModal');
                         // Triggering modal show again might work but might loop.
                         // Better to manually call init if needed, but 'replace' handles it.
                         // If instance destroyed, we need to rebuild.
                         // _edit_scripts has initCropperGlobal which handles this logic but it's inside a function.
                         // Let's assume standard flow:
                         const triggerBtn = document.getElementById('cropImageBtn');
                         if(triggerBtn) triggerBtn.disabled = false;

                         // We need to re-create the cropper if it was destroyed or hidden?
                         // We hid the .img-container.
                    }
                }
                this.exit();
            }, 'image/png');
        },

        cancel() {
            this.exit();
        },

        exit() {
            this.toggleLoading(false); // Ensure loading is cleared
            this.isActive = false;
            this.container.classList.add('d-none');
            document.querySelector('.img-container').style.display = 'block'; // Show Cropper

            // Show Main Modal Footer
            const footer = document.querySelector('.modal-footer');
            if(footer) footer.classList.remove('d-none');

            // Clean up
            this.history = [];
            this.workCanvas = null;
            this.tempCanvas = null;
            this.originalImage = null;
            this.smartPoints = [];

            // Re-enable/Sync Cropper View
            if (window.cropperManager.instance) {
                // If we didn't save, we don't need to do anything to the cropper
                // It just becomes visible again.
            } else {
                 // Re-init if missing
                 const imageToCrop = document.getElementById('imageToCrop');
                 if(imageToCrop.complete) {
                      // We can't easily access initCropperInstance from here as it is scoped.
                      // But the Modal Shown event handles it usually.
                      // A trick: fire 'shown.bs.modal' manually?
                      // Or just rely on the user workflow.
                      // Ideally we didn't destroy the instance, just hid the container.
                      // If we destroyed it, we need to recreate.
                      // Let's verify: In start(), we just hid .img-container.
                      // Cropper instance is still attached to the image element.
                 }
            }
        },

        undo() {
            if (this.history.length === 0) return;
            const lastState = this.history.pop();
            const img = new Image();
            img.onload = () => {
                const ctx = this.workCanvas.getContext('2d');
                ctx.clearRect(0, 0, this.workCanvas.width, this.workCanvas.height);
                ctx.drawImage(img, 0, 0);
                this.render();
                this.updateUndoButton();
            };
            img.src = lastState;
        },

        pushHistory() {
            if (this.history.length >= this.maxHistory) this.history.shift();
            this.history.push(this.workCanvas.toDataURL());
            this.updateUndoButton();
        },

        updateUndoButton() {
            if(this.btnUndo) this.btnUndo.disabled = this.history.length === 0;
        },

        setTool(tool) {
            this.currentTool = tool;

            if (tool === 'smart_erase') {
                 // If switching to smart erase, adjust range input to represent tolerance
                 if (this.rangeSize) this.rangeSize.value = this.tolerance;
            } else {
                 if (this.rangeSize) this.rangeSize.value = this.brushSize;
            }

            // UI Update
            [this.toolEraser, this.toolRestore, this.toolSmart].forEach(btn => {
                if(btn) btn.classList.remove('active');
            });

            if (tool === 'eraser' && this.toolEraser) this.toolEraser.classList.add('active');
            if (tool === 'restore' && this.toolRestore) this.toolRestore.classList.add('active');
            if (tool === 'smart_erase' && this.toolSmart) this.toolSmart.classList.add('active');
        },

        // --- Drawing Logic ---

        getMousePos(evt) {
            const rect = this.displayCanvas.getBoundingClientRect();
            // Scale if canvas display size differs from resolution
            const scaleX = this.displayCanvas.width / rect.width;
            const scaleY = this.displayCanvas.height / rect.height;

            return {
                x: (evt.clientX - rect.left) * scaleX,
                y: (evt.clientY - rect.top) * scaleY
            };
        },

        onMouseDown(e) {
            if (!this.isActive) return;
            this.isDrawing = true;
            this.lastPos = this.getMousePos(e);

            if (this.currentTool === 'smart_erase') {
                this.pushHistory(); // Save before applying magic wand
                this.applyMagicWand(this.lastPos);
            } else {
                this.pushHistory(); // Save state before stroke
                this.draw(this.lastPos);
            }
        },

        onMouseMove(e) {
            if (!this.isActive || !this.isDrawing) return;
            const pos = this.getMousePos(e);

            if (this.currentTool === 'smart_erase') {
                // Do nothing on mouse move for magic wand
            } else {
                // Interpolate for smooth stroke
                this.drawLine(this.lastPos, pos);
                this.lastPos = pos;
            }
        },

        onMouseUp() {
            if (!this.isActive || !this.isDrawing) return;
            this.isDrawing = false;

            if (this.currentTool === 'smart_erase') {
                // Action already performed on mousedown
            }
        },

        draw(pos) {
            const ctx = this.workCanvas.getContext('2d');
            const dCtx = this.displayCanvas.getContext('2d'); // Display context

            // Common Styles
            const setupCtx = (c) => {
                c.lineCap = 'round';
                c.lineJoin = 'round';
                c.lineWidth = this.brushSize;
            };
            setupCtx(ctx);
            setupCtx(dCtx);

            if (this.currentTool === 'eraser') {
                // Update Work Canvas
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                ctx.fill();
                ctx.globalCompositeOperation = 'source-over';

                // Update Display Canvas (Visually Sync)
                dCtx.globalCompositeOperation = 'destination-out';
                dCtx.beginPath();
                dCtx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                dCtx.fill();
                dCtx.globalCompositeOperation = 'source-over';

            } else if (this.currentTool === 'restore') {
                // Update Work Canvas
                ctx.save();
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                ctx.clip();
                ctx.drawImage(this.originalImage, 0, 0, this.workCanvas.width, this.workCanvas.height);
                ctx.restore();

                // Update Display Canvas
                dCtx.save();
                dCtx.beginPath();
                dCtx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                dCtx.clip();
                dCtx.drawImage(this.originalImage, 0, 0, this.displayCanvas.width, this.displayCanvas.height);
                dCtx.restore();
            }

            // Removed this.render() call to improve performance
        },

        drawLine(start, end) {
             const ctx = this.workCanvas.getContext('2d');
             const dCtx = this.displayCanvas.getContext('2d');

             const setupCtx = (c) => {
                 c.lineCap = 'round';
                 c.lineJoin = 'round';
                 c.lineWidth = this.brushSize;
             };
             setupCtx(ctx);
             setupCtx(dCtx);

             if (this.currentTool === 'eraser') {
                 // Work Canvas
                 ctx.globalCompositeOperation = 'destination-out';
                 ctx.beginPath();
                 ctx.moveTo(start.x, start.y);
                 ctx.lineTo(end.x, end.y);
                 ctx.stroke();
                 ctx.globalCompositeOperation = 'source-over';

                 // Display Canvas
                 dCtx.globalCompositeOperation = 'destination-out';
                 dCtx.beginPath();
                 dCtx.moveTo(start.x, start.y);
                 dCtx.lineTo(end.x, end.y);
                 dCtx.stroke();
                 dCtx.globalCompositeOperation = 'source-over';

             } else if (this.currentTool === 'restore') {
                 // Use shared temp canvas to create the brush mask
                 if (!this.tempCanvas) {
                     this.tempCanvas = document.createElement('canvas');
                     this.tempCanvas.width = this.workCanvas.width;
                     this.tempCanvas.height = this.workCanvas.height;
                 }

                 // Note: Drawing lines for Restore efficiently is tricky without render().
                 // We re-use the logic but apply to both canvases?
                 // Since Restore copies from OriginalImage, and OriginalImage is static,
                 // we can just repeat the composite operation on both.

                 const tCtx = this.tempCanvas.getContext('2d');
                 tCtx.clearRect(0,0, this.tempCanvas.width, this.tempCanvas.height);

                 // Draw Stroke on Temp Mask
                 tCtx.lineCap = 'round';
                 tCtx.lineJoin = 'round';
                 tCtx.lineWidth = this.brushSize;
                 tCtx.strokeStyle = '#fff';
                 tCtx.beginPath();
                 tCtx.moveTo(start.x, start.y);
                 tCtx.lineTo(end.x, end.y);
                 tCtx.stroke();
                 tCtx.globalCompositeOperation = 'source-in';
                 tCtx.drawImage(this.originalImage, 0, 0, this.workCanvas.width, this.workCanvas.height);
                 tCtx.globalCompositeOperation = 'source-over'; // Reset

                 // Apply to Work Canvas
                 ctx.drawImage(this.tempCanvas, 0, 0);

                 // Apply to Display Canvas
                 dCtx.drawImage(this.tempCanvas, 0, 0);
             }

             // Removed this.render()
        },

        render() {
            // Simply copy Work Canvas to Display Canvas
            // Note: Display Canvas has checkerboard CSS background, so transparency shows up.
            this.ctx.clearRect(0, 0, this.displayCanvas.width, this.displayCanvas.height);
            this.ctx.drawImage(this.workCanvas, 0, 0);
        },

        // --- Magic Wand Logic (Flood Fill) ---
        applyMagicWand(pos) {
            this.toggleLoading(true, 'Analyzing image...');

            setTimeout(() => {
                try {
                    const width = this.workCanvas.width;
                    const height = this.workCanvas.height;
                    const ctx = this.workCanvas.getContext('2d');

                    // Scale coordinates if canvas display size differs from resolution
                    const x = Math.floor(pos.x);
                    const y = Math.floor(pos.y);

                    if (x < 0 || x >= width || y < 0 || y >= height) {
                        this.toggleLoading(false);
                        return;
                    }

                    const imageData = ctx.getImageData(0, 0, width, height);
                    const data = imageData.data;

                    const targetPos = (y * width + x) * 4;
                    const targetR = data[targetPos];
                    const targetG = data[targetPos + 1];
                    const targetB = data[targetPos + 2];
                    const targetA = data[targetPos + 3];

                    // Don't fill if clicking on transparent area
                    if (targetA === 0) {
                        this.toggleLoading(false);
                        return;
                    }

                    // Use tolerance (1-100 mapped to RGB distance squared)
                    // Max distance is 255^2 * 3 = 195075.
                    // A tolerance of 100 maps to a reasonable max distance, e.g. 150^2 * 3 = 67500
                    const maxDist = Math.pow((this.tolerance / 100) * 150, 2) * 3;

                    const matchColor = (pos) => {
                        const a = data[pos + 3];
                        if (a === 0) return false; // Already transparent

                        const r = data[pos];
                        const g = data[pos + 1];
                        const b = data[pos + 2];

                        const distSq = Math.pow(r - targetR, 2) + Math.pow(g - targetG, 2) + Math.pow(b - targetB, 2);
                        return distSq <= maxDist;
                    };

                    // Implement Scanline Flood Fill for better performance than naive stack
                    let stack = [[x, y]];
                    const visited = new Uint8Array(width * height);

                    while(stack.length > 0) {
                        let [cx, cy] = stack.pop();
                        let currentX = cx;

                        // Move left to find the start of the span
                        while(currentX > 0 && matchColor((cy * width + (currentX - 1)) * 4) && !visited[cy * width + (currentX - 1)]) {
                            currentX--;
                        }

                        let spanUp = false;
                        let spanDown = false;

                        // Move right and fill
                        while(currentX < width) {
                            const p = (cy * width + currentX) * 4;
                            // Check if current pixel matches AND is not visited, OR it's the exact starting pixel we popped from stack
                            const isMatch = matchColor(p);
                            const isStartPixel = (currentX === cx && cy === stack[stack.length] /* not actually accessing stack here but we know cx cy was valid */);
                            // To fix redundancy correctly without infinite loop or breaking fill:
                            if (!isMatch && currentX !== cx) {
                                break; // end of span
                            }
                            if (visited[cy * width + currentX] && currentX !== cx) {
                                break;
                            }

                            data[p + 3] = 0; // Erase (set alpha to 0)
                            visited[cy * width + currentX] = 1;

                            // Check up
                            if (cy > 0) {
                                const upPos = ((cy - 1) * width + currentX) * 4;
                                const upMatch = matchColor(upPos) && !visited[(cy - 1) * width + currentX];
                                if (!spanUp && upMatch) {
                                    stack.push([currentX, cy - 1]);
                                    spanUp = true;
                                } else if (spanUp && !upMatch) {
                                    spanUp = false;
                                }
                            }

                            // Check down
                            if (cy < height - 1) {
                                const downPos = ((cy + 1) * width + currentX) * 4;
                                const downMatch = matchColor(downPos) && !visited[(cy + 1) * width + currentX];
                                if (!spanDown && downMatch) {
                                    stack.push([currentX, cy + 1]);
                                    spanDown = true;
                                } else if (spanDown && !downMatch) {
                                    spanDown = false;
                                }
                            }

                            currentX++;
                        }
                    }

                    // Put modified data back
                    ctx.putImageData(imageData, 0, 0);
                    this.render();

                } catch (err) {
                    console.error("Magic Wand Error:", err);
                    alert("Magic Wand failed: " + err.message + "\nCheck console for details.");
                } finally {
                    this.toggleLoading(false);
                }
            }, 10);
        },

        loadImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });
        },

        toggleLoading(show, text) {
            const overlay = document.getElementById('cropperLoadingOverlay');
            const txt = document.getElementById('cropperLoadingText');
            if(overlay) {
                if(show) overlay.classList.remove('d-none');
                else overlay.classList.add('d-none');
            }
            if(txt && text) txt.textContent = text;
        }
    };

    // Auto Init on Load
    document.addEventListener('DOMContentLoaded', () => {
        window.refineManager.init();
    });

    // --- Generic Form Initialization ---
    // prefix: '' for Create Form, 'edit_' for Edit Form
    window.initEmployeeForm = function(prefix = '') {
        // 1. Ensure global cropper logic is ready (Idempotent call)
        window.initCropperGlobal();

        // 2. Get Form Field References
        const titleTh = document.getElementById(prefix + 'employeeTitleTh');
        const titleEn = document.getElementById(prefix + 'employeeTitleEn');
        const genderInput = document.getElementById(prefix + 'employeeGender');
        const dobInput = document.getElementById(prefix + 'employeeDob');
        const ageInput = document.getElementById(prefix + 'employeeAge');
        const startDateInput = document.getElementById(prefix + 'startDate');
        const workAgeInput = document.getElementById(prefix + 'workAge');
        const nationalitySelect = document.getElementById(prefix + 'employeeNationality');
        const mouGroupSelect = document.getElementById(prefix + 'workPermitMOUGroup');
        const insuranceSelect = document.getElementById(prefix + 'insurance_type');

        // 3. File Triggers
        const triggerFileInput = document.getElementById(prefix + 'triggerFile');
        const triggerCameraInput = document.getElementById(prefix + 'triggerCamera');
        const imageToCrop = document.getElementById('imageToCrop'); // Global element
        const cropperModalEl = document.getElementById('cropperModal'); // Global element

        // --- Logic: Handle File Selection (Triggers Modal) ---
        function handleFileSelect(event) {
            if (event.target.files && event.target.files.length > 0) {
                // Update global state with selected file
                window.cropperManager.originalFile = event.target.files[0];
                window.cropperManager.editedFile = null; // Reset
                window.cropperManager.mimeType = event.target.files[0].type; // Set initial mime type
                // Set Targets based on prefix
                window.cropperManager.targetInputId = prefix + 'employeePhotoInput';
                window.cropperManager.targetPreviewId = prefix + 'employeePhotoPreview';
            } else {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if(imageToCrop) {
                    imageToCrop.src = e.target.result;
                    // Open the modal
                    const modal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);
                    modal.show();
                }
            };
            reader.readAsDataURL(window.cropperManager.originalFile);
            event.target.value = ''; // Reset input to allow re-selecting same file
        }

        if (triggerFileInput) {
             // Remove existing listener if any (to avoid duplicates if called multiple times)
             // But anonymous functions can't be removed easily.
             // Simplest is to check if we already marked it attached?
             // Or clone/replace to strip listeners.
             // For now, assuming standard usage pattern, replacing node is safest.
             const newTrigger = triggerFileInput.cloneNode(true);
             triggerFileInput.parentNode.replaceChild(newTrigger, triggerFileInput);
             newTrigger.addEventListener('change', handleFileSelect);
        }
        if (triggerCameraInput) {
             const newTrigger = triggerCameraInput.cloneNode(true);
             triggerCameraInput.parentNode.replaceChild(newTrigger, triggerCameraInput);
             newTrigger.addEventListener('change', handleFileSelect);
        }

        // --- Logic: Titles & Gender ---
        const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
        const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

        function syncTitles(source) {
            if (!titleTh || !titleEn) return;
            if (source === 'th') {
                const selectedTh = titleTh.value;
                if (thToEnMap[selectedTh]) {
                    titleEn.value = thToEnMap[selectedTh];
                    // Also dispatch change event to ensure any other listeners (if any) are triggered, though careful with loops
                    // For now, direct assignment is enough as we manually call updateGender
                }
            } else {
                const selectedEn = titleEn.value;
                if (enToThMap[selectedEn]) {
                    titleTh.value = enToThMap[selectedEn];
                }
            }
            updateGender();
        }

        function updateGender() {
            if (!titleTh || !genderInput) return;
            const selectedTh = titleTh.value;
            if (selectedTh === 'นาย') genderInput.value = 'ชาย';
            else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') genderInput.value = 'หญิง';
            else genderInput.value = ''; // Don't clear if unknown, or maybe we should? User didn't specify. Keeping as is.
        }

        // Helper to safely attach listener only once
        function attachOnce(element, event, handler) {
            if (!element) return;
            // Remove previous handler if possible? We can't easily with anonymous functions.
            // So we use a flag property on the element.
            if (element.dataset['has_' + event + '_listener']) return;

            element.addEventListener(event, handler);
            element.dataset['has_' + event + '_listener'] = 'true';
        }

        if(titleTh) {
            attachOnce(titleTh, 'change', () => syncTitles('th'));
            // Trigger once for initial state if value exists
            if(titleTh.value) updateGender();
        }
        if(titleEn) {
            attachOnce(titleEn, 'change', () => syncTitles('en'));
        }

        // --- Logic: Age Calculation ---
        function calculateAge() {
            if (!dobInput || !ageInput) return;
            const dob = new Date(dobInput.value);
            if (!isNaN(dob.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
                ageInput.value = age > 0 ? age : 0;
            } else {
                ageInput.value = '';
            }
        }
        if(dobInput) {
            dobInput.addEventListener('change', calculateAge);
            if(dobInput.value) calculateAge();
        }

        // --- Logic: Work Age Calculation ---
        function calculateWorkAge() {
            if (!startDateInput || !workAgeInput) return;
            const startDate = new Date(startDateInput.value);
            if (!isNaN(startDate.getTime())) {
                const today = new Date();
                let years = today.getFullYear() - startDate.getFullYear();
                let months = today.getMonth() - startDate.getMonth();
                let days = today.getDate() - startDate.getDate();

                if (days < 0) {
                    months--;
                    const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    days += lastMonth.getDate();
                }
                if (months < 0) {
                    years--;
                    months += 12;
                }

                const yLabel = "{{ __('Years') }}";
                const mLabel = "{{ __('Months') }}";
                const dLabel = "{{ __('Days') }}";

                let result = [];
                if (years > 0) result.push(`${years} ${yLabel}`);
                if (months > 0) result.push(`${months} ${mLabel}`);
                result.push(`${days} ${dLabel}`);

                workAgeInput.value = result.join(' ');
            } else {
                workAgeInput.value = '';
            }
        }
        if(startDateInput) {
            startDateInput.addEventListener('change', calculateWorkAge);
            if(startDateInput.value) calculateWorkAge();
        }

        // --- Logic: Nationality Conditionals ---
        const myanmarPassportContainer = document.getElementById(prefix + 'passportTypeContainer');
        const cambodiaPassportContainer = document.getElementById(prefix + 'passportTypeCambodiaContainer');

        function toggleNationalityFields() {
            if (!nationalitySelect || !myanmarPassportContainer || !cambodiaPassportContainer) return;
            myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
            cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
        }
        if(nationalitySelect) {
            nationalitySelect.addEventListener('change', toggleNationalityFields);
            toggleNationalityFields();
        }

        // --- Logic: MOU Other ---
        const mouGroupOtherContainer = document.getElementById(prefix + 'workPermitMOUGroupOtherContainer');
        function toggleMouGroupOther() {
            if (!mouGroupSelect || !mouGroupOtherContainer) return;
            mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
        }
        if(mouGroupSelect) {
            mouGroupSelect.addEventListener('change', toggleMouGroupOther);
            toggleMouGroupOther();
        }

        // --- Logic: Insurance Conditionals ---
        const socialContainer = document.getElementById(prefix + 'insuranceSocialSecurity');
        const hospitalContainer = document.getElementById(prefix + 'insuranceHospital');
        const privateContainer = document.getElementById(prefix + 'insurancePrivate');
        function toggleInsuranceVisibility() {
            if (!insuranceSelect || !socialContainer || !hospitalContainer || !privateContainer) return;
            const selectedType = insuranceSelect.value;
            socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
            hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
            privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
        }
        if(insuranceSelect) {
            insuranceSelect.addEventListener('change', toggleInsuranceVisibility);
            toggleInsuranceVisibility();
        }

        // --- Cancel Button Logic (Only for Edit Form usually) ---
        if (prefix === 'edit_') {
            const cancelBtn = document.querySelector('.btn-cancel-edit');
            if (cancelBtn) {
                 const newCancelBtn = cancelBtn.cloneNode(true);
                 cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
                 newCancelBtn.onclick = function() {
                     const modal = document.getElementById('editEmployeeModal');
                     if(modal && modal.classList.contains('show')) {
                         const bsModal = bootstrap.Modal.getInstance(modal);
                         if(bsModal) bsModal.hide();
                     } else {
                         history.back();
                     }
                 }
            }
        }
    };

    // Keep legacy name for backward compatibility if called directly elsewhere
    window.initEmployeeEditForm = function() {
        window.initEmployeeForm('edit_');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Edit Form (Static Page Load)
        // This is required for the standalone Edit Page (employees.edit) which renders _edit_form.blade.php directly.
        window.initEmployeeForm('edit_');

        // Initialize Create Form (Static HTML)
        window.initEmployeeForm('');
    });
</script>
