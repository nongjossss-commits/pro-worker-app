<script>
    // --- Global Cropper State & Logic ---
    // This ensures we only attach listeners to the global modal ONCE.
    window.cropperManager = window.cropperManager || {
        initialized: false,
        instance: null,
        originalFile: null,
        mimeType: null,
        targetInputId: null,
        targetPreviewId: null
    };

    window.openCropperWithUrl = async function(url, targetInputId, targetPreviewId) {
        window.initCropperGlobal();

        const imageToCrop = document.getElementById('imageToCrop');
        const cropperModalEl = document.getElementById('cropperModal');

        if (!imageToCrop || !cropperModalEl) {
             console.error('Cropper elements not found');
             return;
        }

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok');
            const blob = await response.blob();

            let extension = 'jpg';
            if (blob.type === 'image/png') extension = 'png';
            else if (blob.type === 'image/webp') extension = 'webp';

            const file = new File([blob], `existing-image.${extension}`, { type: blob.type });

            window.cropperManager.originalFile = file;
            window.cropperManager.mimeType = blob.type;
            window.cropperManager.targetInputId = targetInputId;
            window.cropperManager.targetPreviewId = targetPreviewId;

            imageToCrop.src = URL.createObjectURL(blob);

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

        window.cropperManager.initialized = true;

        const imageToCrop = document.getElementById('imageToCrop');
        const cropImageBtn = document.getElementById('cropImageBtn');
        let cropperModal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);

        function initCropperInstance() {
            if (typeof Cropper === 'undefined') {
                // Determine if we should alert (maybe library is loading async)
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
            }
        }

        // --- Background Removal Logic ---
        const bgToolbar = document.getElementById('bgToolbar');
        const loadingOverlay = document.getElementById('cropperLoadingOverlay');
        const loadingText = document.getElementById('cropperLoadingText');

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

                try {
                    if (loadingOverlay) loadingOverlay.classList.remove('d-none');
                    if (loadingText) loadingText.textContent = 'Processing...';

                    if (!window.backgroundRemoval) {
                        throw new Error('Background removal library not loaded.');
                    }

                    const processedBlob = await window.backgroundRemoval.process(originalFile, action, (active, text) => {
                        if (loadingText && text) loadingText.textContent = text;
                    });

                    if (action === 'transparent') {
                        window.cropperManager.mimeType = 'image/png';
                    } else {
                        window.cropperManager.mimeType = 'image/jpeg';
                    }

                    const newUrl = URL.createObjectURL(processedBlob);
                    imageToCrop.src = newUrl;

                    if (window.cropperManager.instance) window.cropperManager.instance.destroy();
                    initCropperInstance();

                } catch (err) {
                    console.error(err);
                    alert('Failed to process image: ' + err.message);
                } finally {
                    if (loadingOverlay) loadingOverlay.classList.add('d-none');
                }
            });
        }

        cropperModalEl.addEventListener('shown.bs.modal', function () {
            if (cropImageBtn) cropImageBtn.disabled = true;

            if (window.cropperManager.instance) {
                window.cropperManager.instance.destroy();
                window.cropperManager.instance = null;
            }

            if (imageToCrop.complete) {
                initCropperInstance();
            } else {
                imageToCrop.onload = function() {
                    initCropperInstance();
                };
            }
        });

        cropperModalEl.addEventListener('hidden.bs.modal', function () {
            if (window.cropperManager.instance) {
                window.cropperManager.instance.destroy();
                window.cropperManager.instance = null;
            }
            imageToCrop.src = '';
        });

        cropImageBtn.addEventListener('click', function () {
            const cropper = window.cropperManager.instance;
            const originalFile = window.cropperManager.originalFile;
            const targetInputId = window.cropperManager.targetInputId;
            const targetPreviewId = window.cropperManager.targetPreviewId;

            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 360,
                minWidth: 200,
                minHeight: 200,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) return;

            let outputType = window.cropperManager.mimeType;
            if (!outputType) {
                outputType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
            }

            canvas.toBlob(function (blob) {
                if (!blob) return;

                const croppedImageUrl = URL.createObjectURL(blob);

                if (targetPreviewId) {
                    const employeePhotoPreview = document.getElementById(targetPreviewId);
                    if(employeePhotoPreview) employeePhotoPreview.src = croppedImageUrl;
                }

                let finalName = originalFile ? originalFile.name : 'cropped-image.jpg';
                if (outputType === 'image/png' && !finalName.toLowerCase().endsWith('.png')) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".png";
                } else if (outputType === 'image/jpeg' && !finalName.toLowerCase().match(/\.(jpg|jpeg)$/)) {
                    finalName = finalName.replace(/\.[^/.]+$/, "") + ".jpg";
                }

                const croppedFile = new File([blob], finalName, {
                    type: outputType,
                    lastModified: Date.now()
                });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);

                if (targetInputId) {
                    const actualInput = document.getElementById(targetInputId);
                    if(actualInput) {
                        actualInput.files = dataTransfer.files;
                    }
                }

                cropperModal.hide();

            }, outputType);
        });
    };

    // --- Unified Form Initialization ---
    window.initEmployeeForm = function(prefix = '') {
        window.initCropperGlobal();

        const titleTh = document.getElementById(prefix + 'employeeTitleTh');
        const titleEn = document.getElementById(prefix + 'employeeTitleEn');
        const genderInput = document.getElementById(prefix + 'employeeGender');
        const dobInput = document.getElementById(prefix + 'employeeDob');
        const ageInput = document.getElementById(prefix + 'employeeAge');
        const nationalitySelect = document.getElementById(prefix + 'employeeNationality');
        const mouGroupSelect = document.getElementById(prefix + 'workPermitMOUGroup');
        const insuranceSelect = document.getElementById(prefix + 'insurance_type');

        const triggerFileInput = document.getElementById(prefix + 'triggerFile');
        const triggerCameraInput = document.getElementById(prefix + 'triggerCamera');
        const imageToCrop = document.getElementById('imageToCrop');
        const cropperModalEl = document.getElementById('cropperModal');

        function handleFileSelect(event) {
            if (event.target.files && event.target.files.length > 0) {
                window.cropperManager.originalFile = event.target.files[0];
                window.cropperManager.mimeType = event.target.files[0].type;
                window.cropperManager.targetInputId = prefix + 'employeePhotoInput';
                window.cropperManager.targetPreviewId = prefix + 'employeePhotoPreview';
            } else {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if(imageToCrop) {
                    imageToCrop.src = e.target.result;
                    const modal = bootstrap.Modal.getOrCreateInstance(cropperModalEl);
                    modal.show();
                }
            };
            reader.readAsDataURL(window.cropperManager.originalFile);
            event.target.value = '';
        }

        if (triggerFileInput) {
             const newTrigger = triggerFileInput.cloneNode(true);
             triggerFileInput.parentNode.replaceChild(newTrigger, triggerFileInput);
             newTrigger.addEventListener('change', handleFileSelect);
        }
        if (triggerCameraInput) {
             const newTrigger = triggerCameraInput.cloneNode(true);
             triggerCameraInput.parentNode.replaceChild(newTrigger, triggerCameraInput);
             newTrigger.addEventListener('change', handleFileSelect);
        }

        const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
        const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

        function syncTitles(source) {
            if (!titleTh || !titleEn) return;
            if (source === 'th') {
                const selectedTh = titleTh.value;
                if (thToEnMap[selectedTh]) {
                    titleEn.value = thToEnMap[selectedTh];
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
        }

        // Helper to safely attach listener only once
        function attachOnce(element, event, handler) {
            if (!element) return;
            // Since we can't easily detect anonymous listeners, we trust the caller (initEmployeeForm)
            // isn't called repeatedly on the same DOM elements without replacement.
            // But just in case, we can use a property check if needed.
            // For now, standard addEventListener.
            // Note: If elements are replaced via AJAX, this is fine.
            // If init is called twice on static page, we might duplicate.
            // Cloning node to strip listeners is a heavy hammer but effective.

            // Actually, for dropdowns, cloning might break other plugins (like select2 if used).
            // This project seems to use standard Bootstrap.
            // Let's use the clone approach for critical logic triggers to be safe.
            // OR check a data attribute.
            if (element.dataset['has_' + event]) return;
            element.addEventListener(event, handler);
            element.dataset['has_' + event] = 'true';
        }

        if(titleTh) {
            attachOnce(titleTh, 'change', () => syncTitles('th'));
            if(titleTh.value) updateGender();
        }
        if(titleEn) {
            attachOnce(titleEn, 'change', () => syncTitles('en'));
        }

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
            attachOnce(dobInput, 'change', calculateAge);
            if(dobInput.value) calculateAge();
        }

        const myanmarPassportContainer = document.getElementById(prefix + 'passportTypeContainer');
        const cambodiaPassportContainer = document.getElementById(prefix + 'passportTypeCambodiaContainer');

        function toggleNationalityFields() {
            if (!nationalitySelect) return;
            if(myanmarPassportContainer) myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
            if(cambodiaPassportContainer) cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
        }
        if(nationalitySelect) {
            attachOnce(nationalitySelect, 'change', toggleNationalityFields);
            toggleNationalityFields();
        }

        const mouGroupOtherContainer = document.getElementById(prefix + 'workPermitMOUGroupOtherContainer');
        function toggleMouGroupOther() {
            if (!mouGroupSelect || !mouGroupOtherContainer) return;
            mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
        }
        if(mouGroupSelect) {
            attachOnce(mouGroupSelect, 'change', toggleMouGroupOther);
            toggleMouGroupOther();
        }

        const socialContainer = document.getElementById(prefix + 'insuranceSocialSecurity');
        const hospitalContainer = document.getElementById(prefix + 'insuranceHospital');
        const privateContainer = document.getElementById(prefix + 'insurancePrivate');
        function toggleInsuranceVisibility() {
            if (!insuranceSelect) return;
            const selectedType = insuranceSelect.value;
            if(socialContainer) socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
            if(hospitalContainer) hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
            if(privateContainer) privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
        }
        if(insuranceSelect) {
            attachOnce(insuranceSelect, 'change', toggleInsuranceVisibility);
            toggleInsuranceVisibility();
        }

        // Cancel Button (Edit specific logic, but harmless to include)
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
                     // If purely browser history
                     history.back();
                 }
             }
        }
    };

    // Backward Compatibility Alias
    window.initEmployeeEditForm = function() {
        window.initEmployeeForm('edit_');
    }
</script>
