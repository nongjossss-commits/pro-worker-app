@extends('layouts.app')

@section('title', 'เพิ่มพนักงาน (Registration Resolution)')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงาน (Registration) สำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">เพิ่มพนักงาน (Registration)</h2>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Pass the specific action route for RegistrationController --}}
    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('employees.partials.create_form_partial_content')

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ route('production.registration.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน (Registration)</button>
        </div>
    </form>
</div>

<!-- Cropper Modal (Identical to main create page, copied for functionality) -->
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel">ครอบตัดรูปภาพ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <style>
                    .img-container {
                        max-height: 500px;
                        display: block;
                    }
                    .img-container img {
                        max-width: 100%;
                        display: block;
                    }
                </style>
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture" style="display: block; max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="cropImageBtn">ครอบตัดและบันทึก</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Copied JS logic from employees.create to ensure functionality works here too.
// Ideally, this JS should also be in a partial or a shared JS file.
document.addEventListener('DOMContentLoaded', function () {
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
        const selectedTh = titleTh.value;
        if (selectedTh === 'นาย') {
            genderInput.value = 'ชาย';
        } else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') {
            genderInput.value = 'หญิง';
        } else {
            genderInput.value = '';
        }
    }

    if (titleTh) titleTh.addEventListener('change', () => syncTitles('th'));
    if (titleEn) titleEn.addEventListener('change', () => syncTitles('en'));


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
    if (dobInput) dobInput.addEventListener('change', calculateAge);


    // --- Logic Block 3: Nationality Conditional Fields ---
    function toggleNationalityFields() {
        if (!nationalitySelect) return;
        // Myanmar
        if (myanmarPassportContainer) myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
        // Cambodia
        if (cambodiaPassportContainer) cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
    }
    if (nationalitySelect) nationalitySelect.addEventListener('change', toggleNationalityFields);


    // --- Logic Block 4: MOU "Other" Field ---
     function toggleMouGroupOther() {
        if (!mouGroupSelect) return;
        if (mouGroupOtherContainer) mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
    }
    if (mouGroupSelect) mouGroupSelect.addEventListener('change', toggleMouGroupOther);


    // --- V6: Logic Block 5: Insurance Conditional Fields ---
    function toggleInsuranceVisibility() {
        if (!insuranceSelect) return;
        const selectedType = insuranceSelect.value;
        if (socialContainer) socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
        if (hospitalContainer) hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
        if (privateContainer) privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
    }
    if (insuranceSelect) insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


    // --- Logic Block 6: Photo Cropping ---
    const cropperModalEl = document.getElementById('cropperModal');
    let cropperModal;
    if (cropperModalEl) {
         cropperModal = new bootstrap.Modal(cropperModalEl);
    }
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
            if (imageToCrop) {
                imageToCrop.src = e.target.result;
                if (cropperModal) cropperModal.show();
            }
        };
        reader.readAsDataURL(originalFile);
        // Clear the input value to allow re-selecting the same file
        event.target.value = '';
    }

    if (cropperModalEl) {
        cropperModalEl.addEventListener('shown.bs.modal', function () {
            // Ensure image is loaded and ready
            if (imageToCrop.complete) {
                initCropper();
            } else {
                imageToCrop.onload = initCropper;
            }
        });

        cropperModalEl.addEventListener('hidden.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            // Also clear the src to prevent flashing old image
            imageToCrop.src = '';
        });
    }

    function initCropper() {
        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js is not loaded.');
            return;
        }
        if (cropper) {
            cropper.destroy();
        }
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
            cropBoxMovable: true, // Allow moving the crop box
            cropBoxResizable: true, // Allow resizing the crop box
        });
    }

    if (cropImageBtn) {
        cropImageBtn.addEventListener('click', function () {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 360,
                imageSmoothingQuality: 'high',
            });

            canvas.toBlob(function (blob) {
                if (!blob) return;

                const croppedImageUrl = URL.createObjectURL(blob);
                if (employeePhotoPreview) employeePhotoPreview.src = croppedImageUrl;

                // Create a new File object
                const croppedFile = new File([blob], originalFile.name, {
                    type: originalFile.type || 'image/jpeg',
                    lastModified: Date.now()
                });

                // Use a DataTransfer to create a FileList
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);

                // Assign the FileList to the ACTUAL input for submission
                if (actualInput) actualInput.files = dataTransfer.files;

                if (cropperModal) cropperModal.hide();

            }, originalFile.type || 'image/jpeg');
        });
    }

    if (triggerFileInput) triggerFileInput.addEventListener('change', handleFileSelect);
    if (triggerCameraInput) triggerCameraInput.addEventListener('change', handleFileSelect);


    // --- Initial State Setup on Page Load ---
    if (titleTh) updateGender();
    if (dobInput) calculateAge();
    if (nationalitySelect) toggleNationalityFields();
    if (mouGroupSelect) toggleMouGroupOther();
    if (insuranceSelect) toggleInsuranceVisibility();

});
</script>
@endpush
