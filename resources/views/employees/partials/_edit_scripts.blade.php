<script>
    // --- Global Cropper State & Logic ---
    // This ensures we only attach listeners to the global modal ONCE,
    // preventing the "stacking listeners" bug which caused "Canvas creation failed".
    window.cropperManager = {
        initialized: false,
        instance: null,
        originalFile: null
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

            canvas.toBlob(function (blob) {
                if (!blob) return;

                const croppedImageUrl = URL.createObjectURL(blob);

                // Find CURRENT elements in the DOM (since form is dynamic/AJAX loaded)
                // UPDATE: Target 'edit_' prefixed IDs for the Edit Modal
                const employeePhotoPreview = document.getElementById('edit_employeePhotoPreview');
                const actualInput = document.getElementById('edit_employeePhotoInput');

                if(employeePhotoPreview) employeePhotoPreview.src = croppedImageUrl;

                // Create a new File object
                const fileType = originalFile ? (originalFile.type || 'image/jpeg') : 'image/jpeg';
                const fileName = originalFile ? originalFile.name : 'cropped-image.jpg';

                const croppedFile = new File([blob], fileName, {
                    type: fileType,
                    lastModified: Date.now()
                });

                // Use a DataTransfer to create a FileList for the input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);

                if(actualInput) {
                    actualInput.files = dataTransfer.files;
                } else {
                    // It's possible we are in a context where edit input doesn't exist?
                    // But this script is for Edit Form.
                    // If we were in Add Modal, this listener still runs but finds nothing (good).
                }

                // Hide the modal
                cropperModal.hide();

            }, (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg');
        });
    };

    // --- Per-Form Initialization (Called every time the AJAX form loads) ---
    window.initEmployeeEditForm = function() {
        // 1. Ensure global cropper logic is ready (Idempotent call)
        window.initCropperGlobal();

        // 2. Get Form Field References (Updated IDs)
        const titleTh = document.getElementById('edit_employeeTitleTh');
        const titleEn = document.getElementById('edit_employeeTitleEn');
        const genderInput = document.getElementById('edit_employeeGender');
        const dobInput = document.getElementById('edit_employeeDob');
        const ageInput = document.getElementById('edit_employeeAge');
        const nationalitySelect = document.getElementById('edit_employeeNationality');
        const mouGroupSelect = document.getElementById('edit_workPermitMOUGroup');
        const insuranceSelect = document.getElementById('edit_insurance_type');

        // 3. File Triggers (These are new elements in the AJAX form)
        const triggerFileInput = document.getElementById('edit_triggerFile');
        const triggerCameraInput = document.getElementById('edit_triggerCamera');
        const imageToCrop = document.getElementById('imageToCrop'); // Global element, but ref doesn't hurt
        const cropperModalEl = document.getElementById('cropperModal'); // Global element

        // --- Logic: Handle File Selection (Triggers Modal) ---
        function handleFileSelect(event) {
            if (event.target.files && event.target.files.length > 0) {
                // Update global state with selected file
                window.cropperManager.originalFile = event.target.files[0];
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
             triggerFileInput.addEventListener('change', handleFileSelect);
        }
        if (triggerCameraInput) {
             triggerCameraInput.addEventListener('change', handleFileSelect);
        }

        // --- Logic: Titles & Gender ---
        const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
        const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

        function syncTitles(source) {
            if (!titleTh || !titleEn) return;
            if (source === 'th') {
                const selectedTh = titleTh.value;
                if (thToEnMap[selectedTh]) titleEn.value = thToEnMap[selectedTh];
            } else {
                const selectedEn = titleEn.value;
                if (enToThMap[selectedEn]) titleTh.value = enToThMap[selectedEn];
            }
            updateGender();
        }

        function updateGender() {
            if (!titleTh || !genderInput) return;
            const selectedTh = titleTh.value;
            if (selectedTh === 'นาย') genderInput.value = 'ชาย';
            else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') genderInput.value = 'หญิง';
            else genderInput.value = '';
        }

        if(titleTh) titleTh.addEventListener('change', () => syncTitles('th'));
        if(titleEn) titleEn.addEventListener('change', () => syncTitles('en'));

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
        if(dobInput) dobInput.addEventListener('change', calculateAge);

        // --- Logic: Nationality Conditionals ---
        const myanmarPassportContainer = document.getElementById('edit_passportTypeContainer');
        const cambodiaPassportContainer = document.getElementById('edit_passportTypeCambodiaContainer');

        function toggleNationalityFields() {
            if (!nationalitySelect || !myanmarPassportContainer || !cambodiaPassportContainer) return;
            myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
            cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
        }
        if(nationalitySelect) nationalitySelect.addEventListener('change', toggleNationalityFields);

        // --- Logic: MOU Other ---
        const mouGroupOtherContainer = document.getElementById('edit_workPermitMOUGroupOtherContainer');
        function toggleMouGroupOther() {
            if (!mouGroupSelect || !mouGroupOtherContainer) return;
            mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
        }
        if(mouGroupSelect) mouGroupSelect.addEventListener('change', toggleMouGroupOther);

        // --- Logic: Insurance Conditionals ---
        const socialContainer = document.getElementById('edit_insuranceSocialSecurity');
        const hospitalContainer = document.getElementById('edit_insuranceHospital');
        const privateContainer = document.getElementById('edit_insurancePrivate');
        function toggleInsuranceVisibility() {
            if (!insuranceSelect || !socialContainer || !hospitalContainer || !privateContainer) return;
            const selectedType = insuranceSelect.value;
            socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
            hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
            privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
        }
        if(insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);

        // --- Cancel Button Logic ---
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

        // --- Run Initial Logic for current form ---
        updateGender();
        calculateAge();
        toggleNationalityFields();
        toggleMouGroupOther();
        toggleInsuranceVisibility();
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Run once on page load (handles case where form is static)
        window.initEmployeeEditForm();
    });
</script>
