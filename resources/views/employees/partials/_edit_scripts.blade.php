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
                    if (loadingText) loadingText.textContent = 'Enhancing Face with AI...';

                    // Convert src to Blob
                    const res = await fetch(reviewImage.src);
                    const blob = await res.blob();

                    const formData = new FormData();
                    formData.append('image', blob, 'to_enhance.jpg');
                    // Add CSRF
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const response = await fetch('/employees/photo/enhance', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if(!response.ok) {
                         throw new Error(data.error || 'Enhancement failed');
                    }

                    // Update Review Image
                    reviewImage.src = data.url;

                } catch (err) {
                    console.error(err);
                    alert('AI Enhancement Failed: ' + err.message);
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
                // Draw only the new segment on the Display Canvas (Temporary Visual)
                // We do NOT render() here to avoid full redraws.
                this.ctx.beginPath();
                this.ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)';
                this.ctx.lineWidth = this.brushSize;
                this.ctx.lineCap = 'round';

                // Draw from last point to current point
                if (this.smartPoints.length > 1) {
                    const prev = this.smartPoints[this.smartPoints.length - 2];
                    this.ctx.moveTo(prev.x, prev.y);
                    this.ctx.lineTo(pos.x, pos.y);
                } else {
                    // First point
                    this.ctx.moveTo(pos.x, pos.y);
                    this.ctx.lineTo(pos.x, pos.y);
                }
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

        // --- Smart Erase Logic (OpenCV) ---
        applySmartErase() {
            if (typeof cv === 'undefined' || !cv.Mat || !cv.grabCut) {
                alert('Smart Erase requires OpenCV. Please wait for it to load or check connection.');
                return;
            }

            this.toggleLoading(true, 'Analyzing image...');

            // Use setTimeout to allow UI to render the loading state
            setTimeout(() => {
                let srcMat = null, mask = null, bgdModel = null, fgdModel = null;
                let binMask = null, one = null, alphaScale = null, finalMask = null;
                let alphaMat = null, finalAlphaMat = null, tempMask = null;
                let srcCanvas = null, maskCanvas = null;
                let rect = null;

                try {
                    // 1. Setup Data
                    const width = this.workCanvas.width;
                    const height = this.workCanvas.height;

                    // Downscale for performance
                    const maxDim = 600;
                    const scale = Math.min(1, maxDim / Math.max(width, height));

                    // ENSURE INTEGERS using Math.floor to prevent OpenCV WASM errors (table index out of bounds)
                    const sW = Math.floor(width * scale);
                    const sH = Math.floor(height * scale);

                    // Additional Safety Check
                    if (sW < 1 || sH < 1) throw new Error('Image too small for processing');
                    if (isNaN(sW) || isNaN(sH)) throw new Error('Invalid dimensions calculated');

                    // Create Source Mat (Original Image) - Downscaled
                    srcCanvas = document.createElement('canvas');
                    srcCanvas.width = sW;
                    srcCanvas.height = sH;
                    const srcCtx = srcCanvas.getContext('2d');
                    if (!this.originalImage) throw new Error('Original image source missing');
                    srcCtx.drawImage(this.originalImage, 0, 0, sW, sH);

                    // Use matFromImageData instead of imread to avoid potential internal canvas context issues
                    const srcData = srcCtx.getImageData(0, 0, sW, sH);
                    srcMat = cv.matFromImageData(srcData); // RGBA
                    if (srcMat.empty()) throw new Error('Failed to load source image into OpenCV');
                    cv.cvtColor(srcMat, srcMat, cv.COLOR_RGBA2RGB); // GrabCut needs RGB (CV_8UC3)

                    // Create Mask Mat (From WorkCanvas Alpha) - Downscaled
                    maskCanvas = document.createElement('canvas');
                    maskCanvas.width = sW;
                    maskCanvas.height = sH;
                    const maskCtx = maskCanvas.getContext('2d');
                    maskCtx.drawImage(this.workCanvas, 0, 0, sW, sH);

                    const maskData = maskCtx.getImageData(0, 0, sW, sH);
                    alphaMat = cv.matFromImageData(maskData); // RGBA
                    if (alphaMat.empty()) throw new Error('Failed to load mask into OpenCV');

                    mask = new cv.Mat();
                    cv.cvtColor(alphaMat, mask, cv.COLOR_RGBA2GRAY); // 1 channel (CV_8UC1)

                    // Initialize GrabCut Mask
                    // mask = 0 (BGD) where alpha < 100
                    // mask = 3 (PR_FGD) where alpha >= 100
                    cv.threshold(mask, mask, 100, 3, cv.THRESH_BINARY); // 0 or 3

                    // Apply User Strokes as GC_BGD (0)
                    maskCtx.globalCompositeOperation = 'destination-out';
                    maskCtx.beginPath();
                    maskCtx.lineCap = 'round';
                    maskCtx.lineWidth = this.brushSize * scale;
                    for(let i=0; i<this.smartPoints.length-1; i++) {
                        const p1 = this.smartPoints[i];
                        const p2 = this.smartPoints[i+1];
                        maskCtx.moveTo(p1.x * scale, p1.y * scale);
                        maskCtx.lineTo(p2.x * scale, p2.y * scale);
                    }
                    maskCtx.stroke();
                    maskCtx.globalCompositeOperation = 'source-over';

                    // Read updated mask (with user strokes removed)
                    const finalMaskData = maskCtx.getImageData(0, 0, sW, sH);
                    finalAlphaMat = cv.matFromImageData(finalMaskData);
                    if (finalAlphaMat.empty()) throw new Error('Failed to read updated mask');

                    // Update mask based on strokes: Transparent areas become 0 (BGD), Visible become 3 (PR_FGD)
                    // We need to re-read into 'mask'
                    // Note: 'mask' currently holds initial state. We overwrite it.
                    tempMask = new cv.Mat();
                    cv.cvtColor(finalAlphaMat, tempMask, cv.COLOR_RGBA2GRAY);
                    cv.threshold(tempMask, mask, 50, 3, cv.THRESH_BINARY);
                    if (tempMask && !tempMask.isDeleted()) tempMask.delete();
                    tempMask = null;

                    // Run GrabCut
                    // Explicitly allocate models with correct type/size (1, 65, CV_64FC1)
                    bgdModel = new cv.Mat(1, 65, cv.CV_64FC1);
                    fgdModel = new cv.Mat(1, 65, cv.CV_64FC1);

                    // Create valid rect within bounds (though ignored by GC_INIT_WITH_MASK, it must be valid)
                    // Warning: rect must be deleted explicitly to avoid leaks
                    rect = new cv.Rect(0, 0, sW, sH);

                    // GC_INIT_WITH_MASK (1)
                    try {
                        cv.grabCut(srcMat, mask, rect, bgdModel, fgdModel, 3, cv.GC_INIT_WITH_MASK);
                    } catch (gcErr) {
                         console.error("OpenCV GrabCut Error:", gcErr);
                         throw new Error("Failed to segment object. Try using the simple Eraser tool.");
                    }

                    // Extract Result (Keep FG=1 and PR_FGD=3)
                    binMask = new cv.Mat();
                    one = new cv.Mat(mask.rows, mask.cols, mask.type(), new cv.Scalar(1));
                    cv.bitwise_and(mask, one, binMask); // result is 1 for (1,3), 0 for (0,2)

                    // Scale binMask to 255
                    alphaScale = new cv.Mat(mask.rows, mask.cols, mask.type(), new cv.Scalar(255));
                    cv.multiply(binMask, alphaScale, binMask);

                    // Resize Mask back to Original Size
                    finalMask = new cv.Mat();
                    cv.resize(binMask, finalMask, new cv.Size(width, height), 0, 0, cv.INTER_LINEAR);

                    // Apply to Work Canvas
                    cv.imshow(this.tempCanvas, finalMask);

                    const ctx = this.workCanvas.getContext('2d');
                    ctx.clearRect(0, 0, width, height);
                    ctx.globalCompositeOperation = 'source-over';
                    ctx.drawImage(this.originalImage, 0, 0); // Restore full original
                    ctx.globalCompositeOperation = 'destination-in';
                    ctx.drawImage(this.tempCanvas, 0, 0); // Cut out using new mask
                    ctx.globalCompositeOperation = 'source-over';

                    // Final render to sync display
                    this.render();

                } catch (err) {
                    console.error("Smart Erase Error:", err);
                    alert("Smart Erase failed: " + err.message + "\nCheck console for details.");
                } finally {
                    // Safe cleanup
                    if(srcMat && !srcMat.isDeleted()) srcMat.delete();
                    if(mask && !mask.isDeleted()) mask.delete();
                    if(bgdModel && !bgdModel.isDeleted()) bgdModel.delete();
                    if(fgdModel && !fgdModel.isDeleted()) fgdModel.delete();
                    if(binMask && !binMask.isDeleted()) binMask.delete();
                    if(one && !one.isDeleted()) one.delete();
                    if(alphaScale && !alphaScale.isDeleted()) alphaScale.delete();
                    if(finalMask && !finalMask.isDeleted()) finalMask.delete();
                    if(alphaMat && !alphaMat.isDeleted()) alphaMat.delete();
                    if(finalAlphaMat && !finalAlphaMat.isDeleted()) finalAlphaMat.delete();
                    if(tempMask && !tempMask.isDeleted()) tempMask.delete();
                    if(rect) rect.delete(); // Delete rect (it's a C++ object, not a Mat, so no isDeleted check usually needed but safe to check if it exists)

                    if(srcCanvas) srcCanvas.remove();
                    if(maskCanvas) maskCanvas.remove();

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
