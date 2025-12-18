<script>
    // Define init function globally so it can be called from other scripts (like import modal)
    window.initEmployeeEditForm = function() {
        // --- V6: Get all required elements ---
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


        // --- V6: Logic Block 5: Insurance Conditional Fields ---
        function toggleInsuranceVisibility() {
            if (!insuranceSelect || !socialContainer || !hospitalContainer || !privateContainer) return;
            const selectedType = insuranceSelect.value;
            socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
            hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
            privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
        }
        if(insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


        // --- Logic Block 6: Photo Cropping ---
        const cropperModalEl = document.getElementById('cropperModal');
        if (cropperModalEl) {
            const cropperModal = new bootstrap.Modal(cropperModalEl);
            const imageToCrop = document.getElementById('imageToCrop');
            const cropImageBtn = document.getElementById('cropImageBtn');
            let cropper;
            let originalFile;

            function handleFileSelect(event) {
                if (event.target.files && event.target.files.length > 0) {
                    originalFile = event.target.files[0];
                } else {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imageToCrop.src = e.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(originalFile);
                // Clear the input value to allow re-selecting the same file
                event.target.value = '';
            }

            cropperModalEl.addEventListener('shown.bs.modal', function () {
                // Destroy existing cropper if any to be safe
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }

                // Ensure image is loaded and ready
                if (imageToCrop.complete) {
                    setTimeout(initCropper, 200);
                } else {
                    imageToCrop.onload = function() {
                        setTimeout(initCropper, 200);
                    };
                }
            });

            function initCropper() {
                if (typeof Cropper === 'undefined') {
                    alert('ไม่สามารถโหลดเครื่องมือตัดภาพได้ (Cropper.js) กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต');
                    return;
                }

                try {
                    cropper = new Cropper(imageToCrop, {
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
                    });
                } catch (err) {
                    console.error(err);
                    alert('เกิดข้อผิดพลาดในการเริ่มทำงาน Cropper: ' + err.message);
                }
            }

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                // Also clear the src to prevent flashing old image
                imageToCrop.src = '';
            });

            if(cropImageBtn) {
                cropImageBtn.addEventListener('click', function () {
                    if (!cropper) {
                        alert('กรุณารอให้เครื่องมือตัดภาพทำงาน หรือลองเลือกไฟล์ใหม่');
                        return;
                    }

                    const canvas = cropper.getCroppedCanvas({
                        width: 300,
                        height: 360,
                        imageSmoothingQuality: 'high',
                    });

                    canvas.toBlob(function (blob) {
                        if (!blob) {
                            console.error('Canvas to Blob failed');
                            if (typeof showToast === 'function') {
                                showToast('เกิดข้อผิดพลาดในการประมวลผลรูปภาพ', 'danger');
                            } else {
                                alert('เกิดข้อผิดพลาดในการประมวลผลรูปภาพ');
                            }
                            return;
                        }

                        try {
                            const croppedImageUrl = URL.createObjectURL(blob);
                            if(employeePhotoPreview) {
                                // Force reload by adding a timestamp to avoid caching issues (though Blob URL is unique usually)
                                employeePhotoPreview.src = croppedImageUrl;
                            }

                            // Create a new File object
                            const croppedFile = new File([blob], originalFile.name, {
                                type: originalFile.type || 'image/jpeg',
                                lastModified: Date.now()
                            });

                            // Use a DataTransfer to create a FileList
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(croppedFile);

                            // Assign the FileList to the ACTUAL input for submission
                            if(actualInput) {
                                actualInput.files = dataTransfer.files;

                                // Feedback to user
                                if (typeof showToast === 'function') {
                                    showToast('รูปภาพถูกเลือกแล้ว กรุณากดปุ่มบันทึกด้านล่างเพื่อยืนยันการแก้ไข', 'success');
                                }
                            } else {
                                console.error('Actual input #employeePhotoInput not found');
                            }

                            cropperModal.hide();
                        } catch (e) {
                            console.error('Error handling cropped image:', e);
                            if (typeof showToast === 'function') {
                                showToast('เกิดข้อผิดพลาด: ' + e.message, 'danger');
                            }
                        }

                    }, originalFile.type || 'image/jpeg');
                });
            }

            if (triggerFileInput) triggerFileInput.addEventListener('change', handleFileSelect);
            if (triggerCameraInput) triggerCameraInput.addEventListener('change', handleFileSelect);
        }

        // Cancel Button: If inside modal, close it. Else, history.back
        const cancelBtn = document.querySelector('.btn-cancel-edit');
        if (cancelBtn) {
             cancelBtn.onclick = function() {
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
        window.initEmployeeEditForm();
    });
</script>
