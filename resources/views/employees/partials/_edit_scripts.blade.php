<script>
    // Global state for the active cropping session
    window.cropperState = {
        cropper: null,
        originalFile: null,
        targetInput: null,
        targetPreview: null
    };

    document.addEventListener('DOMContentLoaded', function() {
        // --- One-time setup for Cropper Modal events ---
        const cropperModalEl = document.getElementById('cropperModal');
        const imageToCrop = document.getElementById('imageToCrop');
        const cropImageBtn = document.getElementById('cropImageBtn');

        if (cropperModalEl && imageToCrop && cropImageBtn) {
            const cropperModal = new bootstrap.Modal(cropperModalEl);

            // Initialize Cropper when modal is shown
            cropperModalEl.addEventListener('shown.bs.modal', function() {
                // Destroy previous instance if exists
                if (window.cropperState.cropper) {
                    window.cropperState.cropper.destroy();
                }

                // Ensure image is loaded
                if (imageToCrop.complete) {
                    initCropperInstance();
                } else {
                    imageToCrop.onload = initCropperInstance;
                }
            });

            function initCropperInstance() {
                try {
                    window.cropperState.cropper = new Cropper(imageToCrop, {
                        aspectRatio: 150 / 180,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.8,
                        movable: true,
                        zoomable: true,
                        rotatable: true,
                        scalable: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                    });
                } catch (err) {
                    console.error('Cropper init error:', err);
                    alert('ไม่สามารถโหลดเครื่องมือตัดภาพได้');
                }
            }

            // Clean up when modal is hidden
            cropperModalEl.addEventListener('hidden.bs.modal', function() {
                if (window.cropperState.cropper) {
                    window.cropperState.cropper.destroy();
                    window.cropperState.cropper = null;
                }
                imageToCrop.src = '';
                // Clear state
                window.cropperState.originalFile = null;
            });

            // Handle "Crop and Save" click
            cropImageBtn.addEventListener('click', function() {
                const state = window.cropperState;
                if (!state.cropper) {
                    alert('กรุณารอให้เครื่องมือตัดภาพทำงาน หรือลองเลือกไฟล์ใหม่');
                    return;
                }

                const canvas = state.cropper.getCroppedCanvas({
                    width: 300,
                    height: 360,
                    imageSmoothingQuality: 'high',
                });

                if (!canvas) {
                    alert('เกิดข้อผิดพลาดในการตัดภาพ');
                    return;
                }

                canvas.toBlob(function(blob) {
                    if (!blob) return;

                    const croppedImageUrl = URL.createObjectURL(blob);
                    if (state.targetPreview) {
                        state.targetPreview.src = croppedImageUrl;
                    }

                    // Create a new File object
                    const fileName = state.originalFile ? state.originalFile.name : 'photo.jpg';
                    const fileType = state.originalFile ? (state.originalFile.type || 'image/jpeg') : 'image/jpeg';

                    const croppedFile = new File([blob], fileName, {
                        type: fileType,
                        lastModified: Date.now()
                    });

                    // Assign to the actual file input
                    if (state.targetInput) {
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(croppedFile);
                        state.targetInput.files = dataTransfer.files;

                        // Dispatch change event to notify listeners (if any)
                        state.targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    cropperModal.hide();

                }, state.originalFile ? (state.originalFile.type || 'image/jpeg') : 'image/jpeg');
            });

            // Expose a helper to start cropping
            window.startCropping = function(file, inputElement, previewElement) {
                if (!file) return;

                window.cropperState.originalFile = file;
                window.cropperState.targetInput = inputElement;
                window.cropperState.targetPreview = previewElement;

                const reader = new FileReader();
                reader.onload = function(e) {
                    imageToCrop.src = e.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(file);
            };
        }
    });

    // Define init function globally so it can be called from other scripts (like import modal)
    window.initEmployeeEditForm = function() {
        // --- Get all required elements ---
        const titleTh = document.getElementById('employeeTitleTh');
        const titleEn = document.getElementById('employeeTitleEn');
        const genderInput = document.getElementById('employeeGender');
        const dobInput = document.getElementById('employeeDob');
        const ageInput = document.getElementById('employeeAge');
        const nationalitySelect = document.getElementById('employeeNationality');
        const mouGroupSelect = document.getElementById('workPermitMOUGroup');
        const triggerFileInput = document.getElementById('triggerFile');
        const triggerCameraInput = document.getElementById('triggerCamera');
        const actualInput = document.getElementById('employeePhotoInput');
        const employeePhotoPreview = document.getElementById('employeePhotoPreview');

        // Containers for conditional logic
        const myanmarPassportContainer = document.getElementById('passportTypeContainer');
        const cambodiaPassportContainer = document.getElementById('passportTypeCambodiaContainer');
        const mouGroupOtherContainer = document.getElementById('workPermitMOUGroupOtherContainer');
        const insuranceSelect = document.getElementById('insurance_type');
        const socialContainer = document.getElementById('insuranceSocialSecurity');
        const hospitalContainer = document.getElementById('insuranceHospital');
        const privateContainer = document.getElementById('insurancePrivate');

        // --- Logic Block 1: Title & Gender Sync ---
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
            if (selectedTh === 'นาย') {
                genderInput.value = 'ชาย';
            } else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') {
                genderInput.value = 'หญิง';
            } else {
                genderInput.value = '';
            }
        }

        if(titleTh) titleTh.addEventListener('change', () => syncTitles('th'));
        if(titleEn) titleEn.addEventListener('change', () => syncTitles('en'));


        // --- Logic Block 2: Age Calculation ---
        function calculateAge() {
            if (!dobInput || !ageInput) return;
            const dob = new Date(dobInput.value);
            if (!isNaN(dob.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = age > 0 ? age : 0;
            } else {
                ageInput.value = '';
            }
        }
        if(dobInput) dobInput.addEventListener('change', calculateAge);


        // --- Logic Block 3: Nationality Conditional Fields ---
        function toggleNationalityFields() {
            if (!nationalitySelect || !myanmarPassportContainer || !cambodiaPassportContainer) return;
            // Myanmar
            myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
            // Cambodia
            cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
        }
        if(nationalitySelect) nationalitySelect.addEventListener('change', toggleNationalityFields);


        // --- Logic Block 4: MOU "Other" Field ---
         function toggleMouGroupOther() {
            if (!mouGroupSelect || !mouGroupOtherContainer) return;
            mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
        }
        if(mouGroupSelect) mouGroupSelect.addEventListener('change', toggleMouGroupOther);


        // --- Logic Block 5: Insurance Conditional Fields ---
        function toggleInsuranceVisibility() {
            if (!insuranceSelect || !socialContainer || !hospitalContainer || !privateContainer) return;
            const selectedType = insuranceSelect.value;
            socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
            hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
            privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
        }
        if(insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


        // --- Logic Block 6: Photo Cropping Hook ---
        function handleFileSelection(e) {
            if (e.target.files && e.target.files.length > 0) {
                if (window.startCropping) {
                    window.startCropping(e.target.files[0], actualInput, employeePhotoPreview);
                } else {
                    console.error('Cropper not initialized');
                    alert('ระบบตัดภาพยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้าเว็บ');
                }
                // Reset value to allow re-selection
                e.target.value = '';
            }
        }

        if (triggerFileInput) {
             triggerFileInput.removeEventListener('change', handleFileSelection);
             triggerFileInput.addEventListener('change', handleFileSelection);
        }
        if (triggerCameraInput) {
             triggerCameraInput.removeEventListener('change', handleFileSelection);
             triggerCameraInput.addEventListener('change', handleFileSelection);
        }

        // Cancel Button: If inside modal, close it. Else, history.back
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

        // --- Initial State Setup on Page Load ---
        updateGender();
        calculateAge();
        toggleNationalityFields();
        toggleMouGroupOther();
        toggleInsuranceVisibility();
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Run once on load
        if (document.querySelector('#employeeEditForm')) {
            window.initEmployeeEditForm();
        }
    });
</script>
