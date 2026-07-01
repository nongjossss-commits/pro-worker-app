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
        // Extra panels added in Phase A/B/D — toggled alongside bgToolbarContainer
        const extraPanels = ['rotationToolbarContainer', 'beautyPanelContainer', 'autoToolsContainer']
            .map(id => document.getElementById(id))
            .filter(Boolean);
        function toggleExtraPanels(show) {
            extraPanels.forEach(el => el.classList.toggle('d-none', !show));
        }

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

        // ------------------------------------------------------------------
        // Rotation / Flip / Fine-rotate — Phase A
        // ------------------------------------------------------------------
        // Cropper.js already supports rotate() + scaleX() when the instance
        // is alive, so these handlers just delegate. The fine-rotate slider
        // stores its previous value so successive drags accumulate correctly.
        const rotationToolbarContainer = document.getElementById('rotationToolbarContainer');
        const fineRotationSlider = document.getElementById('fineRotationSlider');
        const fineRotationLabel = document.getElementById('fineRotationLabel');
        const fineRotationReset = document.getElementById('fineRotationReset');
        let fineRotationLast = 0;

        if (rotationToolbarContainer) {
            rotationToolbarContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('button[data-rotate], button[data-flip]');
                if (!btn) return;
                const cropper = window.cropperManager.instance;
                if (!cropper) return;
                if (btn.dataset.rotate) {
                    cropper.rotate(parseInt(btn.dataset.rotate, 10));
                } else if (btn.dataset.flip === 'x') {
                    const data = cropper.getData(true);
                    // scaleX toggle: we store the current scale on the cropper element
                    const cur = cropper._proWorkerFlipX ? -1 : 1;
                    const next = -cur;
                    cropper.scaleX(next);
                    cropper._proWorkerFlipX = !cropper._proWorkerFlipX;
                }
            });
        }

        if (fineRotationSlider) {
            fineRotationSlider.addEventListener('input', function() {
                const cropper = window.cropperManager.instance;
                if (!cropper) return;
                const value = parseInt(this.value, 10);
                const delta = value - fineRotationLast;
                fineRotationLast = value;
                cropper.rotate(delta);
                if (fineRotationLabel) fineRotationLabel.textContent = value + '°';
            });
        }

        if (fineRotationReset) {
            fineRotationReset.addEventListener('click', function() {
                const cropper = window.cropperManager.instance;
                if (!cropper || !fineRotationSlider) return;
                cropper.rotate(-fineRotationLast);
                fineRotationLast = 0;
                fineRotationSlider.value = 0;
                if (fineRotationLabel) fineRotationLabel.textContent = '0°';
            });
        }

        // ------------------------------------------------------------------
        // Beauty / Adjust — Phase B
        // ------------------------------------------------------------------
        // Slider values are live-echoed to the value badges. Applying only
        // happens when the user presses "ใช้ค่านี้ / Preview" — this avoids
        // per-stroke re-rendering which would be slow for large ID photos.
        const beautySliders = document.querySelectorAll('.beauty-slider');
        const applyBeautyBtn = document.getElementById('applyBeautyBtn');
        const resetBeautyBtn = document.getElementById('resetBeautyBtn');
        const autoBeautyBtn = document.getElementById('autoBeautyBtn');

        function readBeautyValues() {
            const values = {};
            beautySliders.forEach(s => {
                values[s.dataset.beautyKey] = parseInt(s.value, 10) || 0;
            });
            return values;
        }

        function setBeautyValues(values) {
            beautySliders.forEach(s => {
                const k = s.dataset.beautyKey;
                if (values[k] !== undefined) {
                    s.value = values[k];
                    const label = document.querySelector(`[data-beauty-value="${k}"]`);
                    if (label) label.textContent = values[k];
                }
            });
        }

        // Live label echo
        beautySliders.forEach(s => {
            s.addEventListener('input', function() {
                const label = document.querySelector(`[data-beauty-value="${this.dataset.beautyKey}"]`);
                if (label) label.textContent = this.value;
            });
        });

        // Undo history for Beauty/Auto-Level/Face-Crop/Rotate operations.
        // Snapshots the *source image URL* right before we replace it, so the
        // user can revert to the state before the last transform. Cap the
        // stack at TRANSFORM_HISTORY_MAX to avoid unbounded memory use on
        // repeated adjustments.
        const TRANSFORM_HISTORY_MAX = 10;
        window.cropperManager.transformHistory = window.cropperManager.transformHistory || [];
        const undoTransformBtn = document.getElementById('undoTransformBtn');

        function refreshUndoBtn() {
            if (!undoTransformBtn) return;
            const canUndo = window.cropperManager.transformHistory.length > 0;
            undoTransformBtn.disabled = !canUndo;
            undoTransformBtn.classList.toggle('opacity-50', !canUndo);
        }
        refreshUndoBtn();

        // Extract the CURRENT full image (with rotation/flip baked in) from
        // Cropper.js WITHOUT cropping down to the user-drawn crop box.
        //
        // Why not cropper.getCroppedCanvas() alone?
        //   That returns only the crop-box area, so if the user has drawn a
        //   small crop rectangle and then presses Auto Beauty, the exported
        //   canvas is that small rectangle — losing head/arms. Each subsequent
        //   press cropped again, snowballing the loss.
        //
        // Fix: temporarily set the crop box to cover the whole visible canvas,
        // export, then restore the user's crop selection.
        function exportFullImageCanvas() {
            const cropper = window.cropperManager.instance;
            if (!cropper) return null;
            const savedCrop = cropper.getCropBoxData();
            const canvasData = cropper.getCanvasData();
            try {
                cropper.setCropBoxData({
                    left: canvasData.left,
                    top: canvasData.top,
                    width: canvasData.width,
                    height: canvasData.height,
                });
                return cropper.getCroppedCanvas({ imageSmoothingQuality: 'high' });
            } finally {
                // Always restore the user's crop selection even if export throws.
                try { cropper.setCropBoxData(savedCrop); } catch (e) { /* ignore */ }
            }
        }

        // Common helper: transform the current image in the cropper by a File-
        // returning async operation, then reload cropper with the result.
        //
        // Preserves the full image (does not bake in the crop box) so
        // adjustments compose without shrinking the frame. Pushes the
        // pre-transform src into transformHistory so the user can Undo.
        async function transformCropperImage(fn, loadingLabel) {
            const cropper = window.cropperManager.instance;
            if (!cropper) return;

            const canvas = exportFullImageCanvas();
            if (!canvas) { alert('ไม่สามารถอ่านรูปภาพปัจจุบันได้'); return; }
            const currentBlob = await new Promise(r => canvas.toBlob(r, window.cropperManager.mimeType || 'image/jpeg', 0.95));
            if (!currentBlob) return;
            const currentFile = new File([currentBlob], 'in-progress.jpg', { type: currentBlob.type });

            const allButtons = cropperModalEl.querySelectorAll('button');
            allButtons.forEach(b => b.disabled = true);
            if (loadingOverlay) loadingOverlay.classList.remove('d-none');
            if (loadingText) loadingText.textContent = loadingLabel || 'Processing...';

            try {
                const newFile = await fn(currentFile);
                if (!newFile) return;

                // Snapshot pre-transform src for Undo before we replace it.
                const prevSrc = imageToCrop.src;
                const prevMime = window.cropperManager.mimeType;
                window.cropperManager.transformHistory.push({ src: prevSrc, mime: prevMime });
                while (window.cropperManager.transformHistory.length > TRANSFORM_HISTORY_MAX) {
                    const dropped = window.cropperManager.transformHistory.shift();
                    if (dropped && dropped.src && dropped.src.startsWith('blob:')) {
                        try { URL.revokeObjectURL(dropped.src); } catch (e) { /* ignore */ }
                    }
                }
                refreshUndoBtn();

                // Cache the edited file so the next bg action skips the AI
                // and uses this file as its foreground source.
                window.cropperManager.editedFile = newFile;
                window.cropperManager.mimeType = newFile.type;

                const newUrl = URL.createObjectURL(newFile);
                imageToCrop.src = newUrl;
                await new Promise(r => { imageToCrop.onload = r; });

                if (window.cropperManager.instance) {
                    window.cropperManager.instance.destroy();
                    window.cropperManager.instance = null;
                }
                initCropperInstance();
            } catch (err) {
                console.error(err);
                alert('เกิดข้อผิดพลาด: ' + err.message);
            } finally {
                if (loadingOverlay) loadingOverlay.classList.add('d-none');
                allButtons.forEach(b => b.disabled = false);
            }
        }

        // Undo: pop the most recent snapshot and restore it as the cropper source.
        async function undoLastTransform() {
            const hist = window.cropperManager.transformHistory;
            if (!hist.length) return;
            const prev = hist.pop();
            refreshUndoBtn();

            const currentSrc = imageToCrop.src;
            window.cropperManager.mimeType = prev.mime;
            imageToCrop.src = prev.src;
            try { await new Promise(r => { imageToCrop.onload = r; }); } catch (e) { /* ignore */ }

            // Revoke the current (post-transform) src blob since we've replaced it.
            if (currentSrc && currentSrc.startsWith('blob:') && currentSrc !== prev.src) {
                try { URL.revokeObjectURL(currentSrc); } catch (e) { /* ignore */ }
            }

            if (window.cropperManager.instance) {
                window.cropperManager.instance.destroy();
                window.cropperManager.instance = null;
            }
            initCropperInstance();

            // Clear editedFile — the source is now a prior snapshot so any
            // cached AI-mask should be recomputed on next bg action.
            window.cropperManager.editedFile = null;
        }

        if (undoTransformBtn) {
            undoTransformBtn.addEventListener('click', undoLastTransform);
        }

        if (applyBeautyBtn) {
            applyBeautyBtn.addEventListener('click', async function() {
                const values = readBeautyValues();
                const anyNonZero = Object.values(values).some(v => v !== 0);
                if (!anyNonZero) return;
                await transformCropperImage(
                    (file) => window.photoEditorTools.applyAdjustments(file, values, window.cropperManager.mimeType || 'image/jpeg'),
                    'กำลังปรับภาพ...'
                );
                // After apply, reset sliders so next Preview is on top of new baseline
                setBeautyValues({ brightness: 0, contrast: 0, saturation: 0, warmth: 0, sharpness: 0, skinSmooth: 0 });
            });
        }

        if (resetBeautyBtn) {
            resetBeautyBtn.addEventListener('click', function() {
                setBeautyValues({ brightness: 0, contrast: 0, saturation: 0, warmth: 0, sharpness: 0, skinSmooth: 0 });
            });
        }

        if (autoBeautyBtn) {
            autoBeautyBtn.addEventListener('click', async function() {
                const preset = window.photoEditorTools.autoBeautyPreset();
                setBeautyValues(preset);
                await transformCropperImage(
                    (file) => window.photoEditorTools.applyAdjustments(file, preset, window.cropperManager.mimeType || 'image/jpeg'),
                    'Auto Beauty...'
                );
                setBeautyValues({ brightness: 0, contrast: 0, saturation: 0, warmth: 0, sharpness: 0, skinSmooth: 0 });
            });
        }

        // ------------------------------------------------------------------
        // Auto-Level + Face-Center Crop — Phase D
        // ------------------------------------------------------------------
        const autoLevelBtn = document.getElementById('autoLevelBtn');
        const autoFaceCropBtn = document.getElementById('autoFaceCropBtn');

        if (autoLevelBtn) {
            autoLevelBtn.addEventListener('click', async function() {
                await transformCropperImage(
                    (file) => window.photoEditorTools.autoLevel(file, window.cropperManager.mimeType || 'image/jpeg'),
                    'Auto-Level...'
                );
            });
        }

        if (autoFaceCropBtn) {
            autoFaceCropBtn.addEventListener('click', async function() {
                if (typeof FaceDetector === 'undefined') {
                    alert('เบราว์เซอร์นี้ไม่รองรับ FaceDetector — โปรดใช้ Chrome/Edge เวอร์ชั่นล่าสุด');
                    return;
                }
                await transformCropperImage(
                    (file) => window.photoEditorTools.faceCenterCrop(file, 150 / 180, window.cropperManager.mimeType || 'image/jpeg'),
                    'ค้นหาใบหน้าและตัดกรอบ...'
                );
            });
        }

        // Also reset the fine-rotation slider whenever we destroy+re-init the cropper
        // (rotation is baked into the exported canvas, so slider should be 0 again).
        const _originalInitCropper = initCropperInstance;
        function _resetFineRotationOnReinit() {
            if (fineRotationSlider) {
                fineRotationSlider.value = 0;
                fineRotationLast = 0;
                if (fineRotationLabel) fineRotationLabel.textContent = '0°';
            }
        }
        // Monkey-patch so every re-init clears the slider state.
        // (Safe: initCropperInstance is a closure-local function.)
        initCropperInstance = function() {
            _resetFineRotationOnReinit();
            return _originalInitCropper.apply(this, arguments);
        };

        // --- Event: Modal Shown ---
        cropperModalEl.addEventListener('shown.bs.modal', function () {
            if (cropImageBtn) cropImageBtn.disabled = true;

            // Reset View to Crop Mode
            if(cropperContainer) cropperContainer.classList.remove('d-none');
            if(cropToolbar) cropToolbar.classList.remove('d-none');
            if(cropperReviewContainer) cropperReviewContainer.classList.add('d-none');
            if(reviewToolbar) reviewToolbar.classList.add('d-none');
            if(bgToolbarContainer) bgToolbarContainer.classList.remove('d-none');
            toggleExtraPanels(true);

            // Clear undo history — a brand-new photo shouldn't inherit undo
            // snapshots from the previous session. Revoke old blob URLs so
            // they can be garbage collected.
            if (window.cropperManager.transformHistory) {
                window.cropperManager.transformHistory.forEach(h => {
                    if (h.src && h.src.startsWith('blob:')) {
                        try { URL.revokeObjectURL(h.src); } catch (e) {}
                    }
                });
                window.cropperManager.transformHistory = [];
            }
            refreshUndoBtn();

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
                width: 600, // Increased for better PDF print quality
                height: 720, // Increased for better PDF print quality
                minWidth: 400,
                minHeight: 400,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                alert('เกิดข้อผิดพลาดในการตัดภาพ (Canvas creation failed). กรุณาลองใหม่อีกครั้ง');
                return;
            }

            // Show Review
            const dataUrl = canvas.toDataURL('image/jpeg', 0.98);
            if(reviewImage) reviewImage.src = dataUrl;

            // Switch UI
            if(cropperContainer) cropperContainer.classList.add('d-none');
            if(cropToolbar) cropToolbar.classList.add('d-none');
            if(bgToolbarContainer) bgToolbarContainer.classList.add('d-none');
            toggleExtraPanels(false);

            if(cropperReviewContainer) cropperReviewContainer.classList.remove('d-none');
            if(reviewToolbar) reviewToolbar.classList.remove('d-none');
        });

        // --- Event: Back to Crop ---
        if(backToCropBtn) {
            backToCropBtn.addEventListener('click', function() {
                if(cropperContainer) cropperContainer.classList.remove('d-none');
                if(cropToolbar) cropToolbar.classList.remove('d-none');
                if(bgToolbarContainer) bgToolbarContainer.classList.remove('d-none');
                toggleExtraPanels(true);

                if(cropperReviewContainer) cropperReviewContainer.classList.add('d-none');
                if(reviewToolbar) reviewToolbar.classList.add('d-none');
            });
        }

        // --- Event: Enhance Button ---
        if(enhanceBtn) {
            enhanceBtn.addEventListener('click', async function() {
                if(!reviewImage.src) return;

                try {
                    if (loadingOverlay) loadingOverlay.classList.remove('d-none');
                    if (loadingText) loadingText.textContent = 'กำลังปรับภาพให้ชัด...';

                    const img = new Image();
                    img.crossOrigin = 'Anonymous';
                    await new Promise((resolve, reject) => {
                        img.onload = resolve;
                        img.onerror = reject;
                        img.src = reviewImage.src;
                    });

                    if (loadingText) loadingText.textContent = 'กำลังส่งภาพไป AI Server...';
                    await new Promise(r => setTimeout(r, 50));

                    // Convert current image to blob for upload
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = img.width;
                    tempCanvas.height = img.height;
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.drawImage(img, 0, 0);

                    const blob = await new Promise(resolve => tempCanvas.toBlob(resolve, 'image/jpeg', 0.95));

                    // Send to AI endpoint
                    const formData = new FormData();
                    formData.append('image', blob, 'enhance.jpg');
                    formData.append('mode', 'auto');
                    formData.append('upscale', '2');

                    if (loadingText) loadingText.textContent = 'AI กำลังประมวลผล... (อาจใช้เวลา 1-3 นาที)';

                    const response = await fetch('/api/image-enhance', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message + (result.details ? '\n' + result.details : ''));
                    }

                    // Apply AI result directly
                    reviewImage.src = result.image;

                    // Update mimeType
                    window.cropperManager.mimeType = 'image/jpeg';

                } catch (err) {
                    console.error("Enhancement Error:", err);
                    alert("การปรับความชัดล้มเหลว: " + err.message);
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
        tolerance: 20, // Default tolerance for Magic Wand (reduced for precision)

        // Zoom & Pan State
        zoomLevel: 1,
        panX: 0,
        panY: 0,
        isPanning: false,
        panStartX: 0,
        panStartY: 0,

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

            // Set custom orange cursor via JS (avoid broken SVG in HTML attributes)
            const wrapperEl = document.getElementById('refineCanvasWrapper');
            if (wrapperEl) {
                const svgCursor = `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32'%3E%3Cline x1='16' y1='0' x2='16' y2='32' stroke='%23FF6600' stroke-width='2'/%3E%3Cline x1='0' y1='16' x2='32' y2='16' stroke='%23FF6600' stroke-width='2'/%3E%3Ccircle cx='16' cy='16' r='3' fill='%23FF6600'/%3E%3C/svg%3E") 16 16, crosshair`;
                wrapperEl.style.cursor = svgCursor;
            }

            // Zoom buttons
            const zoomInBtn = document.getElementById('refineZoomIn');
            const zoomOutBtn = document.getElementById('refineZoomOut');
            const zoomResetBtn = document.getElementById('refineZoomReset');
            if (zoomInBtn) zoomInBtn.addEventListener('click', () => { this.zoomLevel = Math.min(5, Math.round((this.zoomLevel + 0.25) * 100) / 100); this.applyZoom(); });
            if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => { this.zoomLevel = Math.max(0.5, Math.round((this.zoomLevel - 0.25) * 100) / 100); this.applyZoom(); });
            if (zoomResetBtn) zoomResetBtn.addEventListener('click', () => this.resetZoom());

            // Zoom with Ctrl+wheel (normal wheel = scroll/pan)
            const wrapper = document.getElementById('refineCanvasWrapper');
            if (wrapper) {
                wrapper.addEventListener('wheel', (e) => {
                    if (!this.isActive) return;
                    if (e.ctrlKey) {
                        // Ctrl+wheel = zoom
                        e.preventDefault();
                        const delta = e.deltaY > 0 ? -0.15 : 0.15;
                        this.zoomLevel = Math.max(0.5, Math.min(5, Math.round((this.zoomLevel + delta) * 100) / 100));
                        this.applyZoom();
                    }
                    // Without Ctrl = normal scroll (native scrollbar handles pan)
                }, { passive: false });

                // Update magnifier on mouse move
                wrapper.addEventListener('mousemove', (e) => {
                    if (this.isActive) this.updateMagnifier(e);
                });
                wrapper.addEventListener('mouseleave', () => {
                    const mag = document.getElementById('refineMagnifier');
                    if (mag) mag.style.display = 'none';
                });
            }
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
                this.resetZoom();

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
            this.resetZoom();
            const mag = document.getElementById('refineMagnifier');
            if (mag) mag.style.display = 'none';

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

        applyZoom() {
            // Use CSS width/height for zoom so native scrollbars work
            const wrapper = document.getElementById('refineCanvasWrapper');
            if (this.displayCanvas && wrapper) {
                const baseW = this.displayCanvas.width;
                const baseH = this.displayCanvas.height;
                // Fit to wrapper at zoom=1
                const wrapperW = wrapper.clientWidth;
                const fitScale = wrapperW / baseW;
                const displayW = baseW * fitScale * this.zoomLevel;
                const displayH = baseH * fitScale * this.zoomLevel;
                this.displayCanvas.style.width = displayW + 'px';
                this.displayCanvas.style.height = displayH + 'px';
            }
            const pct = Math.round(this.zoomLevel * 100) + '%';
            const indicator = document.getElementById('refineZoomIndicator');
            if (indicator) indicator.textContent = pct;
            const btnLabel = document.getElementById('refineZoomBtnLabel');
            if (btnLabel) btnLabel.textContent = pct;
        },

        resetZoom() {
            this.zoomLevel = 1;
            this.panX = 0;
            this.panY = 0;
            this.applyZoom();
            // Scroll to top-left
            const wrapper = document.getElementById('refineCanvasWrapper');
            if (wrapper) { wrapper.scrollTop = 0; wrapper.scrollLeft = 0; }
        },

        updateMagnifier(evt) {
            const mag = document.getElementById('refineMagnifier');
            const magCanvas = document.getElementById('refineMagnifierCanvas');
            if (!mag || !magCanvas || !this.workCanvas) return;

            const pos = this.getMousePos(evt);
            const magCtx = magCanvas.getContext('2d');
            const magSize = 150;
            const zoomFactor = 3; // 3x magnification
            const srcSize = magSize / zoomFactor;

            // Draw checkerboard background
            magCtx.fillStyle = '#fff';
            magCtx.fillRect(0, 0, magSize, magSize);
            for (let i = 0; i < magSize; i += 10) {
                for (let j = 0; j < magSize; j += 10) {
                    if ((i + j) % 20 === 0) {
                        magCtx.fillStyle = '#ddd';
                        magCtx.fillRect(i, j, 10, 10);
                    }
                }
            }

            // Draw magnified area from work canvas
            magCtx.drawImage(this.workCanvas,
                pos.x - srcSize / 2, pos.y - srcSize / 2, srcSize, srcSize,
                0, 0, magSize, magSize
            );

            // Draw crosshair (orange for visibility)
            magCtx.strokeStyle = '#FF6600';
            magCtx.lineWidth = 2;
            magCtx.beginPath();
            magCtx.moveTo(magSize / 2, 0);
            magCtx.lineTo(magSize / 2, magSize);
            magCtx.moveTo(0, magSize / 2);
            magCtx.lineTo(magSize, magSize / 2);
            magCtx.stroke();

            // Draw brush circle (orange)
            const brushRadius = (this.currentTool === 'smart_erase' ? 3 : this.brushSize / 2) * zoomFactor;
            magCtx.strokeStyle = '#FF6600';
            magCtx.lineWidth = 2;
            magCtx.beginPath();
            magCtx.arc(magSize / 2, magSize / 2, brushRadius, 0, Math.PI * 2);
            magCtx.stroke();

            // Position magnifier near cursor (offset to top-right)
            mag.style.display = 'block';
            mag.style.left = (evt.clientX + 20) + 'px';
            mag.style.top = (evt.clientY - 170) + 'px';
        },

        getMousePos(evt) {
            const rect = this.displayCanvas.getBoundingClientRect();
            // Scale accounts for both CSS display size AND zoom level
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

                    // Improved tolerance: use percentage of max possible distance
                    // tolerance 1-100 → mapped to tighter range for precision
                    // At tolerance 20: maxDist = (20/100 * 80)^2 * 3 = 16^2 * 3 = 768 (very precise)
                    // At tolerance 50: maxDist = (50/100 * 80)^2 * 3 = 40^2 * 3 = 4800 (moderate)
                    // At tolerance 100: maxDist = (100/100 * 80)^2 * 3 = 80^2 * 3 = 19200 (generous)
                    const maxDist = Math.pow((this.tolerance / 100) * 80, 2) * 3;

                    const matchColor = (pos) => {
                        const a = data[pos + 3];
                        if (a === 0) return false; // Already transparent

                        const r = data[pos];
                        const g = data[pos + 1];
                        const b = data[pos + 2];

                        // Compare against TARGET color (the pixel that was clicked)
                        const distSq = Math.pow(r - targetR, 2) + Math.pow(g - targetG, 2) + Math.pow(b - targetB, 2);
                        return distSq <= maxDist;
                    };

                    // Edge detection: check if neighbor has sharp color change
                    const isEdge = (pos) => {
                        const r = data[pos], g = data[pos+1], b = data[pos+2];
                        // Check 4 neighbors for sharp change
                        const neighbors = [pos - width*4, pos + width*4, pos - 4, pos + 4];
                        for (const nPos of neighbors) {
                            if (nPos < 0 || nPos >= data.length - 3) continue;
                            if (data[nPos+3] === 0) continue; // Skip transparent
                            const nr = data[nPos], ng = data[nPos+1], nb = data[nPos+2];
                            const edgeDist = Math.pow(r-nr,2) + Math.pow(g-ng,2) + Math.pow(b-nb,2);
                            if (edgeDist > 3000) return true; // Strong edge detected
                        }
                        return false;
                    };

                    // Implement Scanline Flood Fill with edge detection
                    let stack = [[x, y]];
                    const visited = new Uint8Array(width * height);
                    const maxPixels = width * height * 0.4; // Safety: max 40% of image
                    let filledCount = 0;

                    while(stack.length > 0 && filledCount < maxPixels) {
                        let [cx, cy] = stack.pop();
                        let currentX = cx;

                        // Move left to find the start of the span
                        while(currentX > 0) {
                            const pIdx = (cy * width + (currentX - 1)) * 4;
                            if (!matchColor(pIdx) || visited[cy * width + (currentX - 1)] || isEdge(pIdx)) break;
                            currentX--;
                        }

                        let spanUp = false;
                        let spanDown = false;

                        // Move right and fill
                        while(currentX < width && filledCount < maxPixels) {
                            const p = (cy * width + currentX) * 4;
                            if (visited[cy * width + currentX]) { currentX++; continue; }
                            if (!matchColor(p) || isEdge(p)) break;

                            data[p + 3] = 0; // Erase (set alpha to 0)
                            visited[cy * width + currentX] = 1;
                            filledCount++;

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
            dobInput.addEventListener('input', calculateAge);
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
            startDateInput.addEventListener('input', calculateWorkAge);
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
