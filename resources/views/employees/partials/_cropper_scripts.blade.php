<!-- resources/views/employees/partials/_cropper_scripts.blade.php -->
<script src="https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.3.0/dist/imgly-background-removal.min.js"></script>
<script>
    // --- Global Cropper Manager ---
    window.cropperManager = {
        initialized: false,
        instance: null,
        originalFile: null,      // The file object (if from input)
        originalUrl: null,       // The URL (if from edit existing)
        currentBlob: null,       // The current blob being cropped (could be original or bg-removed)
        targetInputId: null,
        targetPreviewId: null,

        // --- Core Functions ---

        init: function() {
            if (this.initialized) return;

            const cropperModalEl = document.getElementById('cropperModal');
            if (!cropperModalEl) return;

            this.initialized = true;
            const self = this;

            const imageToCrop = document.getElementById('imageToCrop');
            const cropImageBtn = document.getElementById('cropImageBtn');
            const cropperModal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);

            // Event: Modal Shown
            cropperModalEl.addEventListener('shown.bs.modal', function () {
                if (cropImageBtn) cropImageBtn.disabled = true;

                // Destroy existing to be safe
                if (self.instance) {
                    self.instance.destroy();
                    self.instance = null;
                }

                // Ensure image is loaded
                const startCropper = () => self.createCropperInstance();

                if (imageToCrop.complete) {
                    startCropper();
                } else {
                    imageToCrop.onload = startCropper;
                }
            });

            // Event: Modal Hidden
            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (self.instance) {
                    self.instance.destroy();
                    self.instance = null;
                }
                imageToCrop.src = '';
                self.resetLoading();
            });

            // Event: Save Button
            cropImageBtn.addEventListener('click', function () {
                self.save();
            });
        },

        createCropperInstance: function() {
            const imageToCrop = document.getElementById('imageToCrop');
            const cropImageBtn = document.getElementById('cropImageBtn');

            if (typeof Cropper === 'undefined') {
                alert('ไม่สามารถโหลดเครื่องมือตัดภาพได้ (Cropper.js) กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต');
                return;
            }

            try {
                this.instance = new Cropper(imageToCrop, {
                    aspectRatio: 150 / 180, // Employee Photo Ratio
                    viewMode: 1,
                    dragMode: 'move',
                    background: false, // Transparent BG in viewer
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
        },

        // --- Public API ---

        openWithFile: function(file, targetInputId, targetPreviewId) {
            this.originalFile = file;
            this.targetInputId = targetInputId;
            this.targetPreviewId = targetPreviewId;
            this.currentBlob = file; // Start with the file

            const reader = new FileReader();
            reader.onload = (e) => {
                this._openModal(e.target.result);
            };
            reader.readAsDataURL(file);
        },

        openWithUrl: function(url, targetInputId, targetPreviewId) {
            this.originalUrl = url;
            this.originalFile = null; // Clear file since we are editing a URL
            this.targetInputId = targetInputId;
            this.targetPreviewId = targetPreviewId;

            // Load URL into blob to allow processing (CORS might be an issue if not same origin, but storage usually is)
            // For simple display, just URL is fine. For processing, we fetch it.
            this._openModal(url);
        },

        _openModal: function(src) {
            const imageToCrop = document.getElementById('imageToCrop');
            const cropperModalEl = document.getElementById('cropperModal');
            if(imageToCrop && cropperModalEl) {
                imageToCrop.src = src;
                const modal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);
                modal.show();
            }
        },

        save: function() {
            if (!this.instance) return;

            // Export options
            // If background was removed (transparent), we want PNG.
            // If not, JPEG is smaller.
            // We can infer based on whether we processed it, but safer to default to PNG if possible, or JPEG if file was JPEG.
            // However, if user chose "Transparent", we MUST use PNG.
            // If user chose "White/Blue", JPEG is fine.
            let mimeType = 'image/jpeg';
            if (this.currentBlob && this.currentBlob.type === 'image/png') mimeType = 'image/png';

            const canvas = this.instance.getCroppedCanvas({
                width: 300,
                height: 360,
                minWidth: 200,
                minHeight: 200,
                imageSmoothingQuality: 'high',
                fillColor: mimeType === 'image/jpeg' ? '#ffffff' : 'transparent', // Default fill for JPEG
            });

            if (!canvas) {
                alert('Canvas creation failed');
                return;
            }

            const self = this;
            canvas.toBlob(function (blob) {
                if (!blob) return;
                self._finish(blob);
            }, mimeType, 0.95);
        },

        _finish: function(blob) {
            const croppedImageUrl = URL.createObjectURL(blob);
            const cropperModalEl = document.getElementById('cropperModal');
            const modal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);

            // 1. Update Preview
            if (this.targetPreviewId) {
                const preview = document.getElementById(this.targetPreviewId);
                if(preview) preview.src = croppedImageUrl;
            }

            // 2. Update Input
            // We need a filename.
            let fileName = 'cropped.jpg';
            let fileType = blob.type;
            if (this.originalFile) {
                fileName = this.originalFile.name;
                // If we changed type to PNG, update extension
                if (fileType === 'image/png' && !fileName.endsWith('.png')) {
                    fileName = fileName.replace(/\.[^/.]+$/, "") + ".png";
                }
            } else {
                 if (fileType === 'image/png') fileName = 'edited_photo.png';
            }

            const newFile = new File([blob], fileName, { type: fileType, lastModified: Date.now() });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(newFile);

            if (this.targetInputId) {
                const input = document.getElementById(this.targetInputId);
                if(input) {
                    input.files = dataTransfer.files;
                    // Dispatch change event so other scripts know
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            modal.hide();
        },

        // --- AI Background Removal ---

        removeBackground: async function(color) {
            if (typeof imglyRemoveBackground === 'undefined') {
                alert('ระบบ AI ยังไม่พร้อมใช้งาน กรุณารอสักครู่แล้วลองใหม่');
                return;
            }

            this.showLoading();

            try {
                // 1. Get source for AI
                // If we have currentBlob (from file), use it.
                // If we have imageToCrop.src (from URL), use it.
                const imageToCrop = document.getElementById('imageToCrop');
                let source = this.currentBlob ? this.currentBlob : imageToCrop.src;

                // 2. Run AI
                // config: { debug: true, progress: (key, current, total) => { ... } }
                const transparentBlob = await imglyRemoveBackground(source);

                // 3. Apply Background Color
                let finalBlob = transparentBlob;

                if (color === 'white') {
                    finalBlob = await this._compositeBackground(transparentBlob, '#ffffff', 'image/jpeg');
                } else if (color === 'blue') {
                    // Light blue color suitable for ID photos
                    finalBlob = await this._compositeBackground(transparentBlob, '#cce5ff', 'image/jpeg');
                } else {
                    // Transparent - Force PNG
                    finalBlob = transparentBlob; // Already PNG
                }

                // 4. Update Cropper
                this.currentBlob = finalBlob; // Update state
                const newUrl = URL.createObjectURL(finalBlob);

                // Replace image in cropper
                if (this.instance) {
                    this.instance.replace(newUrl);
                } else {
                    imageToCrop.src = newUrl;
                    this.createCropperInstance();
                }

            } catch (error) {
                console.error('AI Error:', error);
                alert('เกิดข้อผิดพลาดในการลบพื้นหลัง: ' + error.message);
            } finally {
                this.hideLoading();
            }
        },

        _compositeBackground: function(blob, colorHex, type) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');

                    // Draw BG
                    ctx.fillStyle = colorHex;
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    // Draw FG
                    ctx.drawImage(img, 0, 0);

                    canvas.toBlob(resolve, type, 0.95);
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(blob);
            });
        },

        showLoading: function() {
            const overlay = document.getElementById('cropperLoading');
            if(overlay) overlay.classList.remove('d-none', 'd-flex');
            if(overlay) overlay.classList.add('d-flex');
        },

        hideLoading: function() {
            const overlay = document.getElementById('cropperLoading');
            if(overlay) overlay.classList.add('d-none');
            if(overlay) overlay.classList.remove('d-flex');
        },

        resetLoading: function() {
             this.hideLoading();
        }
    };

    // Auto-init on load
    document.addEventListener('DOMContentLoaded', function() {
        window.cropperManager.init();
    });
</script>
