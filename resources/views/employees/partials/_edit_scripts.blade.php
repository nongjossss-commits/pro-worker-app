<script>
    // --- Global Cropper State & Logic ---
    // This ensures we only attach listeners to the global modal ONCE,
    // preventing the "stacking listeners" bug which caused "Canvas creation failed".
    window.cropperManager = {
        initialized: false,
        instance: null,
        originalFile: null,
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
            alert('ไม่สามารถโหลดรูปภาพได้: ' + error.message);
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
        // Retrieve or create bootstrap modal instance
        let cropperModal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);

        // --- Helper: Init Cropper Instance ---
        function initCropperInstance() {
            if (typeof Cropper === 'undefined') {
                alert('ไม่สามารถโหลดเครื่องมือตัดภาพได้ (Cropper.js) กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต');
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
                alert('เกิดข้อผิดพลาดในการเริ่มทำงาน Cropper: ' + err.message);
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
                const originalFile = window.cropperManager.originalFile;

                if (!originalFile) {
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
                    const processedBlob = await window.backgroundRemoval.process(originalFile, action, (active, text) => {
                        if (loadingText && text) loadingText.textContent = text;
                    }, currentCancellationToken);

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
            // Note: We do NOT clear window.cropperManager.originalFile here because
            // the save logic might need it (though save happens before hide).
            // Input value clearing is handled in handleFileSelect.
        });

        // --- Event: Save Button Click ---
        cropImageBtn.addEventListener('click', function () {
            const cropper = window.cropperManager.instance;
            const originalFile = window.cropperManager.originalFile;
            const targetInputId = window.cropperManager.targetInputId;
            const targetPreviewId = window.cropperManager.targetPreviewId;

            if (!cropper) {
                alert('กรุณารอให้เครื่องมือตัดภาพทำงาน หรือลองเลือกไฟล์ใหม่');
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
                alert('เกิดข้อผิดพลาดในการตัดภาพ (Canvas creation failed). กรุณาลองใหม่อีกครั้ง');
                return;
            }

            // Determine output format
            // Use explicitly set mimeType (from BG removal) OR fallback to original file type OR default to JPEG
            let outputType = window.cropperManager.mimeType;
            if (!outputType) {
                outputType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
            }

            canvas.toBlob(function (blob) {
                if (!blob) return;

                const croppedImageUrl = URL.createObjectURL(blob);

                // Update Preview Image
                if (targetPreviewId) {
                    const employeePhotoPreview = document.getElementById(targetPreviewId);
                    if(employeePhotoPreview) employeePhotoPreview.src = croppedImageUrl;
                }

                // Create a new File object
                const fileName = originalFile ? originalFile.name : 'cropped-image.jpg';
                // Adjust extension if type changed (e.g. jpeg -> png)
                // But keeping original name is usually fine for uploads, backend might rename.
                // Or we can be smart:
                let finalName = fileName;
                if (outputType === 'image/png' && !finalName.toLowerCase().endsWith('.png')) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".png";
                } else if (outputType === 'image/jpeg' && !finalName.toLowerCase().match(/\.(jpg|jpeg)$/)) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".jpg";
                }

                const croppedFile = new File([blob], finalName, {
                    type: outputType,
                    lastModified: Date.now()
                });

                // Use a DataTransfer to create a FileList for the input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);

                if (targetInputId) {
                    const actualInput = document.getElementById(targetInputId);
                    if(actualInput) {
                        actualInput.files = dataTransfer.files;
                    }
                }

                // Hide the modal
                cropperModal.hide();

            }, outputType);
        });
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
        smartPoints: [],

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

            if(this.rangeSize) this.rangeSize.addEventListener('input', (e) => this.brushSize = parseInt(e.target.value));

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
                this.smartPoints = []; // Start new stroke
                this.smartPoints.push(this.lastPos);
            } else {
                this.pushHistory(); // Save state before stroke
                this.draw(this.lastPos);
            }
        },

        onMouseMove(e) {
            if (!this.isActive || !this.isDrawing) return;
            const pos = this.getMousePos(e);

            if (this.currentTool === 'smart_erase') {
                // Collect points and draw visual trail
                this.smartPoints.push(pos);
                this.render(); // Redraw base
                // Draw trail on top
                this.ctx.beginPath();
                this.ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)';
                this.ctx.lineWidth = this.brushSize;
                this.ctx.lineCap = 'round';
                this.ctx.moveTo(this.smartPoints[0].x, this.smartPoints[0].y);
                for(let p of this.smartPoints) this.ctx.lineTo(p.x, p.y);
                this.ctx.stroke();
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
                this.pushHistory(); // Save before applying smart erase
                this.applySmartErase();
            }
        },

        draw(pos) {
            const ctx = this.workCanvas.getContext('2d');
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = this.brushSize;

            if (this.currentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                ctx.fill();
                ctx.globalCompositeOperation = 'source-over';
            } else if (this.currentTool === 'restore') {
                // Restore is tricky: We want to "reveal" the original image.
                // Approach:
                // 1. Create a temporary path on a temp canvas.
                // 2. Use that path to clip the Original Image.
                // 3. Draw the clipped part onto Work Canvas with source-over.

                // Optimized approach:
                // Draw the Original Image onto Work Canvas using 'source-over' BUT masked by the brush stroke?
                // No, standard canvas doesn't support "Draw Image only where I brush" easily without composite ops.

                // Composite Op Approach:
                // 1. Save context.
                // 2. Begin Path (Circle at pos).
                // 3. Clip().
                // 4. Draw Original Image (It will only draw inside the clip).
                // 5. Restore context.

                ctx.save();
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, this.brushSize / 2, 0, Math.PI * 2);
                ctx.clip();
                ctx.drawImage(this.originalImage, 0, 0, this.workCanvas.width, this.workCanvas.height);
                ctx.restore();
            }

            this.render();
        },

        drawLine(start, end) {
             const ctx = this.workCanvas.getContext('2d');
             ctx.lineCap = 'round';
             ctx.lineJoin = 'round';
             ctx.lineWidth = this.brushSize;

             if (this.currentTool === 'eraser') {
                 ctx.globalCompositeOperation = 'destination-out';
                 ctx.beginPath();
                 ctx.moveTo(start.x, start.y);
                 ctx.lineTo(end.x, end.y);
                 ctx.stroke();
                 ctx.globalCompositeOperation = 'source-over';
             } else if (this.currentTool === 'restore') {
                 // For line, clipping is harder because lines self-intersect.
                 // Better: Draw the line on a temp alpha mask.
                 // Then composite Original * Mask onto Work.

                 // Since this runs every mouse move (high freq), keeping it simple is key.
                 // The "Clip" method works for lines too if we stroke the path?
                 // No, ctx.clip() uses the path area. Stroking a path doesn't create a clip area of the stroke width easily.

                 // Alternative:
                 // 1. Draw line on a separate 'brushCanvas' (white on transparent).
                 // 2. Composite 'brushCanvas' with 'OriginalImage' -> 'SourceIn' (Result = Original parts where brush is).
                 // 3. Draw Result onto 'WorkCanvas'.

                 // Let's implement this "Brush Mask" approach inline for performance?
                 // Creating canvas every move is bad.
                 // We can use a shared temp canvas.
                 if (!this.tempCanvas) {
                     this.tempCanvas = document.createElement('canvas');
                     this.tempCanvas.width = this.workCanvas.width;
                     this.tempCanvas.height = this.workCanvas.height;
                 }
                 const tCtx = this.tempCanvas.getContext('2d');
                 tCtx.clearRect(0,0, this.tempCanvas.width, this.tempCanvas.height);

                 // Draw Stroke
                 tCtx.lineCap = 'round';
                 tCtx.lineJoin = 'round';
                 tCtx.lineWidth = this.brushSize;
                 tCtx.strokeStyle = '#fff';
                 tCtx.beginPath();
                 tCtx.moveTo(start.x, start.y);
                 tCtx.lineTo(end.x, end.y);
                 tCtx.stroke();

                 // Composite Original In
                 tCtx.globalCompositeOperation = 'source-in';
                 tCtx.drawImage(this.originalImage, 0, 0, this.workCanvas.width, this.workCanvas.height);

                 // Draw onto Work
                 ctx.globalCompositeOperation = 'source-over';
                 ctx.drawImage(this.tempCanvas, 0, 0);
             }

             this.render();
        },

        render() {
            // Simply copy Work Canvas to Display Canvas
            // Note: Display Canvas has checkerboard CSS background, so transparency shows up.
            this.ctx.clearRect(0, 0, this.displayCanvas.width, this.displayCanvas.height);
            this.ctx.drawImage(this.workCanvas, 0, 0);
        },

        // --- Smart Erase Logic (OpenCV) ---
        applySmartErase() {
            if (typeof cv === 'undefined') {
                alert('Smart Erase requires OpenCV. Please wait for it to load or check connection.');
                return;
            }

            this.toggleLoading(true, 'Analyzing image...');

            // Use setTimeout to allow UI to render the loading state
            setTimeout(() => {
                try {
                    // 1. Setup Data
                    const width = this.workCanvas.width;
                    const height = this.workCanvas.height;

                    // Downscale for performance?
                    // GrabCut is O(N). 12MP image will crash browser.
                    // Let's downscale to max 600px dimension for the mask calculation.
                    const maxDim = 600;
                    const scale = Math.min(1, maxDim / Math.max(width, height));
                    const sW = width * scale;
                    const sH = height * scale;

                    // Read Current State (Source + Alpha) from WorkCanvas
                    // We need the Original Image colors for GrabCut to distinguish FG/BG
                    // But we use the Current Alpha as the Initial Mask.

                    // Create Source Mat (Original Image) - Downscaled
                    const srcCanvas = document.createElement('canvas');
                    srcCanvas.width = sW;
                    srcCanvas.height = sH;
                    const srcCtx = srcCanvas.getContext('2d');
                    srcCtx.drawImage(this.originalImage, 0, 0, sW, sH);
                    const srcMat = cv.imread(srcCanvas); // RGBA
                    cv.cvtColor(srcMat, srcMat, cv.COLOR_RGBA2RGB); // GrabCut needs RGB (3 channels)

                    // Create Mask Mat (From WorkCanvas Alpha) - Downscaled
                    const maskCanvas = document.createElement('canvas');
                    maskCanvas.width = sW;
                    maskCanvas.height = sH;
                    const maskCtx = maskCanvas.getContext('2d');
                    maskCtx.drawImage(this.workCanvas, 0, 0, sW, sH);
                    const alphaMat = cv.imread(maskCanvas); // RGBA
                    const mask = new cv.Mat();
                    cv.cvtColor(alphaMat, mask, cv.COLOR_RGBA2GRAY); // 1 channel

                    // Initialize GrabCut Mask
                    // Alpha > 200 -> GC_PR_FGD (3) (Probable Foreground)
                    // Alpha < 10 -> GC_BGD (0) (Background)
                    // Else -> GC_PR_BGD (2) ? Or keep as PR_FGD?
                    // Let's assume current visible pixels are PR_FGD. Transparent are BGD.

                    // Simple Threshold map
                    // mask = 0 (BGD) where alpha < 100
                    // mask = 3 (PR_FGD) where alpha >= 100
                    cv.threshold(mask, mask, 100, 3, cv.THRESH_BINARY); // 0 or 3

                    // Apply User Strokes as GC_BGD (0)
                    // Draw user strokes onto a temp canvas then map to mask?
                    // Better: iterate points and draw circles on the Mask Mat?
                    // JS loop might be slow. Use canvas drawing on the maskCanvas before reading!

                    // Re-do Mask Creation with User Input
                    maskCtx.beginPath();
                    maskCtx.lineCap = 'round';
                    maskCtx.lineWidth = this.brushSize * scale;
                    maskCtx.strokeStyle = '#000000'; // Draw Black (Alpha 255 -> RGB 0)
                    // Wait, we need to manipulate the Alpha/Grayscale value directly.
                    // Let's draw on a separate "Stroke Mask" and subtract?

                    // Easier: Draw on the `maskCanvas` (which has the current alpha) BEFORE reading into Mat.
                    // But we want to set these pixels to DEFINITE BACKGROUND (0).
                    // The threshold logic maps <100 to 0. So if we erase (clear) pixels on maskCanvas, they become 0.
                    maskCtx.globalCompositeOperation = 'destination-out';
                    maskCtx.beginPath();
                    for(let i=0; i<this.smartPoints.length-1; i++) {
                        const p1 = this.smartPoints[i];
                        const p2 = this.smartPoints[i+1];
                        maskCtx.moveTo(p1.x * scale, p1.y * scale);
                        maskCtx.lineTo(p2.x * scale, p2.y * scale);
                    }
                    maskCtx.stroke();
                    maskCtx.globalCompositeOperation = 'source-over';

                    // NOW read the mask
                    const finalAlphaMat = cv.imread(maskCanvas);
                    cv.cvtColor(finalAlphaMat, mask, cv.COLOR_RGBA2GRAY);

                    // Remap:
                    // If Pixel was ERASED (Transparent) -> It is 0 -> GC_BGD
                    // If Pixel is OPAQUE (Visible) -> It is >0 -> GC_PR_FGD (3)
                    // Note: cv.imread on transparent canvas puts 0 in channels? Yes.

                    cv.threshold(mask, mask, 50, 3, cv.THRESH_BINARY); // 0 or 3
                    // Now mask contains 0 (Background) and 3 (Probable Foreground).
                    // This setup is perfect for GC_INIT_WITH_MASK.
                    // The user's stroke became 0 (Definite Background).
                    // The existing background is also 0.
                    // The existing person is 3.

                    // Run GrabCut
                    const bgdModel = new cv.Mat();
                    const fgdModel = new cv.Mat();
                    const rect = new cv.Rect();

                    cv.grabCut(srcMat, mask, rect, bgdModel, fgdModel, 3, cv.GC_INIT_WITH_MASK);

                    // Extract Result
                    // Mask values: 0(BGD), 1(FGD), 2(PR_BGD), 3(PR_FGD)
                    // We want to keep 1 and 3.

                    // Create Output Mask
                    const binMask = new cv.Mat();
                    // Set all 1 and 3 to 255, others to 0
                    // Logic: (mask & 1) * 255 ?
                    // GrabCut mask: 0, 1, 2, 3.
                    // Foreground are 1 and 3. Odd numbers!
                    // mask & 1 => 1 for FGD/PR_FGD, 0 for BGD/PR_BGD.

                    // Using low-level loop or bitwise ops?
                    // cv.threshold can't select 1 and 3 easily.
                    // Helper: compare mask with 1 and 3?
                    // Easier:
                    // newMask = (mask == 1) | (mask == 3)

                    // Since JS OpenCV is limited, let's use:
                    // Set PR_BGD(2) to BGD(0)
                    // Set PR_FGD(3) to FGD(1)
                    // Then threshold > 0.

                    // Actually, just thresholding > 0 might include PR_BGD(2)?
                    // Yes. We want to exclude 2.
                    // GrabCut usually converges 2 to 0 and 3 to 1.
                    // But safely:
                    // We can just iterate or use inRange?

                    // Let's treat 2 (Probable BG) as BG (Transparent).
                    // So we only keep 1 (FGD) and 3 (PR_FGD).

                    // How to filter efficiently?
                    // cv.bitwise_and(mask, 1) -> result is 1 for (1,3), 0 for (0,2).
                    // This works perfectly!
                    const one = new cv.Mat(mask.rows, mask.cols, mask.type(), new cv.Scalar(1));
                    cv.bitwise_and(mask, one, binMask);

                    // Scale binMask to 255
                    const alphaScale = new cv.Mat(mask.rows, mask.cols, mask.type(), new cv.Scalar(255));
                    cv.multiply(binMask, alphaScale, binMask);

                    // Resize Mask back to Original Size
                    const finalMask = new cv.Mat();
                    cv.resize(binMask, finalMask, new cv.Size(width, height), 0, 0, cv.INTER_LINEAR);

                    // Apply to Work Canvas
                    // We have the new Alpha Mask in finalMask (Grayscale).
                    // We need to apply this alpha to the Work Canvas.

                    // Convert finalMask to Canvas
                    cv.imshow(this.tempCanvas, finalMask);

                    // Composite:
                    // 1. Draw Original Image on WorkCanvas (Reset it)
                    // 2. Composite TempCanvas (Alpha) -> Destination-In?
                    //    Destination-In: Keeps Source where Dest is opaque.
                    //    Here Source is Alpha Mask. Dest is Original Image?
                    //    No. Dest-In: "The existing content is kept where the new shape overlaps".
                    //    So: Draw Original. Draw Mask with 'destination-in'.

                    const ctx = this.workCanvas.getContext('2d');
                    ctx.clearRect(0, 0, width, height);
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.drawImage(this.originalImage, 0, 0); // Restore full original
                    ctx.globalCompositeOperation = 'destination-in';
                    ctx.drawImage(this.tempCanvas, 0, 0); // Cut out using new mask
                    ctx.globalCompositeOperation = 'source-over';

                    this.render();

                    // Clean up
                    srcMat.delete(); mask.delete(); bgdModel.delete(); fgdModel.delete();
                    binMask.delete(); one.delete(); alphaScale.delete(); finalMask.delete();
                    srcCanvas.remove(); maskCanvas.remove();

                } catch (err) {
                    console.error("Smart Erase Error:", err);
                    alert("Smart Erase failed: " + err.message);
                } finally {
                    this.toggleLoading(false);
                }
            }, 50);
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
