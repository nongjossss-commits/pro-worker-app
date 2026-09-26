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
        targetPreviewId: null,
        // Last Smart Background choice: null | 'original' | 'transparent' | 'white' | 'blue'.
        // Drives the fill colour of rotated corners and the background the
        // Refine tool re-applies on save.
        bgAction: null,
        // The exact (un-cut) photo the background was removed from — aligned
        // pixel-for-pixel with the AI cutout, so Refine can show it as a
        // ghost and restore from it.
        bgSourceFile: null,

        // Colour that empty areas (corners after rotating) should get, or null
        // when the photo is meant to stay transparent.
        fillColor() {
            if (this.bgAction === 'transparent') return null;
            const colors = (window.backgroundRemoval && window.backgroundRemoval.colors) || {};
            if (this.bgAction === 'blue') return colors.blue || '#65a5ff';
            return colors.white || '#FFFFFF';
        }
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
                        // Show rotated corners in the background colour the
                        // export will use (instead of the modal's grey).
                        const canvasBox = cropperModalEl.querySelector('.cropper-canvas');
                        if (canvasBox) canvasBox.style.background = window.cropperManager.fillColor() || '';
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
                    // Use the edited version. Only a Refine cut-out already has
                    // its background removed; a photo changed by Auto-Level /
                    // Beauty / Face Crop is still a normal photo, so the AI
                    // must run on it (skipping it left the old background).
                    fileToProcess = window.cropperManager.editedFile;
                    isEdited = !!window.cropperManager.editedIsCutout;
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
                    // Remember the choice (rotation fill + Refine re-apply) and,
                    // for a real photo (not an already-refined cutout), the
                    // exact source the cutout was made from.
                    window.cropperManager.bgAction = action;
                    if (!isEdited) window.cropperManager.bgSourceFile = fileToProcess;

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
                // Rotated corners: fill with the background colour when the
                // result will be a JPEG (transparent → black otherwise).
                const fill = window.cropperManager.mimeType === 'image/png' ? null : window.cropperManager.fillColor();
                return cropper.getCroppedCanvas({ imageSmoothingQuality: 'high', ...(fill ? { fillColor: fill } : {}) });
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
                window.cropperManager.editedIsCutout = false; // a normal photo, background still in it
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
                // Works in every browser: photoEditorTools.detectFace() falls
                // back to finding the head from the AI cut-out when the
                // browser has no FaceDetector (most desktop Chrome/Edge).
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
        // Refine's save() swaps the image via cropper.replace(), which drops the
        // rotation — let it reset the slider too.
        window.cropperManager.resetFineRotation = _resetFineRotationOnReinit;

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

            // New photo session: forget the previous photo's background choice.
            window.cropperManager.bgAction = null;
            window.cropperManager.bgSourceFile = null;

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

            // Corners exposed by rotating are filled with the chosen background
            // colour (white by default) instead of turning black in the JPEG.
            const fill = window.cropperManager.fillColor();
            const keepTransparent = !fill && window.cropperManager.mimeType === 'image/png';
            const canvas = cropper.getCroppedCanvas({
                width: 600, // Increased for better PDF print quality
                height: 720, // Increased for better PDF print quality
                minWidth: 400,
                minHeight: 400,
                imageSmoothingQuality: 'high',
                ...(keepTransparent ? {} : { fillColor: fill || '#FFFFFF' }),
            });

            if (!canvas) {
                alert('เกิดข้อผิดพลาดในการตัดภาพ (Canvas creation failed). กรุณาลองใหม่อีกครั้ง');
                return;
            }

            // Show Review
            const dataUrl = keepTransparent ? canvas.toDataURL('image/png') : canvas.toDataURL('image/jpeg', 0.98);
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
    //
    // Edits the background cutout by hand.
    //  - Works on the real transparent cutout (not on the white/blue composite),
    //    so what is erased really becomes background.
    //  - Shows the un-cut photo as a faint ghost underneath, so a wrongly cut
    //    arm/shoulder is visible and can be restored exactly where it was.
    //  - "Smart edge" mode: the brush reads the colour under its centre and only
    //    erases/restores the connected area of similar colour inside the brush,
    //    stopping at edges by itself (like a background-eraser with a radar).
    //  - Save re-applies the last chosen background (white by default), so the
    //    user never has to press the background buttons again afterwards.
    window.refineManager = {
        initialized: false,
        isActive: false,

        // Layers (all the same size)
        workCanvas: null,     // the cutout being edited (RGBA)
        refCanvas: null,      // the un-cut photo (ghost + restore source)
        refData: null,        // ImageData of refCanvas (smart brush sampling)
        hasGhost: false,
        displayCanvas: null,
        ctx: null,

        // History (canvas snapshots — fast, no PNG encoding per stroke)
        history: [],
        maxHistory: 12,

        // Tools
        currentTool: 'eraser', // eraser | restore | smart_erase (magic wand)
        brushSize: 30,
        smartMode: true,       // edge-aware brush for eraser/restore
        smartTolerance: 30,
        showGhost: true,
        tolerance: 20,         // magic wand tolerance
        isDrawing: false,
        lastPos: { x: 0, y: 0 },

        // Zoom & pan
        zoomLevel: 1,
        spaceDown: false,
        isPanning: false,
        panStart: null,
        MAX_DIM: 2000,         // editing resolution cap (keeps brushes smooth)
        _renderQueued: false,

        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.container = document.getElementById('refineEditorContainer');
            this.displayCanvas = document.getElementById('refineCanvas');
            this.ctx = this.displayCanvas.getContext('2d');
            this.wrapper = document.getElementById('refineCanvasWrapper');
            this.brushCursor = document.getElementById('refineBrushCursor');

            this.btnStart = document.getElementById('btnStartRefine');
            this.btnSave = document.getElementById('refineBtnSave');
            this.btnCancel = document.getElementById('refineBtnCancel');
            this.btnUndo = document.getElementById('refineBtnUndo');

            this.toolEraser = document.getElementById('refineToolEraser');
            this.toolRestore = document.getElementById('refineToolRestore');
            this.toolSmart = document.getElementById('refineToolSmart');
            this.toggleSmart = document.getElementById('refineToggleSmartEdge');
            this.toggleGhost = document.getElementById('refineToggleGhost');
            this.rangeSize = document.getElementById('refineBrushSize');
            this.rangeLabel = document.getElementById('refineBrushSizeLabel');

            if (this.btnStart) this.btnStart.addEventListener('click', () => this.start());
            if (this.btnSave) this.btnSave.addEventListener('click', () => this.save());
            if (this.btnCancel) this.btnCancel.addEventListener('click', () => this.cancel());
            if (this.btnUndo) this.btnUndo.addEventListener('click', () => this.undo());

            if (this.toolEraser) this.toolEraser.addEventListener('click', () => this.setTool('eraser'));
            if (this.toolRestore) this.toolRestore.addEventListener('click', () => this.setTool('restore'));
            if (this.toolSmart) this.toolSmart.addEventListener('click', () => this.setTool('smart_erase'));
            if (this.toggleSmart) this.toggleSmart.addEventListener('click', () => this.setSmartMode(!this.smartMode));
            if (this.toggleGhost) this.toggleGhost.addEventListener('click', () => this.setGhost(!this.showGhost));

            if (this.rangeSize) {
                this.rangeSize.addEventListener('input', (e) => {
                    const v = parseInt(e.target.value, 10);
                    if (this.currentTool === 'smart_erase') this.tolerance = v;
                    else this.brushSize = v;
                    this.updateSizeLabel();
                });
            }

            // Drawing (pointer events cover mouse, pen and touch)
            const c = this.displayCanvas;
            c.style.touchAction = 'none';
            c.addEventListener('pointerdown', (e) => this.onPointerDown(e));
            c.addEventListener('pointermove', (e) => this.onPointerMove(e));
            window.addEventListener('pointerup', () => this.onPointerUp());
            c.addEventListener('contextmenu', (e) => { if (this.isActive) e.preventDefault(); });

            // Zoom buttons
            const zoomIn = document.getElementById('refineZoomIn');
            const zoomOut = document.getElementById('refineZoomOut');
            const zoomReset = document.getElementById('refineZoomReset');
            if (zoomIn) zoomIn.addEventListener('click', () => this.zoomBy(1.25));
            if (zoomOut) zoomOut.addEventListener('click', () => this.zoomBy(0.8));
            if (zoomReset) zoomReset.addEventListener('click', () => this.resetZoom());

            if (this.wrapper) {
                // Mouse wheel = zoom towards the cursor (no Ctrl needed)
                this.wrapper.addEventListener('wheel', (e) => {
                    if (!this.isActive) return;
                    e.preventDefault();
                    this.zoomBy(e.deltaY > 0 ? 0.85 : 1.18, e);
                }, { passive: false });

                this.wrapper.addEventListener('pointermove', (e) => {
                    if (!this.isActive) return;
                    this.updateBrushCursor(e);
                    this.updateMagnifier(e);
                });
                this.wrapper.addEventListener('pointerleave', () => {
                    const mag = document.getElementById('refineMagnifier');
                    if (mag) mag.style.display = 'none';
                    if (this.brushCursor) this.brushCursor.style.display = 'none';
                });
            }

            // Keyboard shortcuts (only while the editor is open)
            window.addEventListener('keydown', (e) => this.onKeyDown(e));
            window.addEventListener('keyup', (e) => {
                if (e.code === 'Space') { this.spaceDown = false; this.updateCursorStyle(); }
            });
        },

        // ------------------------------------------------------------------
        // Open / save / cancel
        // ------------------------------------------------------------------
        async start() {
            if (this.isActive) return;
            const imageToCrop = document.getElementById('imageToCrop');
            if (!imageToCrop || !imageToCrop.src) {
                alert('No image to refine.');
                return;
            }
            const cm = window.cropperManager;

            this.isActive = true;
            document.querySelector('.img-container').style.display = 'none';
            const footer = document.querySelector('#cropperModal .modal-footer');
            if (footer) footer.classList.add('d-none');
            this.container.classList.remove('d-none');
            this.toggleLoading(true, 'Preparing Editor...');

            try {
                // 1. The layer to edit: a real cutout whenever one exists.
                let workBlob = null;
                const br = window.backgroundRemoval;
                if (cm.editedFile && cm.editedIsCutout) {
                    workBlob = cm.editedFile; // result of a previous refine
                } else if (['transparent', 'white', 'blue'].includes(cm.bgAction) && br && br.cache
                    && br.cache.transparentBlob && br.cache.originalFile === cm.bgSourceFile) {
                    try { workBlob = await br.refineTransparent(br.cache.transparentBlob); }
                    catch (e) { workBlob = br.cache.transparentBlob; }
                }
                const workImg = await this.loadImage(workBlob ? URL.createObjectURL(workBlob) : imageToCrop.src);

                // 2. The un-cut photo for the ghost + restore, if it lines up.
                let refImg = workImg;
                const refFile = cm.bgSourceFile || cm.originalFile;
                if (refFile && workBlob) {
                    const candidate = await this.loadImage(URL.createObjectURL(refFile));
                    const sameShape = Math.abs(candidate.width / candidate.height - workImg.width / workImg.height) < 0.02;
                    if (sameShape) refImg = candidate;
                } else if (refFile && !workBlob) {
                    // Editing the photo itself: restore from the original file.
                    const candidate = await this.loadImage(URL.createObjectURL(refFile));
                    const sameShape = Math.abs(candidate.width / candidate.height - workImg.width / workImg.height) < 0.02;
                    if (sameShape) refImg = candidate;
                }
                this.hasGhost = refImg !== workImg;

                // 3. Size (capped for smooth brushing)
                const scale = Math.min(1, this.MAX_DIM / Math.max(refImg.width, refImg.height));
                const w = Math.round(refImg.width * scale);
                const h = Math.round(refImg.height * scale);

                this.displayCanvas.width = w;
                this.displayCanvas.height = h;

                this.workCanvas = document.createElement('canvas');
                this.workCanvas.width = w; this.workCanvas.height = h;
                this.workCanvas.getContext('2d', { willReadFrequently: true }).drawImage(workImg, 0, 0, w, h);

                this.refCanvas = document.createElement('canvas');
                this.refCanvas.width = w; this.refCanvas.height = h;
                const refCtx = this.refCanvas.getContext('2d', { willReadFrequently: true });
                refCtx.drawImage(refImg, 0, 0, w, h);
                this.refData = refCtx.getImageData(0, 0, w, h);

                this.tempCanvas = document.createElement('canvas');
                this.tempCanvas.width = w; this.tempCanvas.height = h;

                // Brush size relative to the image so it feels the same on any photo
                const base = Math.max(8, Math.round(Math.max(w, h) / 40));
                this.brushSize = base;
                if (this.rangeSize) {
                    this.rangeSize.min = 3;
                    this.rangeSize.max = Math.max(60, base * 5);
                }
                this.history = [];
                this.setTool('eraser');
                this.setSmartMode(this.smartMode);
                this.setGhost(this.hasGhost ? this.showGhost : false);
                if (this.toggleGhost) this.toggleGhost.disabled = !this.hasGhost;

                this.render();
                this.resetZoom();
                this.updateUndoButton();
            } catch (e) {
                console.error(e);
                alert('Failed to start refine mode: ' + e.message);
                this.cancel();
            } finally {
                this.toggleLoading(false);
            }
        },

        async save() {
            if (!this.workCanvas) return;
            const cm = window.cropperManager;
            this.toggleLoading(true, 'Saving...');
            try {
                const toBlob = (canvas, type, q) => new Promise(r => canvas.toBlob(r, type, q));

                // The edited cutout itself — later background buttons composite onto this.
                const cutoutBlob = await toBlob(this.workCanvas, 'image/png');
                const baseName = cm.originalFile ? cm.originalFile.name.replace(/\.[^/.]+$/, '') : 'image';
                cm.editedFile = new File([cutoutBlob], baseName + '.png', { type: 'image/png', lastModified: Date.now() });
                cm.editedIsCutout = true;

                // Re-apply the background the user already chose (white if none),
                // so the result is ready — no extra "White BG" clicks needed.
                let action = cm.bgAction;
                if (!action || action === 'original') action = 'white';
                let resultBlob;
                if (action === 'transparent') {
                    resultBlob = cutoutBlob;
                    cm.mimeType = 'image/png';
                } else {
                    const colors = (window.backgroundRemoval && window.backgroundRemoval.colors) || {};
                    const out = document.createElement('canvas');
                    out.width = this.workCanvas.width; out.height = this.workCanvas.height;
                    const octx = out.getContext('2d');
                    octx.fillStyle = colors[action] || '#FFFFFF';
                    octx.fillRect(0, 0, out.width, out.height);
                    octx.drawImage(this.workCanvas, 0, 0);
                    resultBlob = await toBlob(out, 'image/jpeg', 0.95);
                    cm.mimeType = 'image/jpeg';
                }
                cm.bgAction = action;

                const newUrl = URL.createObjectURL(resultBlob);
                const imageToCrop = document.getElementById('imageToCrop');
                if (cm.instance) {
                    cm.instance.replace(newUrl);
                } else {
                    imageToCrop.src = newUrl;
                }
                if (typeof cm.resetFineRotation === 'function') cm.resetFineRotation();
            } catch (e) {
                console.error(e);
                alert('Failed to save: ' + e.message);
            }
            this.exit();
        },

        cancel() {
            this.exit();
        },

        exit() {
            this.toggleLoading(false);
            this.isActive = false;
            this.isDrawing = false;
            this.isPanning = false;
            this.container.classList.add('d-none');
            document.querySelector('.img-container').style.display = 'block';
            const footer = document.querySelector('#cropperModal .modal-footer');
            if (footer) footer.classList.remove('d-none');

            this.history = [];
            this.workCanvas = null;
            this.refCanvas = null;
            this.refData = null;
            this.tempCanvas = null;
            const mag = document.getElementById('refineMagnifier');
            if (mag) mag.style.display = 'none';
            if (this.brushCursor) this.brushCursor.style.display = 'none';
        },

        // ------------------------------------------------------------------
        // History
        // ------------------------------------------------------------------
        pushHistory() {
            const snap = document.createElement('canvas');
            snap.width = this.workCanvas.width; snap.height = this.workCanvas.height;
            snap.getContext('2d').drawImage(this.workCanvas, 0, 0);
            this.history.push(snap);
            if (this.history.length > this.maxHistory) this.history.shift();
            this.updateUndoButton();
        },

        undo() {
            if (!this.history.length || !this.workCanvas) return;
            const snap = this.history.pop();
            const ctx = this.workCanvas.getContext('2d');
            ctx.clearRect(0, 0, this.workCanvas.width, this.workCanvas.height);
            ctx.drawImage(snap, 0, 0);
            this.render();
            this.updateUndoButton();
        },

        updateUndoButton() {
            if (this.btnUndo) this.btnUndo.disabled = this.history.length === 0;
        },

        // ------------------------------------------------------------------
        // Tool state
        // ------------------------------------------------------------------
        setTool(tool) {
            this.currentTool = tool;
            if (this.rangeSize) this.rangeSize.value = tool === 'smart_erase' ? this.tolerance : this.brushSize;
            [this.toolEraser, this.toolRestore, this.toolSmart].forEach(b => b && b.classList.remove('active'));
            if (tool === 'eraser' && this.toolEraser) this.toolEraser.classList.add('active');
            if (tool === 'restore' && this.toolRestore) this.toolRestore.classList.add('active');
            if (tool === 'smart_erase' && this.toolSmart) this.toolSmart.classList.add('active');
            this.updateSizeLabel();
            this.updateCursorStyle();
        },

        setSmartMode(on) {
            this.smartMode = !!on;
            if (this.toggleSmart) {
                this.toggleSmart.classList.toggle('active', this.smartMode);
                this.toggleSmart.setAttribute('aria-pressed', this.smartMode ? 'true' : 'false');
            }
        },

        setGhost(on) {
            this.showGhost = !!on;
            if (this.toggleGhost) {
                this.toggleGhost.classList.toggle('active', this.showGhost);
                this.toggleGhost.setAttribute('aria-pressed', this.showGhost ? 'true' : 'false');
            }
            if (this.workCanvas) this.render();
        },

        updateSizeLabel() {
            if (!this.rangeLabel) return;
            this.rangeLabel.textContent = this.currentTool === 'smart_erase' ? ('±' + this.tolerance) : (this.brushSize + 'px');
        },

        updateCursorStyle() {
            if (!this.wrapper) return;
            if (this.spaceDown || this.isPanning) this.wrapper.style.cursor = this.isPanning ? 'grabbing' : 'grab';
            else this.wrapper.style.cursor = this.currentTool === 'smart_erase' ? 'crosshair' : 'none';
        },

        onKeyDown(e) {
            if (!this.isActive) return;
            const tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;
            const k = e.key;
            if (e.code === 'Space') { this.spaceDown = true; this.updateCursorStyle(); e.preventDefault(); return; }
            if ((e.ctrlKey || e.metaKey) && k.toLowerCase() === 'z') { this.undo(); e.preventDefault(); return; }
            if (k === '[') { this.changeBrush(-1); e.preventDefault(); }
            else if (k === ']') { this.changeBrush(1); e.preventDefault(); }
            else if (k === '+' || k === '=') { this.zoomBy(1.25); e.preventDefault(); }
            else if (k === '-') { this.zoomBy(0.8); e.preventDefault(); }
            else if (k === '0') { this.resetZoom(); e.preventDefault(); }
            else if (k.toLowerCase() === 'e') this.setTool('eraser');
            else if (k.toLowerCase() === 'r') this.setTool('restore');
            else if (k.toLowerCase() === 'w') this.setTool('smart_erase');
            else if (k.toLowerCase() === 's') this.setSmartMode(!this.smartMode);
            else if (k.toLowerCase() === 'g' && this.hasGhost) this.setGhost(!this.showGhost);
        },

        changeBrush(dir) {
            if (this.currentTool === 'smart_erase') {
                this.tolerance = Math.max(1, Math.min(100, this.tolerance + dir * 5));
                if (this.rangeSize) this.rangeSize.value = this.tolerance;
            } else {
                const step = Math.max(2, Math.round(this.brushSize * 0.15));
                const max = this.rangeSize ? parseInt(this.rangeSize.max, 10) : 300;
                this.brushSize = Math.max(3, Math.min(max, this.brushSize + dir * step));
                if (this.rangeSize) this.rangeSize.value = this.brushSize;
            }
            this.updateSizeLabel();
        },

        // ------------------------------------------------------------------
        // Rendering: faint grey ghost of the un-cut photo, cutout on top
        // ------------------------------------------------------------------
        render() {
            if (!this.workCanvas) return;
            const c = this.ctx;
            c.clearRect(0, 0, this.displayCanvas.width, this.displayCanvas.height);
            if (this.showGhost && this.hasGhost && this.refCanvas) {
                c.save();
                c.globalAlpha = 0.35;
                try { c.filter = 'grayscale(1)'; } catch (e) { /* older browsers: coloured ghost */ }
                c.drawImage(this.refCanvas, 0, 0);
                c.restore();
            }
            c.drawImage(this.workCanvas, 0, 0);
        },

        scheduleRender() {
            if (this._renderQueued) return;
            this._renderQueued = true;
            requestAnimationFrame(() => { this._renderQueued = false; this.render(); });
        },

        // ------------------------------------------------------------------
        // Zoom / pan
        // ------------------------------------------------------------------
        fitScale() {
            const w = this.wrapper ? this.wrapper.clientWidth - 16 : this.displayCanvas.width;
            const h = this.wrapper ? this.wrapper.clientHeight - 16 : this.displayCanvas.height;
            return Math.min(w / this.displayCanvas.width, h / this.displayCanvas.height);
        },

        applyZoom() {
            if (!this.displayCanvas) return;
            const s = this.fitScale() * this.zoomLevel;
            this.displayCanvas.style.width = (this.displayCanvas.width * s) + 'px';
            this.displayCanvas.style.height = (this.displayCanvas.height * s) + 'px';
            this.displayCanvas.style.margin = '8px auto';
            const pct = Math.round(this.zoomLevel * 100) + '%';
            const indicator = document.getElementById('refineZoomIndicator');
            if (indicator) indicator.textContent = pct;
            const btnLabel = document.getElementById('refineZoomBtnLabel');
            if (btnLabel) btnLabel.textContent = pct;
        },

        // Zoom by a factor, keeping the point under the cursor (or the view centre) still.
        zoomBy(factor, evt) {
            if (!this.wrapper || !this.displayCanvas) return;
            const newZoom = Math.max(0.25, Math.min(8, this.zoomLevel * factor));
            if (newZoom === this.zoomLevel) return;
            const wr = this.wrapper.getBoundingClientRect();
            const before = this.displayCanvas.getBoundingClientRect();
            const px = evt ? evt.clientX : wr.left + wr.width / 2;
            const py = evt ? evt.clientY : wr.top + wr.height / 2;
            const fx = (px - before.left) / before.width;
            const fy = (py - before.top) / before.height;
            this.zoomLevel = newZoom;
            this.applyZoom();
            const after = this.displayCanvas.getBoundingClientRect();
            this.wrapper.scrollLeft += (after.left + fx * after.width) - px;
            this.wrapper.scrollTop += (after.top + fy * after.height) - py;
        },

        resetZoom() {
            this.zoomLevel = 1;
            this.applyZoom();
            if (this.wrapper) { this.wrapper.scrollTop = 0; this.wrapper.scrollLeft = 0; }
        },

        // ------------------------------------------------------------------
        // Pointer input
        // ------------------------------------------------------------------
        getMousePos(evt) {
            const rect = this.displayCanvas.getBoundingClientRect();
            return {
                x: (evt.clientX - rect.left) * (this.displayCanvas.width / rect.width),
                y: (evt.clientY - rect.top) * (this.displayCanvas.height / rect.height),
            };
        },

        onPointerDown(e) {
            if (!this.isActive || !this.workCanvas) return;
            // Pan: Space+drag, middle button, or right button
            if (this.spaceDown || e.button === 1 || e.button === 2) {
                this.isPanning = true;
                this.panStart = { x: e.clientX, y: e.clientY, left: this.wrapper.scrollLeft, top: this.wrapper.scrollTop };
                this.updateCursorStyle();
                e.preventDefault();
                return;
            }
            if (e.button !== 0) return;
            this.isDrawing = true;
            this.lastPos = this.getMousePos(e);
            this.pushHistory();
            if (this.currentTool === 'smart_erase') {
                this.applyMagicWand(this.lastPos);
            } else {
                this.dab(this.lastPos);
                this.scheduleRender();
            }
        },

        onPointerMove(e) {
            if (!this.isActive) return;
            if (this.isPanning && this.panStart) {
                this.wrapper.scrollLeft = this.panStart.left - (e.clientX - this.panStart.x);
                this.wrapper.scrollTop = this.panStart.top - (e.clientY - this.panStart.y);
                return;
            }
            if (!this.isDrawing || this.currentTool === 'smart_erase') return;
            const pos = this.getMousePos(e);
            this.strokeTo(this.lastPos, pos);
            this.lastPos = pos;
            this.scheduleRender();
        },

        onPointerUp() {
            if (this.isPanning) { this.isPanning = false; this.panStart = null; this.updateCursorStyle(); }
            this.isDrawing = false;
        },

        updateBrushCursor(e) {
            if (!this.brushCursor) return;
            if (this.currentTool === 'smart_erase' || this.spaceDown || this.isPanning) {
                this.brushCursor.style.display = 'none';
                return;
            }
            const rect = this.displayCanvas.getBoundingClientRect();
            const d = this.brushSize * (rect.width / this.displayCanvas.width);
            const st = this.brushCursor.style;
            st.display = 'block';
            st.width = st.height = d + 'px';
            st.left = (e.clientX - d / 2) + 'px';
            st.top = (e.clientY - d / 2) + 'px';
            st.borderColor = this.currentTool === 'restore' ? '#16a34a' : '#dc2626';
            st.borderStyle = this.smartMode ? 'dashed' : 'solid';
        },

        // ------------------------------------------------------------------
        // Brushes
        // ------------------------------------------------------------------
        // Walk from a to b placing dabs close enough to look continuous.
        strokeTo(a, b) {
            if (!this.smartMode) { this.drawLine(a, b); return; }
            const dist = Math.hypot(b.x - a.x, b.y - a.y);
            const step = Math.max(1.5, this.brushSize * 0.25);
            const n = Math.max(1, Math.ceil(dist / step));
            for (let i = 1; i <= n; i++) {
                this.dab({ x: a.x + (b.x - a.x) * i / n, y: a.y + (b.y - a.y) * i / n });
            }
        },

        dab(pos) {
            if (this.smartMode) { this.smartDab(pos); return; }
            const ctx = this.workCanvas.getContext('2d');
            const r = this.brushSize / 2;
            if (this.currentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath(); ctx.arc(pos.x, pos.y, r, 0, Math.PI * 2); ctx.fill();
                ctx.globalCompositeOperation = 'source-over';
            } else if (this.currentTool === 'restore') {
                ctx.save();
                ctx.beginPath(); ctx.arc(pos.x, pos.y, r, 0, Math.PI * 2); ctx.clip();
                ctx.drawImage(this.refCanvas, 0, 0);
                ctx.restore();
            }
        },

        drawLine(a, b) {
            const ctx = this.workCanvas.getContext('2d');
            if (this.currentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.lineWidth = this.brushSize;
                ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
                ctx.globalCompositeOperation = 'source-over';
            } else if (this.currentTool === 'restore') {
                const t = this.tempCanvas.getContext('2d');
                t.clearRect(0, 0, this.tempCanvas.width, this.tempCanvas.height);
                t.globalCompositeOperation = 'source-over';
                t.lineCap = 'round'; t.lineJoin = 'round'; t.lineWidth = this.brushSize; t.strokeStyle = '#fff';
                t.beginPath(); t.moveTo(a.x, a.y); t.lineTo(b.x, b.y); t.stroke();
                t.globalCompositeOperation = 'source-in';
                t.drawImage(this.refCanvas, 0, 0);
                t.globalCompositeOperation = 'source-over';
                ctx.drawImage(this.tempCanvas, 0, 0);
            }
        },

        // Edge-aware dab: take the colour under the brush centre (from the
        // un-cut photo), flood-fill the connected pixels of similar colour that
        // lie inside the brush circle, and erase/restore only those. Where the
        // colour changes (the edge of a shoulder, hair, clothes) the fill stops,
        // so the brush can overlap the person without damaging them.
        smartDab(pos) {
            const W = this.workCanvas.width, H = this.workCanvas.height;
            const r = Math.max(2, this.brushSize / 2);
            const cx = Math.round(pos.x), cy = Math.round(pos.y);
            if (cx < 0 || cy < 0 || cx >= W || cy >= H) return;
            const x0 = Math.max(0, Math.floor(cx - r)), y0 = Math.max(0, Math.floor(cy - r));
            const x1 = Math.min(W - 1, Math.ceil(cx + r)), y1 = Math.min(H - 1, Math.ceil(cy + r));
            const bw = x1 - x0 + 1, bh = y1 - y0 + 1;
            const ref = this.refData.data;

            // Sample colour: average of a 3x3 at the centre
            let sr = 0, sg = 0, sb = 0, sn = 0;
            for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) {
                const x = cx + dx, y = cy + dy;
                if (x < 0 || y < 0 || x >= W || y >= H) continue;
                const i = (y * W + x) * 4;
                sr += ref[i]; sg += ref[i + 1]; sb += ref[i + 2]; sn++;
            }
            sr /= sn; sg /= sn; sb /= sn;

            const T = 12 + this.smartTolerance * 1.3;   // colour distance threshold
            const hard = T * 0.65;                        // fully affected below this
            const r2 = r * r;

            const ctx = this.workCanvas.getContext('2d', { willReadFrequently: true });
            const img = ctx.getImageData(x0, y0, bw, bh);
            const px = img.data;
            const visited = new Uint8Array(bw * bh);
            const stack = [[cx - x0, cy - y0]];
            let changed = false;

            while (stack.length) {
                const [lx, ly] = stack.pop();
                if (lx < 0 || ly < 0 || lx >= bw || ly >= bh) continue;
                const li = ly * bw + lx;
                if (visited[li]) continue;
                visited[li] = 1;
                const gx = lx + x0, gy = ly + y0;
                const ddx = gx - cx, ddy = gy - cy;
                if (ddx * ddx + ddy * ddy > r2) continue;
                const ri = (gy * W + gx) * 4;
                const dist = Math.sqrt((ref[ri] - sr) ** 2 + (ref[ri + 1] - sg) ** 2 + (ref[ri + 2] - sb) ** 2);
                if (dist > T) continue; // edge reached — do not cross it

                // Soft falloff near the colour threshold keeps edges natural
                const strength = dist <= hard ? 1 : 1 - (dist - hard) / (T - hard);
                const pi = li * 4;
                if (this.currentTool === 'eraser') {
                    const a = px[pi + 3];
                    const na = Math.round(a * (1 - strength));
                    if (na !== a) { px[pi + 3] = na; changed = true; }
                } else if (this.currentTool === 'restore') {
                    const target = Math.round(255 * strength);
                    if (target > px[pi + 3]) {
                        px[pi] = ref[ri]; px[pi + 1] = ref[ri + 1]; px[pi + 2] = ref[ri + 2];
                        px[pi + 3] = target;
                        changed = true;
                    }
                }
                stack.push([lx + 1, ly], [lx - 1, ly], [lx, ly + 1], [lx, ly - 1]);
            }
            if (changed) ctx.putImageData(img, x0, y0);
        },

        // --- Magic wand: click an area of similar colour to erase it all ---
        applyMagicWand(pos) {
            this.toggleLoading(true, 'Analyzing image...');
            setTimeout(() => {
                try {
                    const width = this.workCanvas.width, height = this.workCanvas.height;
                    const ctx = this.workCanvas.getContext('2d', { willReadFrequently: true });
                    const x = Math.floor(pos.x), y = Math.floor(pos.y);
                    if (x < 0 || x >= width || y < 0 || y >= height) return;

                    const imageData = ctx.getImageData(0, 0, width, height);
                    const data = imageData.data;
                    const tp = (y * width + x) * 4;
                    if (data[tp + 3] === 0) return; // already transparent
                    const tR = data[tp], tG = data[tp + 1], tB = data[tp + 2];
                    const maxDist = Math.pow((this.tolerance / 100) * 80, 2) * 3;
                    const match = (p) => data[p + 3] !== 0
                        && ((data[p] - tR) ** 2 + (data[p + 1] - tG) ** 2 + (data[p + 2] - tB) ** 2) <= maxDist;

                    const visited = new Uint8Array(width * height);
                    const stack = [[x, y]];
                    const maxPixels = width * height * 0.4;
                    let filled = 0;
                    while (stack.length && filled < maxPixels) {
                        const [px, py] = stack.pop();
                        if (px < 0 || py < 0 || px >= width || py >= height) continue;
                        const vi = py * width + px;
                        if (visited[vi]) continue;
                        visited[vi] = 1;
                        const p = vi * 4;
                        if (!match(p)) continue;
                        data[p + 3] = 0;
                        filled++;
                        stack.push([px + 1, py], [px - 1, py], [px, py + 1], [px, py - 1]);
                    }
                    ctx.putImageData(imageData, 0, 0);
                    this.render();
                } catch (err) {
                    console.error('Magic Wand Error:', err);
                    alert('Magic Wand failed: ' + err.message);
                } finally {
                    this.toggleLoading(false);
                }
            }, 10);
        },

        // ------------------------------------------------------------------
        // Magnifier (shows the result + ghost around the cursor, 3x)
        // ------------------------------------------------------------------
        updateMagnifier(evt) {
            const mag = document.getElementById('refineMagnifier');
            const magCanvas = document.getElementById('refineMagnifierCanvas');
            if (!mag || !magCanvas || !this.workCanvas || this.isPanning) return;
            const rect = this.displayCanvas.getBoundingClientRect();
            if (evt.clientX < rect.left || evt.clientX > rect.right || evt.clientY < rect.top || evt.clientY > rect.bottom) {
                mag.style.display = 'none';
                return;
            }
            const pos = this.getMousePos(evt);
            const m = magCanvas.getContext('2d');
            const size = 150, zoom = 3, src = size / zoom;
            m.fillStyle = '#fff'; m.fillRect(0, 0, size, size);
            for (let i = 0; i < size; i += 10) for (let j = 0; j < size; j += 10) {
                if ((i + j) % 20 === 0) { m.fillStyle = '#e5e7eb'; m.fillRect(i, j, 10, 10); }
            }
            m.drawImage(this.displayCanvas, pos.x - src / 2, pos.y - src / 2, src, src, 0, 0, size, size);
            m.strokeStyle = '#FF6600'; m.lineWidth = 2;
            m.beginPath(); m.moveTo(size / 2, 0); m.lineTo(size / 2, size); m.moveTo(0, size / 2); m.lineTo(size, size / 2); m.stroke();
            if (this.currentTool !== 'smart_erase') {
                m.beginPath(); m.arc(size / 2, size / 2, (this.brushSize / 2) * zoom, 0, Math.PI * 2); m.stroke();
            }
            mag.style.display = 'block';
            mag.style.left = (evt.clientX + 24) + 'px';
            mag.style.top = (evt.clientY - 174) + 'px';
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
            if (overlay) overlay.classList.toggle('d-none', !show);
            if (txt && text) txt.textContent = text;
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
